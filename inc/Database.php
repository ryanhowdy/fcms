<?php
/**
 * Database
 * 
 * PHP version 5
 * 
 * @category  FCMS
 * @package   FamilyConnections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2012 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 * @since     3.2
 */

require_once 'config_inc.php';

/**
 * Database 
 * 
 * @category  FCMS
 * @package   Family_Connections
 * @author    Ryan Haudenschilt <r.haudenschilt@gmail.com> 
 * @copyright 2012 Haudenschilt LLC
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GPLv2
 * @link      http://www.familycms.com/wiki/
 */
class Database
{
    private $result;
    private $sql;
    private $dbh;
    private $host;
    private $db;
    private $user;
    private $pass;
    private $port;
    private $error;
    private $fetchType;
    private $rowCount;
    
    public static $instance = null;

    /**
     * __construct 
     * 
     * @return void
     */
    private function __construct ($error)
    {
        global $cfg_mysql_host, $cfg_mysql_user, $cfg_mysql_pass, $cfg_mysql_db;

        $this->error     = $error;
        $this->host      = $cfg_mysql_host;
        $this->db        = $cfg_mysql_db;
        $this->user      = $cfg_mysql_user;
        $this->pass      = $cfg_mysql_pass;
        $this->fetchType = PDO::FETCH_ASSOC;
        //$this->port     = $cfg_mysql_port;
        $this->rowCount  = 0;

        $this->connect();
    }

    /**
     * getInstance 
     * 
     * @param object $error 
     * 
     * @return object
     */
    public static function getInstance ($error)
    {
        if (!isset(self::$instance))
        {
            self::$instance = new Database($error);
        }

        return self::$instance;
    }

    /**
     * connect 
     * 
     * Opens a connection to the MySQL database.
     * 
     * @return void
     */
    private function connect ()
    {
        if ($this->dbh)
        {
            return true;
        }

        // Connect
        try
        {  
            $this->dbh = new PDO(
                'mysql:host='.$this->host.';dbname='.$this->db, 
                $this->user, 
                $this->pass, 
                array(PDO::ATTR_PERSISTENT => true)
            );  
        }  
        catch (PDOException $e)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => sprintf(T_('Could not connect to host [%s] with user [%s].'), $this->host, $this->user),
                'error'   => $e,
                'file'    => __FILE__,
                'line'    => __LINE__,
             ));

            return false;
        }

        // UTF8
        $result = $this->dbh->exec("SET NAMES 'utf8'");
        if ($result === false)
        {
            $this->error->add(array(
                'message' => T_('Could not set names utf8.'),
                'error'   => $this->dbh->errorInfo(),
                'file'    => __FILE__,
                'line'    => __LINE__,
             ));

            return false;
        }

        return true;
    }

    /**
     * setFetchType 
     * 
     * @param string  $type 
     * 
     * @return void
     */
    public function setFetchType ($type)
    {
        $this->fetchType = $type;
    }

    /**
     * getRow 
     * 
     * Will return an array of results from the db.
     * 
     * If params are given, will prepare the sql statement and then execute it.
     * 
     * @param string $sql 
     * @param mixed  $params 
     * 
     * @return mixed
     */
    public function getRow ($sql, $params = null)
    {
        $this->sql = $sql;

        // Without params
        if (is_null($params))
        {
            return $this->getRowQuery();
        }
        // With params
        else
        {
            return $this->getRowPrepared($params);
        }
    }

    /**
     * getRowQuery 
     * 
     * @return mixed
     */
    function getRowQuery ()
    {
        $result = $this->dbh->query($this->sql);
        if ($result === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot query database.'),
                'error'   => $this->dbh->errorInfo(),
                'file'    => __FILE__,
                'line'    => __LINE__,
                'sql'     => $this->sql
            ));

            return false;
        }

        return $result->fetch($this->fetchType);
    }

    /**
     * getRowPrepared 
     * 
     * @param mixed $params 
     * 
     * @return mixed
     */
    function getRowPrepared ($params)
    {
        if (!is_array($params))
        {
            $params = array($params);
        }

        // Prepare
        $stmt = $this->dbh->prepare($this->sql);
        if ($stmt === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot prepare SQL statement.'),
                'error'   => $this->dbh->errorInfo(),
                'file'    => __FILE__,
                'line'    => __LINE__,
                'sql'     => $this->sql
            ));

            return false;
        }

        // Execute
        if ($stmt->execute($params) === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot execute SQL statement.'),
                'error'   => $stmt->errorInfo(),
                'file'    => __FILE__,
                'line'    => __LINE__,
                'sql'     => $this->sql
            ));

            return false;
        }

        // Fetch
        $result = $stmt->fetch($this->fetchType);
        if ($result === false)
        {
            return array();
        }

        return $result;
    }

    /**
     * getRows
     * 
     * Will return an array of all the results from the db.
     * 
     * @param string $sql 
     * @param mixed  $params 
     * 
     * @return mixed
     */
    public function getRows ($sql, $params = null)
    {
        $this->sql = $sql;

        // params must be null or an array
        if (!is_null($params) && !is_array($params))
        {
            $params = array($params);
        }

        // Prepare
        $stmt = $this->dbh->prepare($this->sql);
        if ($stmt === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot prepare SQL statement.'),
                'error'   => $this->dbh->errorInfo(),
                'file'    => __FILE__,
                'line'    => __LINE__,
                'sql'     => $this->sql
            ));

            return false;
        }

        // Execute
        if ($stmt->execute($params) === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot execute SQL statement.'),
                'error'   => $stmt->errorInfo(),
                'file'    => __FILE__,
                'line'    => __LINE__,
                'sql'     => $this->sql
            ));

            return false;
        }

        // Fetch
        $result = $stmt->fetchAll($this->fetchType);
        if ($result === false)
        {
            return false;
        }

        return $result;
    }

    /**
     * insert 
     * 
     * If successful, will return last insert id, otherwise false.
     * 
     * @param string $sql 
     * @param mixed  $params 
     * 
     * @return mixed
     */
    function insert ($sql, $params)
    {
        $this->sql = $sql;

        if (!is_null($params) && !is_array($params))
        {
            $params = array($params);
        }

        $stmt = $this->dbh->prepare($sql);
        if ($stmt === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot prepare SQL statement.'),
                'error'   => $this->dbh->errorInfo(),
                'file'    => __FILE__,
                'line'    => __LINE__,
                'sql'     => $this->sql,
            ));

            return false;
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot insert SQL statement.'),
                'error'   => $stmt->errorInfo(),
                'file'    => __FILE__,
                'line'    => __LINE__,
                'sql'     => $this->sql,
            ));

            return false;
        }

        return $this->dbh->lastInsertId();
    }

    /**
     * update 
     * 
     * @param string $sql 
     * @param array  $params 
     * 
     * @return boolean
     */
    function update ($sql, $params = null)
    {
        $this->sql = $sql;

        if (substr($this->sql, 0, 6) != 'UPDATE')
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Could not query database.'),
                'error'   => 'Called update on non UPDATE',
                'line'    => __LINE__,
                'file'    => __FILE__,
                'sql'     => $this->sql
            ));

            return false;
        }

        if (is_null($params))
        {
            if ($this->dbh->exec($sql) === false)
            {
                $this->error->add(array(
                    'type'    => 'operation',
                    'message' => T_('Cannot update SQL statement.'),
                    'error'   => $this->dbh->errorInfo(),
                    'line'    => __LINE__,
                    'file'    => __FILE__,
                    'sql'     => $this->sql
                ));

                return false;
            }
        }
        else
        {
            $stmt = $this->dbh->prepare($sql);
            if ($stmt === false)
            {
                $this->error->add(array(
                    'type'    => 'operation',
                    'message' => T_('Cannot prepare SQL statement.'),
                    'error'   => $this->dbh->errorInfo(),
                    'line'    => __LINE__,
                    'file'    => __FILE__,
                    'sql'     => $this->sql
                ));

                return false;
            }

            if (!is_null($params) && !is_array($params))
            {
                $params = array($params);
            }

            $result = $stmt->execute($params);
            if ($result === false)
            {
                $this->error->add(array(
                    'type'    => 'operation',
                    'message' => T_('Cannot update SQL statement.'),
                    'error'   => $stmt->errorInfo(),
                    'line'    => __LINE__,
                    'file'    => __FILE__,
                    'sql'     => $this->sql
                ));

                return false;
            }

            $this->rowCount = $stmt->rowCount();
        }

        return true;
    }

    /**
     * delete 
     * 
     * @param string $sql 
     * @param array  $params 
     * 
     * @return boolean
     */
    function delete ($sql, $params = null)
    {
        $this->sql = $sql;

        $stmt = $this->dbh->prepare($sql);
        if ($stmt === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot prepare SQL DELETE statement.'),
                'error'   => $this->dbh->errorInfo(),
                'line'    => __LINE__,
                'file'    => __FILE__,
                'sql'     => $this->sql
            ));

            return false;
        }

        if (!is_null($params) && !is_array($params))
        {
            $params = array($params);
        }

        $result = $stmt->execute($params);
        if ($result === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot delete SQL statement.'),
                'error'   => $stmt->errorInfo(),
                'line'    => __LINE__,
                'file'    => __FILE__,
                'sql'     => $this->sql
            ));

            return false;
        }

        return true;
    }

    /**
     * alter
     * 
     * @param string $sql 
     * @param array  $params 
     * 
     * @return boolean
     */
    function alter ($sql, $params = null)
    {
        $this->sql = $sql;

        if (substr($this->sql, 0, 5) != 'ALTER')
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Could not alter database.'),
                'error'   => 'Called alter on non ALTER statement',
                'line'    => __LINE__,
                'file'    => __FILE__,
                'sql'     => $this->sql
            ));

            return false;
        }

        if (is_null($params))
        {
            if ($this->dbh->exec($sql) === false)
            {
                $this->error->add(array(
                    'type'    => 'operation',
                    'message' => T_('Cannot alter SQL statement.'),
                    'error'   => $this->dbh->errorInfo(),
                    'line'    => __LINE__,
                    'file'    => __FILE__,
                    'sql'     => $this->sql
                ));

                return false;
            }
        }
        else
        {
            $stmt = $this->dbh->prepare($sql);
            if ($stmt === false)
            {
                $this->error->add(array(
                    'type'    => 'operation',
                    'message' => T_('Cannot prepare SQL statement.'),
                    'error'   => $this->dbh->errorInfo(),
                    'line'    => __LINE__,
                    'file'    => __FILE__,
                    'sql'     => $this->sql
                ));

                return false;
            }

            if (!is_null($params) && !is_array($params))
            {
                $params = array($params);
            }

            $result = $stmt->execute($params);
            if ($result === false)
            {
                $this->error->add(array(
                    'type'    => 'operation',
                    'message' => T_('Cannot alter SQL statement.'),
                    'error'   => $stmt->errorInfo(),
                    'line'    => __LINE__,
                    'file'    => __FILE__,
                    'sql'     => $this->sql
                ));

                return false;
            }
        }

        return true;
    }

    /**
     * execute
     * 
     * @param string $sql 
     * 
     * @return boolean
     */
    function execute ($sql)
    {
        $this->sql = $sql;

        if ($this->dbh->exec($sql) === false)
        {
            $this->error->add(array(
                'type'    => 'operation',
                'message' => T_('Cannot execute SQL statement.'),
                'error'   => $this->dbh->errorInfo(),
                'line'    => __LINE__,
                'file'    => __FILE__,
                'sql'     => $this->sql
            ));

            return false;
        }

        return true;
    }

    /**
     * getRowCount 
     * 
     * @return int
     */
    function getRowCount ()
    {
        return $this->rowCount;
    }

}

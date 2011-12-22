<?php
/*
    Test-More-OO.php:
        A workalike of Perl's Test::More for PHP.

    Why Test-More?
        Test-More is a great way to start testing RIGHT NOW.

    Why ok and not ok?
        Test-More produces TAP compliant output.
        For more on TAP, see: http://testanything.org
        For the TAP spec, see: http://search.cpan.org/dist/TAP/TAP.pm

    Other testing libraries:
        You can replace Test-Simple with Test-More without making any changes
        to existing test code, providing access to further testing methods.
        You can also replace any other PHP Test::More workalike library out there
        with Test-More.php and it will work without making any changes to the code.

    Assertions:
        produce TAP output
        provide testing functions
        exit with error code:
            0                   all tests successful
            255                 test died or all passed but wrong # of tests run
            any other number    how many failed (including missing or extras)

    Example:
        require_once('Test-More-OO.php');
        $t = new TestMore();
        $t->plan(2);
        $t->ok(1 + 1 = 2, 'One plus one equals two');
        $t->ok( doSomethingAndReturnTrue() , 'doSomethingAndReturnTrue() successful');

    Procedural Example:
        require_once('Test-More.php');
        plan(2);
        ok(1 + 1 = 2, 'One plus one equals two');
        ok( doSomethingAndReturnTrue() , 'doSomethingAndReturnTrue() successful');

    From a browser
        If you are running Test-Simple on a web server and want slightly more web-readable
        output, call the web_output() method/function.

    Updates
        Updates will be posted to the Google code page:
        http://code.google.com/p/test-more-php/

    Bugs
        Please file bug reports via the Issues tracker at the google code page.

    Acknowledgements
        Michael G Schwern: http://search.cpan.org/~mschwern/Test-Simple/
        Chris Shiflet: http://shiflett.org/code/test-more.php

    Author
        Copyright RJ Herrick <RJHerrick@beyondlogical.net> 2009, 2010

*/

require_once('Test-Simple-OO.php');

class TestMore extends TestSimple {

/* Test-More extensions */
    private $interp;

    function pass ($name = NULL) {
        return $this->ok(TRUE, $name);
    }

    function fail ($name = NULL) {
        return $this->ok(FALSE, $name);
    }

    function _compare ($operator, $thing1, $thing2, $name = NULL) {
    // Test.php's cmp_ok function accepts coderefs, hmmm

        $result = eval("return (\$thing1 $operator \$thing2);");

        return $this->ok($result, $name);
    }

    function is ($thing1, $thing2, $name = NULL) {
        $pass = $this->_compare ('==',$thing1,$thing2,$name);
        if (!$pass) {
            if (is_array($thing1)) {
                $order  = array("\r\n", "\n", "\r", " ");
                $thing1 = print_r($thing1, true);
                $thing1 = str_replace($order, '', $thing1);
                $thing2 = print_r($thing2, true);
                $thing2 = str_replace($order, '', $thing2);
            }
            $this->diag("         got: '$thing1'",
                        "    expected: '$thing2'");
        }
        return $pass;
    }

    function isnt ($thing1, $thing2, $name = NULL) {
        $pass = $this->_compare ('!=',$thing1,$thing2,$name);
        if (!$pass) {
            $this->diag("         got: '$thing1'",
                        "    expected: '$thing2'");
        }
        return $pass;
    }

    function like ($string, $pattern, $name = NULL) {
        $pass = preg_match($pattern, $string);

        $ok = $this->ok($pass, $name);

        if (!$ok) {
            $this->diag("                  '$string'");
            $this->diag("    doesn't match '$pattern'");
        }

        return $ok;
    }

    function unlike ($string, $pattern, $name = NULL) {
        $pass = !preg_match($pattern, $string);

        $ok = $this->ok($pass, $name);

        if (!$ok) {
            $this->diag("                  '$string'");
            $this->diag("          matches '$pattern'");
        }

        return $ok;
    }

    function cmp_ok ($thing1, $operator, $thing2, $name = NULL) {
        eval("\$pass = (\$thing1 $operator \$thing2);");

        ob_start();
        var_dump($thing1);
        $_thing1 = trim(ob_get_clean());

        ob_start();
        var_dump($thing2);
        $_thing2 = trim(ob_get_clean());

        $ok = $this->ok($pass, $name);

        if (!$ok) {
            $this->diag("         got: $_thing1");
            $this->diag("    expected: $_thing2");
        }

        return $ok;
    }

    function can_ok ($object, $methods) {
        $pass = TRUE;
        $errors = array();
        if (!is_array($methods)) $methods = array($methods);

        foreach ($methods as $method) {
            if (!method_exists($object, $method)) {
                $pass = FALSE;
                $errors[] = "    method_exists(\$object, $method) failed";
            }
        }

        $ok = $this->ok($pass, "method_exists(\$object, ...)");

        if (!$ok) {
            $this->diag($errors);
        }

        return $ok;
    }

    function isa_ok ($object, $expected_class, $object_name = 'The object') {
        $got_class = get_class($object);

        if (version_compare(phpversion(), '5', '>=')) {
            $pass = ($got_class == $expected_class);
        } else {
            $pass = ($got_class == strtolower($expected_class));
        }

        if ($pass) {
            $ok = $this->ok(TRUE, "$object_name isa $expected_class");
        } else {
            $ok = $this->ok(FALSE, "$object_name isn't a '$expected_class' it's a '$got_class'");
        }

        return $ok;
    }

    function _include_fatal_error_handler ($buffer) { 

        // Finish successfully? Carry on.
        if ($buffer === 'included OK') return '';

        $module = $this->LastModuleTested;

        // Inside ob_start, won't see the output
        $this->ok(FALSE,"include $module");

        $message = trim($buffer);
        $unrunmsg = '';

        if ( is_int($this->NumberOfTests) ) {
            $unrun = $this->NumberOfTests - (int)$this->TestsRun;
            $plural = $unrun == 1 ? '' : 's';
            $unrunmsg = "# Looks like ${unrun} planned test${plural} never ran.\n";
        }

        $gasp = $this->LastFail . "\n"
              . "#     Tried to include '$module'\n"
              . "#     $message\n"
              . $unrunmsg
              . "# Looks like 1 test aborted before it could finish due to a fatal error!\n"
              . "Bail out! Terminating prematurely due to fatal error.\n"
              ;

        return $gasp;
    }

    function _include_ok ($module,$type) {
        $path = null;
        $full_path = null;
        $retval = 999;

        // Resolve full path, nice to know although only necessary on windows
        foreach (explode(PATH_SEPARATOR,get_include_path()) as $prefix) {
            // Repeat existance test and find full path
            $full_path = realpath($prefix.DIRECTORY_SEPARATOR.$module);
            $lines = @file($full_path);
            // Stop on success
            if ($lines) {
                $path = $full_path;
                break;
            }
        }
        // Make sure, if we would include it, it's not going to choke on syntax
        $error = false;
        if ($path) {
            @exec('"'.$this->interp().'" -l '.$path, $bunk, $retval);
            if ($retval===0) {
                // Prep in case we hit error handler
                $this->Backtrace = debug_backtrace();
                $this->LastModuleTested = $module;
                ob_start(array($this,'_include_fatal_error_handler'));
                if ($type === 'include') {
                    $done = (include $module);
                } else if ($type === 'require') {
                    $done = (require $module);
                } else {
                    $this->bail("Second argument to _include_ok() must be 'require' or 'include'");
                }
                echo "included OK";
                ob_end_flush();
                if (!$done) $error = "  Unable to $type '$module'";
            } else {
                $error = "  Syntax check for '$module' failed";
            }
        } else {
            $error = "  Cannot find ${type}d file '$module'";
        }

        $pass = !$retval && $done; 
        $ok = $this->ok($pass, "$type $module" );
        if ($error) $this->diag($error);
        if ($error && $path) $this->diag("  Resolved $module as $full_path");
        return $ok;
    }

    function include_ok ($module) {
    // Test success of including file, but continue testing if possible even if unable to include

        return $this->_include_ok($module,'include');
    }


    function require_ok ($module) {
    // As include_ok() but exit gracefully if requirement missing

        $ok = $this->_include_ok($module,'require');

        // Stop testing if we fail a require test
        // Not a bail because you asked for it
        if ($ok == FALSE) {
            $this->diag("  Exiting due to missing requirement.");
            exit();
        }

        return $ok;
    } 

    function skip($why, $num) {

        if ($num < 0) $num = 0;

        $this->Skips += $num;
        $this->SkipReason = $why;

        return TRUE;
    }

    function eq_array ($thing1, $thing2) {
    // Deprecated comparison function provided for compatibility
    // Look only at values, order is important
        $this->diag(" ! eq_array() is a deprecated comparison function provided for compatibility. Use array_diff() or similar instead.");
        return !array_diff($thing1, $thing2);
    }

    function eq_hash ($thing1, $thing2) {
    // Deprecated comparison function provided for compatibility
    // Look at keys and values, order is NOT important
        $this->diag(" ! eq_hash() is a deprecated comparison function provided for compatibility. Use array_diff() or similar instead.");
        return !array_diff_assoc($thing1, $thing2);
    }

    function eq_set ($thing1, $thing2, $name = NULL) {
    // Deprecated comparison function provided for compatibility
    // Look only at values, duplicates are NOT important
        $this->diag(" ! eq_set() is a deprecated comparison function provided for compatibility. Use array_diff() or similar instead.");
        $a = $thing1;
        sort($a);
        $b = $thing2;
        sort($b);
        return !array_diff($a, $b);
    }

    function is_deeply ($thing1, $thing2, $name = NULL) {

        $pass = $this->_compare_deeply($thing1, $thing2, $name);

        $ok = $this->ok($pass,$name);

        if (!$ok) {
            foreach(array($thing1,$thing2) as $it){
                ob_start();
                var_dump($it);
                $dump = ob_get_clean();
                #$stringified[] = implode("\n#",explode("\n",$dump));
                $stringified[] = str_replace("\n","\n#   ",$dump);
            }
            $this->diag(" wanted:  ".$stringified[0]);
            $this->diag("    got:  ".$stringified[1]);
        }

        return $ok;
    }

    function isnt_deeply ($thing1, $thing2, $name = NULL) {

        $pass = !$this->_compare_deeply($thing1, $thing2, $name);

        $ok = $this->ok($pass,$name);

        if (!$ok) $this->diag("Structures are identical.\n");

        return $ok;
    }

    function _compare_deeply ($thing1, $thing2) {
        
        if (is_array($thing1) && is_array($thing2)) {
            if ((count($thing1) === count($thing2)) && !array_diff_key($thing1,$thing2)) {
                foreach(array_keys($thing1) as $key){
                    $pass = $this->_compare_deeply($thing1[$key],$thing2[$key]);
                    if(!$pass) {
                        return FALSE;
                    }
                }
                return TRUE;

            } else {
                return FALSE;
            }

        } else {
            return $thing1 === $thing2;
        }
    }

    function todo ($why, $howmany) {
    // Marks tests as expected to fail, then runs them anyway

        if ($howmany < 0) $howmany = 0;

        $this->Todo = $howmany;
        $this->TodoReason = $why;

        return TRUE;
    }

    function todo_skip ($why, $howmany) {
    // Marks tests as expected to fail, then skips them, as they are expected to also create fatal errors

        $this->todo($why, $howmany);
        $this->skip($why, $howmany);

        return TRUE;
    }

    function todo_start ($why) {
    // as starting a TODO block in Perl- instead of using todo() to set a number of tests, all
    // tests until todo_end are expected to fail and run anyway

        $this->TodoBlock = FALSE;
        $this->TodoReason = $why;

        return TRUE;
    }

    function todo_end () {
    // as ending a SKIP block in Perl

        $this->TodoBlock = FALSE;
        unset($this->TodoReason);

        return TRUE;
    }

    function interp ($new_interp_command=NULL) {
    // Return the command used to invoke the PHP interpreter, such as for exec()

        if ($new_interp_command == NULL && $this->interp == '') {
            // In some situations you might need to specify a php interpreter.
            if ( isset($_SERVER['PHP']) ) {
                $new_interp_command = escapeshellcmd($_SERVER['PHP']);
            } else { 
                $new_interp_command = 'php';
            }
        }
        if ($new_interp_command != $this->interp) {
            $this->interp = $new_interp_command;

            // Check that we can use the interpreter
            @exec('"'.$this->interp.'" -v', $bunk, $retval);
            if ($retval!==0) $this->bail("Unable to run PHP interpreter with '$this->interp'. Try setting the PHP environmant variable to the path of the interpreter.");
        }

        return $this->interp;
    }


}

?>

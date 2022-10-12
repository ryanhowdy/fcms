<?php

/**
 * addChildOppositeSexParents 
 * 
 * Verifies that both parents are of opposite sex.
 * 
 * @param array $data 
 * 
 * @return boolean
 */
function addChildOppositeSexParents ($data)
{
    $fcmsError    = FCMS_Error::getInstance();
    $fcmsDatabase = Database::getInstance($fcmsError);

    if (empty($data['parentId2']))
    {
        return true;
    }

    // Get parents sex if not provided
    if (empty($data['parentSex1']) || empty($data['parentSex2']))
    {
        $sql = "SELECT `id`, `sex`
                FROM `fcms_users`
                WHERE `id` = ?
                UNION
                SELECT `id`, `sex`
                FROM `fcms_users`
                WHERE `id` = ?";

        $params = array(
            $data['parentId1'],
            $data['parentId2']
        );

        $parentsInfo = $fcmsDatabase->getRows($sql, $params);
        if ($parentsInfo === false)
        {
            return false;
        }

        $data['parentSex1'] = $parentsInfo[0]['sex'];
        $data['parentSex2'] = $parentsInfo[1]['sex'];
    }

    if ($data['parentSex1'] === $data['parentSex2'])
    {
        return false;
    }

    return true;
}

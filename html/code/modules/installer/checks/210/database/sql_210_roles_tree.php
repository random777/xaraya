<?php

function sql_210_roles_tree()
{
    // Define parameters
    $roles = xarDB::getPrefix() . '_roles';
    $rolemembers = xarDB::getPrefix() . '_rolemembers';

    // Define the task and result
    $data['success'] = true;
    $data['task'] = xarML("
        Checking for consistency in the roles tree
    ");
    $data['reply'] = xarML("
        Done!
    ");

    // Run the query
    $dbconn = xarDB::getConn();
    try {
        $dbconn->begin();
        $data['sql'] = "
        SELECT 
        `id`,
        `uname`
        FROM $roles r LEFT JOIN $rolemembers rm ON r.id = rm.role_id WHERE r.id != 1 AND rm.parent_id IS NULL
        ";
        $result = $dbconn->Execute($data['sql']);
        if (!$result->EOF) {
            list($id, $uname) = $result->fields;
            $data['success'] = false;
            $data['reply'] = xarML("
            No parent: $uname
            ");
        }

        $dbconn->commit();
    } catch (Exception $e) {
        // Damn
        $dbconn->rollback();
        $data['success'] = false;
        $data['reply'] = xarML("
        Failed!
        ");
    }
    return $data;
}
?>
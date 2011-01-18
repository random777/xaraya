<?php
/**
 * Delete a block group
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * delete a group
 * @author Jim McDonald, Paul Rosania
 * @param int $args['gid'] the ID of the block group to delete
 * @return bool true on success, false on failure
 */
function blocks_adminapi_delete_group($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($gid) || !is_numeric($gid)) {
        $msg = xarML('Invalid parameter');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    // Security
    if (!xarSecurityCheck('DeleteBlock', 1, 'Block', "::$gid")) {return;}

    $dbconn =& xarDB::getConn();
    $xartable =& xarDBGetTables();
    $block_groups_table = $xartable['block_groups'];
    $block_group_instances_table = $xartable['block_group_instances'];

    // Delete group-instance links
    $query = "DELETE FROM $block_group_instances_table
              WHERE xar_group_id = " . $gid;
    $result =& $dbconn->Execute($query);
    if (!$result) {return;}

    // Delete block group definition
    $query = "DELETE FROM $block_groups_table
              WHERE xar_id = ?";
    $result =& $dbconn->Execute($query,array($gid));
    if (!$result) {return;}

    return true;
}

?>
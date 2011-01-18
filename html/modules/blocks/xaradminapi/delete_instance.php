<?php
/**
 * Delete a block instance
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
 * delete a block
 * @author Jim McDonald, Paul Rosania
 * @param int bid the ID of the block to delete
 * @return bool true on success, false on failure
 */
function blocks_adminapi_delete_instance($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid) || !is_numeric($bid)) {
        $msg = xarML('Invalid parameter');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    // Security
    if (!xarSecurityCheck('DeleteBlock', 1, 'Block', "::$bid")) {return;}

    $dbconn =& xarDB::getConn();
    $xartable =& xarDBGetTables();
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    $query = "DELETE FROM $block_group_instances_table
              WHERE xar_instance_id = ?";
    $result =& $dbconn->Execute($query,array($bid));
    if (!$result) {return;}

    $query = "DELETE FROM $block_instances_table
              WHERE xar_id = ?";
    $result =& $dbconn->Execute($query,array($bid));
    if (!$result) {return;}

    //let's make sure the cache blocks instance as well is deleted, if it exists bug #5815
    if (!empty($xartable['cache_blocks'])) {
        $deletecacheblock = xarMod::apiFunc('blocks','admin','delete_cacheinstance', array('bid' => $bid));
    }

    xarMod::apiFunc('blocks', 'admin', 'resequence');

    $args['module'] = 'blocks';
    $args['itemtype'] = 3; // block instance
    $args['itemid'] = $bid;
    xarModCallHooks('item', 'delete', $bid, $args);

    return true;
}

?>
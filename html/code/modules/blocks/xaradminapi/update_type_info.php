<?php
/**
 * Read the info details of a block type
 * @package modules
 * @subpackage blocks module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Read the info details of a block type into the database.
 *
 * @author Jim McDonald
 * @author Paul Rosania
 * @access public
 * @param array    $args array of optional parameters<br/>
 *        string   $args['modName'] the module name (deprecated)<br/>
 *        string   $args['blockType'] the block type (deprecated)<br/>
 *        integer  $args['tid'] the type id<br/>
 *        string   $args['module'] the module name<br/>
 *        string   $args['type'] the block type<br/>
 * @return mixed ID of block type registered (even if already registered), false on failure
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_update_type_info(Array $args=array())
{
    extract($args);

    // Get the type details from the database.
    $type = xarMod::apiFunc('blocks', 'user', 'getblocktype', $args);

    if (empty($type)) {
        // No type registered in the database.
        return;
    }

    // Load and execute the info function of the block.
    if (empty($args['info'])) {
        $block_info = xarMod::apiFunc('blocks', 'user', 'read_type_info',
                                    array('module' => $type['module'],
                                          'type' => $type['type']));
        if (empty($block_info)) {return;}
    } else {
        $block_info = $args['info'];
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    $block_types_table =& $xartable['block_types'];

    // Update the info column for the block in the database.
    $query = "UPDATE $block_types_table SET info = ? WHERE id = ?";
    $bind = array(serialize($block_info), $type['tid']);
    $dbconn->Execute($query, $bind);
    return true;
}

?>

<?php
/**
 * Check for existance of a block type
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */
/**
 * Check for existance of a block type
 *
 * @author Jim McDonald, Paul Rosania
 * @access public
 * @param modName the module name
 * @param blockType the block type
 * @returns bool
 * @return true if exists, false if not found
 * @raise DATABASE_ERROR, BAD_PARAM
 * @deprec Deprecated 11 Jan 2004 - use countblocktypes directly
 */
function blocks_adminapi_block_type_exists($args)
{
    extract($args);

    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    if (empty($blockType)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'blockType');
        return;
    }

    $count = xarModAPIfunc('blocks', 'user', 'countblocktypes', array('module'=>$modName, 'type'=>$blockType));

    return ($count > 0) ? true : false;
}

?>

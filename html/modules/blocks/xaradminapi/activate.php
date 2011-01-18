<?php
/**
 * Activate a block
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
 * activate a block
 * @author Jim McDonald, Paul Rosania
 * @param int $args['bid'] the ID of the block to activate
 * @return bool true on success, false on failure
 */
function blocks_adminapi_activate($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($bid) || !is_numeric($bid)) {
        $msg = xarML('Wrong arguments for blocks_adminapi_activate');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // Security
    if(!xarSecurityCheck('CommentBlock',1,'Block',"::$bid")) {return;}

    $dbconn =& xarDB::getConn();
    $xartable =& xarDBGetTables();
    $blockstable = $xartable['block_instances'];

    // Activate
    $query = "UPDATE $blockstable SET xar_state = ? WHERE xar_id = ?";
    $result =& $dbconn->Execute($query,array(2,$bid));
    if (!$result) {return;}

    return true;
}

?>
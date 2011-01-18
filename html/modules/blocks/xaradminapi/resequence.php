<?php
/**
 * Resequence a blocks table
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
 * resequence a blocks table
 * @author Jim McDonald, Paul Rosania
 * @return bool true
 */
function blocks_adminapi_resequence($args)
{
    extract($args);

    $dbconn =& xarDB::getConn();
    $xartable =& xarDBGetTables();

    $bind = array();
    $where_clause = '';

    if (!empty($gid) && is_numeric($gid)) {
        $where_clause .= ' WHERE xar_group_id = ?';
        $bind[] = $gid;
    }

    $block_group_instances_table =& $xartable['block_group_instances'];

    // Get the information
    $query = 'SELECT xar_id, xar_group_id, xar_position'
        . ' FROM ' . $block_group_instances_table
        . $where_clause
        .' ORDER BY xar_group_id, xar_position, xar_id';

    $qresult =& $dbconn->Execute($query, $bind);
    if (!$qresult) {return;}

    // Fix sequence numbers
    $last_gid = NULL;
    while (!$qresult->EOF) {
        list($link_id, $gid, $old_position) = $qresult->fields;
        $qresult->MoveNext();

        // Reset sequence number if we've changed the group we're sorting
        if ($last_gid != $gid) {
            $last_gid = $gid;
            $position = 1;
        }
        if ($position != $old_position) {
            $query = 'UPDATE ' . $block_group_instances_table
                . ' SET xar_position = ? WHERE xar_id = ? AND xar_position <> ?';
            $bind = array($position, $link_id, $position);

            $uresult =& $dbconn->Execute($query, $bind);
            if (!$uresult) {return;}
        }

        $position++;
    }

    $qresult->Close();

    return true;
}

?>
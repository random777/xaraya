<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ poll data into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @link http://www.xaraya.com
 *
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Note : this file is part of import_pn.php and cannot be run separately
 */

    echo "<strong>$step. Importing old poll data</strong><br/>\n";

    if (!xarModIsAvailable('polls')) {
        echo "The polls module is not activated in Xaraya<br/>\n";
        return;
    }

    $query = 'SELECT pollID, optionText, optionCount, voteID
              FROM ' . $oldprefix . '_poll_data
              ORDER BY pollID ASC, voteID ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select poll options failed : " . $dbconn->ErrorMsg());
    }
    $num = 1;
    while (!$result->EOF) {
        list($pid,$text,$count,$vid) = $result->fields;
        // Check for both default and CHAR(50) data
        if ($text === '' || $text === '                                                  ') {
            $num++;
            $result->MoveNext();
            continue;
        } elseif (!isset($pollid[$pid])) {
            echo "Unknown poll id $pid for option $text<br />\n";
            $num++;
            $result->MoveNext();
            continue;
        }
        $newvid = xarModAPIFunc('polls','admin','createopt',
                                array('pid' => $pollid[$pid],
                                      'option' => $text,
                                      'votes' => $count));
        if (empty($newvid)) {
            echo "Insert poll option ($pid $vid) $text failed : " . xarErrorRender('text') . "<br/>\n";
        } elseif ($count < 100) {
            echo "Inserted poll option ($pid $vid) $text<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted poll option $num<br/>\n";
            flush();
        }
        $num++;
        $result->MoveNext();
    }
    $result->Close();
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['polls']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['polls_info']);

?>
<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ comments into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @link http://www.xaraya.com
 *
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Note : this file is part of import_pn.php and cannot be run separately
 */

    echo "<strong>$step. Importing comments</strong><br/>\n";

    $userid = unserialize(xarModGetVar('installer','userid'));
    $regid = xarModGetIDFromName('articles');
    $pid2cid = array();
// TODO: fix issue for large # of comments
    $pids = xarModGetVar('installer','commentid');
    if (!empty($pids)) {
        $pid2cid = unserialize($pids);
        $pids = '';
    }
    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_comments';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count comments failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();

    // Use different unix timestamp conversion function for
    // MySQL and PostgreSQL databases
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $dbfunction = "UNIX_TIMESTAMP(date)";
            break;
        case 'postgres':
                $dbfunction = "DATE_PART('epoch',date)";
            break;
        default:
            die("Unknown database type");
            break;
    }

    switch ($phpnukeversion) {
    case "6.5":
    case "6.8":
        $query = 'SELECT tid, sid, pid, ' . $dbfunction . ' , username, user_id,
                         host_name, subject, comment
                  FROM ' . $oldprefix . '_comments
                  LEFT JOIN ' . $oldprefix . '_users
                  ON ' . $oldprefix . '_users.username = ' . $oldprefix . '_comments.name
                  ORDER BY tid ASC';
        break;
    default:
        $query = 'SELECT tid, sid, pid, ' . $dbfunction . ' , uname, uid,
                         host_name, subject, comment
                  FROM ' . $oldprefix . '_comments
                  LEFT JOIN ' . $oldprefix . '_users
                  ON ' . $oldprefix . '_users.uname = ' . $oldprefix . '_comments.name
                  ORDER BY tid ASC';
        break;
    }

    $numitems = 1500;
    if (!isset($startnum)) {
        $startnum = 0;
    }

    if ($count > $numitems) {
        $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbconn->Execute($query);
    }
    if (!$result) {
        die("Oops, select comments failed : " . $dbconn->ErrorMsg());
    }
    if ($reset && $startnum == 0) {
        $dbconn->Execute("DELETE FROM " . $tables['comments']);
    }
    $num = 1;
    while (!$result->EOF) {
        list($tid,$sid,$pid,$date,$uname,$uid,$hostname,$subject,$comment) = $result->fields;

        if (isset($userid[$uid])) {
            $uid = $userid[$uid];
        } // else we're lost :)
        if (empty($uid) || $uid < 2) {
            $uid = _XAR_ID_UNREGISTERED;
        }
        $data = array();
        $data['modid'] = $regid;
        $data['itemtype'] = 1; // news articles
        $data['objectid'] = $sid;
        if (!empty($pid) && !empty($pid2cid[$pid])) {
            $pid = $pid2cid[$pid];
        }
        $data['pid'] = $pid;
        $data['author'] = $uid;
        $data['title'] = $subject;
        $data['comment'] = $comment;
        $data['hostname'] = $hostname;
        //$data['cid'] = $tid;
        $data['date'] = $date;
        $data['postanon'] = 0;

        $cid = xarModAPIFunc('comments','user','add',$data);
        if (empty($cid)) {
            echo "Failed inserting comment ($sid $pid) $uname - $subject : ".$dbconn->ErrorMsg()."<br/>\n";
        } elseif ($count < 200) {
            echo "Inserted comment ($sid $pid) $uname - $subject<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted comment " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        $pid2cid[$tid] = $cid;
        $num++;
        $result->MoveNext();
    }
    $result->Close();

    echo "<strong>TODO : import other comments</strong><br/><br/>\n";
    echo '<a href="import_nuke.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        xarModSetVar('installer','commentid',serialize($pid2cid));
        $startnum += $numitems;
        echo '<a href="import_nuke.php?step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - comments ' . $startnum . '+ of ' . $count . '</a><br/>';
    } else {
        xarModDelVar('installer','commentid');
        echo '<a href="import_nuke.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['comments']);

?>
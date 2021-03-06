<?php
/**
 * Import phpBB_14 module forums into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 * @author voll <voll@xaraya.com>
 */

/**
 * Note : this file is part of import_pn.php and cannot be run separately
 */

    if ($importmodule == 'articles') {
        echo "<strong>$step. Importing phpBB forums into categories</strong><br/>\n";
    } else {
        $users = xarModGetVar('installer','userid');
        if (!isset($users)) {
            $userid = array();
        } else {
            $userid = unserialize($users);
        }
        $settings = xarModGetVar('xarbb','settings');
        echo "<strong>$step. Importing phpBB forums into xarBB</strong><br/>\n";
    }

    $query = 'SELECT f.forum_id, f.cat_id, f.forum_name, f.forum_desc, f.forum_order, f.forum_posts, f.forum_topics, f.forum_last_post_id, p.poster_id, p.post_time
              FROM ' . $oldprefix . '_phpbb14_forums as f
              LEFT JOIN ' . $oldprefix . '_phpbb14_posts as p
              ON f.forum_last_post_id = p.post_id
              ORDER BY f.cat_id ASC, f.forum_order ASC, f.forum_id ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select forums failed : " . $dbconn->ErrorMsg());
    }
    $forumid = array();
    while (!$result->EOF) {
        list($fid, $cid, $name, $descr, $order, $posts, $topics, $lastpostid, $lastposter, $lastposttime) = $result->fields;
        if (!isset($catid[$cid])) {
            echo "Oops - no category id for $cid<br />\n";
            $catid[$cid] = 0;
        }
        if ($importmodule == 'articles') {
            $forumid[$fid] = xarModAPIFunc('categories', 'admin', 'create', array(
                                  'name' => $name,
                                  'description' => $descr,
                                  'parent_id' => $catid[$cid]));
        } else {
            if (isset($userid[$lastposter])) {
                $lastposter = $userid[$lastposter];
            } // else we're lost :)
            if (empty($lastposter) || $lastposter < 2) {
                $lastposter = _XAR_ID_UNREGISTERED;
            }
            $forumid[$fid]=xarModAPIFunc('xarbb',
                               'admin',
                               'create',
                               array('fname'    => $name,
                                     'fdesc'    => $descr,
                                     'cids'     => array($catid[$cid]),
                                     'ftopics'  => $topics,
                                     'fposts'   => $posts,
                                     'fposter'  => $lastposter,
                                     'fpostid'  => strtotime($lastposttime),
                                     'fstatus'  => 0));
            // use default settings here
            xarModSetVar('xarbb','settings.'.$forumid[$fid],$settings);
        }
        echo "Creating forum ($fid) $name - $descr in category $catid[$cid]<br/>\n";
        $result->MoveNext();
    }
    $result->Close();
    xarModSetVar('installer','forumid',serialize($forumid));
    echo '<a href="import_pn.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_pn.php?step=' . ($step+1) . '&module=' . $importmodule . '">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>
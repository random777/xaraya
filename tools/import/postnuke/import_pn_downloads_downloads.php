<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ downloads into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Note : this file is part of import_pn.php and cannot be run separately
 */

    echo "<strong>$step. Importing old downloads</strong><br/>\n";

    $userid = unserialize(xarModGetVar('installer','userid'));
    if (xarModIsAvailable('hitcount') && xarModAPILoad('hitcount','admin')) {
        $docounter = 1;
    }
    $downloads_cats = unserialize(xarModGetVar('installer','downloads_cats'));
    $regid = xarModGetIDFromName('articles');

    // Use different unix timestamp conversion function for 
    // MySQL and PostgreSQL databases
    $dbtype = xarModGetVar('installer','dbtype');
    switch ($dbtype) {
        case 'mysql':
                $dbfunction = "UNIX_TIMESTAMP(pn_date)";
            break;
        case 'postgres':
                $dbfunction = "DATE_PART('epoch',pn_date)";
            break;
        default:
            die("Unknown database type");
            break;
    }

    $query = 'SELECT pn_lid, pn_cid, pn_title, ' . $oldprefix . '_downloads_downloads.pn_url, pn_description,
                     ' . $dbfunction . ', ' . $oldprefix . '_downloads_downloads.pn_name, ' . $oldprefix . '_downloads_downloads.pn_email, pn_hits,
                     pn_submitter, pn_ratingsummary, pn_totalvotes, pn_uid
              FROM ' . $oldprefix . '_downloads_downloads
              LEFT JOIN ' . $oldprefix . '_users
              ON ' . $oldprefix . '_users.pn_uname = ' . $oldprefix . '_downloads_downloads.pn_submitter
              ORDER BY pn_lid ASC';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select downloads failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($lid, $catid, $title, $url, $descr, $date, $name,
            $email, $hits, $submitter, $rating, $votes, $uid) = $result->fields;
        $status = 2;
        $language = '';
        if (isset($userid[$uid])) {
            $uid = $userid[$uid];
        } // else we're lost :)
        if (empty($uid) || $uid < 2) {
            $uid = _XAR_ID_UNREGISTERED;
        }
        if (!empty($email)) {
            $email = ' <' . $email . '>';
        }
        $cids = array();
        if (isset($downloads_cats[$catid])) {
            $cids[] = $downloads_cats[$catid];
        }
        if (empty($title)) {
            $title = xarML('[none]');
        }
        $newaid = xarModAPIFunc('articles',
                                'admin',
                                'create',
                                array('title' => $title,
                                      'summary' => $descr,
                                      'body' => $url,
                                      'notes' => $name . $email,
                                      'status' => $status,
                                      'ptid' => 8,
                                      'pubdate' => $date,
                                      'authorid' => $uid,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $hits
                                     )
                               );
        if (!isset($newaid)) {
            echo "Insert download ($lid) $title failed : " . xarErrorRender('text') . "<br/>\n";
        } else {
            echo "Inserted download ($lid) $title<br/>\n";
        }
// TODO: ratings
        $result->MoveNext();
    }
    $result->Close();
    echo "<strong>TODO : import ratings, editorials, new downloads and modifications etc.</strong><br/><br/>\n";
    echo '<a href="import_pn.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_pn.php?step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>
<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ FAQ answers into your Xaraya test site
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

    echo "<strong>$step. Importing FAQ questions & answers</strong><br/>\n";

    $regid = xarMod::getRegID('articles');
    $faqs = xarModGetVar('installer','faqs');
    $faqid = unserialize(xarModGetVar('installer','faqid'));

    switch ($phpnukeversion) {
    case "6.0":
    case "6.5":
        $query = 'SELECT id, id_cat, question, answer
                  FROM ' . $oldprefix . '_faqAnswer
                  ORDER BY id ASC';
        break;
    default:
        $query = 'SELECT id, id_cat, question, answer
                  FROM ' . $oldprefix . '_faqanswer
                  ORDER BY id ASC';
        break;
    }
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, select faq answer failed : " . $dbconn->ErrorMsg());
    }
    while (!$result->EOF) {
        list($id, $catid, $title, $content) = $result->fields;
        $notes = '';
        $language = '';
        $cids = array();
    // TODO: check if we want to add articles to the Sections root too or not
        //$cids[] = $sections;
        if (isset($faqid[$catid])) {
            $cids[] = $faqid[$catid];
        }
        if (count($cids) == 0) {
            $cids[] = $faqs;
        }
        $counter = 0;
        $status = 2;
        if (empty($title)) {
            $title = xarML('[none]');
        }
        $newaid = xarMod::apiFunc('articles',
                                'admin',
                                'create',
                                array('title' => $title,
                                      'summary' => '',
                                      'body' => $content,
                                      'notes' => $notes,
                                      'status' => $status,
                                      'ptid' => 4,
                                      'pubdate' => 0,
                                      'authorid' => _XAR_ID_UNREGISTERED,
                                      'language' => $language,
                                      'cids' => $cids,
                                      'hits' => $counter
                                     )
                               );
        if (!isset($newaid)) {
            echo "Insert FAQ ($id) $title failed : " . xarErrorRender('text') . "<br/>\n";
        } else {
            echo "Inserted FAQ ($id) $title<br/>\n";
        }
        $result->MoveNext();
    }
    $result->Close();
    echo "<strong>TODO : do something with FAQ display</strong><br/><br/>\n";
    echo '<a href="import_nuke.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_nuke.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['articles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories_linkage']);
    if (!empty($docounter)) {
        $dbconn->Execute('OPTIMIZE TABLE ' . $tables['hitcount']);
    }

?>
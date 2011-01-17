<?php
/**
 * File: $Id$
 *
 * Import PostNuke .71+ FAQ categories into your Xaraya test site
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

    echo "<strong>$step. Importing old FAQs into categories</strong><br/>\n";

    echo "Creating root for old FAQs<br/>\n";
    $faqs = xarMod::apiFunc('categories', 'admin', 'create', array(
                             'name' => 'FAQs',
                             'description' => 'Frequently Asked Questions (.7x style)',
                             'parent_id' => 0));
    if ($reset) {
        $settings = unserialize(xarModGetVar('articles', 'settings.4'));
        $settings['number_of_categories'] = 1;
        $settings['cids'] = array($faqs);
        $settings['defaultview'] = 'c' . $faqs;
        xarModSetVar('articles', 'settings.4', serialize($settings));
        xarModSetVar('articles', 'number_of_categories.4', 1);
        xarModSetVar('articles', 'mastercids.4', $faqs);
    }
    if ($faqs > 0) {
        $query = 'SELECT pn_id_cat, pn_categories, pn_parent_id
                  FROM ' . $oldprefix . '_faqcategories
                  ORDER BY pn_parent_id ASC, pn_id_cat ASC';
        $result =& $dbconn->Execute($query);
        if (!$result) {
            die("Oops, select faqcategories failed : " . $dbconn->ErrorMsg());
        }
        // set parent 0 to root FAQ category
        $faqid[0] = $faqs;
        while (!$result->EOF) {
            list($id, $name, $parent) = $result->fields;
            if (!isset($parent) || $parent < 0) {
                $parent = 0;
            }
            if (!isset($faqid[$parent])) {
                echo "Oops, missing parent $parent for FAQ ($id) $name<br/>\n";
            } else {
                $faqid[$id] = xarMod::apiFunc('categories', 'admin', 'create',
                                           array('name' => $name,
                                           'description' => $name,
                                           'parent_id' => $faqid[$parent]));
                echo "Creating FAQ ($id) $name [parent $parent]<br/>\n";
            }
            $result->MoveNext();
        }
        $result->Close();
    }
    xarModSetVar('installer','faqs',$faqs);
    xarModSetVar('installer','faqid',serialize($faqid));
    echo '<a href="import_pn.php">Return to start</a>&nbsp;&nbsp;&nbsp;
          <a href="import_pn.php?step=' . ($step+1) . '&module=articles">Go to step ' . ($step+1) . '</a><br/>';
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['categories']);

?>
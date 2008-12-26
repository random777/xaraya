<?php
/**
 * Get menu links
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the DynamicData module development team
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function dynamicdata_userapi_getmenulinks()
{
    $menulinks = array();

    if(xarSecurityCheck('ViewDynamicDataItems')) {

        // get items from the objects table
        $objects = DataObjectMaster::getObjects();
        if (!isset($objects)) {
            return $menulinks;
        }
        $mymodid = xarModGetIDFromName('dynamicdata');
        foreach ($objects as $object) {
            $itemid = $object['objectid'];
            // skip the internal objects
            if ($itemid < 3) continue;
            $modid = $object['moduleid'];
            // don't show data "belonging" to other modules for now
            if ($modid != $mymodid) {
                continue;
            }
            // nice(r) URLs
            if ($modid == $mymodid) {
                $modid = null;
            }
            $itemtype = $object['itemtype'];
            if ($itemtype == 0) {
                $itemtype = null;
            }
            $label = $object['label'];
            $menulinks[] = Array('url'   => xarModURL('dynamicdata','user','view',
                                                      array('modid' => $modid,
                                                            'itemtype' => $itemtype)),
                                 'title' => xarML('View #(1)', $label),
                                 'label' => $label);
        }
    }

    return $menulinks;
}

?>

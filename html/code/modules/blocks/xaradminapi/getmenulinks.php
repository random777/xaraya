<?php
/**
 * Utility function to pass individual menu items
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author Jim McDonald, Paul Rosania
 * @returns array
 * @return array containing the menulinks for the main menu items.
 */
function blocks_adminapi_getmenulinks()
{
    /*
    This menu gets its data from the adminmenu.php file in the module's xardataapi folder.
    You can add or change menu items by changing the data there.
    Or you can create your own menu items here. They should have the form of this example:

    $menulinks = array();
    .....
    if (xarSecurityCheck('EditRole',0)) {
        $menulinks[] = array('url'   => xarModURL('roles',
                                                  'admin',
                                                  'viewroles'),
                              'title' => xarML('View and edit the groups on the system'),
                              'label' => xarML('View All Groups'));
    }
    .....
    return $menulinks;
    */

    // No special menu. Just return a standard array
    return xarMod::apiFunc('base','admin','menuarray',array('module' => 'blocks'));
}

?>

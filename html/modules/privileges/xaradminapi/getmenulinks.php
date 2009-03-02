<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Example module development team
 * @return array containing the menulinks for the main menu items.
 */
function privileges_adminapi_getmenulinks()
{
    static $menulinks = array();
    if (isset($menulinks[0])) {
        return $menulinks;
    }
    if (xarSecurityCheck('EditPrivilege',0)) {

        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'viewprivileges',array('phase' => 'active')),
                              'active'=> array('viewprivileges'),
                              'title' => xarML('View all privileges on the system'),
                              'label' => xarML('View Privileges'));
    }

    if (xarSecurityCheck('AssignPrivilege',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'newprivilege'),
                              'active'=> array('newprivileges'),
                              'title' => xarML('Add a new privilege to the system'),
                              'label' => xarML('Add Privilege'));
    }

    if (xarSecurityCheck('ReadPrivilege',0,'Realm') && xarModGetVar('privileges','showrealms')) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'viewrealms'),
                              'active'=> array('viewrealms',
                                               'newrealm',
                                               'viewrealms',
                                               'modifyrealm',
                                               'deleterealm'
                              ),
                              'title' => xarML('Add, change or delete realms'),
                              'label' => xarML('Manage Realms'));
    }

    if (xarSecurityCheck('AdminPrivilege',0)) {
        $menulinks[] = Array('url'   => xarModURL('privileges',
                                                  'admin',
                                                  'modifyconfig'),
                              'active'=> array('modifyconfig'),
                              'title' => xarML('Modify the privileges module configuration'),
                              'label' => xarML('Modify Config'));
        $menulinks[] = Array('url'    => xarModURL('privileges',
                                                   'admin',
                                                   'overview'
                               ),
                              'active'=> array('overview'),
                              'title' => xarML('Introduction on handling this module'),
                              'label' => xarML('Overview')
        );
    }
    return $menulinks;
}

?>
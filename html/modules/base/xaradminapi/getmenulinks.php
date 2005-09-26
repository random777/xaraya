<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/**
 * Pass individual menu items to the admin menu
 *
 * @author the Base module development team
 * @return array containing the menulinks for the admin menu items.
 */
function base_adminapi_getmenulinks()
{
     // Security Check
    $menulinks = array();
    if (xarSecurityCheck('AdminBase',0)) {

        $menuLinks[] = array('url'   => xarModURL('base','admin','sysinfo'),
                             'title' => xarML('View your PHP configuration'),
                             'label' => xarML('System'));
        $menuLinks[] = array('url'   => xarModURL('base','admin','release'),
                             'title' => xarML('View recent released extensions'),
                             'label' => xarML('Extension Releases'));
        $menuLinks[] = array('url'   => xarModURL('base','admin','modifyconfig'),
                             'title' => xarML('Modify Base configuration values'),
                             'label' => xarML('Modify Config'));
    }
    return $menuLinks;
}
?>
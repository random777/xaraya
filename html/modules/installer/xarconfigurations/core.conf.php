<?php
/**
 * Core configuration
 *
 * @package Installer
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 * @link http://xaraya.com/index.php/release/200.html
 */
/*
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

$configuration_name = xarML('Core Xaraya install - minimal modules needed to run Xaraya');

sys::import('modules.privileges.class.privileges');

function installer_core_moduleoptions()
{
    return array();
}

function installer_core_privilegeoptions()
{
    return array(
        array(
            'item' => 'p1',
            'option' => 'true',
            'comment' => xarML('Registered users have read access to all modules of the site.')
        ),
        array(
            'item' => 'p2',
            'option' => 'false',
            'comment' => xarML('Unregistered users have read access to the non-core modules of the site. If this option is not chosen unregistered users see only the first page.')
        ),

    );
}

/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
function installer_core_configuration_load($args)
{
// load the privileges chosen

    if(in_array('p1',$args)) {
        installer_core_readaccess();
        xarPrivileges::assign('ReadAccess','Users');
    }
    else {
        installer_core_casualaccess();
        xarPrivileges::assign('CasualAccess','Users');
    }

    if(in_array('p2',$args)) {
        installer_core_readaccess();
        installer_core_readnoncore();
        xarPrivileges::assign('ReadNonCore','Everybody');
   }
    else {
        if(in_array('p1',$args)) installer_core_casualaccess();
        xarPrivileges::assign('CasualAccess','Everybody');
    }

    return true;
}

function installer_core_casualaccess()
{
    xarPrivileges::register('CasualAccess','All','themes','Block','All','ACCESS_OVERVIEW','Minimal access to a site');
    xarPrivileges::register('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW','View the Login block');
    xarPrivileges::register('ViewBlocks','All','base','Block','All','ACCESS_OVERVIEW','View blocks of the Base module');
    xarPrivileges::register('ViewLoginItems','All','dynamicdata','Item','All','ACCESS_OVERVIEW','View some Dynamic Data items');
    xarPrivileges::register('ViewBlockItems','All','blocks','BlockItem','All','ACCESS_OVERVIEW','View block items in general');
    xarPrivileges::makeMember('ViewAuthsystem','CasualAccess');
    xarPrivileges::makeMember('ViewLogin','CasualAccess');
    xarPrivileges::makeMember('ViewBlocks','CasualAccess');
    xarPrivileges::makeMember('ViewLoginItems','CasualAccess');
    xarPrivileges::makeMember('ViewBlockItems','CasualAccess');
}

function installer_core_readnoncore()
{
    xarPrivileges::register('ReadNonCore','All',null,'All','All','ACCESS_NONE','Read access only to none-core modules');
    xarPrivileges::register('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
    xarPrivileges::register('DenyBlocks','All','blocks','All','All','ACCESS_NONE','Deny access to the Blocks module');
    xarPrivileges::register('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarPrivileges::register('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarPrivileges::register('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarPrivileges::makeMember('ReadAccess','ReadNonCore');
    xarPrivileges::makeMember('DenyPrivileges','ReadNonCore');
    xarPrivileges::makeMember('DenyBlocks','ReadNonCore');
    xarPrivileges::makeMember('DenyMail','ReadNonCore');
    xarPrivileges::makeMember('DenyModules','ReadNonCore');
    xarPrivileges::makeMember('DenyThemes','ReadNonCore');
}

function installer_core_readaccess()
{
        xarPrivileges::register('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
}
?>

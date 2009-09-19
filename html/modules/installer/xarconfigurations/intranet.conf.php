<?php
/**
 * Intranet configuration
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
 * @author Marc Lutolf
 */
$configuration_name = xarML('Intranet - modules and privilege appropriate for restricted access');

sys::import('modules.privileges.class.privileges');

function installer_intranet_moduleoptions()
{
    return array(
        array('name' => "autolinks",            'regid' => 11),
        array('name' => "bloggerapi",           'regid' => 745),
        array('name' => "categories",           'regid' => 147),
        array('name' => "comments",             'regid' => 14),
        array('name' => "example",              'regid' => 36),
        array('name' => "hitcount",             'regid' => 177),
        array('name' => "registration",         'regid' => 30205),
        array('name' => "search",               'regid' => 32),
        array('name' => "sniffer",              'regid' => 755),
        array('name' => "stats",                'regid' => 34),
        array('name' => "xmlrpcserver",         'regid' => 743),
        array('name' => "xmlrpcsystemapi",      'regid' => 744),
        array('name' => "xmlrpcvalidatorapi",   'regid' => 746),
        array('name' => "articles",             'regid' => 151)
    );
}

function installer_intranet_privilegeoptions()
{
    return array(
                     array(
                           'item' => 'p1',
                           'option' => 'true',
                           'comment' => xarML('Registered users have read access to the non-core modules of the site.')),
                     array(
                           'item' => 'p2',
                           'option' => 'false',
                           'comment' => xarML("Create an Oversight role that has full access but cannot change security. Password will be 'password'."))
                     );
}

/**
 * Load the configuration
 *
 * @access public
 * @return boolean
 */
function installer_intranet_configuration_load($args)
{
// load the privileges chosen

    installer_intranet_casualaccess();
    xarAssignPrivilege('CasualAccess','Everybody');

// now do the necessary loading for each item

    if(in_array('p1',$args)) {
        installer_intranet_readaccess();
        installer_intranet_readnoncore();
        xarAssignPrivilege('ReadNonCore','Users');
    }
    else {
        xarAssignPrivilege('CasualAccess','Users');
    }

    if(in_array('p2',$args)) {
        installer_intranet_oversightprivilege();
        installer_intranet_oversightrole();
        xarAssignPrivilege('Oversight','Oversight');
        if(!in_array('p1',$args)) {
            xarPrivileges::register('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Exclude access to the Privileges modules');
        }
        xarPrivileges::makeMember('DenyPrivileges','Oversight');
//        xarPrivileges::makeMember('Administration','Oversight');
   }

   return true;

}

function installer_intranet_oversightprivilege()
{
    xarPrivileges::register('Oversight','All',null,'All','All','ACCESS_NONE','The privilege container for the Oversight group');
}

function installer_intranet_oversightrole()
{
    $rolefields = array(
                    'itemid' => 0,  // make this explicit, because we are going to reuse the roles we define
                    'users' => 0,
                    'regdate' => time(),
                    'state' => ROLES_STATE_ACTIVE,
                    'valcode' => 'createdbysystem',
                    'authmodule' => xarMod::getID('roles'),
    );
    $group = DataObjectMaster::getObject(array('name' => 'roles_groups'));
    $rolefields['role_type'] = ROLES_GROUPTYPE;
    $rolefields['name'] = 'Oversight';
    $rolefields['uname'] = 'oversight';
    $group->createItem($rolefields);

    $user = DataObjectMaster::getObject(array('name' => 'roles_users'));
    $rolefields['role_type'] = ROLES_USERTYPE;
    $rolefields['name'] = 'Overseer';
    $rolefields['uname'] = 'overseer';
    $rolefields['password'] = MD5('password');
    $user->createItem($rolefields);

    xarRoles::makeMemberByName('Oversight','Administrators');
    xarRoles::makeMemberByName('Overseer','Oversight');
}

function installer_intranet_casualaccess()
{
    xarPrivileges::register('CasualAccess','All','themes','Block','All','ACCESS_OVERVIEW','Minimal access to a site');
//    xarPrivileges::register('ViewRegistrationLogin','All','registration','Block','rlogin:Login:All','ACCESS_OVERVIEW','View the User Access block');
    xarPrivileges::register('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW','View the Login block');
    xarPrivileges::register('ViewBlocks','All','base','Block','All','ACCESS_OVERVIEW','View blocks of the Base module');
    xarPrivileges::register('ViewLoginItems','All','dynamicdata','Item','All','ACCESS_OVERVIEW','View some Dynamic Data items');
    xarPrivileges::register('ViewBlockItems','All','blocks','BlockItem','All','ACCESS_OVERVIEW','View block items in general');
    xarPrivileges::makeMember('ViewAuthsystem','CasualAccess');
    xarPrivileges::makeMember('ViewLogin','CasualAccess');
    xarPrivileges::makeMember('ViewBlocks','CasualAccess');
    xarPrivileges::makeMember('ViewLoginItems','CasualAccess');
//    xarPrivileges::makeMember('ViewRegistrationLogin','CasualAccess');
    xarPrivileges::makeMember('ViewBlockItems','CasualAccess');
}

function installer_intranet_readnoncore()
{
    xarPrivileges::register('ReadNonCore','All',null,'All','All','ACCESS_NONE','Read access only to none-core modules');
//    xarPrivileges::register('ViewRegistrationLogin','All','registration','Block','rlogin:Login:All','ACCESS_OVERVIEW','View the User Access block');
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
    xarPrivileges::makeMember('ViewAuthsystem','ReadNonCore');
//    xarPrivileges::makeMember('ViewRegistrationLogin','ReadNonCore');
    //xarPrivileges::makeMember('DenyDynamicData','ReadNonCore');
}

function installer_intranet_readaccess()
{
        xarPrivileges::register('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
}
?>

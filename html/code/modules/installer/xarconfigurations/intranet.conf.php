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
            xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Exclude access to the Privileges modules');
        }
        xarMakePrivilegeMember('DenyPrivileges','Oversight');
//        xarMakePrivilegeMember('Administration','Oversight');
   }

   return true;

}

function installer_intranet_oversightprivilege()
{
    xarRegisterPrivilege('Oversight','All',null,'All','All','ACCESS_NONE','The privilege container for the Oversight group');
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

    xarMakeRoleMemberByName('Oversight','Administrators');
    xarMakeRoleMemberByName('Overseer','Oversight');
}

function installer_intranet_casualaccess()
{
    xarRegisterPrivilege('CasualAccess','All','themes','Block','All','ACCESS_OVERVIEW','Minimal access to a site');
//    xarRegisterPrivilege('ViewRegistrationLogin','All','registration','Block','rlogin:Login:All','ACCESS_OVERVIEW','View the User Access block');
    xarRegisterPrivilege('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW','View the Login block');
    xarRegisterPrivilege('ViewBlocks','All','base','Block','All','ACCESS_OVERVIEW','View blocks of the Base module');
    xarRegisterPrivilege('ViewLoginItems','All','dynamicdata','Item','All','ACCESS_OVERVIEW','View some Dynamic Data items');
    xarRegisterPrivilege('ViewBlockItems','All','blocks','BlockItem','All','ACCESS_OVERVIEW','View block items in general');
    xarMakePrivilegeMember('ViewAuthsystem','CasualAccess');
    xarMakePrivilegeMember('ViewLogin','CasualAccess');
    xarMakePrivilegeMember('ViewBlocks','CasualAccess');
    xarMakePrivilegeMember('ViewLoginItems','CasualAccess');
//    xarMakePrivilegeMember('ViewRegistrationLogin','CasualAccess');
    xarMakePrivilegeMember('ViewBlockItems','CasualAccess');
}

function installer_intranet_readnoncore()
{
    xarRegisterPrivilege('ReadNonCore','All',null,'All','All','ACCESS_NONE','Read access only to none-core modules');
//    xarRegisterPrivilege('ViewRegistrationLogin','All','registration','Block','rlogin:Login:All','ACCESS_OVERVIEW','View the User Access block');
    xarRegisterPrivilege('DenyPrivileges','All','privileges','All','All','ACCESS_NONE','Deny access to the Privileges module');
    xarRegisterPrivilege('DenyBlocks','All','blocks','All','All','ACCESS_NONE','Deny access to the Blocks module');
    xarRegisterPrivilege('DenyMail','All','mail','All','All','ACCESS_NONE','Deny access to the Mail module');
    xarRegisterPrivilege('DenyModules','All','modules','All','All','ACCESS_NONE','Deny access to the Modules module');
    xarRegisterPrivilege('DenyThemes','All','themes','All','All','ACCESS_NONE','Deny access to the Themes module');
    xarMakePrivilegeMember('ReadAccess','ReadNonCore');
    xarMakePrivilegeMember('DenyPrivileges','ReadNonCore');
    xarMakePrivilegeMember('DenyBlocks','ReadNonCore');
    xarMakePrivilegeMember('DenyMail','ReadNonCore');
    xarMakePrivilegeMember('DenyModules','ReadNonCore');
    xarMakePrivilegeMember('DenyThemes','ReadNonCore');
    xarMakePrivilegeMember('ViewAuthsystem','ReadNonCore');
//    xarMakePrivilegeMember('ViewRegistrationLogin','ReadNonCore');
    //xarMakePrivilegeMember('DenyDynamicData','ReadNonCore');
}

function installer_intranet_readaccess()
{
        xarRegisterPrivilege('ReadAccess','All','All','All','All','ACCESS_READ','Read access to all modules');
}
?>
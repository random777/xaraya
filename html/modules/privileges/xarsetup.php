<?php
/**
 * Default setup for roles and privileges
 *
 * @package core modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 */
/**
 * Purpose of file:  Default setup for roles and privileges
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

function initializeSetup()
{
    /*********************************************************************
    * Enter some default groups and users
    *********************************************************************/
    xarMakeGroup('Everybody');
    xarMakeUser('Anonymous','anonymous','anonymous@invalid.tld');
    xarMakeUser('Admin','Admin','none@invalid.tld','password');
    xarMakeGroup('Administrators');
    xarMakeGroup('Users');
    xarMakeUser('Myself','myself','myself@invalid.tld','password');

    /*********************************************************************
    * Arrange the roles in a hierarchy
    * Format is
    * makeMember(Child,Parent)
    *********************************************************************/

    xarMakeRoleRoot('Everybody');
    xarMakeRoleMemberByName('Administrators','Everybody');
    xarMakeRoleMemberByName('Admin','Administrators');
    xarMakeRoleMemberByName('Users','Everybody');
    xarMakeRoleMemberByName('Anonymous','Everybody');
    xarMakeRoleMemberByName('Myself','Everybody');

    /*********************************************************************
    * Define instances for the core modules
    * Format is
    * xarDefineInstance(Module,Component,Querystring,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/
    $systemPrefix = xarDBGetSystemTablePrefix();

    $blockGroupsTable    = $systemPrefix . '_block_groups';
    $blockTypesTable     = $systemPrefix . '_block_types';
    $blockInstancesTable = $systemPrefix . '_block_instances';
    $modulesTable        = $systemPrefix . '_modules';
    $rolesTable          = $systemPrefix . '_roles';
    $roleMembersTable    = $systemPrefix . '_rolemembers';
    $privilegesTable     = $systemPrefix . '_privileges';
    $privMembersTable    = $systemPrefix . '_privmembers';
    $themesTable         = $systemPrefix . '_themes';

    /*-------------------------------- Blocks Module
     *jojodee - why are the block instance defines here? Modules module is now loaded prior.
     * Move this (at post v1.1.1) back to Blocks initialization, try and keep everything in the blocks xarinit as much as possible.
     * TODO: Review all others and move out to their respective modules if possible.
     */
    /*
    $query1 = "SELECT DISTINCT xar_name FROM $blockGroupsTable";
    $query2 = "SELECT DISTINCT xar_id FROM $blockGroupsTable";
    $instances = array(array('header'  => 'Group Name:',
                             'query'   => $query1,
                             'limit'   => 20),
                       array('header'  => 'Group ID:',
                             'query'   => $query2,
                             'limit'   => 20));

    xarDefineInstance('blocks','BlockGroups',$instances);

    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable ";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('blocks','Blocks',$instances);
    */
   //--------------------------------- Roles Module
    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_module = 'roles'";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'roles'";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'roles'";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('roles','Block',$instances);

    $query = "SELECT DISTINCT xar_name FROM $rolesTable";
    $instances = array(array('header' => 'Users and Groups',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('roles','Roles',$instances,0,$roleMembersTable,'xar_uid','xar_parentid','Instances of the roles module, including multilevel nesting');

    $instances = array(array('header' => 'Parent:',
                             'query' => $query,
                             'limit' => 20),
                       array('header' => 'Child:',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('roles','Relation',$instances,0,$roleMembersTable,'xar_uid','xar_parentid','Instances of the roles module, including multilevel nesting');

   // ----------------------------- Privileges Module
    $query = "SELECT DISTINCT xar_name FROM $privilegesTable";
    $instances = array(array('header' => 'Privileges',
                             'query' => $query,
                             'limit' => 20));
    xarDefineInstance('privileges','Privileges',$instances,0,$privMembersTable,'xar_pid','xar_parentid','Instances of the privileges module, including multilevel nesting');

    // ----------------------------- Base Module
    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_module = 'base'";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'base'";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'base'";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('base','Block',$instances);

   // ------------------------------- Themes Module - move to themes module install
   /* $query1 = "SELECT DISTINCT xar_name FROM $themesTable";
    $query2 = "SELECT DISTINCT xar_regid FROM $themesTable";
    $instances = array(array('header' => 'Theme Name:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Theme ID:',
                             'query' => $query2,
                             'limit' => 20));
    xarDefineInstance('themes','Themes',$instances);

    $query1 = "SELECT DISTINCT xar_type FROM $blockTypesTable WHERE xar_module = 'themes'";
    $query2 = "SELECT DISTINCT instances.xar_title FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'themes'";
    $query3 = "SELECT DISTINCT instances.xar_id FROM $blockInstancesTable as instances LEFT JOIN $blockTypesTable as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'themes'";
    $instances = array(array('header' => 'Block Type:',
                             'query' => $query1,
                             'limit' => 20),
                       array('header' => 'Block Title:',
                             'query' => $query2,
                             'limit' => 20),
                       array('header' => 'Block ID:',
                             'query' => $query3,
                             'limit' => 20));
    xarDefineInstance('themes','Block',$instances);
   */
    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * xarregisterMask(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterMask('pnLegacyMask','All','All','All','All','ACCESS_NONE');
    xarRegisterMask('AdminAll','All','All','All','All','ACCESS_ADMIN');

    xarRegisterMask('ViewBaseBlocks','All','base','Block','All:All:All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadBaseBlock','All','base','Block','All:All:All','ACCESS_READ');
    xarRegisterMask('EditBaseBlock','All','base','Block','All:All:All','ACCESS_EDIT');
    xarRegisterMask('AddBaseBlock','All','base','Block','All:All:All','ACCESS_ADD');
    xarRegisterMask('DeleteBaseBlock','All','base','Block','All:All:All','ACCESS_DELETE');
    xarRegisterMask('AdminBaseBlock','All','base','Block','All:All:All','ACCESS_ADMIN');
    xarRegisterMask('ViewBase','All','base','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadBase','All','base','All','All','ACCESS_READ');
    xarRegisterMask('AdminBase','All','base','All','All','ACCESS_ADMIN');
    /* This AdminPanel mask is added to replace the adminpanel module equivalent
     *   - since adminpanel module is removed as of 1.1.0
     * At some stage we should remove this but practice has been to use this mask in xarSecurityCheck
     * frequently in module code and templates - left here for now for ease in backward compatibiilty
     */
    xarRegisterMask('AdminPanel','All','base','All','All','ACCESS_ADMIN');

    xarRegisterMask('AdminInstaller','All','installer','All','All','ACCESS_ADMIN');
   /* Moved to Auth System module
    xarRegisterMask('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystemBlocks','All','authsystem','Block','All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
    */
   // xarRegisterMask('ViewLogin','All','roles','Block','login:Login:All','ACCESS_OVERVIEW'); //No longer required in V1.1
    xarRegisterMask('ViewRolesBlocks','All','roles','Block','All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewRoles','All','roles','Roles','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadRole','All','roles','Roles','All','ACCESS_READ');
    xarRegisterMask('EditRole','All','roles','Roles','All','ACCESS_EDIT');
    xarRegisterMask('AddRole','All','roles','Roles','All','ACCESS_ADD');
    xarRegisterMask('DeleteRole','All','roles','Roles','All','ACCESS_DELETE');
    xarRegisterMask('AdminRole','All','roles','Roles','All','ACCESS_ADMIN');
    xarRegisterMask('MailRoles','All','roles','Mail','All','ACCESS_ADMIN');

    xarRegisterMask('AttachRole','All','roles','Relation','All','ACCESS_ADD');
    xarRegisterMask('RemoveRole','All','roles','Relation','All','ACCESS_DELETE');
    /* Move to Mail
    xarRegisterMask('EditMail','All','mail','All','All','ACCESS_EDIT');
    xarRegisterMask('AddMail','All','mail','All','All','ACCESS_ADD');
    xarRegisterMask('DeleteMail', 'All','mail','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminMail','All','mail','All','All','ACCESS_ADMIN');
    */
    /* Post V1.1.1 Moved to Blocks module initialization and upgrade
    xarRegisterMask('CommentBlock','All','blocks','All','All','ACCESS_EDIT');
    xarRegisterMask('EditBlock','All','blocks','All','All','ACCESS_EDIT');
    xarRegisterMask('AddBlock','All','blocks','All','All','ACCESS_ADD');
    xarRegisterMask('DeleteBlock','All','blocks','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminBlock','All','blocks','All','All','ACCESS_ADMIN');
    */
    xarRegisterMask('AssignPrivilege','All','privileges','All','All','ACCESS_ADD');
    xarRegisterMask('DeassignPrivilege','All','privileges','All','All','ACCESS_DELETE');
    xarRegisterMask('ViewPrivileges','All','privileges','All','All','ACCESS_READ');
    xarRegisterMask('EditPrivilege','All','privileges','All','All','ACCESS_EDIT');
    xarRegisterMask('AddPrivilege','All','privileges','All','All','ACCESS_ADD');
    xarRegisterMask('DeletePrivilege','All','privileges','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminPrivilege','All','privileges','All','All','ACCESS_ADMIN');

    xarRegisterMask('ViewPrivileges','All','privileges','Realm','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadPrivilege','All','privileges','Realm','All','ACCESS_READ');
    xarRegisterMask('EditPrivilege','All','privileges','Realm','All','ACCESS_EDIT');
    xarRegisterMask('AddPrivilege','All','privileges','Realm','All','ACCESS_ADD');
    xarRegisterMask('DeletePrivilege','All','privileges','Realm','All','ACCESS_DELETE');

    xarRegisterMask('EditModules','All','modules','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminModules','All','modules','All','All','ACCESS_ADMIN');
    /* Move to themes module
    xarRegisterMask('ViewThemes','All','themes','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('AdminTheme','All','themes','All','All','ACCESS_ADMIN');
    */
    // Initialisation successful
    return true;
}

?>
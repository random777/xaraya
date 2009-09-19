<?php
/**
 * Initialise the roles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 */

/**
 * Initialise the roles module
 *
 * @access public
 * @return bool
 * @throws DATABASE_ERROR
 */
function roles_init()
{
    $dbconn =& xarDB::getConn();
    $tables =& xarDB::getTables();

    $prefix = xarDB::getPrefix();
    $tables['roles'] = $prefix . '_roles';
    $tables['rolemembers'] = $prefix . '_rolemembers';

    // Create tables inside a transaction
    try {
        $charset = xarSystemVars::get(sys::CONFIG, 'DB.Charset');
        $dbconn->begin();

        sys::import('xaraya.installer');
        Installer::createTable('schema', 'roles');

        // We're done, commit
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    //Database Initialisation successful
    return true;
}

function roles_activate()
{
    //TODO: this stuff is happening here because at install blocks is not yet installed

    // --------------------------------------------------------
    //
    // Create some modvars
    //
    //TODO: improve on this hardwiring
    xarModVars::set('roles', 'defaultauthmodule', 'authsystem');
    xarModVars::set('roles', 'defaultregmodule', '');
    xarModVars::set('roles', 'rolesdisplay', 'tabbed');
    xarModVars::set('roles', 'locale', '');
    xarModVars::set('roles', 'duvsettings', serialize(array()));
    xarModVars::set('roles', 'userhome', 'undefined');
    xarModVars::set('roles', 'userlastlogin', 0);
    xarModVars::set('roles', 'passwordupdate', 0);
    xarModVars::set('roles', 'usertimezone', xarConfigVars::get(null, 'Site.Core.TimeZone'));
    xarModVars::set('roles', 'useremailformat', 'text');
    xarModVars::set('roles', 'displayrolelist', false);
    xarModVars::set('roles', 'usereditaccount', true);
    xarModVars::set('roles', 'allowuserhomeedit', false);
    xarModVars::set('roles', 'loginredirect', true);
    xarModVars::set('roles', 'allowexternalurl', false);
    xarModVars::set('roles', 'allowemail', false);
    xarModVars::set('roles', 'requirevalidation', true);

    /*
    // set the current session information to the right anonymous id
    // TODO: make the setUserInfo a class static in xarSession.php
    xarSession_setUserInfo($role->getID(), 0);
    */

    // --------------------------------------------------------
    // Register block types
    xarModAPIFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'online'));
    xarModAPIFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'user'));
    xarModAPIFunc('blocks', 'admin','register_block_type', array('modName' => 'roles','blockType' => 'language'));

    // Register hooks here, init is too soon
    xarModRegisterHook('item', 'search', 'GUI','roles', 'user', 'search');
    xarModRegisterHook('item', 'usermenu', 'GUI','roles', 'user', 'usermenu');

    // --------------------------------------------------------
    //
    // Enter some default groups and users and put them in a hierarchy
    //
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
    xarModVars::set('roles', 'defaultgroup', 0);

    // The top level group Everybody
    $rolefields['name'] = 'Everybody';
    $rolefields['uname'] = 'everybody';
    $rolefields['parentid'] = 0;
    $topid = $group->createItem($rolefields);
    xarModVars::set('roles', 'everybody', $topid);
    xarModVars::set('roles', 'primaryparent', $topid);
    xarModUserVars::set('roles', 'userhome', '[base]',$topid);

    // The Administrators group
    $rolefields['name'] = 'Administrators';
    $rolefields['uname'] = 'administrators';
    $rolefields['parentid'] = $topid;
    $admingroup = $group->createItem($rolefields);
    $lockdata = array('roles' => array( array('id' => $admingroup,
                                              'name' => $rolefields['name'],
                                              'notify' => TRUE)),
                                              'message' => '',
                                              'locked' => 0,
                                              'notifymsg' => '');
    xarModVars::set('roles', 'lockdata', serialize($lockdata));

    // The Users group group
    $rolefields['name'] = 'Users';
    $rolefields['uname'] = 'users';
    $rolefields['parentid'] = $topid;
    $usergroup = $group->createItem($rolefields);
    xarModVars::set('roles', 'defaultgroup', $usergroup);

    $user = DataObjectMaster::getObject(array('name' => 'roles_users'));
    $rolefields['role_type'] = ROLES_USERTYPE;

        // The Anonymous user
    $rolefields['name'] = 'Anonymous';
    $rolefields['uname'] = 'anonymous';
    $rolefields['parentid'] = $topid;
    $anonid = $user->createItem($rolefields);
    xarConfigVars::set(null, 'Site.User.AnonymousUID', $anonid);

    // The Administrator
    $rolefields['name'] = 'Administrator';
    $rolefields['uname'] = 'admin';
    $rolefields['email'] = 'none@none.com';
    $rolefields['parentid'] = $admingroup;
    $adminid = $user->createItem($rolefields);
    xarModVars::set('roles', 'admin', $adminid);

    // Installation complete; check for upgrades
    return roles_upgrade('2.0.0');
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @returns bool
 */
function roles_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0':
      break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @return bool
 */
function roles_delete()
{
  //this module cannot be removed
  return false;
}
?>

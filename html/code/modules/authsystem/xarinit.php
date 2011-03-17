<?php
/**
 * Functions that manage installation, upgrade and deinstallation of the module
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 *
 * @author Jan Schrage
 * @author John Cox
 * @author Gregor Rothfuss
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 */

/**
 * Initialise the module. This function is called once when the module is intalled.
 *
 * @return boolean true on success, false on failure
 */
function authsystem_init()
{
    // @CHECKME: is this necessary during init? surely it should have these defaults to begin with?
    $dbconn =& xarDB::getConn();
    $xartable =& xarDB::getTables();
    $modulesTable = xarDB::getPrefix() .'_modules';
    $modid = xarModGetIDFromName('authsystem');
    // update the modversion class and admin capable
    $query = "UPDATE $modulesTable SET class=?, admin_capable=?
             WHERE regid = ?";
    $bindvars = array('Authentication',true,$modid);
    $result = $dbconn->Execute($query,$bindvars);

    // Create the login block
    if (!$result) return;
    //create the blocktype
    $bid = xarMod::apiFunc('blocks','admin','register_block_type',
           array('modName' => 'authsystem',
                 'blockType' => 'login'));
    if (!$bid) return;

    // Installation complete; check for upgrades
    return authsystem_upgrade('2.0.0');
}

/**
 * Activate the module. This function is called when the module is changed from installed to active state.
 *
 * @return boolean true on success, false on failure
 */
function authsystem_activate()
{
    xarRegisterPrivilege('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
    xarRegisterPrivilege('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');

    xarRegisterMask('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystemBlocks','All','authsystem','Block','All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
    xarRegisterMask('ManageAuthsystem','All','authsystem','All','All','ACCESS_DELETE');
    xarRegisterMask('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
    
    // Installation complete; check for upgrades
    return authsystem_upgrade('2.0.0');
}

/**
 * Upgrade the module from an old version. This function is called when the module is being upgraded.
 *
 * @param string $oldversion The three digit version number of the currently installed (old) version
 * @return boolean true on success, false on failure
 */
function authsystem_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0.0': // Upgrades to 2.2.0
            // Register event subjects
            xarEvents::registerSubject('UserLogin', 'user', 'authsystem');
            xarEvents::registerSubject('UserLogout', 'user', 'authsystem');
        case '2.2.0':
            sys::import('modules.authsystem.class.authsystem');
            // Register AuthSystem specific event subjects and observers
            // Site lock subjects
            AuthSystem::registerSubject('AuthSiteLock', 'auth', 'authsystem', 'class', 'authsubjects');
            AuthSystem::registerSubject('AuthSiteUnlock', 'auth', 'authsystem', 'class', 'authsubjects');
            // Site lock observers 
            AuthSystem::registerObserver('AuthSiteLock', 'authsystem', 'class', 'authobservers');
            AuthSystem::registerObserver('AuthSiteUnlock', 'authsystem', 'class', 'authobservers');
            // ServerRequest observer (for sitelock)
            //xarEvents::registerObserver('ServerRequest', 'authsystem');
            // Authentication subjects
            AuthSystem::registerSubject('AuthLogin', 'auth', 'authsystem', 'class', 'authsubjects');
            AuthSystem::registerSubject('AuthLoginForm', 'auth', 'authsystem', 'class', 'authsubjects');
            // Authentication observers 
            AuthSystem::registerObserver('AuthLogin', 'authsystem', 'class', 'authobservers');
            AuthSystem::registerObserver('AuthLoginForm', 'authsystem', 'class', 'authobservers');

      break;
    }
    return true;
}

/**
 * Delete the module.
 * This function is called when the module is being uninstalled.
 *
 * @return boolean true on success, false on failure
 */
function authsystem_delete()
{
  //this module cannot be removed
  return false;
}

?>

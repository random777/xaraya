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
    //Set the default authmodule if not already set
    $isdefaultauth = xarModVars::get('roles','defaultauthmodule');
    if (empty($isdefaultauth)) {
       xarModVars::get('roles', 'defaultauthmodule', 'authsystem');
    }

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

    /* Define Module vars */
    xarModVars::set('authsystem', 'lockouttime', 15);
    xarModVars::set('authsystem', 'lockouttries', 3);
    xarModVars::set('authsystem', 'uselockout', false);
    
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

            // Site lock subjects
            xarEvents::registerSubject('AuthSiteLock', 'auth', 'authsystem', 'class', 'authsubjects');
            xarEvents::registerSubject('AuthSiteUnlock', 'auth', 'authsystem', 'class', 'authsubjects');
            // Site lock observers 
            xarEvents::registerObserver('AuthSiteLock', 'authsystem', 'class', 'authobservers');
            xarEvents::registerObserver('AuthSiteUnlock', 'authsystem', 'class', 'authobservers');
            // ServerRequest observer (for sitelock)
            //xarEvents::registerObserver('ServerRequest', 'authsystem');
            // Authentication subjects
            xarEvents::registerSubject('AuthLogin', 'auth', 'authsystem', 'class', 'authsubjects');
            xarEvents::registerSubject('AuthLoginForm', 'auth', 'authsystem', 'class', 'authsubjects');
            // Authentication observers 
            xarEvents::registerObserver('AuthLogin', 'authsystem', 'class', 'authobservers');
            xarEvents::registerObserver('AuthLoginForm', 'authsystem', 'class', 'authobservers');

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

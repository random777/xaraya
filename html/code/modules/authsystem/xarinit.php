<?php
/**
 * Initialise the Authsystem module
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage authsystem
 * @link http://xaraya.com/index.php/release/42.html
 * @author Jan Schrage, John Cox, Gregor Rothfuss
 */

/**
 * Initialise the Authsystem module
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @access public
 * @return bool
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

    // Installation complete; <chris> don't check for upgrades here
    // since we don't have all modules activated at install time 
    // return authsystem_upgrade('2.0.0');
    return true;
}
/*
 * We don't have all modules activated at install time
 * @CHECKME: does this function run after an upgrade too?
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

    /* Added sitelock module var at 2.3.0 */
    $sitelock = array(
        'locked' => 0,
        'lockstate' => 0,
        'lockmessage' => xarML('The site is currently locked. Thank you for your patience'),
        'lockaccess' => array(),                
        'locknotify' => '',
        'adminnotify' => 0,
    );
    xarModVars::set('authsystem', 'sitelock', serialize($sitelock));
    
    // Installation complete; <chris> no need to check for upgrades here
    // all core modules should be automatically upgraded during core install/upgrade
    // return authsystem_upgrade('2.0.0');
    return true;
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @returns bool
 */
function authsystem_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.2.0': // Upgrades to 2.3.0 
            /* Added sitelock module var */
            // @TODO: import lock settings from roles 
            $sitelock = array(
                'locked' => 0,
                'lockstate' => 0,
                'lockmessage' => xarML('The site is currently locked. Thank you for your patience'),
                'lockaccess' => array(),                
                'locknotify' => '',
                'adminnotify' => 0,
            );
            xarModVars::set('authsystem', 'sitelock', serialize($sitelock));
      break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @return bool
 */
function authsystem_delete()
{
  //this module cannot be removed
  return false;
}

?>

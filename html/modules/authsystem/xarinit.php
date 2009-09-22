<?php
/**
 * Initialise the Authsystem module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
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
    $bid = xarModAPIFunc('blocks','admin','register_block_type',
           array('modName' => 'authsystem',
                 'blockType' => 'login'));
    if (!$bid) return;

    // Installation complete; check for upgrades
    return authsystem_upgrade('2.0.0');
}
/*
 * We don't have all modules activated at install time
 */
function authsystem_activate()
{
    sys::import('modules.privileges.class.privileges');
    xarPrivileges::register('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
    xarPrivileges::register('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');

    xarMasks::register('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW');
    xarMasks::register('ViewAuthsystemBlocks','All','authsystem','Block','All','ACCESS_OVERVIEW');
    xarMasks::register('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    xarMasks::register('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
    xarMasks::register('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');

    /* Define Module vars */
    xarModVars::set('authsystem', 'lockouttime', 15);
    xarModVars::set('authsystem', 'lockouttries', 3);
    xarModVars::set('authsystem', 'uselockout', false);

    // Installation complete; check for upgrades
    return authsystem_upgrade('2.0.0');
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
function authsystem_delete()
{
  //this module cannot be removed
  return false;
}

?>

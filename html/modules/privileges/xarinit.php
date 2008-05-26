<?php
/**
 * Initialisation functions for the security module
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage privileges
 * @link http://xaraya.com/index.php/release/1098.html
 */

sys::import('xaraya.tableddl');

 /**
 * Initialise the privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 *
 * @returns bool
 * @throws DATABASE_ERROR
 */
function privileges_init()
{
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();

    $prefix = xarDB::getPrefix();
    $tables['privileges'] = $prefix . '_privileges';
    $tables['privmembers'] = $prefix . '_privmembers';
    $tables['security_acl'] = $prefix . '_security_acl';
    $tables['security_instances'] = $prefix . '_security_instances';
    $tables['security_realms']      = $prefix . '_security_realms';
    $tables['security_privsets']      = $prefix . '_security_privsets';

    // All or nothing
    try {
        $dbconn->begin();

        // Create tables
        /*********************************************************************
         * Here we create all the tables for the privileges module
         *
         * prefix_privileges       - holds privileges info
         * prefix_privmembers      - holds info on privileges group membership
         * prefix_security_acl     - holds info on privileges assignments to roles
         * prefix_security_masks   - holds info on masks for security checks
         * prefix_security_instances       - holds module instance definitions
         * prefix_security_realms  - holds realsm info
         ********************************************************************/

        sys::import('xaraya.installer');
        Installer::createTable('schema', 'privileges');

        xarDB::importTables(array('privileges' => $prefix . '_privileges'));
        xarDB::importTables(array('privmembers' => $prefix . '_privmembers'));
        xarDB::importTables(array('security_acl' => $prefix . '_security_acl'));
        xarDB::importTables(array('security_instances' => $prefix . '_security_instances'));

        $dbconn->commit();
        // Set up an initial value for module variables.

        // Initialisation successful
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}

function privileges_activate()
{
    // On activation, set our variables
    xarModVars::set('privileges', 'showrealms', false);
    xarModVars::set('privileges', 'inheritdeny', true);
    xarModVars::set('privileges', 'tester', 0);
    xarModVars::set('privileges', 'test', false);
    xarModVars::set('privileges', 'testdeny', false);
    xarModVars::set('privileges', 'testmask', 'All');
    xarModVars::set('privileges', 'realmvalue', 'none');
    xarModVars::set('privileges', 'realmcomparison','exact');
    return true;
}

/**
 * Upgrade the privileges module from an old version
 *
 * @param oldVersion the old version to upgrade from
 * @returns bool
 */
function privileges_upgrade($oldVersion)
{
    switch($oldVersion) {
    case '0.1.0':
        if (!xarModAPIFunc('privileges','admin','createobjects')) return;
        break;
    }
    return true;
}

/**
 * Delete the privileges module
 *
 * @param none
 * @returns boolean
 */
function privileges_delete()
{
    // this module cannot be removed
    return false;

    /*********************************************************************
    * Drop the tables
    *********************************************************************/

    // Get database information
    $dbconn =& xarDB::getConn();
    $tables =& xarDB::getTables();

    // TODO: wrap in transaction? (this section is only for testing anyways)
    $query = xarDBDropTable($tables['privileges']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    $query = xarDBDropTable($tables['privmembers']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    $query = xarDBDropTable($tables['security_realms']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    $query = xarDBDropTable($tables['security_acl']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    $query = xarDBDropTable($tables['security_masks']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    $query = xarDBDropTable($tables['security_instances']);
    if (empty($query)) return; // throw back
    $dbconn->Execute($query);

    return true;
}
?>

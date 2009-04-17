<?php
/**
 * Module initialization functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules
 * @link http://xaraya.com/index.php/release/1.html
 */
// Load Table Maintainance API
sys::import('xaraya.tableddl');
/**
 * Initialise the modules module
 *
 * @return bool
 * @throws DATABASE_ERROR
 */
function modules_init()
{
    // Get database information
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();

    $prefix = xarDB::getPrefix();

    $tables['modules'] = $prefix . '_modules';
    $tables['module_vars'] = $prefix . '_module_vars';
    $tables['module_itemvars'] = $prefix . '_module_itemvars';
    $tables['hooks'] = $prefix . '_hooks';
    // Create tables
    // This should either go, or fail competely
    try {
        $dbconn->begin();
        /**
         * Here we create all the tables for the module system
         *
         * prefix_modules       - basic module info
         * prefix_module_vars   - module variables table
         * prefix_hooks         - table for hooks
         */
        // prefix_modules
        /**
         * CREATE TABLE xar_modules (
         *   id int(11) NOT NULL auto_increment,
         *   name varchar(64) NOT NULL,
         *   regid int(10) integer unsigned NOT NULL,
         *   directory varchar(64) NOT NULL,
         *   version varchar(10) NOT NULL default '0',
         *   class varchar(64) NOT NULL,
         *   category varchar(64) NOT NULL,
         *   admin_capable INTEGER NOT NULL default '0',
         *   user_capable INTEGER NOT NULL default '0',
         *   state INTEGER NOT NULL default '0'
         *   PRIMARY KEY  (id)
         * )
         */
        $fields = array(
                        'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
                        'name' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'regid' => array('type' => 'integer', 'unsigned'=>true, 'null' => false),
                        'directory' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'version' => array('type' => 'varchar', 'size' => 10, 'null' => false),
                        'class' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'category' => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'admin_capable' => array('type' => 'boolean', 'default' => false),
                        'user_capable' => array('type' => 'boolean', 'default' => false),
                        'state' => array('type' => 'integer', 'size' => 'tiny','unsigned'=>true, 'null' => false, 'default' => '1')
                        );

        // Create the modules table
        $query = xarDBCreateTable($tables['modules'], $fields);
        $dbconn->Execute($query);

        // Manually Insert the Base and Modules module into modules table
        $query = "INSERT INTO " . $tables['modules'] . "
              (name, regid, directory, version,
               class, category, admin_capable, user_capable, state )
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $modInfo = xarMod_getFileInfo('modules');
        if (!isset($modInfo)) return; // throw back
        // Use version, since that's the only info likely to change
        $modVersion = $modInfo['version'];
        $bindvars = array('modules',1,'modules',(string) $modVersion,'Core Admin','Global',true,false,3);
        $dbconn->Execute($query,$bindvars);

        $modInfo = xarMod_getFileInfo('base');
        if (!isset($modInfo)) return; // throw back
        // Use version, since that's the only info likely to change
        $modVersion = $modInfo['version'];

        $bindvars = array('base',68,'base',(string) $modVersion,'Core Admin','Global',true,true,3);
        $dbconn->Execute($query,$bindvars);

        /** Module vars table is created earlier now (base mod, where config_vars table was created */

        /**
         * CREATE TABLE module_itemvars (
         *   module_var_id    integer unsigned NOT NULL,
         *   item_id          integer unsigned NOT NULL,
         *   value            longtext,
         *   PRIMARY KEY      (module_var_id, item_id)
         * )
         */
        $fields = array(
                        'module_var_id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'primary_key' => true),
                        'item_id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'unsigned' => true, 'primary_key' => true),
                        'value' => array('type' => 'text', 'size' => 'long')
                        );

        // Create the module itemvars table
        $query = xarDBCreateTable($tables['module_itemvars'], $fields);
        $dbconn->Execute($query);

        /**
         * CREATE TABLE xar_hooks (
         *   id         integer NOT NULL auto_increment,
         *   object     varchar(64) NOT NULL,
         *   action     varchar(64) NOT NULL,
         *   s_module_id integer unsigned default null,
         *   s_type      varchar(64) NOT NULL,
         *   t_area      varchar(64) NOT NULL,
         *   t_module_id integer unsigned not null,
         *   t_type      varchar(64) NOT NULL,
         *   t_func      varchar(64) NOT NULL,
         *   priority    integer default 0
         *   PRIMARY KEY (id)
         * )
         */
        $fields = array(
                        'id' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'increment' => true, 'primary_key' => true),
                        'object'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'action'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        's_module_id' => array('type' => 'integer', 'unsigned' => true, 'null' => true, 'default' => null),
                        // TODO: switch to integer for itemtype (see also xarMod.php)
                        's_type'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        't_area'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        't_module_id'  => array('type' => 'integer','unsigned' => true, 'null' => false),
                        't_type'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        't_func'      => array('type' => 'varchar', 'size' => 64, 'null' => false),
                        'priority'       => array('type' => 'integer', 'size' => 'tiny', 'unsigned' => true, 'null' => false, 'default' => '0')
                    );
        // TODO: no indexes?

        // Create the hooks table
        $query = xarDBCreateTable($tables['hooks'], $fields);
        $dbconn->Execute($query);

        // <andyv> Add module variables for default user/admin, used in modules list
        /**
         * at this stage of installer mod vars cannot be set, so we use DB calls
         * prolly need to move this closer to installer, not sure yet
         */

        $sql = "INSERT INTO " . $tables['module_vars'] . " (module_id, name, value)
                VALUES (?,?,?)";
        $stmt = $dbconn->prepareStatement($sql);

        $modulesmodid = xarMod::getID('modules');
        $modvars = array(
                         // default show-hide core modules
                         array($modulesmodid,'hidecore','0'),
                         // default regenerate command
                         array($modulesmodid,'regen','0'),
                         // default style of module list
                         array($modulesmodid,'selstyle','plain'),
                         // default filtering based on module states
                         array($modulesmodid,'selfilter', '0'),
                         // default modules list sorting order
                         array($modulesmodid,'selsort','nameasc'),
                         // default show-hide modules statistics
                         array($modulesmodid,'hidestats','0'),
                         // default maximum number of modules listed per page
                         array($modulesmodid,'selmax','all'),
                         // default start page
                         array($modulesmodid,'startpage','overview'),
                         // disable overviews
                         array($modulesmodid,'disableoverview',0),
                         // expertlist
                         array($modulesmodid,'expertlist','0'),
                         // the configuration settings pertaining to modules for the base module
                         array($modulesmodid,'defaultmoduletype','user'),
                         array($modulesmodid,'defaultmodule','base'),
                         array($modulesmodid,'defaultmodulefunction','main'),
                         array($modulesmodid,'defaultdatapath','lib/'));

        foreach($modvars as &$modvar) {
            $stmt->executeUpdate($modvar);
        }
        // We're done, thanks, commit the thingie
        $dbconn->commit();
    } catch (Exception $e) {
        // Damn
        $dbconn->rollback();
        throw $e;
    }

    // Installation complete; check for upgrades
    return modules_upgrade('2.0');}

/**
 * Activates the modules module
 *
 * @param none $
 * @returns bool
 */
function modules_activate()
{
    // make sure we dont miss empty variables (which were not passed thru)
    $selstyle = xarModVars::get('modules', 'hidecore');
    $selstyle = xarModVars::get('modules', 'selstyle');
    $selstyle = xarModVars::get('modules', 'selfilter');
    $selstyle = xarModVars::get('modules', 'selsort');
    if (empty($hidecore)) xarModVars::set('modules', 'hidecore', 0);
    if (empty($selstyle)) xarModVars::set('modules', 'selstyle', 'plain');
    if (empty($selfilter)) xarModVars::set('modules', 'selfilter', XARMOD_STATE_ANY);
    if (empty($selsort)) xarModVars::set('modules', 'selsort', 'nameasc');



    // New in 1.1.x series but not used
    xarModVars::set('modules', 'disableoverview',0);

    return true;
}

/**
 * Upgrade this module from an old version
 *
 * @param oldVersion
 * @returns bool
 */
function modules_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case '2.0':
        case '2.1':
      break;
    }
    return true;
}

/**
 * Delete this module
 *
 * @return bool
 */
function modules_delete()
{
    // this module cannot be removed
    return false;
}

?>

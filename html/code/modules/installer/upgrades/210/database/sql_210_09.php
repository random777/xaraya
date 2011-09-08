<?php
/**
 * Upgrade SQL file
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

sys::import('modules.installer.class.upgrade_step');

class sql_210_09 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Adding SiteManager level masks to the core moduless
                        ");
    }

    public function run()
    {    
        // Define parameters
        $modules = xarDB::getPrefix() . '_modules';
        $privileges = xarDB::getPrefix() . '_privileges';
    
        // Run the query
        $dbconn = xarDB::getConn();
        try {
            $dbconn->begin();
            $data['sql'] = "
            INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
                SELECT 'ManageBase',  m.id, 'All', 'All', 700, 'Site Manager mask for base module',3 FROM $modules m WHERE name = 'base';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
                SELECT 'ManageBlocks',  m.id, 'All', 'All', 700, 'Site Manager mask for blocks module',3 FROM $modules m WHERE name = 'blocks';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
                SELECT 'ManageMail',  m.id, 'All', 'All', 700, 'Site Manager mask for mail module',3 FROM $modules m WHERE name = 'mail';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
                SELECT 'ManageModules',  m.id, 'All', 'All', 700, 'Site Manager mask for modules module',3 FROM $modules m WHERE name = 'modules';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
                SELECT 'ManagePrivileges',  m.id, 'All', 'All', 700, 'Site Manager mask for privileges module',3 FROM $modules m WHERE name = 'privileges';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
                SELECT 'ManageRoles',  m.id, 'All', 'All', 700, 'Site Manager mask for roles module',3 FROM $modules m WHERE name = 'roles';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
                SELECT 'ManageThemes',  m.id, 'All', 'All', 700, 'Site Manager mask for themes module',3 FROM $modules m WHERE name = 'themes';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
                SELECT 'ManageAuthsystem',  m.id, 'All', 'All', 700, 'Site Manager mask for authsystem module',3 FROM $modules m WHERE name = 'authsystem';
            ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
            INSERT INTO $privileges (name,  module_id, component, instance, level, description, itemtype)  
                SELECT 'ManageDynamicData',  m.id, 'All', 'All', 700, 'Site Manager mask for dynamicdata module',3 FROM $modules m WHERE name = 'dynamicdata';
            ";
            $dbconn->Execute($data['sql']);
            $dbconn->commit();
        } catch (Exception $e) {
            // Damn
            $dbconn->rollback();
            $this->fail();
        }
        return $this->success;
    }
}
?>
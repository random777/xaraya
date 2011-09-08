<?php
/**
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

class sql_220_16 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Move the access data from the config to the access field
                        ");
    }

    public function run()
    {    
        // Define parameters
        $table = xarDB::getPrefix() . '_dynamic_objects';
        
        // Run the query
        $dbconn  = xarDB::getConn();
        try {
            $dbconn->begin();
            $objects = array(
                    'objects',
                    'properties',
                    'configurations',
                    'dynamicdata_tablefields',
                    'module_settings',
                    'modules',
                    'roles_user_settings',
                    'themes_user_settings',
                    'privileges_baseprivileges',
                    'privileges_privileges',
                    'roles_user_settings',
                    );
            foreach ($objects as $object) {
                $query = "UPDATE $table SET access = config WHERE `name` = '" . $object . "'";              
                $dbconn->Execute($query);        
                $query = "UPDATE $table SET config = 'a:0:{}' WHERE `name` = '" . $object . "'";              
                $dbconn->Execute($query);        
            }
            
            // Special case of the roles objects
            $query = "UPDATE $table SET access = config WHERE `name` = 'roles_users'";              
            $dbconn->Execute($query);        
            $query = "UPDATE $table SET config = 'a:1:{s:5:\"where\";a:1:{i:0;s:13:\"role_type = 1\";}}' WHERE `name` = 'roles_users'";              
            $dbconn->Execute($query);        
            $query = "UPDATE $table SET access = config WHERE `name` = 'roles_groups'";              
            $dbconn->Execute($query);        
            $query = "UPDATE $table SET config = 'a:1:{s:5:\"where\";a:1:{i:0;s:13:\"role_type = 2\";}}' WHERE `name` = 'roles_groups'";              
            $dbconn->Execute($query);        
    
            $dbconn->commit();
            
        } catch (Exception $e) { throw($e);
            // Damn
            $dbconn->rollback();
            $this->fail();
        }
        return $this->success;
    }
}
?>
<?php
/**
 * Upgrade SQL file
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage installer module
 * @link http://xaraya.com/index.php/release/200.html
 */

sys::import('modules.installer.class.upgrade_step');

class sql_211_02 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Upgrading the default theme module variable
                        ");
    }

    public function run()
    {    
        // Define parameters
        $module_vars = xarDB::getPrefix() . '_module_vars';
        $dd_objects = xarDB::getPrefix() . '_dynamic_objects';
        $dd_properties = xarDB::getPrefix() . '_dynamic_properties';
        $themesid = xarMod::getId('themes');
    
        // Run the query
        $dbconn = xarDB::getConn();
        try {
            $dbconn->begin();
            $data['sql'] = "
                UPDATE $module_vars
                SET `name` = 'default_theme'
                WHERE `name` = 'default' AND `module_id` = $themesid;
                ";
            $dbconn->Execute($data['sql']);
            $data['sql'] = "
                UPDATE $dd_properties p, $dd_objects o
                SET p.name = 'default_theme'
                WHERE o.name = 'themes_user_settings' AND p.object_id = o.id AND p.name = 'default';
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
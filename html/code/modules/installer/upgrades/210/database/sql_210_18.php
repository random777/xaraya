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

class sql_210_18 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Adding the 'confirm'configuration option for the email dataproperty
                        ");
    }

    public function run()
    {    
        // Define parameters
        $dynamic_configurations = xarDB::getPrefix() . '_dynamic_configurations';
    
        // Run the query
        $dbconn = xarDB::getConn();
        try {
            $dbconn->begin();
            $data['sql'] = "
            INSERT INTO `xar_dynamic_configurations` (`name`, `description`, `property_id`, `label`, `ignore_empty`, `configuration`) VALUES
            ('validation_email_confirm', 'Show a second email field to be filled in', 14, 'Confirm Email', 1, 'a:1:{s:14:\"display_layout\";s:7:\"default\";}');
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

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

class sql_210_01 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Upgrading the core module version numbers
                        ");
    }

    public function run()
    {    
        // Define parameters
        $table = xarDB::getPrefix() . '_modules';
    
        $core_modules = array(
                                'authsystem',
                                'base',
                                'blocks',
                                'dynamicdata',
                                'installer',
                                'mail',
                                'modules',
                                'privileges',
                                'roles',
                                'themes',
        );
        // Run the query
        $dbconn = xarDB::getConn();
        try {
            $dbconn->begin();
            foreach ($core_modules as $core_module) {
                $data['sql'] = "
                UPDATE $table SET version = '2.1.0' WHERE `name` = '" . $core_module . "';
                ";
                $dbconn->Execute($data['sql']);
            }
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
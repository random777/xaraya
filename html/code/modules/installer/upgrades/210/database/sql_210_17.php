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

class sql_210_17 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Redefining mask names in the Themes module
                        ");
    }

    public function run()
    {    
        // Define parameters
        $privileges = xarDB::getPrefix() . '_privileges';
    
        // Run the query
        $dbconn = xarDB::getConn();
        try {
            $dbconn->begin();
            $data['sql'] = "
            UPDATE $privileges SET name = 'AdminThemes' WHERE name = 'AdminTheme';
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

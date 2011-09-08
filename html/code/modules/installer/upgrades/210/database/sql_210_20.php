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

class sql_210_20 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Add the version build configuration variable
                        ");
    }

    public function run()
    {    
        // Define parameters
        $module_vars = xarDB::getPrefix() . '_module_vars';
        $roles = xarDB::getPrefix() . '_roles';
    
        // Run the query
        try {
            xarConfigVars::set(null, 'System.Core.VersionRev', xarCore::VERSION_REV);
        } catch (Exception $e) {
            // Damn
            $this->fail();
        }
        return $this->success;
    }
}
?>
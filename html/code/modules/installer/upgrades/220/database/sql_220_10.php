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

class sql_220_10 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Move the debug users to Roles module
                        ");
    }

    public function run()
    {    
        try {
            xarConfigVars::set(null, 'Site.User.DebugAdmins', array('admin'));
            xarModVars::delete('dynamicdata','debugusers');
        } catch (Exception $e) {
            // Damn
            $dbconn->rollback();
            $this->fail();
        }
        return $this->success;
    }
}
?>
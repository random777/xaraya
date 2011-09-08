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

class sql_230_05 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Registering Mod* event subjects and observers
                        ");
    }
    // Run the query
    public function run()
    {        
        // Define parameters
        $module = 'modules';
        
        try {
            $systemArgs = array();
            xarEvents::init($systemArgs);
            // register modules module event subjects
            xarEvents::registerSubject('ModInitialise', 'module', 'modules');
            xarEvents::registerSubject('ModActivate', 'module', 'modules');
            xarEvents::registerSubject('ModDeactivate', 'module', 'modules');
            xarEvents::registerSubject('ModRemove', 'module', 'modules');
    
            // Register modules module event observers
            xarEvents::registerObserver('ModInitialise', 'modules');
            xarEvents::registerObserver('ModActivate', 'modules');
            xarEvents::registerObserver('ModDeactivate', 'modules');
            xarEvents::registerObserver('ModRemove', 'modules');
            
            // Register blocks module event observers 
            xarEvents::registerObserver('ModRemove', 'blocks');
        } catch (Exception $e) {
            // Damn
            $this->fail();
        }
        return $this->success;
    }
}
?>
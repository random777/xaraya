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

class sql_220_04 extends UpgradeStep
{
    public function __construct() {
        parent::__construct();
        $this->task = xarML("
                        Initialising event system and registering subjects and observers
                        ");
    }

    public function run()
    {    
        try {
            // initialise event system
            $systemArgs = array();
            xarEvents::init($systemArgs);        
            // Register base module event subjects
            xarEvents::registerSubject('Event', 'event', 'base');
            xarEvents::registerSubject('ServerRequest', 'server', 'base');
            xarEvents::registerSubject('SessionCreate', 'session', 'base');
            // Register base module event observers
            xarEvents::registerObserver('Event', 'base');
            // Register modules module event subjects
            xarEvents::registerSubject('ModLoad', 'module', 'modules');
            xarEvents::registerSubject('ModApiLoad', 'module', 'modules');
            // Register authsystem event subjects
            xarEvents::registerSubject('UserLogin', 'user', 'authsystem');
            xarEvents::registerSubject('UserLogout', 'user', 'authsystem');
        } catch (Exception $e) {
            // Damn
            $this->fail();
        }
        return $this->success;
    }
}
?>
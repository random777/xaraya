<?php
/**
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * AuthSiteUnlock event subject
 *
 * This event is raised by authsystem_admin_modifylogin when the site lock is disengaged
 *
 * NOTE: observers must supply at least the notify method, the others are optional
 * See authsystem/class/eventobservers/authsiteunlock.php for an example listener
 * which implements all methods accessed by this subjects notify method
**/
// The base AuthSubject class supplies all the methods we need
sys::import('modules.authsystem.class.authsubjects.authsubject');
class AuthsystemAuthSiteUnlockSubject extends AuthsystemAuthSubject implements ixarEventSubject
{
    protected $subject = 'AuthSiteUnlock';

/**
 * AuthSiteUnlock Subject constructor
 * This subject is responsible for attaching its own observers ;)
 * Here we attach all available AuthSiteUnlock observers and their info
**/ 
    public function __construct($args=null)
    {
        parent::__construct($args);
        // get AuthSiteUnlock subject observers
        /**/
        $obs_mods = AuthSystem::getObservers($this);
        foreach ($obs_mods as $obs_mod => $obs) {
            try {
                if (!AuthSystem::fileLoad($obs)) continue;
                $obsmod = xarMod::getName($obs['module_id']);
                $obs['module'] = $obsmod;
                // define class (loadFile already checked it exists)
                $className = ucfirst($obsmod) . $obs['event'] . "Observer";
                $obsclass = new $className();
                // attach observer to subject
                $this->attach($obsclass);
            } catch (Exception $e) {
                continue;
            }            
        }   
                      
    }
}
?>
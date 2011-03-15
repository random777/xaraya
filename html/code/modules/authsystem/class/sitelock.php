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
 * Site Lock ModVar Object
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
sys::import('modules.authsystem.class.authsystem');
sys::import('xaraya.structures.variableobject');
Class AuthsystemSitelock extends xarVariableObject
{
    // required properties, these are never stored
    protected static $instance;
    protected static $variable = 'sitelock';
    protected static $scope    = 'module';
    protected static $module   = 'authsystem';

    const SITE_STATE_OPEN = 0;
    const SITE_STATE_LOGIN = 1;
    // public properties we want to store
    public $locked = false;  // the current state of the site lock
    public $locked_by;       // user id who last locked the site
    public $locked_at;       // time the site was last locked
    public $unlocked_by;     // user id who last unlocked the site
    public $unlocked_at;     // time the site was last unlocked

    public $lockout_state;   // restrict access to login|lockout_page
    public $lockout_msg;     // the message to display on login page when site is locked

    public $lockout_states;

    // SiteLock Actions
    public $locked_notify;   // notify users when site is locked
    public $locked_mail;     // message to be sent to users notified when site is locked
    public $locked_purge;    // purge logged in users when site is locked

    // SiteUnlock Actions
    public $unlocked_notify; // notify users when site is unlocked
    public $unlocked_mail;   // message to be sent to users notified when site is unlocked

    public $access_list;     // user and group ids allowed to login when site is locked
    public $site_admin;      // designated site admin
    public $admin_notify;    // notify admin on lock state change
    
    public $schedule;        // array of times to lock/unlock the site

    public function __construct()
    {
        // set defaults (this method is only ever called once)
        // wakeup
        self::__wakeup();
        // import legacy site lock data from roles
        $lockvars = @unserialize(xarModVars::get('roles','lockdata'));
        if (!empty($lockvars) && is_array($lockvars)) {
            $lockout_msg = !empty($lockvars['message']) ? $lockvars['message'] : '';
            $locked_mail = $unlocked_mail = $lockvars['notifymsg'];
            $locked = $lockvars['locked'];
            $roles = $lockvars['roles'];
            $access_list = array();
            if (!empty($roles)) {
                foreach ($roles as $role) {
                    if ($role['id'] == $this->site_admin) continue;
                    $r = xarRoles::get($role['id']);
                    if (!$r) continue;
                    $access_list[$r->getId()] = !empty($role['notify']);
                }
            }
            xarModVars::delete('roles', 'lockdata');
        }
        $this->lockout_msg = !empty($lockout_msg) ? $lockout_msg : xarML('The site is currently locked, thank you for your patience, please try again later');
        $this->locked_mail = !empty($locked_mail) ? $locked_mail : '';
        $this->unlocked_mail = !empty($unlocked_mail) ? $unlocked_mail : '';
        $this->access_list = !empty($access_list) ? $access_list : array();
        $this->lockout_states = array(
            self::SITE_STATE_OPEN => array('id' => self::SITE_STATE_OPEN, 'name' => xarML('No Restrictions')),
            self::SITE_STATE_LOGIN => array('id' => self::SITE_STATE_LOGIN, 'name' => xarML('Login Page')),
        ); 
    }

    public function lockSite()
    {
        // Security
        if (!xarSecurityCheck('AdminAuthsystem', false)) return;
        // site already locked?
        if ($this->locked) return true;
        $this->locked_at = time();
        $this->locked_by = xarUserGetVar('id');
        $this->locked = true;
        return true;
    }

    public function unlockSite()
    {
        // Security
        if (!xarSecurityCheck('AdminAuthsystem', false)) return;
        // site already unlocked?
        if (!$this->locked) return true;
        $this->unlocked_at = time();
        $this->unlocked_by = xarUserGetVar('id');
        $this->locked = false;
        return true;
    }

/**
 * Check if a user has access when the site is locked
**/
    public function checkAccess($id=null)
    {
        // site not locked, access granted...
        if (!$this->locked) return true;
        // get current user id if it wasn't specified
        if (!isset($id))
            $id = xarUserGetVar('id');
        // user is site admin or last resort admin?
        if ($id == $this->site_admin || $id == AuthSystem::LAST_RESORT) return true;
        // check if user is on the access list
        $access_list = $this->getAccessList();
        if (isset($access_list[$id])) return true;
        return false;
    }

    public function getAccessList()
    {
        $list = array();
        if (!empty($this->access_list)) {
            foreach ($this->access_list as $rid => $notify) {
                $r = xarRoles::get($rid);
                // not a role?
                if (!$r) {
                    unset($this->access_list[$rid]);
                    continue;
                // role is user?
                } elseif ($r->isUser()) {
                    $list[$r->getId()] = array(
                        'id' => $r->getId(),
                        'notify' => $notify ? $r->getEmail() : false,
                    );
                // role is group?
                } elseif ($g = $r->getUsers()) {
                    foreach ($g as $u) {
                        $list[$u->getId()] = array(
                            'id' => $u->getId(),
                            'notify' => $notify ? $u->getEmail() : false,
                        );
                    }
                }
            }
        }
        return $list;        
    }

    public function getInfo()
    {
        return $this->getPublicProperties();
    }

    public function __wakeup()
    {
        $this->site_admin = xarModVars::get('roles', 'admin');
    }

    public function __sleep()
    {
        return parent::__sleep();
    }
}
?>
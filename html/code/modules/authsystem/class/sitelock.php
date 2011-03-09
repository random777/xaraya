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
sys::import('modules.authsystem.class.auth');
sys::import('xaraya.structures.variableobject');
Class SiteLock extends xarVariableObject
{
    // required properties, these are never stored
    protected static $instance;
    protected static $variable = 'sitelock';
    protected static $scope    = 'module';
    protected static $module   = 'authsystem';

    // public properties we want to store
    public $locked = false;  // the current state of the site lock
    public $locked_by;       // user id who last locked the site
    public $locked_at;       // time the site was last locked
    public $unlocked_by;     // user id who last unlocked the site
    public $unlocked_at;     // time the site was last unlocked

    public $login_alias;     // the alias to use for the admin login url
    public $lockout_page;    // the page to display when site is locked
    public $lockout_msg;     // the message to display on login page when site is locked

    public $locked_notify;   // notify users when site is locked
    public $locked_mail;     // message to be sent to users notified when site is locked
    public $unlocked_notify; // notify users when site is unlocked
    public $unlocked_mail;   // message to be sent to users notified when site is unlocked

    public $access_list;     // user and group ids allowed to login when site is locked
    public $site_admin;      // designated site admin
    public $admin_notify;    // notify admin on lock state change

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
        if ($id == $this->site_admin || $id == xarAuth::LAST_RESORT) return true;
        // check if user is on the access list
        if (!empty($this->access_list)) {
            foreach (array_keys($this->access_list) as $rid) {
                $r = xarRoles::get($rid);
                if (!$r) {
                    unset($this->access_list[$rid]);
                    continue;
                }
                // id matches a user on the list
                if ($r->isUser() && $r->getId() == $id) {
                    // access granted
                    return true;
                // check group access
                } elseif (!$r->isUser()) {
                    $g = $r->getUsers();
                    if (!empty($g)) {
                        foreach ($g as $u) {
                            // id matches group member, access granted
                            if ($u->getId() == $id) return true;
                        }
                    }
                }
            }
        }
        return false;
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
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
 * AuthSystem Config ModVar Object
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
sys::import('modules.authsystem.class.authsystem');
sys::import('xaraya.structures.variableobject');
Class AuthsystemConfig extends xarVariableObject
{
    // required properties, these are never stored
    protected static $instance;
    protected static $variable = 'authconfig';
    protected static $scope    = 'module';
    protected static $module   = 'authsystem';
    
    public $login_template    = '';    // alternative user-login-{$template}.xt to use (todo)
    public $login_state       = AuthSystem::STATE_LOGIN_USER; // the current login entry point [(user)|admin]
    public $login_alias       = '';    // optional alias to use when accessing admin login entry point

    public $auth_active    = array(); // array of AuthLogin observer states
    public $auth_order     = array(); // the order in which observer authenticate methods will be called
    public $form_active    = array(); // array of AuthLoginForm observer states  
    public $form_order     = array(); // the order in which observers will be displayed in the login form

    public function __construct()
    {
        self::__wakeup();
    }

/**
 * re-order list items
 * Utility function to re-order items on the lists
 *
 * @author Chris Powis
 * @access public
 * @param  string $mod name of auth mod to move
 * @param  string $dir direction to move [up|down]
 * @param  string $list name of list item belongs to [auth_order|form_order]
 * @throws none
 * @return boolean true on success
**/
    public function reorder($mod, $dir, $list)
    {

        if (empty($list) || !is_string($list)) return;
        $items = $this->$list;
        foreach ($items as $index => $authmod) {
            if ($mod == $authmod) {
                if ($dir == 'up' && isset($items[$index-1])) {
                    $temp = $items[$index-1];
                    $items[$index-1] = $items[$index];
                    $items[$index] = $temp;
                } elseif ($dir == 'down' && isset($items[$index+1])) {
                    $temp = $items[$index+1];
                    $items[$index+1] = $items[$index];
                    $items[$index] = $temp;
                }
                break;
            } else {
                $items[$index] = $authmod;
            }
        }
        $this->$list = $items; 
        return true;
    }
    
    public function getInfo()
    {
        return $this->getPublicProperties();
    }

    public function __wakeup()
    {
        // authsystem authentication is always available
        $this->auth_active['authsystem'] = true;
        $this->form_active['authsystem'] = true;
        if (empty($this->auth_order) || !in_array('authsystem', $this->auth_order))
            $this->auth_order[] = 'authsystem';
        if (empty($this->form_order) || !in_array('authsystem', $this->form_order))
            $this->form_order[] = 'authsystem';            
    }

    public function __sleep()
    {
        return parent::__sleep();
    }
}
?>
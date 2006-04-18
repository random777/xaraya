<?php
/**
 * Displays the dynamic user menu.
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Displays the dynamic user menu.
 * Currently does not work, due to design
 * of menu not in place, and DD not in place. 
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @todo    Finish this function.
 */
function roles_user_account()
{
    if(!xarVarFetch('moduleload','str', $data['moduleload'], '', XARVAR_NOT_REQUIRED)) {return;}
   //let's make sure other modules that refer here get to the login form
    $defaultauthmodule=xarModGetNameFromId(xarModGetVar('roles','defaultauthmodule'));
    if (!isset($defaultauthmodule) || empty($defaultauthmodule)) {
            $authmodule='authsystem';
    } else{
           $authmodule=$defaultauthmodule;
    }
   
    if (!xarUserIsLoggedIn()){

        if (!file_exists('modules/'.$authmodule.'/xaruser/showloginform.php')) {
            $authmodule='authsystem'; // incase the authmodule doesn't provide a login
        }
            xarResponseRedirect(xarModURL($authmodule,'user','showloginform'));
    }
    if (!file_exists('modules/'.$authmodule.'/xaruser/logout.php')) {
        $logoutmodule='authsystem'; // incase the authmodule doesn't provide a login
    }else{
        $logoutmodule=$authmodule;
    }
    $data['uid'] = xarUserGetVar('uid');
    $data['name'] = xarUserGetVar('name');
    $data['logoutmodule']=$logoutmodule;
    if ($data['uid'] == XARUSER_LAST_RESORT) {
        $data['message'] = xarML('You are logged in as the last resort administrator.');
    } else  {
        $data['current'] = xarModURL('roles', 'user', 'display', array('uid' => xarUserGetVar('uid')));

        $output = array();
    $item = array();
    $item['module'] = 'roles';
    $item['itemtype'] = ROLES_USERTYPE;
        $output = xarModCallHooks('item', 'usermenu', '', array('module' => 'roles'));

        if (empty($output)){
            $message = xarML('There are no account options configured.');
        }
        $data['output'] = $output;

        if (empty($message)){
            $data['message'] = '';
        }
    }
    return $data;
}

?>

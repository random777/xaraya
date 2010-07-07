<?php
/**
 * Log user out of system
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * log user out of system
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @return bool true on success of redirect
 */
function authsystem_user_logout()
{
    $redirect=xarServer::getBaseURL();
    if (!xarUserIsLoggedIn())
        xarController::redirect($redirect);

    // Get input parameters
    if (!xarVarFetch('redirecturl','str:1:254',$redirecturl,$redirect,XARVAR_NOT_REQUIRED)) return;

    if (strstr($redirecturl, 'authsystem')) {
        $redirecturl = $redirect;
    }
    // get the authenticating module
    $authmodule = xarSessionGetVar('authenticationModule');
    // let the authenticating module know this user is about to log out
    // @CHECKME: do we want/need to notify *all* auth modules here?
    sys::import('modules.authsystem.class.xarauth');
    $authobj = xarAuth::getAuthObject($authmodule);
    // @CHECKME: do we want to raise an exception here if authenticating module logout fails?
    if ($authobj) {
        if (!$authobj->logout(xarUserGetVar('id')))
            throw new ForbiddenOperationException(array($authmodule, 'logout'),xarML('Problem Logging Out.  Module #(1) Function #(2)'));;
    }
    // Log user out
    if (!xarUserLogOut())
        throw new ForbiddenOperationException(array('authsystem', 'logout'),xarML('Problem Logging Out.  Module #(1) Function #(2)'));

    xarController::redirect($redirecturl);
    return true;
}
?>

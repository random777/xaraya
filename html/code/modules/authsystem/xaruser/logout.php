<?php
/**
 * Log a user out from the system
 *
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
 * log user out of system
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @return boolean true on success of redirect
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
    
    sys::import('modules.authsystem.class.xarauth');
    if (!xarAuth::logout()) {
        $vars = array('authsystem', 'logout');
        $msg = xarML('Problem Logging Out.  Module #(1) Function #(2)');        
        throw new ForbiddenOperationException($vars,$msg);
    }

    xarController::redirect($redirecturl);
    return true;
}
?>

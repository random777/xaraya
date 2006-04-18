<?php
/**
 * Log user out of system
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * log user out of system
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function authsystem_user_logout()
{
    $redirect=xarServerGetBaseURL();

    // Get input parameters
    if (!xarVarFetch('redirecturl','str:1:254',$redirecturl,$redirect,XARVAR_NOT_REQUIRED)) return;

    // Defaults
    if (preg_match('/authsystem/',$redirecturl)) {
        $redirecturl = $redirect;
    }

    // Log user out
    if (!xarUserLogOut()) {
        throw new ForbiddenOperationException(array('authsystem', 'logout'),xarML('Problem Logging Out.  Module #(1) Function #(2)'));
    }
    xarResponseRedirect($redirecturl);
    return true;
}
?>
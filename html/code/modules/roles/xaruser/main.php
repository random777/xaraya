<?php
/**
 * Main entry point for the user interface of this module
 *
 * @package modules
 * @subpackage roles module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * The main user interface function of this module.
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments. The function checks if user is logged in and redirects the user to his/her account, or displays the showloginform page of the current authentication module.
 * @return boolean true after redirection
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
*/
function roles_user_main()
{
    $return_url = xarModURL('roles', 'user', 'account');
    if (xarUserIsLoggedIn())
        xarController::redirect($return_url);    

    xarController::redirect(xarModURL('authsystem', 'user', 'login', 
        array('return_url' => urlencode($return_url))));    
}

?>
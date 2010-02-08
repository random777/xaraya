<?php
/**
 * Default user function
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
 * the main user function
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  Function decides if user is logged in
 * and returns user to correct location.
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Jo Dalle Nogare<jojodee@xaraya.com>
 * @return bool true
 */
function authsystem_user_main()
{
    //no registration here - just redirect to the login form
    xarResponse::redirect(xarModURL('authsystem','user','showloginform'));

    return true;
}

?>

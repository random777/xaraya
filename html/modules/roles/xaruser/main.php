<?php
/**
 * Default user function
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
 * the main user function
 * This function is the default function, and is called whenever the module is
 * initiated without defining arguments.  Function decides if user is logged in
 * and returns user to correct location.
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
*/
function roles_user_main()
{

// Security Check
    // Security Check
    //This is limiting all admin users the chance to get to the menu for the roles.
    /*
    if(xarSecurityCheck('EditRole',0)) {

        if (xarModGetVar('modules', 'disableoverview') == 0){
            return xarTplModule('roles','admin', 'main',array());
        } else {
            xarResponseRedirect(xarModURL('roles', 'admin', 'viewroles'));
        }
    }
    elseif(xarSecurityCheck('ViewRoles',0)) {
    */

    $defaultauthmodule = xarModGetVar('roles','defaultauthmodule');
    $authmodule = xarModGetNameFromID($defaultauthmodule);
    $defaultregmodule = xarModGetVar('roles','defaultregmodule');
    $regmodule = !empty($defaultregmodule) ? xarModGetNameFromID($defaultregmodule) : '';
    if (!file_exists('modules/'.$authmodule.'/xaruser/showloginform.php')) {
            $authmodule='authsystem'; // incase the authmodule doesn't provide a login
    }
    //jojodee -Need to use default authsystem for now. Most authentication modules don't have login forms
    //When we have better guidelines for authmodules this would be  a good option
    //to have their own login forms. Some do now but only as a block which makes it hacky.

	if (xarUserIsLoggedIn()) {
	   xarResponseRedirect(xarModURL('roles',
									 'user',
									 'account'));
	} elseif ($allowregistration != true || empty($regmodule)) {
		xarResponseRedirect(xarModURL($authmodule,
									  'user',
									  'showloginform'));
	} else {
		xarResponseRedirect(xarModURL($regmodule,
									  'user',
									  'register'));
	}
	return true;
}

?>

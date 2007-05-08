<?php
/**
 * Redirect for validating users
 *
 * @package server
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * 
 * @author John Cox
 * @todo jojodee - rethink dependencies between roles, authentication(authsystem) and 
 *                 registration in relation to validation
*/

/**
 *  initialize the Xaraya core
 */
include 'includes/xarCore.php';
xarCoreInit(XARCORE_SYSTEM_ALL);

if (!xarVarFetch('v', 'str:1', $v)) return;
if (!xarVarFetch('u', 'str:1', $u)) return;

$user = xarModAPIFunc('roles', 'user', 'get',
                       array('uid' => $u));

//check no-one is already logged into a xaraya session and log out just in case
if (xarUserIsLoggedIn()) {
    xarUserLogOut();
}
xarResponseRedirect(xarModURL('roles', 'user', 'getvalidation',
                              array('stage'   => 'getvalidate',
                                    'valcode' => $v,
                                    'uname'   => $user['uname'],
                                    'phase'   => 'getvalidate')));

// done
exit;
?>
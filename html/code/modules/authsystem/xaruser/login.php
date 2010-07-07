<?php
/**
 * Log user in to system
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
 * @param   uname users name
 * @param   pass user password
 * @param   rememberme session set to expire
 * @param   redirecturl page to return user if possible
 * @return  true if status is 3
 * @raise   exceptions raised if status is 0, 1, or 2
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function authsystem_user_login($args)
{
    if (!xarVarFetch('uname', 'str:1:64', $uname, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pass', 'str:1:254', $pass, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('rememberme','checkbox',$rememberme,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('redirecturl','str:1:254',$redirecturl, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('authmodule', 'str:1:', $authmodule, null, XARVAR_NOT_REQUIRED)) return;

    extract($args);

    // Authenticate user
    $auth = xarMod::apiFunc('authsystem', 'user', 'authenticate', array(
        'uname' => $uname,
        'pass' => $pass,
        'rememberme' => $rememberme,
        'return_url' => $redirecturl,
        'authmodule' => $authmodule,
    ));

    if (empty($auth['invalid'])) {
        // authenticated user, attempt to log them in
        if (xarUserLogIn($auth['uname'], $auth['pass'], $auth['rememberme'])) {
            // Logged in, set any additional log in details for this user
            // @TODO: handle last login time
            // Let the authenticating module know the user was logged in
            // @CHECKME: do we want/need to notify *all* auth modules here?
            $authmod = $auth['authmodule'];
            $authobj = xarAuth::getAuthObject($authmod);
            if ($authobj)
                $authobj->login(xarUserGetVar('id'));

            // now send them on their way :)
            // @TODO: implement the home page cascade
            // Redirect cascade is as follows, first match takes precedence
            // User specific, falling back to...
            // Group specific, falling back to...
            // All users, falling back to...
            // Redirected URL from input, falling back to...
            // Front Page

            if (empty($return_url) && !empty($redirecturl))
                $return_url = $redirecturl;

            if (empty($return_url) || strpos($return_url, 'authsystem') !== false)
                $return_url = xarServer::getBaseURL();

            xarController::redirect($return_url);
        }
        // If login returns false here, failed to set user info for this session
        // @TODO: error message for failure?
        $auth['invalid'] = array('layout' => 'unknown_error');
    }

    // login failed, return error message
    return xarTplModule('authsystem', 'user', 'errors', $auth['invalid']);

}
?>

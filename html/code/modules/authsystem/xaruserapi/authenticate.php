<?php
function authsystem_userapi_authenticate($args)
{
    extract($args);

    // We purposely do not validate uname and pass here,
    // since the auth module may fetch different params,
    // eg tokens from some external auth service like OAuth
    // Instead, the authenticate method is expected to return a
    // valid Xaraya user name and password for the authenticated user
    if (empty($uname)) $uname = '';
    if (empty($pass)) $pass = '';
    if (empty($rememberme)) $rememberme = 0;
    if (empty($return_url)) $return_url = '';

    // When logging in via authsystem_user_login this function is called twice with the same params
    // Once by the login function itself, and then by xarUserLogIn() function,
    // so we cache the result keyed on a hash of uname and pass to save some processing
    static $auth = array();
    $key = md5(serialize(array('uname' => $uname, 'pass' => $pass)));
    if (isset($auth[$key])) return $auth[$key];

    $invalid = array();

    // Check for cookie capability
    if (!$_COOKIE) {
        $invalid = array('layout' => 'no_cookies');
    }

    // Check for locked out user
    $unlockTime  = (int) xarSession::getVar('authsystem.login.lockedout');
    $lockouttime = xarModVars::get('authsystem','lockouttime')? xarModVars::get('authsystem','lockouttime') : 15;
    $lockouttries = xarModVars::get('authsystem','lockouttries') ? xarModVars::get('authsystem','lockouttries') : 3;
    if ((time() < $unlockTime) && (xarModVars::get('authsystem','uselockout') == true))
        $invalid = array('layout' => 'bad_tries_exceeded', 'lockouttime' => $lockouttime);

    // Invalid here, we're done, throw back
    if (!empty($invalid)) {
        $data['invalid'] = $invalid;
        $auth[$key] = $data;
        return $data;
    }

    // Import xarAuth class
    sys::import('modules.authsystem.class.xarauth');
    // Get authmodules (objects)
    $authmodules = xarAuth::getAuthObjects();

    // Attempt authentication against each auth module
    foreach ($authmodules as $authmod => $authobj) {
        // if an authmodule is specified, skip authenticating against other modules
        if (isset($authmodule) && $authmodule != $authmod) continue;
        // let the auth modules authenticate method do it's thing
        $state = $authobj->authenticate($uname, $pass, $rememberme, $return_url);
        // User was either authenticated or denied access
        if ($state != XARUSER_AUTH_FAILED) break;
    }

    // If auth was succesful these will be a valid Xaraya username and password
    $userName = $authobj->getUname();
    $password = $authobj->getPass();

    // Now determine the state of the authenticated user
    switch ($state) {

        case xarRoles::ROLES_STATE_DELETED:
            // User is deleted by all means.  Return a message that says the same.
            $invalid = array('layout' => 'account_deleted');
        break;

        case xarRoles::ROLES_STATE_INACTIVE:
            // User is inactive.  Return message stating.
            $invalid = array('layout' => 'account_inactive');
        break;

        case xarRoles::ROLES_STATE_NOTVALIDATED:
            // User still must validate
            xarController::redirect(xarModURL('roles', 'user', 'getvalidation'));
        break;

        case xarRoles::ROLES_STATE_ACTIVE:
        case XARUSER_LAST_RESORT:
            // check the site lock, only if not last resort admin
            if ($state != XARUSER_LAST_RESORT) {
                $sitelock = @unserialize(xarModVars::get('authsystem', 'sitelock'));
                if (!empty($sitelock) && is_array($sitelock)) {
                    if ($sitelock['locked']) {
                        $hasaccess = false;
                        foreach ($sitelock['lockaccess'] as $id => $role) {
                            $r = xarRoles::get($id);
                            if ($r->isUser() && $r->getUser() == $userName) {
                                $hasaccess = true;
                                break;
                            } else {
                                $group = $r->getUsers();
                                foreach ($group as $g) {
                                    if ($g->isUser() && $g->getUser() == $userName) {
                                        $hasaccess = true;
                                        break;
                                    }
                                }
                                if ($hasaccess) break;
                            }
                        }
                        if (!$hasaccess) {
                            $invalid = array('layout' => 'site_locked', 'message'  => $sitelock['lockmessage']);
                        }
                    }
                }
            }
        break;

        case XARUSER_AUTH_DENIED:
        case XARUSER_AUTH_FAILED:
        default:
            // fall through
        break;
    }
    // get info from auth object
    $data = $authobj->getInfo();

    // No user id here means auth failed against every auth module
    if (empty($data['id'])) {
        // Check for bad password or username
        if (empty($invalid) && (empty($userName) || empty($password))) {
            $invalid = array('layout' => 'missing_data');
        }
        // see if we're locking users out after consecutive failed attempts
        if ((bool) xarModVars::get('authsystem', 'uselockout')) {
            $attempts = (int) xarSession::getVar('authsystem.login.attempts');
            $attempts++;
            if ($attempts > $lockouttries) {
                $now = time();
                xarSession::setVar('authsystem.login.lockedout', $now + (60 * $lockouttime));
                xarSession::setVar('authsystem.login.attempts', 0);
                $invalid = array('layout' => 'bad_tries_exceeded', 'lockouttime' => $lockouttime);
                // check if we're notifying admin of failed lockouts
                if ((bool) xarModVars::get('authsystem', 'lockoutnotify') == true) {
                    $admin = xarRoles::get(xarModVars::get('roles', 'admin'));
                    $sitename = xarModVars::get('themes','SiteName');
                    $forwarded = xarServer::getVar('HTTP_X_FORWARDED_FOR');
                    $ipaddr = !empty($forwarded) ? preg_replace('/,.*/', '', $forwarded) : xarServer::getVar('REMOTE_ADDR');
                    $subject = $sitename . ' User Locked Out';
                    $message = 'The following visitor was locked out of site ' . $sitename . "\n\n";
                    $message .= 'IP address: ' . $ipaddr . "\n\n";
                    $message .= 'Locked out at: ' . xarLocaleGetFormattedTime('long', $now);
                    $message .= ' on ' . xarLocaleGetFormattedDate('long', $now) . "\n\n";
                    // send the email
                    xarMod::apiFunc('mail','admin','sendmail', array(
                        'info' => $admin->getEmail(),
                        'subject' => $subject,
                        'message' => $message,
                        'from' => $admin->getEmail(),
                    ));
                }
            } else {
                xarSession::setVar('authsystem.login.attempts', $attempts);
                $invalid = array('layout' => 'bad_try', 'attempts' => $attempts);
            }
        }
        if (empty($invalid))
            // auth failed for some unknown reason
            $invalid = array('layout' => 'unknown_error');
    }

    $data['invalid'] = $invalid;
    $auth[$key] = $data;
    return $data;
}
?>
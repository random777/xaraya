<?php
/**
 * Handle the user supplied data for login information
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * log user in to system
 * Description of status
 * Status 0 = deleted user
 * Status 1 = inactive user
 * Status 2 = not validated user
 * Status 3 = actve user
 *
 * @param   uname users name
 * @param   pass user password
 * @param   rememberme session set to expire
 * @param   redirecturl page to return user if possible
 * @return  true if status is 3
 * @throws  exceptions raised if status is 0, 1, or 2
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function authsystem_user_login()
{
    global $xarUser_authenticationModules;

    if (!$_COOKIE) {
        xarErrorFree();
        $msg = xarML('You must enable cookies on your browser to run Xaraya. Check the browser configuration options to make sure cookies are enabled, click on  the "Back" button of the browser and try again.');
        xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
        return;
    }

    /* First, see if this user has been locked, so we dont need to do authentication at all.
     * The system will check to see if the number of configurable lockout tries for this session and configurable time
     * has been exceeded and if so disallow another attempt.
     */
    $unlockTime   = (int) xarSessionGetVar('authsystem.login.lockedout');
    $lockouttime  = xarModGetVar('authsystem','lockouttime')? xarModGetVar('authsystem','lockouttime') : 15;
    $lockouttries = xarModGetVar('authsystem','lockouttries') ? xarModGetVar('authsystem','lockouttries') : 3;

    if ((time() < $unlockTime) && (xarModGetVar('authsystem','uselockout')==true)) {
        $msg = xarML('Your account has been locked for #(1) minutes.', $lockouttime);
        xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
        return;
    }

    // Fetch and validate the values entered into the login form
    // Username
    if (!xarVarFetch('uname','pre:trim:str:1:100',$uname))
    {
        xarErrorFree();
        $msg = xarML('You must provide a username.');
        xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
        return;
    }
    // Password
    if (!xarVarFetch('pass','pre:trim:str:1:100',$pass))
    {
        xarErrorFree();
        $msg = xarML('You must provide a password.');
        xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
        return;
    }

    // Check to see if the user want's their session/login to be 'remembered' - made persistent
    if (!xarVarFetch('rememberme','checkbox',$rememberme,false,XARVAR_NOT_REQUIRED)) return;

    // By default redirect to the base URL on the site
    $redirect=xarServerGetBaseURL();
    if (!xarVarFetch('redirecturl','pre:trim:str:1:254',$redirecturl,$redirect,XARVAR_NOT_REQUIRED)) return;
    // If the redirect URL contains authsystem go to base url
    // CHECKME: <mrb> why is this?
    if (preg_match('/authsystem/',$redirecturl)) {
        $redirecturl = $redirect;
    }

    // Scan authentication modules and set user state appropriately
    $extAuthentication = false;

    foreach($xarUser_authenticationModules as $authModName) {

       switch(strtolower($authModName)) {
       // Ooof, didn't realize we were doing this.  We really need a hook here.
            case 'authldap':

                // The authldap module allows the admin to allow an
                // LDAP user to automatically login to Xaraya without
                // having a Xaraya user account in the roles table.
                // If the user is successfully retrieved from LDAP,
                // then a corresponding entry will be created in the
                // roles table.  So set the user state to allow for
                // login.
                $state =ROLES_STATE_ACTIVE;
                $extAuthentication = true;
                break;

            case 'authimap':
            case 'authsso':

                // The authsso module delegates login authority to
                // web server (trusts the web server to authenticate
                // the user's credentials), just as authldap
                // delegates to an LDAP server. Behavior same as
                // described in authldap case.
                $state = ROLES_STATE_ACTIVE;
                $extAuthentication = true;
                break;

            case 'authsystem':
                //Set a $lastresort flag var
                $lastresort=false;
                // Still need to check if user exists as the user may be
                // set to inactive in the user table
                //Get and check last resort first before going to db table
                $lastresortvalue=array();
                $lastresortvalue=xarModGetVar('privileges','lastresort');
                if (isset($lastresortvalue)) {
                    $secret = @unserialize(xarModGetVar('privileges','lastresort'));
                    if (is_array($secret)) {
                        if ($secret['name'] == MD5($uname) && $secret['password'] == MD5($pass)) {
                            $lastresort=true;
                            $state = ROLES_STATE_ACTIVE;
                            break; //let's go straight to login api
                        }
                    }
                }

                // check for user and grab uid if exists
                $user = xarModAPIFunc('roles','user','get', array('uname' => $uname));

                // Make sure we haven't already found authldap module
                if (empty($user) && ($extAuthentication == false))
                {
                    $msg = xarML('Problem logging in: Invalid username or password.');
                    xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
                    return;
                } elseif (empty($user)) {
                    // Check if user has been deleted.
                    $user = xarModAPIFunc('roles', 'user', 'getdeleteduser',
                                          array('uname' => $uname));
                    if (xarCurrentErrorType() == XAR_USER_EXCEPTION)
                    {
                        //getdeleteduser raised an exception
                        xarErrorFree();
                    }
                }

                if (!empty($user)) {
                    $rolestate = $user['state'];
                    // If external authentication has already been set but
                    // the Xaraya users table has a different state (ie invalid)
                    // then override the external state
                    if (($extAuthentication == true) && ($state != $rolestate)) {
                        $state = $rolestate;
                    } else {
                        // No external authentication, so set state
                        $state = $rolestate;
                    }
                }

                break;
            default:
                // some other auth module is being used.  We're going to assume
                // that xaraya will be the slave to the other system and
                // if the user is successfully retrieved from that auth system,
                // then a corresponding entry will be created in the
                // roles table.  So set the user state to allow for
                // login.
                $state = ROLES_STATE_ACTIVE;
                $extAuthentication = true;
                break;
        }
    }

    switch(strtolower($state)) {

        case ROLES_STATE_DELETED:
            // User is deleted by all means.  Return a message that says the same.
            $msg = xarML('Your account has been terminated by your request or at the administrator\'s discretion.');
            xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
            return;
            break;
        case ROLES_STATE_INACTIVE:
            // User is inactive.  Return message stating.
            $msg = xarML('Your account has been marked as inactive.  Contact the administrator if you have further questions.');
            xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
            return;
            break;
        case ROLES_STATE_NOTVALIDATED:
            //User still must validate
            xarResponseRedirect(xarModURL('roles', 'user', 'getvalidation'));
            break;
        case ROLES_STATE_ACTIVE:
        default:
            // User is active or state to be determined by external authentication
            // TODO: remove this when everybody has moved to 1.0
            // <mrb> Havent we now? If not, this shouldn't be here?

            if(!xarModGetVar('roles', 'lockdata')) {
            //We know the default administrator from roles after 1.0, so get the admin and find their group
            //Assume we have our old pre 1.0 values - valid in majority of cases
            $admingroupuid = 4;
            $admingroupname = 'Administrators';
            /* Grab the default roles admin and find their parent group (post 1.0)
            $defaultadmin = xarModGetVar('roles','admin');
            if (isset($defaultadmin)) and !empty($defaultadmin)) {
                $admindata = xarModAPIFunc('roles','user','getrole',array('uid' => $defaultadmin));
                //get the site admin parent group
                $adminrole = xarUFindRole($admindata['uname']);
                $parentrole = $adminrole->getParents();
                //assume the admin has one parent??
                $admingroupuid = $parentrole[0]->uid;
                $admingroupname = $parentrole[0]->uname;
            }
            */
                $lockdata = array(
                    'roles' => array(
                        array(
                            'uid'    => $admingroupuid,
                            'name'   => $admingroupname,
                            'notify' => true
                        )
                    ),
                    'message'   => '',
                    'locked'    => 0,
                    'notifymsg' => ''
                );
                xarModSetVar('roles', 'lockdata', serialize($lockdata));
            }

            // Check if the site is locked and this user is allowed in
            $lockvars = unserialize(xarModGetVar('roles','lockdata'));
            if ($lockvars['locked'] ==1)
            {
                $rolesarray = array();
                $rolemaker = new xarRoles();
                $roles = $lockvars['roles'];
                for($i=0, $max = count($roles); $i < $max; $i++)
                        $rolesarray[] = $rolemaker->getRole($roles[$i]['uid']);
                $letin = array();
                foreach($rolesarray as $roletoletin)
                {
                    if ($roletoletin->isUser())
                        $letin[] = $roletoletin;
                    else
                        $letin = array_merge($letin,$roletoletin->getUsers());
                }
                $letthru = false;
                foreach ($letin as $roletoletin)
                {
                    if (strtolower($uname) == strtolower($roletoletin->getUser()))
                    {
                        $letthru = true;
                        break;
                    }
                }

                if (!$letthru)
                {
                    xarErrorSet(XAR_SYSTEM_MESSAGE,
                    'SITE_LOCKED',
                     new SystemMessage($lockvars['message']));
                     return;
                }
            }

            // OK, let's try to log this user in, we no longer have enough
            // information to determine this here, so we pass it on to the
            // login API function and let that determine for us if this user/pw
            // combo can be authenticated.
            xarLogMessage("Authsystem: passing authentication to core");
            $res = xarModAPIFunc(
                'authsystem','user','login',
                array('uname' => $uname, 'pass' => $pass, 'rememberme' => $rememberme)
            );
            xarLogMessage("Authsystem: authentication chain delivered: ". var_export($res,true));// jojodee : var_export for >= php4.2

            if ($res === null)
            {
                // Null means error?
                return;
            }
            elseif ($res == false)
            {
                // Problem logging in
                // TODO - work out flow, put in appropriate HTML
                xarLogMessage("Authsystem: auth failed");

                // Cast the result to an int in case VOID is returned
                $attempts = (int) xarSessionGetVar('authsystem.login.attempts');

                if (($attempts >= $lockouttries) && (xarModGetVar('authsystem','uselockout')==true)){
                    // set the time for fifteen minutes from now
                    xarSessionSetVar('authsystem.login.lockedout', time() + (60 * $lockouttime));
                    xarSessionSetVar('authsystem.login.attempts', 0);
                    $msg = xarML('Problem logging in: Invalid username or password.  Your account has been locked for #(1) minutes.', $lockouttime);
                    xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
                    return;
                } else {
                    $newattempts = $attempts + 1;
                    xarSessionSetVar('authsystem.login.attempts', $newattempts);
                    $msg = xarML('Problem logging in: Invalid username or password.  You have tried to log in #(1) times.', $newattempts);
                    xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
                    return;
                }
            }

            //FR for last login - first capture the last login for this user
            $thislastlogin =xarModGetUserVar('roles','userlastlogin');
            if (!empty($thislastlogin)) {
                //move this to a session var for this user
                xarSessionSetVar('roles_thislastlogin',$thislastlogin);
            }
            xarModSetUserVar('roles','userlastlogin',time()); //this is what everyone else will see

            $externalurl = false; //used as a flag for userhome external url
            if (xarModGetVar('roles', 'loginredirect'))
            {
                //only redirect to home page if this option is set
                if (xarModAPIFunc('roles','admin','checkduv',array('name' => 'setuserhome', 'state' => 1)))
                {
                    $truecurrenturl = xarServerGetCurrentURL(array(), false);
                    $role = xarUFindRole($uname);
                    $url = $lastresort ? '[base]' : $role->getHome();
                    if (!isset($url) || empty($url))
                    {
                        //jojodee - we now have primary parent implemented so can use this if activated
                        if (xarModGetVar('roles','setprimaryparent'))
                        {
                            //primary parent is activated
                            //TODO: we should really take this out and do this once somewhere for use in other cases
                            $primaryparent = $role->getPrimaryParent();
                            $primaryparentrole = xarUFindRole($primaryparent);
                            $parenturl = $primaryparentrole->getHome();
                            if (!empty($parenturl))
                                $url= $parenturl;
                       } else {
                            // take the first home url encountered.
                            // TODO: what would be a more logical choice?
                            foreach ($role->getParents() as $parent)
                            {
                                $parenturl = $parent->getHome();
                                if (!empty($parenturl))
                                {
                                    $url = $parenturl;
                                    break;
                                }
                            }
                        }
                    }

                    /* move the half page of code out to a Roles function. No need to repeat everytime it's used */
                    $urldata = xarModAPIFunc(
                        'roles','user','userhome',
                        array('url'=>$url,'truecurrenturl'=>$truecurrenturl)
                    );
                    $data=array();
                    if (!is_array($urldata) || !$urldata)
                    {
                        $externalurl=false;
                        $redirecturl=xarServerGetBaseURL();
                    } else
                    {
                        $externalurl=$urldata['externalurl'];
                        $redirecturl=$urldata['redirecturl'];
                    }
                }
            } //end get homepage redirect data
            if ($externalurl) {
                /* Open in IFrame - works if you need it */
                /* $data['page'] = $redirecturl;
                   $data['title'] = xarML('Home Page');
                   return xarTplModule('roles','user','homedisplay', $data);
                 */
                 xarResponseRedirect($redirecturl);
            } else {
                xarResponseRedirect($redirecturl);
            }
            return true;
            break;
        case ROLES_STATE_PENDING:
            // User is pending activation
            $msg = xarML('Your account has not yet been activated by the site administrator');
            xarErrorSet(XAR_USER_EXCEPTION, 'LOGIN_ERROR', new DefaultUserException($msg));
            return;
            break;
    }
    return true;
}
?>
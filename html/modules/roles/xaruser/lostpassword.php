<?php
/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Sends a new password to the user if they have forgotten theirs.
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_user_lostpassword()
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;

    //If a user is already logged in, no reason to see this.
    //We are going to send them to their account.
    if (xarUserIsLoggedIn()) {
        xarResponseRedirect(xarModURL('roles', 'user', 'account'));
       return true;
    }

    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Lost Password')));

    if (!xarVarFetch('phase','str:1:100',$phase,'request',XARVAR_NOT_REQUIRED)) return;

    switch(strtolower($phase)) {

        case 'request':
        default:
            $authid = xarSecGenAuthKey();
            $data   = xarTplModule('roles','user', 'requestpw',
                             array('authid'     => $authid,
                                   'emaillabel' => xarML('E-Mail New Password')));

            break;

        case 'send':

            if (!xarVarFetch('uname', 'pre:trim:str:1:255', $uname, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('email', 'pre:trim:str:1:255', $email, '', XARVAR_NOT_REQUIRED)) return;

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;

            $invalid = array();
            
            if ((empty($uname)) && (empty($email))) {
                $invalid['getpassword'] = xarML('You must enter either a valid username or email to proceed.');
                //$msg = xarML('You must enter your username or your email to proceed');
                //xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                //return;
            }
            
            //check for invalids
            $countInvalid = count($invalid);

            $userargs = array();           
            //what should take precedence - even a config to force both  uname and email?
            $matchemail = xarModGetVar('roles','matchemailforpw') ? xarModGetVar('roles','matchemailforpw'):false;
            //let's continue check
            if ($countInvalid <= 0) { // we can check databaes now           
                if ($matchemail) {
                    $userargs = array('uname'=>$uname,'email' => $email);   
                    $invalid['getpassword'] =  xarML('That email address and username combination is not valid or registered on this site.');              
                } elseif (!empty($uname) && (empty($email))) {
                    $userargs = array('uname'=>$uname);  
                    $invalid['uname'] =  xarML('That username has an invalid format or is not registered on this site.');                    
                } elseif (!empty($email) && empty($uname)){
                    $userargs= array('email'=>$email);
                    $invalid['email'] =  xarML('That email has an invalid format or is not registered on this site.');                   
                } elseif (!empty($email) && !empty($uname)) { //just use the email
                    $userargs = array('uname'=>$uname,'email' => $email);
                    $invalid['getpassword'] =  xarML('Either the email address or username is not valid or registered on this site. You can try username or email address if you have forgotten the combination.');
                }
              
               // check for user and grab uid if exists
                $user = xarModAPIFunc('roles',  'user', 'get', $userargs);
                if (!empty($user)) { 
                    //we have what we want, so reset all these
                    $invalid =array();
                //$msg = xarML('That email address or username is not registered');
                //xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                //return;
                } 
            }
            
            // Check for invalid content and return to get correct input
            $countInvalid = count($invalid);
            if ($countInvalid > 0) { 
                        $authid = xarSecGenAuthKey();
                        return xarTplModule('roles','user', 'requestpw',
                                 array('authid'     => $authid,
                                       'email'     => $email,
                                       'uname'     => $uname,
                                       'invalid'   => $invalid,
                                       'emaillabel' => xarML('E-Mail New Password')));            
            } 
 
            // We must have found a user if we got here so make new password
            $user['pass'] = xarModAPIFunc('roles', 'user', 'makepass');

            if (empty($user['pass'])) {
                $msg = xarML('Problem generating new password');
                xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                return;
            }

            // We need to tell some hooks that we are coming from the lost password screen
            // and not the update the actual roles screen.  Right now, the keywords vanish
            // into thin air.  Bug 1960 and 3161
            xarVarSetCached('Hooks.all','noupdate',1);

            //Update user password
            // check for user and grab uid if exists
            if (!xarModAPIFunc('roles','admin','update',$user)) {
                $msg = xarML('Problem updating the user information');
                xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
            }
              // Send Reminder Email
            if (!xarModAPIFunc('roles', 'admin','senduseremail',
                          array('uid'      => array($user['uid'] => '1'),
                                                    'mailtype'   => 'reminder',
                                                    'pass'       => $user['pass']))) return;

            // Let user know that they have an email on the way.
            $data = xarTplModule('roles','user','requestpwconfirm');
          break;
    }
    return $data;
}
?>

<?php
/**
 * Main user menu
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/*
 * Main user menu
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_user_usermenu($args)
{

    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;
    extract($args);
    if(!xarVarFetch('phase','notempty', $phase, 'menu', XARVAR_NOT_REQUIRED)) {return;}
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Your Account Preferences')));
    $data = array(); $hooks = array();
    switch(strtolower($phase)) {
        case 'menu':
            $iconbasic = xarTplGetImage('home.gif', 'roles');
            $iconenhanced = xarTplGetImage('home.gif', 'roles');
            $current = xarModURL('roles', 'user', 'account', array('moduleload' => 'roles'));
            $data = xarTplModule('roles','user', 'user_menu_icon', array('iconbasic'    => $iconbasic,
                                                                         'iconenhanced' => $iconenhanced,
                                                                         'current'      => $current));
            break;
        case 'form':
        case 'formbasic':
            $properties = null;
            $withupload = (int) FALSE;
            if (xarModIsAvailable('dynamicdata')) {
                // get the Dynamic Object defined for this module (and itemtype, if relevant)
                $object = xarModAPIFunc('dynamicdata','user','getobject',
                                         array('module' => 'roles'));
                if (isset($object) && !empty($object->objectid)) {
                    // get the Dynamic Properties of this object
                    $properties =& $object->getProperties();
                }

                if (is_array($properties)) {
                    foreach ($properties as $key => $prop) {
                        if (isset($prop->upload) && $prop->upload == TRUE) {
                            $withupload = (int) TRUE;
                        }
                    }
                }
            }
            unset($properties);
            $uname = xarUserGetVar('uname');
            $name = xarUserGetVar('name');
            $uid = xarUserGetVar('uid');
            $email = xarUserGetVar('email');
            $role = xarUFindRole($uname);
            $home = $role->getHome();
            $authid = xarSecGenAuthKey();
            $submitlabel = xarML('Submit');
            $item['module'] = 'roles';
            $upasswordupdate = $role->getPasswordUpdate();
            $item['itemtype'] = ROLES_USERTYPE;

            $hooks = xarModCallHooks('item','modify',$uid,$item);
            if (isset($hooks['dynamicdata'])) {
                unset($hooks['dynamicdata']);
            }

            $data = xarTplModule('roles','user', 'user_menu_form',
                                  array('authid'       => $authid,
                                  'withupload'   => $withupload,
                                  'name'         => $name,
                                  'uname'        => $uname,
                                  'home'         => $home,
                                  'hooks'        => $hooks,
                                  'emailaddress' => $email,
                                  'submitlabel'  => $submitlabel,
                                  'uid'          => $uid,
                                  'upasswordupdate' => $upasswordupdate));
            break;

        case 'formenhanced':
            $name = xarUserGetVar('name');
            $uid = xarUserGetVar('uid');
            $authid = xarSecGenAuthKey();
            $item['module'] = 'roles';
            $hooks = xarModCallHooks('item','modify',$uid,$item);

            $data = xarTplModule('roles','user', 'user_menu_formenhanced', array('authid'   => $authid,
                                                                                 'name'     => $name,
                                                                                 'uid'      => $uid,
                                                                                 'hooks'    => $hooks));
            break;
        case 'updatebasic':
            if(!xarVarFetch('uid',   'isset', $uid,     NULL, XARVAR_DONT_SET)) return;
            if(!xarVarFetch('name',  'isset', $name,    NULL, XARVAR_DONT_SET)) return;
            if(!xarVarFetch('email', 'isset', $email,   NULL, XARVAR_DONT_SET)) return;
            if(!xarVarFetch('home',  'isset', $home,    NULL, XARVAR_DONT_SET)) return;
            if(!xarVarFetch('pass1', 'isset', $pass1,   NULL, XARVAR_DONT_SET)) return;
            if(!xarVarFetch('pass2', 'isset', $pass2,   NULL, XARVAR_DONT_SET)) return;
            $uname = xarUserGetVar('uname');
            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey()) return;
            if (!empty($pass1)){
                $minpasslength = xarModGetVar('roles', 'minpasslength');
                if (strlen($pass2) < $minpasslength) {
                    throw new VariableValidationException(array('password','*password value hidden*','minimum length: '.$minpasslength));
                }
                // Check to make sure passwords match
                if ($pass1 == $pass2){
                    $pass = $pass1;
                    if (xarModGetVar('roles','setpasswordupdate')){
                        $passwordupdate=time();
                    }
                } else {
                    throw new VariableValidationException(array('passwords','*password values hidden*','must be equal'));
                }
                $oldemail = xarUserGetVar('email');
                // The API function is called.
                if(!xarModAPIFunc('roles',
                                  'admin',
                                  'update',
                                   array('uid' => $uid,
                                         'uname' => $uname,
                                         'name' => $name,
                                         'home' => $home,
                                         'email' => $oldemail,
                                         'state' => ROLES_STATE_ACTIVE,
                                         'pass' => $pass,
                                         'passwordupdate' => $passwordupdate))) return;
            }
            if (!empty($email)){
                // Steps for changing email address.
                // 1) Validate the new email address for errors.
                // 2) Log user out.
                // 3) Change user status to 2 (if validation is set as option)
                // 4) Registration process takes over from there.

                // Step 1
                $emailcheck = xarModAPIFunc('roles',
                                            'user',
                                            'validatevar',
                                            array('var' => $email,
                                                  'type' => 'email'));

                if ($emailcheck == false) {
                    throw new VariableValidationException(array('email',$email,'valid address'));
                }

                if(xarModGetVar('roles','uniqueemail')) {
                    // check for duplicate email address
                    $user = xarModAPIFunc('roles',
                                          'user',
                                          'get',
                                           array('email' => $email));
                    if ($user != false) {
                        unset($user);
                        throw new DuplicateException(array('email address',$email));
                    }
                }

                // check for disallowed email addresses
                $disallowedemails = xarModGetVar('roles','disallowedemails');
                if (!empty($disallowedemails)) {
                    $disallowedemails = unserialize($disallowedemails);
                    $disallowedemails = explode("\r\n", $disallowedemails);
                    if (in_array ($email, $disallowedemails)) {
                        $msg = 'That email address is either reserved or not allowed on this website';
                        throw new ForbiddenOperationException(null,$msg);
                    }
                }
                // Step 3
                $requireValidation = xarModGetVar('roles', 'requirevalidation');
                if ((!xarModGetVar('roles', 'requirevalidation')) || (xarUserGetVar('uname') == 'admin')){
                    // The API function is called.
                    if(!xarModAPIFunc('roles',
                                      'admin',
                                      'update',
                                       array('uid' => $uid,
                                             'uname' => $uname,
                                             'name' => $name,
                                             'home' => $home,
                                             'email' => $email,
                                             'state' => ROLES_STATE_ACTIVE))) return;
                } else {

                    // Step 2
                    // Create confirmation code and time registered
                    $confcode = xarModAPIFunc('roles',
                                              'user',
                                              'makepass');

                    // Step 3
                    // Set the user to not validated
                    // The API function is called.
                    if(!xarModAPIFunc('roles',
                                      'admin',
                                      'update',
                                       array('uid'      => $uid,
                                             'uname'    => $uname,
                                             'name'     => $name,
                                             'home'     => $home,
                                             'email'    => $email,
                                             'valcode'  => $confcode,
                                             'state'    => ROLES_STATE_NOTVALIDATED))) return;
                    // Step 4
                    //Send validation email
                    if (!xarModAPIFunc( 'roles', 'admin', 'senduseremail',
                                        array('uid' => array($uid => '1'), 'mailtype' => 'validation'))) {
                        $msg = xarML('Problem sending confirmation email');
                        throw new Exception($msg);
                    }
                    // Step 5
                    // Log the user out. This needs to happen last
                    xarUserLogOut();
                }
            } else {
                $email = xarUserGetVar('email');
                // The API function is called.
                if(!xarModAPIFunc('roles',
                                  'admin',
                                  'update',
                                   array('uid' => $uid,
                                         'uname' => $uname,
                                         'name' => $name,
                                         'home' => $home,
                                         'email' => $email,
                                         'state' => ROLES_STATE_ACTIVE))) return;
            }

            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'account'));
            return true;
        case 'updateenhanced':
            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'account'));
            return true;
    }
    return $data;
}
?>

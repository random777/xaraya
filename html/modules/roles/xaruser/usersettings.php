<?php
/**
 * User settings
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Update user settings
 * @author Chris Powis <crisp@xaraya.com>
 */
function roles_user_usersettings($args)
{
    // Security check
    if (!xarSecurityCheck('ViewRoles')) return;
    extract($args);
    if (!xarVarFetch('tab', 'notempty', $tab, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('uid',        'isset',    $uid,        NULL,  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('returnurl', 'str:1', $returnurl, '', XARVAR_NOT_REQUIRED)) return;


    if (empty($uid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'user id', 'user', 'usersettings', 'roles');
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    $invalid = array();
    $data = array();
    $uname = xarUserGetVar('uname');

    // update basic account details
    if ($tab == 'basic') {
        if (!xarVarFetch('name',       'isset',    $name,       NULL,  XARVAR_DONT_SET)) return;
        if (!xarVarFetch('email',      'isset',    $email,      NULL,  XARVAR_DONT_SET)) return;
        if (!xarVarFetch('home',       'isset',    $home,       NULL,  XARVAR_DONT_SET)) return;
        if (!xarVarFetch('pass1',      'isset',    $pass1,      NULL,  XARVAR_DONT_SET)) return;
        if (!xarVarFetch('pass2',      'isset',    $pass2,      NULL,  XARVAR_DONT_SET)) return;
        if (!xarVarFetch('allowemail', 'checkbox', $allowemail, false, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('utimezone',  'str:1:',   $utimezone,  NULL,  XARVAR_NOT_REQUIRED)) return;

        //set emailing options for the user
        xarModSetUserVar('roles', 'usersendemails', $allowemail, $uid);
        // Confirm authorisation code.
        if (!xarSecConfirmAuthKey()) return;
        $dopasswordupdate=false; //switch

        //adjust the timezone value for saving
        if (xarModGetVar('roles','setusertimezone') && (isset($utimezone))) {
            $timeinfo = xarModAPIFunc('base','user','timezones', array('timezone' => $utimezone));
            list($hours,$minutes) = explode(':',$timeinfo[0]);
            $offset        = (float) $hours + (float) $minutes / 60;
            $timeinfoarray = array('timezone' => $utimezone, 'offset' => $offset);
            $usertimezone  = serialize($timeinfoarray);
            xarModSetUserVar('roles', 'usertimezone', $usertimezone, $uid);
        } else {
            xarModSetUserVar('roles','usertimezone','', $uid);
            $usertimezone = '';
        }

        /* Check if external urls are allowed in home page */
        $allowexternalurl=xarModGetVar('roles','allowexternalurl');
        $url_parts = parse_url($home);
        if (!$allowexternalurl) {
            if ((preg_match("%^http://%", $home, $matches)) &&
                ($url_parts['host'] != $_SERVER["SERVER_NAME"]) &&
                ($url_parts['host'] != $_SERVER["HTTP_HOST"])) {
                    $invalid['home'] = xarML('External URLs such as #(1) are not permitted in your User Account.', $home);
            }
        }

        if (!empty($pass1)){
            $minpasslength = xarModGetVar('roles', 'minpasslength');
            if (strlen($pass2) < $minpasslength) {
                $invalid['pass1']  = xarML('Your password must be #(1) characters long.', $minpasslength);
            }
            // Check to make sure passwords match
            if ($pass1 == $pass2){
                $pass = $pass1;
                if (xarModGetVar('roles','setpasswordupdate')){
                    $dopasswordupdate=true;
                }
            } else {
                $invalid['pass1'] = xarML('The passwords do not match');

            }
            if (empty($invalid)) {
                $oldemail = xarUserGetVar('email');
                // The API function is called.
                if(!xarModAPIFunc('roles', 'admin', 'update',
                                   array('uid'   => $uid,
                                         'uname' => $uname,
                                         'name'  => $name,
                                         'home'  => $home,
                                         'email' => $oldemail,
                                         'state' => ROLES_STATE_ACTIVE,
                                         'pass'  => $pass,
                                         'usertimezone' => $usertimezone,
                                         'dopasswordupdate' => $dopasswordupdate))) return;
            }
        }

        if (!empty($email)){
            /* updated steps for changing email address
               1) Validate the new email address for errors.
               2) Check if validation is required and if so create confirmation code
               3) Change user status to 2 (if validation is set as option)
               4) If validation is required for a change, send the user an email about validation
               5) if user is logged in (ie existing user), log user out
               6) Display appropriate message
            */

            // Step 1
            $emailcheck = xarModAPIFunc('roles','user','validatevar',
                                  array('var'  => $email,
                                        'type' => 'email'));

            if ($emailcheck == false) {
                    $invalid['email'] = xarML('There is an error in the supplied email address');

            }
            if (xarModGetVar('roles','uniqueemail')) {
                // check for duplicate email address
                $user = xarModAPIFunc('roles', 'user','get',
                                array('email' => $email));

                if ($user != false) {
                    unset($user);
                    $invalid['email'] = xarML('That email address is already registered.');
                }
            }

            // check for disallowed email addresses
            $disallowedemails = xarModGetVar('roles','disallowedemails');
            if (!empty($disallowedemails)) {
                $disallowedemails = unserialize($disallowedemails);
                $disallowedemails = explode("\r\n", $disallowedemails);
                if (in_array ($email, $disallowedemails)) {
                    $invalid['email'] = xarML('That email address is either reserved or not allowed on this website');
                }
            }

            // Step 2 Check for validation required or not
            $requireValidation = xarModGetVar('roles', 'requirevalidation');
            if ($requireValidation && empty($invalid) && xarUserGetVar('uname') != 'admin') {
                // Step 2
                // Create confirmation code and time registered
                $confcode = xarModAPIFunc('roles','user','makepass');

                // Step 3
                // Set the user to not validated
                // The API function is called.
                if(!xarModAPIFunc('roles', 'admin', 'update',
                                   array('uid'      => $uid,
                                         'uname'    => $uname,
                                         'name'     => $name,
                                         'home'     => $home,
                                         'email'    => $email,
                                         'usertimezone' => $usertimezone,
                                         'valcode'  => $confcode,
                                         'state'    => ROLES_STATE_NOTVALIDATED))) return;
                // Step 4
                //Send validation email
                if (!xarModAPIFunc( 'roles',  'admin', 'senduseremail',
                              array('uid' => array($uid => '1'), 'mailtype' => 'validation'))) {

                    $msg = xarML('Problem sending confirmation email');
                    xarErrorSet(XAR_USER_EXCEPTION, 'MISSING_DATA', new DefaultUserException($msg));
                }
                // Step 5
                // Log the user out. This needs to happen last
                xarUserLogOut();

                //Step 6
                //Show a nice message for the person about email validation
                $data = xarTplModule('roles', 'user', 'waitingconfirm');
                return $data;
            } elseif ((!$requireValidation || xarUserGetVar('uname') == 'admin') && empty($invalid)) {
                // The API function is called.
                if(!xarModAPIFunc('roles',  'admin', 'update',
                                   array('uid'     => $uid,
                                         'uname'   => $uname,
                                         'name'    => $name,
                                         'home'    => $home,
                                         'email'   => $email,
                                         'usertimezone' => $usertimezone,
                                         'state'   => ROLES_STATE_ACTIVE))) return;
            }
        } elseif (empty($invalid)) {
            $email = xarUserGetVar('email');

            // The API function is called.
            if(!xarModAPIFunc('roles', 'admin', 'update',
                               array('uid'     => $uid,
                                     'uname'   => $uname,
                                     'name'    => $name,
                                     'home'    => $home,
                                     'email'   => $email,
                                     'usertimezone'=> $usertimezone,
                                     'state'   => ROLES_STATE_ACTIVE))) return;
        }

        if (!empty($invalid)) {
            $data['name'] = $name;
            $data['home'] = $home;
            $data['emailaddress'] = xarUserGetVar('email');
            $data['utimezone'] = $usertimezone;
            $data['formaction'] = xarModURL('roles', 'user', 'usersettings');
            $data['authid'] = xarSecGenAuthKey('roles');
            $data['itemtype'] = 0;
            $data['pass1'] = $pass1;
            $data['email'] = $email;
            $item = array();
            $item['module'] = 'roles';
            $hooks            = xarModCallHooks('item','modify',$uid,$item);
            if (isset($hooks['dynamicdata'])) {
                unset($hooks['dynamicdata']);
            }
            $data['hooks'] = !empty($hooks) ? $hooks : '';
        }
    } else {
        if (!xarVarFetch('itemtype', 'id', $itemtype, 0, XARVAR_NOT_REQUIRED)) return;
        // get the Dynamic Object defined for this module (and itemtype, if relevant)
        $mylist = xarModAPIFunc('dynamicdata','user','getobject',
                                 array('moduleid' => xarModGetIdFromName('roles'),
                                       'itemtype' => $itemtype,
                                       'itemid' => $uid,
                                        'tplmodule' => 'roles',
                                        'template' => 'usersettings'));
        if (!isset($mylist)) return;

        // get the values for this item
        $newid = $mylist->getItem();
        if (!isset($newid) || $newid != $uid) return;

        // check the input values for this object
        $isvalid = $mylist->checkInput();

        if ($isvalid) {
            // update the item
            $itemid = $mylist->updateItem();

            if (empty($itemid)) return; // throw back
        } else {
            $data['mylist'] =& $mylist;
            $data['name'] = xarUserGetVar('name');
            $data['formaction'] = xarModURL('roles', 'user', 'usersettings');
            $data['authid'] = xarSecGenAuthKey('roles');
            $data['itemtype'] = 0;
            $invalid = true;
        }

    }

    // account function needs some extra data
    if (!empty($invalid)) {
        $defaultauthdata      = xarModAPIFunc('roles','user','getdefaultauthdata');
        $defaultauthmodname   = $defaultauthdata['defaultauthmodname'];
        $defaultloginmodname  = $defaultauthdata['defaultloginmodname'];
        $defaultlogoutmodname = $defaultauthdata['defaultlogoutmodname'];
        $data['invalid'] = $invalid;
        $data['uid'] = $uid;
        $data['uname'] = $uname;
        $data['tab'] = $tab;
        $data['logoutmodule'] = $defaultlogoutmodname;
        $data['loginmodule']  = $defaultloginmodname;
        $data['authmodule']   = $defaultauthmodname;

        /* build our menu tabs */
        // TODO: move this to getmenulinks()
        $menutabs = array();
        // display members list if allowed
        if (xarModGetVar('roles', 'displayrolelist')){
            $menutabs[] = array('url' => xarModURL('roles', 'user', 'view'),
                                'title' => xarML('Browse members profiles'),
                                'label' => xarML('Memberslist'),
                                'active' => false);
        }
        // we always show the user account tab
        $menutabs[] = array(
            'url' => xarModURL('roles', 'user', 'account'),
            'label' => xarML('Account'),
            'title' => xarML('View and edit your account'),
            'active' => true
        );
        // show logout
        if (!empty($defaultlogoutmodname)) {
            $menutabs[] = array(
                'url' => xarModURL($defaultlogoutmodname, 'user', 'logout'),
                'label' => xarML('Logout'),
                'title' => xarML('Logout'),
                'active' => false
            );
        }
        $data['menutabs'] = $menutabs;

        /* build account option tabs */
        $taboptions=array();
        // always link to profile display
        $taboptions[] = array(
            'url' => xarModURL('roles', 'user', 'account', array('tab' => 'profile')),
            'label' => xarML('Display'),
            'title' => xarML('Display your account profile'),
            'active' => empty($tab) || $tab == 'profile' ? true : false
        );
        // TODO: need admin config option to disable this since we lost the usermenu hook
        // always link to basic account info (for now)
        $taboptions[] = array(
            'url' => xarModURL('roles', 'user', 'account', array('tab' => 'basic')),
            'label' => xarML('Basic Info'),
            'title' => xarML('Edit your account details'),
            'active' => $tab == 'basic' ? true : false
        );
        // get dd objects
        $objects = xarModAPIFunc('dynamicdata', 'user', 'getobjects');
        // need this so we can skip objects belonging to other modules
        $modid = xarModGetIdFromName('roles');
        // we got some objects
        if (!empty($objects)) {
            foreach ($objects as $object) {
                // skip objects belonging to other modules
                if ($object['moduleid'] != $modid) continue;
                $objprops = xarModAPIFunc('dynamicdata', 'user', 'getprop', array('objectid' => $object['objectid']));
                if (empty($objprops)) continue;
                unset($objprops);
                // Itemtype 0 is the Roles DD Object itself
                if ($object['itemtype'] == 0) {
                    $modinfo = xarModGetInfo($modid);
                    $taboptions[] = array(
                        'url' => xarModURL('roles', 'user', 'account', array('tab' => $object['name'])),
                        'label' => $object['label'],
                        'title' => $object['label'],
                        'active' => $tab == $object['name'] ? true : false
                    );
                // all other itemtypes are the moduleid the settings belong to
                } else {
                    $modinfo = xarModGetInfo($object['itemtype']);
                    if (!empty($modinfo) && xarModIsAvailable($modinfo['name'])) {
                        $taboptions[] = array(
                            'url' => xarModURL('roles', 'user', 'account', array('tab' => $object['name'])),
                            'label' => $modinfo['displayname'],
                            'title' => $object['label'],
                            'active' => $tab == $object['name'] ? true : false
                        );
                    }
                }
            }
        }
        $data['taboptions'] = $taboptions;
        return xarTPLModule('roles', 'user', 'account', $data);
    }

    $returnurl = empty($returnurl) ? xarModURL('roles', 'user', 'account', array('tab' => $tab)) : $returnurl;
    return xarResponseRedirect($returnurl);

}
?>
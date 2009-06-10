<?php
/**
 * Displays the dynamic user menu.
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
 * Displays the dynamic user menu.
 *
 * The menu is formed by data from the roles module, hooked Dynamic Data
 * and other hooked modules. Hooked modules should provide a hook called 'usermenu'
 * to show a submenu in this function
 *
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @param string moduleload The current module. This can be a hooked menu for which the menu is activated.
 * @todo    Finish this function.
 */
function roles_user_account()
{

    //let's make sure other modules that refer here get to a default and existing login or logout form
    $defaultauthdata      = xarModAPIFunc('roles','user','getdefaultauthdata');
    $defaultauthmodname   = $defaultauthdata['defaultauthmodname'];
    $defaultloginmodname  = $defaultauthdata['defaultloginmodname'];
    $defaultlogoutmodname = $defaultauthdata['defaultlogoutmodname'];

    if (!xarUserIsLoggedIn()){
        xarResponseRedirect(xarModURL($defaultloginmodname,'user','showloginform'));
    }

    $data['uid']          = xarUserGetVar('uid');
    $data['name']         = xarUserGetVar('name');
    $data['uname']        = xarUserGetVar('uname');
    $data['logoutmodule'] = $defaultlogoutmodname;
    $data['loginmodule']  = $defaultloginmodname;
    $data['authmodule']   = $defaultauthmodname;
    if (xarModGetVar('roles','setuserlastlogin')) {
    //only display it for current user or admin
        if (xarUserIsLoggedIn() && xarUserGetVar('uid')==$data['uid']) { //they should be but ..
            $userlastlogin=xarSessionGetVar('roles_thislastlogin');
            $usercurrentlogin=xarModGetUserVar('roles','userlastlogin',$data['uid']);
        }elseif (xarSecurityCheck('AdminRole',0,'Roles',$data['name']) && xarModGetUserVar('roles','userlastlogin',$data['uid'])){
            $usercurrentlogin='';
            $userlastlogin= xarModGetUserVar('roles','userlastlogin',$data['uid']);
        }else{
            $userlastlogin='';
            $usercurrentlogin='';
        }
    }else{
        $userlastlogin='';
        $usercurrentlogin='';
    }
    $data['userlastlogin'] = $userlastlogin;
    $data['home']       = xarModGetUserVar('roles','userhome');// now user mod var not 'duv'. $role->getHome();

    if ($data['uid'] == XARUSER_LAST_RESORT) {
        $data['message'] = xarML('You are logged in as the last resort administrator.');
    }

    if (!xarVarFetch('tab', 'str:1', $tab, '', XARVAR_NOT_REQUIRED)) return;

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
                    'label' => $object['label'], // tab name can be changed by editing the object label
                    'title' => $object['label'],
                    'active' => $tab == $object['name'] ? true : false
                );
                // if we're displaying or editing user profile we need this object
                if (empty($tab) || $tab == $object['name'] || $tab == 'profile') {
                    $mylist = xarModAPIFunc('dynamicdata', 'user', 'getobject',
                        array(
                            'objectid' => $object['objectid'],
                            'itemid' => $data['uid'],
                            'tplmodule' => 'roles',
                            // showform uses this template, theme authors can over-ride it
                            // and/or provide different layouts by adding layout attribute in template
                            'template' => 'usersettings'
                        ));
                }

           // all other itemtypes are the moduleid the settings belong to
            } else {
                $modinfo = xarModGetInfo($object['itemtype']);
                if (!empty($modinfo) && xarModIsAvailable($modinfo['name'])) {
                    $taboptions[] = array(
                        'url' => xarModURL('roles', 'user', 'account', array('tab' => $object['name'])),
                        'label' => $object['label'], // tab name can be changed by editing the object label
                        'title' => $object['label'],
                        'active' => $tab == $object['name'] ? true : false
                    );
                    // get the object for the current tab
                    if ($tab == $object['name']) {
                        $mylist = xarModAPIFunc('dynamicdata', 'user', 'getobject',
                            array(
                                'objectid' => $object['objectid'],
                                'moduleid' => $object['moduleid'],
                                'itemtype' => $object['itemtype'],
                                'itemid'   => $data['uid'],
                                'tplmodule' => $modinfo['name'],
                                'template' => 'usersettings'
                            ));
                    }
                }
            }
            // get the values for this object
            if (!empty($mylist)) {
                $newid = $mylist->getItem();
                if (!isset($newid) || $newid != $data['uid']) return;
            }
            // if we're editing this object, we need some more info for the form
            if ($tab == $object['name']) {
                $data['itemtype'] = $object['itemtype'];
                $data['modid'] = $modid;
                $data['itemid'] = $data['uid'];
                // check if this module has a usersettings function
                // this allows module authors to perform extra operations
                // when the form is submitted (eg, extra validation)
                if (file_exists('modules/'.$modinfo['name'].'/xaruser/usersettings.php')) {
                    $data['formaction'] = xarModURL($modinfo['name'], 'user', 'usersettings');
                    $data['authid'] = xarSecGenAuthKey($modinfo['name']);
                // default uses roles module usersettings function
                } else {
                    $data['formaction'] = xarModURL('roles', 'user', 'usersettings');
                    $data['authid'] = xarSecGenAuthKey('roles');
                }
                // get any extrainfo for this modules usersettings
                $usersettings = xarModAPIFunc($modinfo['name'], 'user', 'usersettings', array(), false);
            }
        }
    }

    // basic tab means we're editing the default account info
    if ($tab == 'basic') {
        $data['emailaddress']      = xarUserGetVar('email');
        $role       = xarUFindRole($data['uname']);
        $data['allowemail'] = xarModGetUserVar('roles','usersendemails',$data['uid']); //allow someone to send an email to the user via a form


        $data['usercurrentlogin'] = $usercurrentlogin;
        $item['module']   = 'roles';
        $data['upasswordupdate']  = xarModGetUserVar('roles','passwordupdate');//now user mod var not 'duv'. $role->getPasswordUpdate();
        $usertimezonedata = unserialize(xarModGetUserVar('roles','usertimezone'));
        $data['utimezone']        = $usertimezonedata['timezone'];
        $hooks            = xarModCallHooks('item','modify',$data['uid'],$item);
        if (isset($hooks['dynamicdata'])) {
            unset($hooks['dynamicdata']);
        }
        $data['itemtype'] = 0;
        $data['formaction'] = xarModURL('roles', 'user', 'usersettings');
        $data['authid'] = xarSecGenAuthKey('roles');
        $data['pass1'] = '';
        $data['email'] = '';
    } elseif (empty($tab) || $tab == 'profile') {
        //timezone
        if (xarModGetVar('roles','setusertimezone')) {
            $usertimezone      =  unserialize(xarModGetUserVar('roles','usertimezone'));
            $data['utimezone'] = $usertimezone['timezone'];
            $offset            = $usertimezone['offset'];
            //make it pretty
            if (isset($offset)) {
                $hours = intval($offset);
                if ($hours != $offset) {
                    $minutes = abs($offset - $hours) * 60;
                } else {
                    $minutes = 0;
                }
                if ($hours > 0) {
                    $data['offset'] = sprintf("%+d:%02d",$hours,$minutes);
                } else {
                  $data['offset'] = sprintf("%+d:%02d",$hours,$minutes);
                }
            }
        } else {
            $data['utimezone'] = '';
            $data['offset']    = '';
        }
        $item['module']   = 'roles';
        $hooks = xarModCallHooks('item', 'display', $data['uid'], $item);
    }
    $data['hooks'] = !empty($hooks) ? $hooks : '';

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
    // check for uploads
    $withupload = false;
    if (!empty($tab) && !empty($mylist) && $tab == $mylist->name) {
        $properties =& $mylist->getProperties();
        if (is_array($properties)) {
            foreach ($properties as $key => $prop) {
                if (isset($prop->upload) && $prop->upload == TRUE) {
                    $withupload = (int) TRUE;
                    break;
                }
            }
        }
        unset($properties);
    }
    $data['withupload'] = $withupload;
    $data['usersettings'] = !empty($usersettings) ? $usersettings : '';
    $data['mylist'] = !empty($mylist) ? $mylist : '';
    $data['menutabs'] = $menutabs;
    $data['taboptions'] = $taboptions;
    $data['tab'] = $tab;

    xarTPLSetPageTitle(xarVarPrepForDisplay($data['name']));

    return $data;
}

?>
<?php
/**
 * display user
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
 * display user
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_user_display($args)
{
    extract($args);

    if (!xarVarFetch('uid','int:1:',$uid, xarUserGetVar('uid'))) return;

    if (xarUserIsLoggedIn() && $uid == xarUserGetVar('uid')) {
        return xarResponseRedirect(xarModURL('roles', 'user', 'account'));
    }

    // Get user information
    $data = xarModAPIFunc('roles', 'user', 'get',
                          array('uid' => $uid));

    if ($data == false) return;

    $data['email'] = xarVarPrepForDisplay($data['email']);
    //let's make sure other modules that refer here get to a default and existing login or logout form
    $defaultauthdata      = xarModAPIFunc('roles','user','getdefaultauthdata');
    $defaultauthmodname   = $defaultauthdata['defaultauthmodname'];
    $defaultloginmodname  = $defaultauthdata['defaultloginmodname'];
    $defaultlogoutmodname = $defaultauthdata['defaultlogoutmodname'];
    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = 0; // handle groups differently someday ?
    $item['returnurl'] = xarModURL('roles', 'user', 'display',
                                   array('uid' => $uid));

    //Setup user home url if Userhome is activated and user can set the URL
    //So it can display in link of User account page
    $externalurl=false; //used as a flag for userhome external url
    if (xarModAPIFunc('roles','admin','checkduv',array('name' => 'setuserhome', 'state' => 1))) {

        $role = xarUFindRole(xarUserGetVar('uname',$uid));
        $url  = $role->getHome(); //what about last resort here?
        if (!isset($url) || empty($url)) {
            //we now have primary parent implemented so can use this if activated
            if (xarModGetVar('roles','setprimaryparent')) { //primary parent is activated
                $primaryparent = $role->getPrimaryParent();
                if (!empty($primaryparent)) {
                    $primaryparentrole = xarUFindRole($primaryparent);
                    $parenturl = $primaryparentrole->getHome();
                    if (!empty($parenturl)) $url= $parenturl;
                }
            } else {
                // take the first home url encountered - other viable option atm?
                foreach ($role->getParents() as $parent) {
                    $parenturl = $parent->getHome();
                    if (!empty($parenturl))  {
                        $url = $parenturl;
                        break;
                    }
                }
            }
        }
        //We have a home url - let us see if it is a shortcut, or internal, or external URL
        $homeurldata =xarModAPIFunc('roles','user','userhome',array('url'=>$url,'truecurrenturl'=>$item['returnurl']));
        if (!is_array($homeurldata) || !$homeurldata) {
            $externalurl = false;
            $homeurl = xarServerGetBaseURL(array(),false);
        } else{
           $externalurl = $homeurldata['externalurl'];
           $homeurl     = $homeurldata['redirecturl'];
        }

        $data['externalurl'] = $externalurl;
        $data['homelink']    = $homeurl;
    } else {
        $data['externalurl'] = false;
        $data['homelink']    = '';
    }
    if (xarModGetVar('roles','setuserlastlogin')) {
        //only display it for current user or admin
        if (xarUserIsLoggedIn() && xarUserGetVar('uid')==$uid) {
            $data['userlastlogin']    = xarSessionGetVar('roles_thislastlogin');
            $data['usercurrentlogin'] = xarModGetUserVar('roles','userlastlogin',$uid);
        }elseif (xarSecurityCheck('AdminRole',0)){
            $data['usercurrentlogin'] = '';
            $data['userlastlogin']    = xarModGetUserVar('roles','userlastlogin',$uid);
        }else{
            $data['userlastlogin']    = '';
            $data['usercurrentlogin'] = '';
        }
    }else{
        $data['userlastlogin']    = '';
        $data['usercurrentlogin'] = '';
    }
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

    $hooks = array();
    $hooks = xarModCallHooks('item', 'display', $uid, $item);
    $data['hooks'] = $hooks;

    xarTplSetPageTitle(xarVarPrepForDisplay($data['name']));
    if (xarModIsHooked('dynamicdata', 'roles')) {
        $mylist = xarModAPIFunc('dynamicdata', 'user', 'getobject',
            array(
                'moduleid' => xarModGetIDFromName('roles'),
                'itemid' => $uid,
                'tplmodule' => 'roles'
        ));
    }
    // get the values for this object
    if (!empty($mylist)) {
        $newid = $mylist->getItem();
        if (!isset($newid) || $newid != $uid) return;
    }
    $data['mylist'] = !empty($mylist) ? $mylist : '';

    /* build our menu tabs */
    // TODO: move this to getmenulinks()
    $menutabs = array();
    // display members list if allowed
    if (xarModGetVar('roles', 'displayrolelist')){
        $menutabs[] = array('url' => xarModURL('roles', 'user', 'view'),
                            'title' => xarML('Browse members profiles'),
                            'label' => xarML('Memberslist'),
                            'active' => true);
    }
    // we always show the user account tab
    $menutabs[] = array(
        'url' => xarModURL('roles', 'user', 'account'),
        'label' => xarML('Account'),
        'title' => xarML('View and edit your account'),
        'active' => false
    );
    // show logout
    if (!empty($defaultlogoutmodname) && xarUserIsLoggedIn()) {
        $menutabs[] = array(
            'url' => xarModURL($defaultlogoutmodname, 'user', 'logout'),
            'label' => xarML('Logout'),
            'title' => xarML('Logout'),
            'active' => false
        );
    }
    $data['menutabs'] = $menutabs;

    return $data;
}

?>

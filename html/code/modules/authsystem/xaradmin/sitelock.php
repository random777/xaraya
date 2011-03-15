<?php
/**
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * Modify Site Lock Configuration
**/
sys::import('modules.authsystem.class.authsystem');
function authsystem_admin_sitelock(Array $args=array())
{
    // Security
    if (!xarSecurityCheck('AdminAuthsystem')) return;
    extract($args);

    if (!xarVarFetch('tab', 'pre:trim:lower:str:1:',
        $tab, null, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'pre:trim:lower:enum:update', 
        $phase, 'form', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'pre:trim:str:1:',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;    

    // get the site lock object
    $sitelock = Authsystem::$sitelock;
    
    $data = array();
    $invalid = array();

    // deal with any updates first
    if ($phase == 'update') {

        if (!xarSecConfirmAuthKey())
            return xarTplModule('privileges', 'user', 'errors', array('layout' => 'bad_author'));

        // check for lock toggle...        
        if (!xarVarFetch('locktoggle', 'checkbox',
            $locktoggle, false, XARVAR_NOT_REQUIRED)) return;
        // toggle lock
        if ($locktoggle) {
            // determine current lock state 
            // NOTE: we don't notify observers here, since we still have updates to process 
            if ($sitelock->locked) {
                // unlock the site
                $result = $sitelock->unlockSite();
            } else {
                // lock the site
                $result = $sitelock->lockSite();
            }
            if (!$result) 
                // set invalid message
                $invalid['locktoggle'] = xarML('Unable to #(1) the site',$sitelock->locked ? 'unlock':'lock');
        }        

        // get the auth sitelock event subject 
        $locksubject = AuthSystem::getAuthSubject('AuthSiteLock');
        // get the auth siteunlock event subject 
        $unlocksubject = AuthSystem::getAuthSubject('AuthSiteUnlock');
        
        // now deal with the config 
        switch ($tab) {
            
            case 'lock':
            default:

                // check config for all sitelock event observers...
                $isvalid = $locksubject->checkconfig();
                if (!xarVarFetch('lockout_state', 'int:0:3',
                    $lockout_state, 0, XARVAR_NOT_REQUIRED)) return;
                if ($isvalid && empty($invalid)) {
                    // fetch sitelock input
                    if (!xarVarFetch('lockout_msg', 'pre:trim:str:1:',
                        $lockout_msg, '', XARVAR_NOT_REQUIRED)) return;
                    
                    // fetch took care of validation, just update the sitelock
                    $sitelock->lockout_state = $lockout_state;
                    $sitelock->lockout_msg = $lockout_msg;
                    
                    // update config for all sitelock event observers 
                    if (!$locksubject->updateconfig())
                        // observer(s) failed to update...
                        $invalid['sitelock'] = xarML('Failure updating one or more AuthSiteLock observers');
                } else {
                    // observer(s) failed validation...
                    $invalid['sitelock'] = xarML('Failure validating one or more AuthSiteLock observers');
                }
            
            break;
            
            case 'unlock':


                // check config for all siteunlock event observers...
                $isvalid = $unlocksubject->checkconfig();
                if ($isvalid) {
                    // update config for all siteunlock event observers
                    if (!$unlocksubject->updateconfig())
                        // observer(s) failed to update...
                        $invalid['sitelock'] = xarML('Failure updating one or more AuthSiteUnlock observers');
                } else {
                    // observer(s) failed validation...
                    $invalid['sitelock'] = xarML('Failure validating one or more AuthSiteUnlock observers');
                }
            
            break;
            
            case 'access':
                if (!xarVarFetch('admin_notify', 'checkbox',
                    $admin_notify, false, XARVAR_NOT_REQUIRED)) return;
                $sitelock->admin_notify = $admin_notify;
                
                if (!xarVarFetch('access', 'array',
                    $access, array(), XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('addrole', 'pre:trim:str:1:',
                    $addrole, '', XARVAR_NOT_REQUIRED)) return;
                
                // synch the access list
                if (!empty($access)) {
                    foreach ($access as $rid => $role) {
                        if (!isset($sitelock->access_list[$rid])) continue;
                        if (!empty($role['remove'])) {
                            unset($sitelock->access_list[$rid]);
                            continue;
                        }
                        $sitelock->access_list[$rid] = !empty($role['notify']);
                    }
                }
                
                // add role to list
                if (!empty($addrole)) {
                    // try for uname
                    $r = xaruFindRole($addrole);
                    // fall back to name
                    if (!$r) $r = xarFindRole($addrole);
                    if ($r) {
                        if (!xarVarFetch('addnotify', 'checkbox',
                            $addnotify, false, XARVAR_NOT_REQUIRED)) return;
                        $newid = $r->getID();
                        $sitelock->access_list[$newid] = $addnotify;
                    } else {
                        $invalid['addrole'] = xarML('Unable to add role "#(1)" to permitted users and groups, role does not exist', $addrole);
                    }
                }                     
            
            break;

        }
        
        // if the update was a success
        if (empty($invalid)) {
            // see if lock state changed
            if ($locktoggle) {
                if ($sitelock->locked) {
                    // let observers know site was locked
                    $locksubject->notify();
                    // enable the serverrequest observer so we can handle display
                    xarEvents::registerObserver('ServerRequest', 'authsystem');
                } else {
                    // let observers know the site was unlocked 
                    $unlocksubject->notify();
                    // disable the serverrequest observer 
                    xarEvents::unregisterObserver('ServerRequest', 'authsystem');
                }
            }
            // redirect to form 
            if (empty($return_url)) 
                $return_url = xarModURL('authsystem', 'admin', 'sitelock', array('tab' => $tab));
            xarController::redirect($return_url);
        }
        // otherwise, fall through and render the errors
    }
    
    // if we're here, either an update failed, or we're in form phase
    switch ($tab) {

        case 'lock':
        default:
        
            // get the auth sitelock event subject
            $locksubject = AuthSystem::getAuthSubject('AuthSiteLock');
            // get sitelock listener configs 
            $data['lockconfig'] = $locksubject->modifyconfig();
        break;
            
        case 'unlock':

            // get the auth siteunlock event subject
            $unlocksubject = AuthSystem::getAuthSubject('AuthSiteUnlock');
            // get siteunlock listener configs 
            $data['unlockconfig'] = $unlocksubject->modifyconfig();
            
        break;
            
        case 'access':

            // get the designated site admin
            if (!isset($admin)) 
                $admin = xarRoles::get($sitelock->site_admin);
            $data['admin'] = array(
                'id' => $admin->getId(),
                'name' => $admin->getName(),
                'uname' => $admin->getUser(),
                'notify' => $sitelock->admin_notify,
            );

            // build access list for display 
            $access_list = array();
            if (!empty($sitelock->access_list)) {
                foreach ($sitelock->access_list as $rid => $notify) {
                    $r = xarRoles::get($rid);
                    // check role exists, could have been deleted
                    // @todo: check state
                    if (!$r) {
                        // role removed, remove from list 
                        unset($sitelock->access_list[$rid]);
                        continue;
                    }
                    $access_list[$rid] = array(
                        'id' => $r->getId(),
                        'name' => $r->getName(),
                        'uname' => $r->getUser(),
                        'type' => $r->isUser() ? xarRoles::ROLES_USERTYPE : xarRoles::ROLES_GROUPTYPE,
                        'notify' => $notify,
                    );
                }
            }
            $data['access_list'] = $access_list;
            
            $data['roletypes'] = array(
                xarRoles::ROLES_USERTYPE => xarML('User'),
                xarRoles::ROLES_GROUPTYPE => xarML('Group'),
            );

        break;
    
    }
    
    $data['sitelock'] = $sitelock->getInfo();
    $data['tab'] = $tab;
    $data['invalid'] = $invalid;
    $data['return_url'] = $return_url;
    
    return $data;
}
?>
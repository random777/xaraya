<?php
function authsystem_admin_sitelock($args)
{
    // @CHECKME: only allow access to this page to roles in the access list?
    if (!xarSecurityCheck('AdminAuthsystem')) return;

    if (!xarVarFetch('phase', 'pre:trim:lower:enum:update', $phase, 'form', XARVAR_NOT_REQUIRED)) return;

    $data = unserialize(xarModVars::get('authsystem', 'sitelock'));

    // get designated site admin
    $admin = xarRoles::get(xarModVars::get('roles','admin'));

    switch ($phase) {

        case 'form':
        default:
            // prep stored settings for display
            if (empty($data['lockaccess'])) $data['lockaccess'] = array();
            // prep access list for display
            foreach ($data['lockaccess'] as $id => $role) {
                $r = xarRoles::get($id);
                if (!$r) {
                    unset($data['lockaccess'][$id]);
                    continue;
                }
                $role['uname'] = $r->getUser();
                $role['name'] = $r->getName();
                $role['itemtype'] = $r->isUser() ? xarRoles::ROLES_USERTYPE : xarRoles::ROLES_GROUPTYPE;
                $data['lockaccess'][$id] = $role;
            }
            // data for template
            // @TODO: remove these defaults and set in modvar during init/install
            if (empty($data['locknotify'])) $data['locknotify'] = '';
            if (empty($data['adminnotify'])) $data['adminnotify'] = 0;
            if (empty($data['lockmessage'])) $data['lockmessage'] = xarML('The site is currently locked, thank you for your patience.');
            // designated site admin info
            $data['adminid'] = $admin->getID();
            $data['adminuname'] = $admin->getUser();
            $data['adminname'] = $admin->getName();

            if (empty($data['locked'])) {
                $data['lockstatus'] = xarML('Site is unlocked');
                $data['locklabel'] = xarML('Lock the site');
            } else {
                $data['lockstatus'] = xarML('Site is locked');
                $data['locklabel'] = xarML('Unlock the site');
            }
            $data['authid'] = xarSecGenAuthKey();
            $data['statusmsg'] = xarSession::getVar('authsystem_status');
            xarSession::setVar('authsystem_status', '');
            return $data;
        break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }
            // prep input for storage
            // site lock
            if (!xarVarFetch('locksite', 'checkbox', $locksite, 0, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('lockstate', 'int:0:2', $lockstate, 0, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('lockmessage', 'str:1:', $lockmessage, '', XARVAR_NOT_REQUIRED)) return;
            // locked access
            if (!xarVarFetch('lockaccess', 'array', $lockaccess, array(), XARVAR_NOT_REQUIRED)) return;
            // access for new role
            if (!xarVarFetch('newaccess', 'str:1:', $newaccess, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('newnotify', 'checkbox', $newnotify, 0, XARVAR_NOT_REQUIRED)) return;
            // message to send to notified roles
            if (!xarVarFetch('locknotify', 'str:1:', $locknotify, '', XARVAR_NOT_REQUIRED)) return;
            // notify designated site admin
            if (!xarVarFetch('adminnotify', 'checkbox', $adminnotify, 0, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('return_url', 'str:1:', $return_url, '', XARVAR_NOT_REQUIRED)) return;

            $validmsg = $warningmsg = $errormsg = array();
            // update settings
            $data['adminnotify'] = $adminnotify;
            $data['lockstate'] = $lockstate;
            $data['lockmessage'] = $lockmessage;
            // update lock state change email notification message
            $data['locknotify'] = $locknotify;

            // Handle lock status change
            if (!empty($locksite)) {
                // Toggle the site lock
                $data['locked'] = !empty($data['locked']) ? 0 : 1;
                // build notification message
                $subject = xarModVars::get('themes','SiteName') . ' Changed Lock Status';
                $from = $admin->getEmail();
                $byname = xarUserGetVar('name');
                if (!empty($data['locked'])) {
                    // site was just locked
                    $message = 'The site ' . xarModVars::get('themes','SiteName') . ' has been locked by '.$byname.'.';
                    // clear sessions of users not in the access list
                    $spared = array_keys($data['lockaccess']);
                    if(!xarMod::apiFunc('roles','admin','clearsessions', $spared)) {
                        $errormsg[] = xarML('Could not clear sessions table when locking site');
                    }
                } else {
                    // site was just unlocked
                    $message = 'The site ' . xarModVars::get('themes','SiteName') . ' has been unlocked by '.$byname.'.';
                }
                if (!empty($data['locknotify'])) {
                    $message .= "\n\n" . $data['locknotify'];
                }
                $notify = array();
                // notify designated site admin
                if (!empty($data['adminnotify'])) {
                    $notify[$admin->getID()] = $admin;
                }
            }

            // handle roles access list
            foreach ($data['lockaccess'] as $id => $role) {
                if (isset($lockaccess[$id]) && !empty($lockaccess[$id]['delete'])) {
                    unset($data['lockaccess'][$id]);
                    continue;
                }
                $r = xarRoles::get($id);
                if (!$r) {
                    unset($data['lockaccess'][$id]);
                    continue;
                }
                $role['notify'] = isset($lockaccess[$id]) && !empty($lockaccess[$id]['notify']);
                // if the lock status changed add users to notify list
                if (!empty($locksite) && !empty($role['notify'])) {
                    if ($r->isUser()) {
                        // key by role id
                        $notify[$id] = $r;
                    } else {
                        $group = $r->getUsers();
                        if (!empty($group)) {
                            foreach ($group as $member) {
                                // key by role id
                                $rid = $member->getID();
                                $notify[$rid] = $member;
                            }
                        }
                    }
                }
                $data['lockaccess'][$id] = $role;
            }
            // add role to list
            if (!empty($newaccess)) {
                $r = xaruFindRole($newaccess);
                if (!$r) $r = xarFindRole($newaccess);
                if ($r) {
                    $newid = $r->getID();
                    $data['lockaccess'][$newid] = array(
                        'id' => $newid,
                        'uname' => $r->getUser(),
                        'name' => $r->getName(),
                        'itemtype' => $r->isUser() ? xarRoles::ROLES_USERTYPE : xarRoles::ROLES_GROUPTYPE,
                        'notify' => !empty($newnotify),
                    );
                    // if the status changed add user to notify list
                    if (!empty($locksite) && !empty($data['lockaccess'][$newid]['notify'])) {
                        $notify[$newid] = $r;
                    }
                } else {
                    $errormsg[] = xarML('Unable to add role #(1) to permitted users and groups, role does not exist', $newaccess);
                }
            }

            // store site lock settings
            xarModVars::set('authsystem', 'sitelock', serialize($data));

            // notify users on state change
            if (!empty($notify) && is_array($notify)) {
                $badmails = array();
                foreach ($notify as $id => $role) {
                    // @CHECKME: do we want to skip sending email to current user?
                    //if (!$role->isUser() || $id == xarUserGetVar('id')) continue;
                    if (!$role->isUser()) continue;
                    $email = array(
                        'info' => $role->getEmail(),
                        'subject' => $subject,
                        'message' => $message,
                        'from' => $from,
                    );
                    if (!xarMod::apiFunc('mail','admin','sendmail', $email)) {
                        $badmails[] = $role->getName();
                    }
                }

                if(!empty($badmails)) {
                    // return xarTplModule('roles','user','errors',array('layout' => 'mail_failed', 'badmails' => count($badmails)));
                    $grammar = count($badmails == 1) ? 'role' : 'roles';
                    $grammar1 = $data['locked'] ? 'locking' : 'unlocking';
                    $errormsg[] = xarML('Unable to send email to the following #(1) when #(2) site...', $grammar, $grammar1);
                    $errormsg[] = join(', ', $badmails);
                }
                $grammar3 = $data['locked'] ? 'locked' : 'unlocked';
                if (empty($errormsg)) {
                    $validmsg[] = xarML('Site was #(1)', $grammar3);
                } else {
                    $warningmsg[] = xarML('Site was #(1) with error(s), see below', $grammar3);
                }
            }
            if (empty($locksite) && empty($errormsg)) {
                $validmsg[] = xarML('Site lock configuration updated');
            } elseif (empty($locksite) && !empty($errormsg)) {
                $warningmsg[] = xarML('Site lock configuration updated with error(s), see below');
            }

            $statusmsg = array();
            if (!empty($validmsg)) $statusmsg['valid'] = $validmsg;
            if (!empty($warningmsg)) $statusmsg['warning'] = $warningmsg;
            if (!empty($errormsg)) $statusmsg['error'] = $errormsg;
            xarSessionSetVar('authsystem_status', $statusmsg);
            if (empty($return_url)) $return_url = xarModURL('authsystem', 'admin', 'sitelock');
            xarController::redirect($return_url);

        break;

    }

}
?>
<?php
/**
 * File: $Id$
 *
 * Import phpBB users into your Xaraya test site
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage import
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Note : this file is part of import_phpbb.php and cannot be run separately
 */

    echo "<strong>$step. Importing users</strong><br/>\n";

    $query = 'SELECT COUNT(*) FROM ' . $oldprefix . '_users';
    $result =& $dbconn->Execute($query);
    if (!$result) {
        die("Oops, count users failed : " . $dbconn->ErrorMsg());
    }
    $count = $result->fields[0];
    $result->Close();
    $query = 'SELECT user_id, username, username, user_email, user_password, user_website, user_regdate,
                     user_timezone, user_avatar, user_icq, user_aim, user_yim, user_msnm,
                     user_from, user_occ, user_interests, user_sig, user_sig_bbcode_uid
              FROM ' . $oldprefix . '_users 
              WHERE user_id > 2
              ORDER BY user_id ASC';
    $numitems = 1000;
    if (!isset($startnum)) {
        $startnum = 0;
    }
    if ($count > $numitems) {
        $result =& $dbconn->SelectLimit($query, $numitems, $startnum);
    } else {
        $result =& $dbconn->Execute($query);
    }
    if (!$result) {
        die("Oops, select users failed : " . $dbconn->ErrorMsg());
    }
    // check if there's a dynamic object defined for users
    $myobject = xarMod::apiFunc('dynamicdata','user','getobject',
                              array('moduleid' => xarMod::getRegID('roles'), // it's this module
                                     'itemtype' => 0));                          // with no item type
    if (empty($myobject) || empty($myobject->objectid)) {
        // if not, import the dynamic properties for users
        $objectid = xarMod::apiFunc('dynamicdata','util','import',
                                  array('file' => 'modules/dynamicdata/users.xml'));
        if (empty($objectid)) {
            die('Error creating the dynamic user properties');
        }
        $myobject = xarMod::apiFunc('dynamicdata','user','getobject',
                                  array('objectid' => $objectid));
    }
    // Disable dynamicdata hooks for roles (to avoid create + update)
    if (xarModIsHooked('dynamicdata','roles')) {
        xarMod::apiFunc('modules','admin','disablehooks',
                      array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));
    }
    // Check for the default users group
    $defaultgid = xarModGetVar('installer', 'defaultgid');
    if (empty($defaultgid)) {
        $userRole = xarModGetVar('roles', 'defaultgroup');

        // Get the group id
        $defaultRole = xarMod::apiFunc('roles',
                                     'user',
                                     'get',
                                     array('uname'  => $userRole,
                                           'type'   => 1));
        if (empty($defaultRole)) {
            echo "Unable to find default group id : " . xarErrorRender('text') . "<br/>\n";
            die('Oops');
        }
        $defaultgid = $defaultRole['uid'];
        xarModSetVar('installer','defaultgid',$defaultgid);
    }

    $users = xarModGetVar('installer', 'userid');
    if (!empty($users)) {
        $userid = unserialize($users);
    } else {
        $userid = array();
        $userid[0] = _XAR_ID_UNREGISTERED; // Anonymous account
        $userid[1] = _XAR_ID_UNREGISTERED; // Anonymous account
        $userid[2] = _XAR_ID_UNREGISTERED + 1; // Admin account - VERIFY !
    }
    $num = 0;
    while (!$result->EOF) {
        list($uid,$name,$uname,$email,$pass,$url,$date,
             $timezone,$avatar,$icq,$aim,$yim,$msnm,
             $location,$occupation,$interests,$signature,$bbcode) = $result->fields;
        $extra_info = '';
        if (empty($name)) {
            $name = $uname;
        }
        if (empty($date)) {
            $date = time();
        }
        $user = array(//'uid'        => $uid,
                      'uname'      => $uname,
                      'realname'   => $name,
                      'email'      => $email,
                      'cryptpass'  => $pass,
                      'pass'       => '', // in case $pass is empty
                      'date'       => $date,
                      'valcode'    => 'createdbyadmin',
                      'authmodule' => 'authsystem',
                      'state'      => 3);
        // this will *not* fill in the dynamic properties now
        $newuid = xarMod::apiFunc('roles',
                                'admin',
                                'create',
                                $user);

        $num++;
        $result->MoveNext();

        if (empty($newuid)) {
            echo "Insert user ($uid) $uname failed - ";
            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
                xarErrorRender('text');
                xarErrorHandled();
            }
        // same player, shoot again :)
            $user['uname'] .= $uid;
            echo "trying again with username " . $user['uname'] . " : ";
            $newuid = xarMod::apiFunc('roles',
                                    'admin',
                                    'create',
                                    $user);
            if (empty($newuid)) {
                echo "failed<br/>\n";
                flush();
                continue;
            }
            echo "succeeded<br/>\n";
            flush();
        } elseif ($count < 200) {
            echo "Inserted user ($uid) $name - $uname<br/>\n";
        } elseif ($num % 100 == 0) {
            echo "Inserted user " . ($num + $startnum) . "<br/>\n";
            flush();
        }
        $userid[$uid] = $newuid;

        if ($url === 'http://') {
            $url = '';
        }
        if (!empty($bbcode) && !empty($signature) && preg_match("/:$bbcode\]/",$signature)) {
            $signature = preg_replace("/:$bbcode\]/",']',$signature);
        }
        // fill in the dynamic properties - cfr. users.xml !
        $dynamicvalues = array(
                               'itemid'     => $newuid,
                               'website'    => empty($url) ? null : $url,
                               'timezone'   => $timezone == 0 ? null : $timezone, // GMT default
                               'avatar'     => empty($avatar) ? null : $avatar,
                               'icq'        => empty($icq) ? null : $icq,
                               'aim'        => empty($aim)  ? null : $aim,
                               'yim'        => empty($yim) ? null : $yim,
                               'msnm'       => empty($msnm) ? null : $msnm,
                               'location'   => empty($location) ? null : $location,
                               'occupation' => empty($occupation) ? null : $occupation,
                               'interests'  => empty($interests) ? null : $interests,
                               'signature'  => empty($signature) ? null : $signature,
                               'extra_info' => empty($extra_info) ? null : $extra_info,
                              );
        $myobject->createItem($dynamicvalues);

        // add user to the default group
        xarMakeRoleMemberByID($newuid, $defaultgid);

/*    // TODO: import groups once roles/privileges are ok
        if (!xarMod::apiFunc('groups',
                           'user',
                           'newuser', array('gname' => $usergroup,
                                            'uid'   => $uid))) {
            echo "Insert user ($uid) $uname in group $usergroup failed : " . xarErrorRender('text') . "<br/>\n";
        }
*/
    }
    $result->Close();
    if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        xarErrorRender('text');
        xarErrorHandled();
    }
    xarModSetVar('installer','userid',serialize($userid));
    echo "<strong>TODO : import groups and ranks</strong><br/><br/>\n";
    echo '<a href="import_phpbb.php">Return to start</a>&nbsp;&nbsp;&nbsp;';
    if ($count > $numitems && $startnum + $numitems < $count) {
        $startnum += $numitems;
        echo '<a href="import_phpbb.php?module=roles&step=' . $step . '&startnum=' . $startnum . '">Go to step ' . $step . ' - users ' . $startnum . '+ of ' . $count . '</a><br/>';
        flush();
// auto-step
        echo "<script>
document.location = '" . xarServerGetBaseURL() . 'import_phpbb.php?module=roles&step=' . $step . '&startnum=' . $startnum . "'
</script>";
    } else {
        // Enable dynamicdata hooks for roles
        xarMod::apiFunc('modules','admin','enablehooks',
                      array('callerModName' => 'roles', 'hookModName' => 'dynamicdata'));
        echo '<a href="import_phpbb.php?module=categories&step=' . ($step+1) . '">Go to step ' . ($step+1) . '</a><br/>';
    }

    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['roles']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['rolemembers']);
    $dbconn->Execute('OPTIMIZE TABLE ' . $tables['dynamic_data']);

?>

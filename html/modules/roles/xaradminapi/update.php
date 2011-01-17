<?php
/**
 * Update a role core info
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
 * Update a user's core info
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] user ID
 * @param $args['name'] user display name
 * @param $args['uname'] user name
 * @param $args['email'] user email address
 * @param $args['pass'] user password
 * TODO: move url to dynamic user data
 *       replace with status
 * @param $args['url'] user url
 */
function roles_adminapi_update($args)
{
    extract($args);

    // Argument check - make sure that all required arguments are present,
    // if not then set an appropriate error message and return
    if ((!isset($uid)) ||
        (!isset($name)) ||
        (!isset($uname)) ||
        (!isset($email)) ||
        (!isset($state))) {
        $msg = xarML('Invalid Parameter Count');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return;
    }
    $item = xarMod::apiFunc('roles', 'user', 'get',
            array('uid' => $uid));

    if ($item == false) {
        $msg = xarML('No such user');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'ID_NOT_EXIST',
                     new SystemException($msg));
        return false;
    }

    // The former instance definition 'Item' was "username::uid". That enabled
    // the Admin to use the 'myself' instance placeholder function for allowing
    //  users to edit their own info. The current instance definition 'Roles'
    // prohibits this because only the username is used for seccheck.
    // So we try to fake an instance based security check.
    if (!xarSecurityCheck('AdminRole', 0)) {
        // Current user hasn't Admin privileges on Roles, need to go deeper
        if (!xarSecurityCheck('EditRole', 0) || $uid != xarSessionGetVar('uid')) {
            // Bug 6440: when coming from lostpassword, user will be anonymous
            // resetpassword flag will be set, and pass will not be empty
            if ( xarUserIsLoggedIn() || empty($resetpassword) || empty($pass) ) {
                // Current user hasn't Edit privs or isn't the one he wants to change or wasn't resetting password
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
                return;
            }
        }
    }

    if (empty($valcode)) {
        $valcode = '';
    }
    if (empty($home)) {
        $home = '';
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $rolesTable = $xartable['roles'];

    if (!empty($pass)){
        $cryptpass=md5($pass);
        $query = "UPDATE $rolesTable
                  SET xar_name = ?, xar_uname = ?, xar_email = ?,
                      xar_pass = ?, xar_valcode = ?, xar_state = ?
                WHERE xar_uid = ?";
        $bindvars = array($name,$uname,$email,$cryptpass,$valcode,$state,$uid);
    } else {
        $query = "UPDATE $rolesTable
                SET xar_name = ?, xar_uname = ?, xar_email = ?,
                    xar_valcode = ?, xar_state = ?
                WHERE xar_uid = ?";
        $bindvars = array($name,$uname,$email,$valcode,$state,$uid);
    }

    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['name'] = $name;
    $item['home'] = $home;
    $item['uname'] = $uname;
    $item['email'] = $email;

    xarModCallHooks('item', 'update', $uid, $item);

    return true;
}

?>

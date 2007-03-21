<?php
/**
 * Authenticate a user
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * authenticate a user
 * @public
 * @author Marco Canini
 * @param args['uname'] user name of user
 * @param args['pass'] password of user
 * @todo use roles api, not direct db
 * @return int uid on successful authentication, XARUSER_AUTH_FAILED otherwise
 */
function authsystem_userapi_authenticate_user($args)
{
    extract($args);

    assert('!empty($uname) && isset($pass)');

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // Get user information
    $rolestable = $xartable['roles'];
    $query = "SELECT xar_uid, xar_pass FROM $rolestable WHERE xar_uname = ?";
    $result =& $dbconn->Execute($query,array($uname));
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        return XARUSER_AUTH_FAILED;
    }

    list($uid, $realpass) = $result->fields;
    $result->Close();

    // Confirm that passwords match
    if (!xarUserComparePasswords($pass, $realpass, $uname, substr($realpass, 0, 2))) {
        return XARUSER_AUTH_FAILED;
    }

    return $uid;
}

?>
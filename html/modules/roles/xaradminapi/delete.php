<?php
/**
 * Delete a users item
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
 * delete a users item
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] ID of the item
 * @returns bool
 * @return true on success, false on failure
 */
function roles_adminapi_delete($args)
{
    // Get arguments
    extract($args);

    // Argument check
    if (!isset($uid)) {
        $msg = xarML('Wrong arguments to roles_adminapi_delete.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    // The user API function is called.
    $item = xarMod::apiFunc('roles',
            'user',
            'get',
            array('uid' => $uid));

    if ($item == false) {
        $msg = xarML('No such user','roles');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'ID_NOT_EXIST',
                     new SystemException($msg));
        return false;
    }

    // Security check is formally correct, but whole instance definition is TODO
    if (!xarSecurityCheck('DeleteRole', 0, 'Roles', $item['name'])) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'NO_PERMISSION');
        return;
    }

    // Get datbase setup
    $dbconn =& xarDB::getConn();
    $xartable =& xarDB::getTables();
    $rolestable = $xartable['roles'];

    // Delete the item
    $query = "DELETE FROM $rolestable WHERE xar_uid = ?";
    $result =& $dbconn->Execute($query,array($uid));
    if (!$result) return;

    // Let any hooks know that we have deleted this user.
    $item['module'] = 'roles';
    $item['itemid'] = $uid;
    $item['method'] = 'delete';
    xarModCallHooks('item', 'delete', $uid, $item);

    //finished successfully
    return true;
}

?>
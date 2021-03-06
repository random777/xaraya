<?php
/**
 * Delete a group & info
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * deletegroup - delete a group & info
 * @param $args['uid']
 * @return true on success, false otherwise
 */
function roles_adminapi_deletegroup($args)
{
    extract($args);

    if(!isset($uid)) {
        $msg = xarML('Wrong arguments to groups_adminapi_deletegroup');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

// Security Check
    if(!xarSecurityCheck('EditRole')) return;

    $roles = new xarRoles();
    $role = $roles->getRole($uid);

// Prohibit removal of any groups the system needs

    if($role->getName() == xarModGetVar('roles','defaultgroup')) {
        $msg = xarML('The group #(1) is the default group for new users. If you want to remove it change the appropriate configuration setting first.', $role->getName());
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

// OK, go ahead
    return $role->remove();
}

?>
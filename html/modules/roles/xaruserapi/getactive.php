<?php
/**
 * Check if a user is active or not on the site
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * check if a user is active or not on the site
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param bool $include_anonymous whether or not to include anonymous user
 * @returns array
 * @return array of users, empty string, or false on failure
 */
function roles_userapi_getactive($args)
{
    extract($args);

    if (!empty($uid) && !is_numeric($uid)) {
        $msg = xarML('Wrong arguments to roles_userapi_get.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    // Security Check
    if (!xarSecurityCheck('ReadRole')) return;

    // We are only interested in the user ID (not sure why though).
    // Since the uid is mandatory, we will either get a single record or not.
    $args['mode'] = 'UID';
    $users = xarModAPIfunc('roles', 'user', 'getallactive', $args);

    if (empty($users)){
        $sessions = '';
    } else {
        $sessions = array('uid' => array_shift($users));
    }

    return $sessions;
}

?>

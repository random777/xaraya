<?php
/**
 * Count all active users
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
 * count all active users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param bool $count_users Count sessions only, unless 'count_users' is true. Default false
 * @params See getallactive for further parameters supported
 * @returns integer
 * @return number of users with active sessions or number of sessions sessions 
 */
function roles_userapi_countallactive($args)
{
    extract($args);

    // Security Check
    if (!xarSecurityCheck('ReadRole')) return;

    // We can count users (non-anonymous sessions) or sessions. Default to sessions.
    if (!empty($count_users)) {
        $args['mode'] = 'COUNTUSERS';
    } else {
        $args['mode'] = 'COUNTSESS';
    }

    return xarModAPIfunc('roles', 'user', 'getallactive', $args);
}

?>

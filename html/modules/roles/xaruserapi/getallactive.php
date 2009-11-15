<?php
/**
 * Get all active users
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
 * get all active users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Jason Judge <judgej@xaraya.com>
 * @param bool $include_anonymous whether or not to include anonymous user (default true)
 * @param bool $include_myself whether or not to include current user (default true)
 * @param string $group comma-separated list of group names or IDs (default NULL = all)
 * @param integer $uid user ID of user to check (default NULL = all)
 * @param string $mode Determines data type to return, COUNTUSERS, COUNTSESS, UID or FULL (default FULL)
 * @returns array
 * @return array of users, or false on failure
 * @todo Merge this whole function into the getall() API to (a) Avoid duplication; (b) provide additional selection features; (c) more consistency.
 */
function roles_userapi_getallactive($args)
{
    extract($args);

    // Determine the mode:
    // FULL = full details returned (default)
    // COUNTUSERS = count active users
    // COUNTSESS = count active sessions
    // UID = user ids only
    //
    if (!xarVarValidate('pre:upper:enum:FULL:UID:COUNTUSERS:COUNTSESS', $mode, true)) $mode = 'FULL';

    if (!isset($include_anonymous)) {
        $include_anonymous = true;
    } else {
        $include_anonymous = (bool) $include_anonymous;
    }

    if (!isset($include_myself)) {
        $include_myself = true;
    } else {
        $include_myself = (bool) $include_myself;
    }

    // Optional arguments.
    if (!isset($startnum)) {
        $startnum = 1;
    }
    if (!isset($numitems)) {
        $numitems = -1;
    }
    if (!isset($order)) {
        $order = "name";
    }

    if (empty($filter)){
        $filter = time() - (xarConfigGetVar('Site.Session.InactivityTimeout') * 60);
    }

    $roles = array();

    // Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    // Restriction by group.
    // This is a CSV list of IDs or names (or a mix)
    if (!empty($group)) {
        $groups = explode(',', $group);
        $group_list = array();
        foreach ($groups as $group) {
            $group = xarModAPIFunc(
                'roles', 'user', 'get', array(
                    (is_numeric($group) ? 'uid' : 'name') => $group,
                    'type' => 1
                )
            );
            if (isset($group['uid']) && is_numeric($group['uid'])) {
                $group_list[] = (int) $group['uid'];
            }
        }
    }

    // Get database setup
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $sessioninfoTable = $xartable['session_info'];
    $rolestable = $xartable['roles'];
    $rolemembtable = $xartable['rolemembers'];

    // The select terms will depend on the mode, i.e. what structures we want to return.
    switch ($mode) {
        case 'COUNTUSERS':
            // TODO: sqlite requires SELECT COUNT(*) FROM (SELECT DISTINCT ...)
            $select = 'COUNT(DISTINCT a.xar_uid)';
            break;
        case 'COUNTSESS':
            // TODO: sqlite requires SELECT COUNT(*) FROM (SELECT DISTINCT ...)
            $select = 'COUNT(DISTINCT b.xar_ipaddr)';
            break;
        case 'UID':
            $select = 'DISTINCT a.xar_uid';
            break;
        case 'FULL':
        default:
            $select = 'DISTINCT a.xar_uid, a.xar_uname, a.xar_name,  a.xar_email, a.xar_date_reg, b.xar_ipaddr';
            break;
    }

    $bindvars = array();
    $where = array();

    // Common where-clauses
    $where[] = 'a.xar_uid = b.xar_uid';
    $where[] = 'b.xar_lastused > ?';
    $bindvars[] = $filter;
    $where[] = 'a.xar_uid > 1';

    if (empty($group_list)) {
        $from = "$rolestable a, $sessioninfoTable b";
    } else {
        $from = "$rolestable a, $sessioninfoTable b, $rolemembtable AS c";
        $where[] = 'a.xar_uid = c.xar_uid';

        if (count($group_list) > 1) {
            $where[] = 'c.xar_parentid in (?' . str_repeat(',?', count($group_list)-1) . ')';
            $bindvars = array_merge($bindvars, $group_list);
        } else {
            $where[] = 'c.xar_parentid = ?';
            $bindvars[] = $group_list[0];
        }
    }

    // Additional where-clauses can be injected.
    // Make sure they do not include a leading "AND".
    if (isset($selection)) {
        $where[] = preg_replace('/[\s]*and/i', '', $selection);
    }

    // If we aren't including anonymous in the query,
    // then find the anonymous user's uid and add an exclusion
    // to the where clause.
    if (!$include_anonymous) {
        $anon = xarModAPIFunc('roles', 'user', 'get', array('uname'=>'anonymous'));
        $where[] = 'a.xar_uid != ?';
        $bindvars[] = (int) $anon['uid'];
    }

    if (!$include_myself) {
        $thisrole = xarModAPIFunc('roles', 'user', 'get', array('uname' => 'myself'));
        $where[] = 'a.xar_uid != ?';
        $bindvars[] = (int) $thisrole['uid'];
    }

    if (isset($uid) && is_numeric($uid)) {
        $where[] = 'a.xar_uid = ?';
        $bindvars[] = (int) $uid;
    }

    $where[] = 'xar_type = 0';

    // Finally build the query from all the parts.
    if ($dbconn->databaseType == 'sqlite' && ($mode == 'COUNTUSERS' || $mode == 'COUNTSESS')) {
        // Special way to do COUNT(DISTINCT ...) for sqlite.
        $query = 'SELECT COUNT(*) FROM'
            . ' (SELECT ' . str_replace(array('COUNT','(',')'), '', $select)
            . ' FROM ' . $from
            . ' WHERE ' . implode(' AND ', $where)
            . ')';
    } else {
        $query = 'SELECT ' . $select
            . ' FROM ' . $from
            . ' WHERE ' . implode(' AND ', $where)
            . ($mode != 'COUNTUSERS' && $mode != 'COUNTSESS' ? ' ORDER BY xar_' . $order : '');
    }

    // cfr. xarcachemanager - this approach might change later
    $expire = xarModGetVar('roles', 'cache.userapi.getallactive');
    if ($startnum == 0 && $numitems == -1) {
        if (!empty($expire)){
            $result = $dbconn->CacheExecute($expire, $query, $bindvars);
        } else {
            $result = $dbconn->Execute($query, $bindvars);
        }
    } else {
        if (!empty($expire)){
            $result = $dbconn->CacheSelectLimit($expire, $query, $numitems, $startnum-1, $bindvars);
        } else {
            $result = $dbconn->SelectLimit($query, $numitems, $startnum-1, $bindvars);
        }
    }

    if (!$result) return;

    // Check the mode before assigning the results to any variables.
    if ($mode == 'COUNTUSERS' || $mode == 'COUNTSESS') {
        list($sessions) = $result->fields;
    } else {
        // Put users into result array
        $sessions = array();
        for (; !$result->EOF; $result->MoveNext())
        {
            if ($mode == 'UID') {
                list($uid) = $result->fields;
                $sessions[] = $uid;
            } else {
                list($uid, $uname, $name, $email, $date_reg, $ipaddr) = $result->fields;
                if (xarSecurityCheck('ViewRoles', 0, 'Roles', array($uname)))
                {
                    $sessions[] = array(
                        'uid'       => (int) $uid,
                        'name'      => $name,
                        'uname'     => $uname,
                        'email'     => $email,
                        'date_reg'  => $date_reg,
                        'ipaddr'    => $ipaddr,
                    );
                }
            }
        }
    }

    return $sessions;
}

?>

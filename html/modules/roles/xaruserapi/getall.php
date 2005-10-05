<?php
/**
 * Get all users
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * get all users
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['order'] comma-separated list of order items; default 'name'
 * @param $args['selection'] extra coonditions passed into the where-clause
 * @param $args['group'] comma-separated list of group names or IDs, or
 * @param $args['uidlist'] array of user ids
 * @returns array
 * @return array of users, or false on failure
 */
function roles_userapi_getall($args)
{
    extract($args);

    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];
    $rolemembtable = $xartable['rolemembers'];

    $q = new xarQuery('SELECT');
    $q->addtable($rolestable, 'r');
    $q->addfields(array(
                'r.xar_uid AS uid',
                'r.xar_uname AS uname',
                'r.xar_name AS name',
                'r.xar_email AS email',
                'r.xar_state AS state',
                'r.xar_date_reg AS date_reg'));

    // Optional arguments.
    if (!isset($startnum)) $q->setstartat(1);
    else $q->setstartat($startnum);
    if (!isset($numitems)) $q->rowstodo(0);
    else $q->setrowstodo($numitems);

    // Create the order array.
    if (!isset($order)) {
        $q->setorder('r.xar_name');
    } else {
        $order_clause = array();
        foreach (explode(',', $order) as $order_field) {
            if (preg_match('/^[-]?(name|uname|email|uid|state|date_reg)$/', $order_field)) {
                if (strstr($order_field, '-')) {
                    $q->addorder('r.xar_' . str_replace('-', '', $order_field) . ' desc');
                } else {
                    $q->addorder('r.xar_' . $order_field);
                }
            }
        }
    }
    // Restriction by group: define the group array
    if (isset($group)) {
        $groups = explode(',', $group);
        $group_list = array();
        foreach ($groups as $group) {
            $group = xarModAPIFunc(
                'roles', 'user', 'get',
                array(
                    (is_numeric($group) ? 'uid' : 'name') => $group,
                    'type' => 1
                )
            );
            if (isset($group['uid']) && is_numeric($group['uid'])) {
                $group_list[] = (int) $group['uid'];
            }
        }
    }

    // Restrict to users of certain groups
    if (!empty($group_list)) {
        // Select-clause.
        $q->addtable($rolemembtable, 'rm');
        $q->join('r.xar_uid', 'r.xar_uid');
        if (count($group_list) > 1) {
            $q->in('rm.xar_parentid', $group_list);
        } else {
            $q->eq('rm.xar_parentid', $group_list[0]);
        }
    }

    // Don't show certain states
    if (!empty($state) && is_numeric($state) && $state != ROLES_STATE_CURRENT) {
        $q->eq('r.xar_state', (int)$state);
    } else {
        $q->ne('r.xar_state', (int) ROLES_STATE_DELETED);
    }

    // Hide pending users from non-admins
    if (!xarSecurityCheck('AdminRole', 0)) {
        $q->ne('r.xar_state', (int) ROLES_STATE_PENDING);
    }

    // Check about Anonymous
    if (!isset($include_anonymous)) {
        $include_anonymous = true;
    } else {
        $include_anonymous = (bool) $include_anonymous;
    }
    if (!$include_anonymous) {
        $anon = xarModAPIFunc('roles','user','get',array('uname'=>'anonymous'));
        $q->ne('r.xar_uid', (int) $anon['uid']);
    }

    // Check about Myself
    if (!isset($include_myself)) {
        $include_myself = true;
    } else {
        $include_myself = (bool) $include_anonymous;
    }
    if (!$include_myself) {
        $thisrole = xarModAPIFunc('roles','user','get',array('uname'=>'myself'));
        $q->ne('r.xar_uid', (int) $thisrole['uid']);
    }

    // Return only users (not groups).
        $q->eq('r.xar_type', (int) 0);

    if (isset($selection)) $q->unite($q, $selection);

    if (isset($uidlist) && is_array($uidlist) && count($uidlist) > 0) {
        $q->in('r.xar_uid', $uidlist);
    }

    $securityfilter = xarQueryMask('ReadRole');
//    echo var_dump($securityfilter);
    $q->addsecuritycheck('r.xar_uname',$securityfilter);
//    echo var_dump($q->conditions);exit;

/*// cfr. xarcachemanager - this approach might change later
    $expire = xarModGetVar('roles','cache.userapi.getall');
    if ($startnum == 0) {
        if (!empty($expire)){
            $result = $dbconn->CacheExecute($expire,$query,$bindvars);
        } else {
            $result = $dbconn->Execute($query,$bindvars);
        }
    } else {
        if (!empty($expire)){
            $result = $dbconn->CacheSelectLimit($expire, $query, $numitems, $startnum-1,$bindvars);
        } else {
            $result = $dbconn->SelectLimit($query, $numitems, $startnum-1,$bindvars);
        }
    }
*/
    return $q;
}


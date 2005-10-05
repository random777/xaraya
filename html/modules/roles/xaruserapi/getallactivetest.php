<?php
/**
 * Get all active users
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @subpackage Roles Module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * get all active users
 * @param bool $include_anonymous whether or not to include anonymous user
 * @returns array
 * @return array of users, or false on failure
 */
function roles_userapi_getallactive($args)
{
    if(!xarSecurityCheck('ReadRole')) return;

    extract($args);

    $xartable =& xarDBGetTables();
    $sessioninfoTable = $xartable['session_info'];
    $rolestable = $xartable['roles'];

    $q = new xarQuery('SELECT');
    $q->addtable($rolestable, 'a');
    $q->addtable($sessioninfoTable, 'b');
    $q->addfields(array('a.xar_uid AS uid',
                        'a.xar_name AS name',
                        'a.xar_email AS email',
                        'b.xar_ipaddr AS ipaddr'));
    $q->join('a.xar_uid','b.xar_uid');
    $q->gt('a.xar_uid', 1);
    $q->eq('a.xar_type', 0);

    // Optional arguments.
    if (!isset($startnum)) $q->setstartat(1);
    else $q->setstartat($startnum);
    if (!isset($numitems)) $q->rowstodo(0);
    else $q->setrowstodo($numitems);
    if (!isset($order)) $q->setsort('a.xar_name');
    else $q->setorder('a.xar_' . $order);

    // Check about Anonymous
    if (!isset($include_anonymous)) {
        $include_anonymous = true;
    } else {
        $include_anonymous = (bool) $include_anonymous;
    }
    if (!$include_anonymous) {
        $anon = xarModAPIFunc('roles','user','get',array('uname'=>'anonymous'));
        $q->ne('a.xar_uid', (int) $anon['uid']);
    }

    // Check about Myself
    if (!isset($include_myself)) {
        $include_myself = true;
    } else {
        $include_myself = (bool) $include_anonymous;
    }
    if (!$include_myself) {
        $thisrole = xarModAPIFunc('roles','user','get',array('uname'=>'myself'));
        $q->ne('a.xar_uid', (int) $thisrole['uid']);
    }

    if (empty($filter)){
        $filter = time() - (xarConfigGetVar('Site.Session.Duration') * 60);
    }

    if (isset($selection)) $q = $q->unite($q, $selection);

    $securityfilter = xarQueryMask('ReadRole');
    $q->addsecuritycheck('a.xar_uname',$securityfilter);

    // Return the query
    return $q;
}
?>
<?php
/**
 * Generate all groups listing.
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * viewallgroups - generate all groups listing.
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param none
 * @return groups listing of available groups
 */

function roles_userapi_getallgroups($args)
{
    extract($args);
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

// Security Check
    if(!xarSecurityCheck('ViewRoles')) return;

    $q = new xarQuery('SELECT');
    $q->addtable($xartable['roles'],'r');
    $q->addtable($xartable['rolemembers'], 'rm');
    $q->join('rm.xar_uid','r.xar_uid');
    $q->addfields(array('r.xar_uid AS uid','r.xar_name AS name','r.xar_users AS users','rm.xar_parentid AS parentid'));

    $conditions = array();
    // Restriction by group.
    if (isset($group)) {
        $groups = explode(',', $group);
        foreach ($groups as $group) {
            $conditions[] = $q->eq('r.xar_name',$group);
        }
    }
// Restriction by parent group.
    if (isset($parent)) {
        $groups = explode(',', $parent);
        foreach ($groups as $group) {
            $group = xarModAPIFunc(
                'roles', 'user', 'get',
                array(
                    (is_numeric($group) ? 'uid' : 'name') => trim($group),
                    'type' => ROLES_GROUPTYPE
                )
            );
            if (isset($group['uid']) && is_numeric($group['uid'])) {
                $conditions[] = $q->eq('rm.xar_parentid',$group['uid']);
            }
        }
    }
// Restriction by ancestor group.
    if (isset($ancestor)) {
        $groups = explode(',', $ancestor);
        $q1 = new xarQuery('SELECT');
        $q1->addtable($xartable['roles'],'r');
        $q1->addtable($xartable['roles'],'r1');
        $q1->addtable($xartable['rolemembers'], 'rm');
        $q1->join('rm.xar_uid','r.xar_uid');
        $q1->join('rm.xar_parentid','r1.xar_uid');
        $q1->addfields(array('r.xar_name','rm.xar_uid','r1.xar_name','rm.xar_parentid'));
        $q1->eq('r.xar_type',ROLES_GROUPTYPE);
        $q1->run();
        $allgroups = $q1->output();
        $descendants = array();
        foreach ($groups as $group) {
            $descendants = array_merge($descendants,_getDescendants($group,$allgroups));
        }
        $ids = array();
        foreach ($descendants as $descendant) {
            if (!in_array($descendant[1],$ids)) {
                $ids[] = $descendant[1];
                $conditions[] = $q->eq('rm.xar_uid',$descendant[1]);
            }
        }
    }

    if (count($conditions) != 0) $q->qor($conditions);
    $q->eq('r.xar_type',ROLES_GROUPTYPE);
    $q->ne('r.xar_state',ROLES_STATE_DELETED);
    $q->run();
    return $q->output();
}

function _getDescendants($ancestor,$groups)
{
    $descendants = array();
    foreach($groups as $group){
        if($group['r1.xar_name'] == $ancestor)
        $descendants[$group['rm.xar_uid']] = array($group['r.xar_name'],$group['rm.xar_uid']);
    }
    foreach($descendants as $descendant){
        $subgroups = _getDescendants($descendant[0],$groups);
        foreach($subgroups as $subgroup){
            $descendants[$subgroup['rm.xar_uid']] = $subgroup['rm.xar_uid'];
        }
    }
    return $descendants ;
}
?>

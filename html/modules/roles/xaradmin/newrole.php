<?php
/**
 * Create a new role
 *
 * @package modules
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * newRole - create a new role
 * Takes no parameters
 *
 * @author Marc Lutolf
 */
function roles_admin_newrole()
{
    $defaultRole = xarModAPIFunc('roles', 'user', 'get', array('name'  => xarModAPIFunc('roles','user','getdefaultgroup'), 'type'   => 1));
    if (!xarVarFetch('return_url',  'isset',  $return_url, NULL, XARVAR_DONT_SET)) {return;}
    $defaultuid = $defaultRole['uid'];
    if (!xarVarFetch('return_url',  'isset', $return_url, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('pparentid', 'int:', $pparentid, $defaultuid, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pname',       'str:1:', $name, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype',    'int',    $itemtype, ROLES_USERTYPE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('puname',      'str:1:35:', $uname, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pemail',      'str:1:', $email, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ppass1',      'str:1:', $pass, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('state',       'str:1:', $state, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phome', 'str', $home, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pprimaryparent', 'int', $primaryparent, '', XARVAR_NOT_REQUIRED)) return;

	$data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('moduleid' => 27, 'itemtype' => $itemtype));
	$types = xarModAPIFunc('roles','user','getitemtypes');
	$data['itemtypename'] = $types[$itemtype]['label'];

    // Security Check
    if (!xarSecurityCheck('AddRole')) return;
    // Call the Roles class
    // should be static, but apparently not doable in php?
    $roles = new xarRoles();

    $groups = array();
    $names = array();
    foreach($roles->getgroups() as $temp) {
        $nam = $temp['name'];
        if (!in_array($nam, $names)) {
            $names[] = $nam;
            $groups[] = $temp;
        }
    }
    // Load Template
    if (isset($name)) {
        $data['pname'] = $name;
    } else {
        $data['pname'] = '';
    }

    if (isset($itemtype)) {
        $data['itemtype'] = $itemtype;
    } else {
        $data['itemtype'] = ROLES_GROUPTYPE;
    }

    if (isset($uname)) {
        $data['puname'] = $uname;
    } else {
        $data['puname'] = '';
    }

    if (isset($email)) {
        $data['pemail'] = $email;
    } else {
        $data['pemail'] = '';
    }

    if (isset($pass)) {
        $data['ppass1'] = $pass;
    } else {
        $data['ppass1'] = '';
    }

    if (isset($state)) {
        $data['pstate'] = $state;
    } else {
        $data['pstate'] = 1;
    }

    if (isset($home)) {
        $data['phome'] = $home;
    } else {
        $data['phome'] = '';
    }

    if (isset($primaryparent)) {
        $data['pprimaryparent'] = $primaryparent;
    } else {
        $data['pprimaryparent'] = '';
    }

    if (isset($pparentid)) {
        $data['pparentid'] = $pparentid;
    } else {
        $data['pparentid'] = $defaultuid;
    }

    // call item new hooks (for DD etc.)
    $item = $data;
    $item['module'] = 'roles';
    $data['hooks'] = xarModCallHooks('item', 'new', '', $item);

	$data['authid'] = xarSecGenAuthKey();
    $data['addlabel'] = xarML('Add');
    $data['groups'] = $groups;
    $data['return_url'] = $return_url;
    return $data;
}
?>

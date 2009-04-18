<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * Show new role form
 *
 * @author Marc Lutolf
 * @author Johnny Robeson
 */
function roles_admin_new()
{
    if (!xarSecurityCheck('AddRole')) return;

    if (!xarVarFetch('return_url',  'isset', $data['return_url'], NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('parentid',    'id',    $data['parentid'], xarModVars::get('roles','defaultgroup'), XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('itemtype',    'int',   $data['itemtype'], xarRoles::ROLES_USERTYPE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('duvs',        'array', $data['duvs'], array(), XARVAR_NOT_REQUIRED)) return;

    switch ($data['itemtype']) {
        case 1: $name = "roles_roles"; break;
        case 2: $name = "roles_users"; break;
        case 3: $name = "roles_groups"; break;
    }
    $data['object'] = DataObjectMaster::getObject(array('name' => $name));

    xarSession::setVar('ddcontext.roles', array(
                                            'return_url' => xarServer::getCurrentURL(),
                                            'itemtype' => $data['itemtype'],
                                            'parentid' => $data['parentid'],
                                                ));
    // call item new hooks
    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $data['itemtype'];
    $data['hooks'] = xarModCallHooks('item', 'new', '', $item);

    return $data;
}
?>

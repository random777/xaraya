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
    if (!xarVarFetch('itemtype',    'int',   $itemtype, xarRoles::ROLES_USERTYPE, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('duvs',        'array', $data['duvs'], array(), XARVAR_NOT_REQUIRED)) return;

    $data['object'] = DataObjectMaster::getObject(array('module'   => 'roles', 'itemtype' => $itemtype));
    $data['basetype'] = xarModAPIFunc('dynamicdata','user','getbaseitemtype',array('objectid' => $data['object']->objectid));

    xarSession::setVar('ddcontext.roles', array(
                                            'return_url' => xarServer::getCurrentURL(),
                                            'basetype' => $data['basetype'],
                                            'parentid' => $data['parentid'],
                                                ));
    // call item new hooks
    $item = $data;
    $item['module'] = 'roles';
    $item['itemtype'] = $itemtype;
    $data['hooks'] = xarModCallHooks('item', 'new', '', $item);

    return $data;
}
?>

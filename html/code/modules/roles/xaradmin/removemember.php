<?php
/**
 * Remove a user or group from a group
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
 * removeMember - remove a user or group from a group
 *
 * Remove a user or group as a member of another group.
 * This is an action page..
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @access public
 * @param none $
 * @return none
 * @throws none
 * @todo none
 */
function roles_admin_removemember()
{
    // Check for authorization code
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        
    // get input from any view of this page
    if (!xarVarFetch('parentid', 'int', $parentid, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('childid',  'int', $childid, XARVAR_NOT_REQUIRED)) return;
    // call the Roles class and get the parent and child objects
    sys::import('modules.roles.class.roles');
    $role   = xarRoles::get($parentid);
    $member = xarRoles::get($childid);

    // Security Check
    if(!xarSecurityCheck('RemoveRole',1,'Relation',$role->getName() . ":" . $member->getName())) return;

    // remove the child from the parent and bail if an error was thrown
    if (!xarMod::apiFunc('roles','user','removemember', array('id' => $childid, 'gid' => $parentid))) return;

    // call item create hooks (for DD etc.)
    $pargs['module']   = 'roles';
    $pargs['itemtype'] = $role->getType(); // we might have something separate for groups later on
    $pargs['itemid']   = $parentid;
    xarModCallHooks('item', 'unlink', $parentid, $pargs);

    // redirect to the next page
    xarResponse::Redirect(xarModURL('roles', 'admin', 'modify',  array('id' => $childid)));
}
?>

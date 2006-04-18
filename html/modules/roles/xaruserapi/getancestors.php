<?php
/**
 * Get ancestors of a role
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
 * getancestors - get ancestors of a role
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] role id
 * @return $ancestors array containing name, uid
 */
function roles_userapi_getancestors($args)
{
    extract($args);

    if(!isset($uid)) throw new EmptyParameterException('uid');

    if(!xarSecurityCheck('ReadRole')) return;

    $roles = new xarRoles();
    $role = $roles->getRole($uid);

    $ancestors = $role->getAncestors();

    $flatancestors = array();
    foreach($ancestors as $ancestor) {
        $flatancestors[] = array('uid' => $ancestor->getID(),
                        'name' => $ancestor->getName()
                        );
    }
    return $flatancestors;
}
?>

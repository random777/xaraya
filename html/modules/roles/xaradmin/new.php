<?php
/**
 * Create a new role
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * new - generic wrapper to create a new item
 * Takes no parameters
 *
 * @author Marc Lutolf
 */
function roles_admin_new()
{
    return xarModFunc('roles', 'admin', 'newrole');
}
?>
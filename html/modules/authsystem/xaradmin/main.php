<?php
/**
 * Main admin function
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * the main administration function
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @return bool true
 */
function authsystem_admin_main()
{
    // Security Check
    if (!xarSecurityCheck('AdminAuthsystem')) return;

    xarResponse::redirect(xarModURL('authsystem', 'admin', 'modifyconfig'));

    // success
    return true;
}
?>
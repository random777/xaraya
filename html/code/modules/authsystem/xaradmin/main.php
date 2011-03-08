<?php
/**
 * Main entry point for the admin interface of this module
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * Main entry point for the admin interface of this module
 *
 * This function is the default function for the admin interface, and is called whenever the module is
 * initiated with only an admin type but no func parameter passed.  
 * The function displays the module's overview page, or redirects to another page if overviews are disabled.
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 * @return mixed output display string or boolean true if redirected
 */
function authsystem_admin_main()
{
    // Security
    if (!xarSecurityCheck('EditAuthsystem')) return;
    $info = xarController::$request->getInfo();
    if ((bool)xarModVars::get('modules', 'disableoverview') == true) {
        $refererinfo = xarController::$request->getInfo(xarServer::getVar('HTTP_REFERER'));
        $overview = $info[0] == $refererinfo[0];
    } else {
        $overview = true;
    }
    if (!$overview)
        xarController::redirect(xarModURL($info[0], 'admin', 'modifyconfig'));

    if (!xarVarFetch('tab', 'pre:trim:lower:str:1:', $data['tab'], null, XARVAR_NOT_REQUIRED)) return;
    return xarTplModule($info[0],'admin','overview', $data);

}
?>

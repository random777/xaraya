<?php
/**
 * Main admin function
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
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
 */
function authsystem_admin_main()
{
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

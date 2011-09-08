<?php
/**
 * Main entry point for the admin interface of this module
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author Marc Lutolf
 */

function installer_admin_main()
{
    // Security
    if(!xarSecurityCheck('AdminInstaller')) return;

    $request = new xarRequest();
    $refererinfo = xarController::$request->getInfo(xarServer::getVar('HTTP_REFERER'));
    $module = xarController::$request->getModule();
    $samemodule = $module == $refererinfo[0];
    
    if (1 || ((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTpl::module('installer','admin','overview');
    } else {
        xarController::redirect(xarModURL('installer', 'admin', 'modifyconfig'));
        return true;
    }
}

?>

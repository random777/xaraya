<?php
/**
 * Main entry point for the admin interface of this module
 *
 * @package modules
 * @subpackage themes module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * The main admin interface function of this module.
 * This function is the default function for the admin interface, and is called whenever the module is
 * initiated with only an admin type but no func parameter passed.  
 * The function displays the module's overview page, or redirects to the list page if overviews are disabled.
 * @return mixed output display string or boolean true if redirected
 *
 * @author Marty Vance
 */
function themes_admin_main()
{
    // Security
    if(!xarSecurityCheck('EditThemes')) return;

    $refererinfo = xarController::$request->getInfo(xarServer::getVar('HTTP_REFERER'));
    $info = xarController::$request->getInfo();
    $samemodule = $info[0] == $refererinfo[0];
    
    if (((bool)xarModVars::get('modules', 'disableoverview') == false) || $samemodule){
        return xarTplModule('themes','admin','overview');
    } else {
        xarController::redirect(xarModURL('themes', 'admin', 'list'));
        return true;
    }
}

?>

<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Default theme for site
 *
 * Sets the module var for the default site theme.
 *
 * @author Marty Vance
 * @param id the theme id to set
 * @returns
 * @return
 */
function themes_admin_setdefault()
{
    // Security and sanity checks
    if (!xarSecConfirmAuthKey()) return;
    if (!xarSecurityCheck('AdminTheme')) return; 
    if (!xarVarFetch('id', 'int:1:', $defaulttheme)) return;

    $whatwasbefore = xarModGetVar('themes', 'default');

    if (!isset($defaulttheme)) {
        $defaulttheme = $whatwasbefore;
    } 

    $themeInfo = xarThemeGetInfo($defaulttheme);

    if ($themeInfo['class'] != 2) {
        xarResponseRedirect(xarModURL('themes', 'admin', 'modifyconfig'));
    } 

    if (xarVarIsCached('Mod.Variables.themes', 'default')) {
        xarVarDelCached('Mod.Variables.themes', 'default');
    } 

    //update the database - activate the theme
    if (!xarModAPIFunc('themes','admin','install',array('regid'=>$defaulttheme))) {
        xarResponseRedirect(xarModURL('themes', 'admin', 'modifyconfig'));
    }
    
    // update the data
    xarTplSetThemeDir($themeInfo['directory']);
    xarModSetVar('themes', 'default', $themeInfo['directory']); 

    // set the target location (anchor) to go to within the page
    $target = $themeInfo['name'];
    xarResponseRedirect(xarModURL('themes', 'admin', 'list', array('state' => 0), NULL, $target));
    return true;
}
?>
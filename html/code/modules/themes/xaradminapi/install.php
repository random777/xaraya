<?php
/**
 * Install a theme.
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Install a theme.
 *
 * @author Marty Vance
 * @param $maindId int ID of the module to look dependents for
 * @returns bool
 * @return true on dependencies activated, false for not
 * @throws NO_PERMISSION
 */
function themes_adminapi_install($args)
{
    //    static $installed_ids = array();
    $mainId = $args['regid'];

    // Security Check
    // need to specify the module because this function is called by the installer module
    if (!xarSecurityCheck('AdminTheme', 1, 'All', 'All', 'themes')) return;

    // Argument check
    if (!isset($mainId)) throw new EmptyParameterException('regid');
    // See if we have lost any modules since last generation
    if (!xarMod::apiFunc('themes', 'admin', 'checkmissing')) return;

    // Make xarModGetInfo not cache anything...
    //We should make a funcion to handle this or maybe whenever we
    //have a central caching solution...
    $GLOBALS['xarTheme_noCacheState'] = true;

    // Get module information
    $modInfo = xarThemeGetInfo($mainId);
    if (!isset($modInfo)) {
        throw new ThemeNotFoundException($regid,'Theme (regid: #(1)) does not exist.');
    }

    switch ($modInfo['state']) {
        case XARTHEME_STATE_ACTIVE:
        case XARTHEME_STATE_UPGRADED:
            //It is already installed
            return true;
        case XARTHEME_STATE_INACTIVE:
            $initialised = true;
            break;
        default:
            $initialised = false;
            break;
    }

    //Checks if the theme is already initialised
    if (!$initialised) {
        if (!xarMod::apiFunc('themes', 'admin', 'initialise', array('regid' => $mainId))) {
            $msg = xarML('Unable to initialize theme "#(1)".', $modInfo['displayname']);
            throw new Exception($msg);
        }
    }

    // And activate it!
    if (!xarMod::apiFunc('themes', 'admin', 'activate', array('regid' => $mainId))) {
        $msg = xarML('Unable to activate theme "#(1)".', $modInfo['displayname']);
        throw new Exception($msg);
    }
    return true;
}
?>

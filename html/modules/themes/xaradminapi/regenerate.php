<?php
/**
 * Regenerate theme list
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Regenerate theme list
 *
 * @author Marty Vance
 * @param none
 * @return bool true on success, false on failure
 * @throws NO_PERMISSION
 */
function themes_adminapi_regenerate()
{
    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminTheme',1,'All','All','themes')) return;

    //Finds and updates missing themes
    if (!xarModAPIFunc('themes','admin','checkmissing')) {return;}

    //Finds and adds new themes
    if (!xarModAPIFunc('themes','admin','checknew')) {return;}

    //Get all themes in the filesystem
    $fileThemes = xarModAPIFunc('themes','admin','getfilethemes');
    if (!isset($fileThemes)) return;

    // Get all themes in DB
    $dbThemes = xarModAPIFunc('themes','admin','getdbthemes');
    if (!isset($dbThemes)) return;

    // current block layout version
    $bl_cur = xarConfigGetVar('System.Core.BLVersionNum');

    // See if any current themes have been upgraded
    foreach ($fileThemes as $name => $themeInfo) {
        // check uninitialised themes are compatible with current BL version
        // this just prevents incompatible themes being installed, we can't
        // do anything about current themes, since we can't de-activate them
        // or the user will not be able to access the system.
        if ($dbThemes[$name]['state'] != XARTHEME_STATE_ACTIVE &&
            $dbThemes[$name]['state'] != XARTHEME_STATE_UPGRADED) {
            $bl_ver = $themeInfo['bl_version'];
            $vercompare = xarModAPIfunc(
                'base', 'versions', 'compare',
                array(
                    'ver1'=>$bl_ver,
                    'ver2'=>$bl_cur,
                    'strict' => false
                )
            );
            // the BL versions must be equal, anything else and we set error state
            if ($vercompare <> 0) {
                if ($dbThemes[$name]['state'] == XARTHEME_STATE_BL_ERROR_UNINITIALISED) continue;
                if ($dbThemes[$name]['state'] == XARTHEME_STATE_UNINITIALISED) {
                    if (!xarModAPIFunc('themes','admin','setstate',
                        array(
                            'regid' => $dbThemes[$name]['regid'],
                            'state' => XARTHEME_STATE_BL_ERROR_UNINITIALISED
                        ))) return;
                    // skip to next theme
                    continue;
                }
            }
        }
        // BEGIN bugfix (561802) - cmgrote
        if ($dbThemes[$name]['version'] != $themeInfo['version'] &&
            $dbThemes[$name]['state'] != XARTHEME_STATE_UNINITIALISED) {
            if (!xarModAPIFunc('themes','admin','setstate',
                array(
                    'regid' => $dbThemes[$name]['regid'],
                    'state' => XARTHEME_STATE_UPGRADED
                ))) return;

        }
        // if we're here, we have a theme in the filesystem and db
        // look for themes previously in error state, and reset
        $newstate = XARTHEME_STATE_ANY;
        switch ($dbThemes[$name]['state']) {
            case XARTHEME_STATE_BL_ERROR_UNINITIALISED:
            $newstate = XARTHEME_STATE_UNINITIALISED;
            break;
        }
        if ($newstate != XARTHEME_STATE_ANY) {
            $set = xarModAPIFunc(
                'themes', 'admin', 'setstate',
                array(
                    'regid' => $dbThemes[$name]['regid'],
                    'state' => $newstate
                )
            );
        }
    }
    return true;
}
?>
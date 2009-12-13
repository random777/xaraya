<?php
/**
 * View an error with a module
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes Module
 * @link http://xaraya.com/index.php/release/70.html
 */

/**
 * View an error with a theme
 *
 * @author Xaraya Development Team
 * @param id the theme's registered id
 * @returns bool
 * @return true on success, error message on failure
 */
function themes_admin_viewerror()
{
    // Get parameters
    xarVarFetch('id', 'id', $regId);

    //if (!xarSecConfirmAuthKey()) return;

    // Get module information from the database
    $dbTheme = xarModAPIFunc('themes',
                              'admin',
                              'getdbthemes',
                              array('regid' => $regId));

    if (count($dbTheme) == 1) {
        $dbTheme = array_shift($dbTheme);
    }

    // Get module information from the filesystem
    $fileTheme = xarModAPIFunc('themes',
                                'admin',
                                'getfilethemes',
                                array('regid' => $regId));
    if (isset($fileTheme[$dbTheme['name']])) $fileTheme = $fileTheme[$dbTheme['name']];

    // Get the module state and display appropriate template
    // for the error that was encountered with the module
    switch($dbTheme['state']) {
        case XARTHEME_STATE_UNINITIALISED:
        case XARTHEME_STATE_INACTIVE:
        case XARTHEME_STATE_ACTIVE:
        case XARTHEME_STATE_UPGRADED:
            // Set template to 'update'
            $template = 'errorupdate';

            // Set regId
            $data['regId'] = $regId;

            // Set module name
            if (isset($dbTheme['name'])) {
                $data['themename'] = $dbTheme['name'];
            } else {
                $data['themename'] = xarML('[ unknown ]');
            }

            // Set db version
            if (isset($dbTheme['version'])) {
                $data['dbversion'] = $dbTheme['version'];
            } else {
                $data['dbversion'] = xarML('[ unknown ]');
            }

            // Set file version number of module
            if (isset($fileTheme['version'])) {
                $data['fileversion'] = $fileTheme['version'];
            } else {
                $data['fileversion'] = xarML('[ unknown ]');
            }
            break;

        case XARTHEME_STATE_MISSING_FROM_UNINITIALISED:
        case XARTHEME_STATE_MISSING_FROM_INACTIVE:
        case XARTHEME_STATE_MISSING_FROM_ACTIVE:
        case XARTHEME_STATE_MISSING_FROM_UPGRADED:
            // Set template to 'missing'
            $template = 'missing';

            // Set regId
            $data['regId'] = $regId;

            // Set module name
            if (isset($dbTheme['name'])) {
                $data['themename'] = $dbTheme['name'];
            } else {
                $data['themename'] = xarML('[ unknown ]');
            }

            // Set db version
            if (isset($dbTheme['version'])) {
                $data['dbversion'] = $dbTheme['version'];
            } else {
                $data['dbversion'] = xarML('[ unknown ]');
            }

            // Set file version number of module
            if (isset($fileTheme['version'])) {
                $data['fileversion'] = $fileTheme['version'];
            } else {
                $data['fileversion'] = xarML('[ unknown ]');
            }
            break;
        case XARTHEME_STATE_BL_ERROR_UNINITIALISED:
            // Set template to 'update'
            $template = 'blerror';

            // Set regId
            $data['regId'] = $regId;

            // Set module name
            if (isset($dbTheme['name'])) {
                $data['themename'] = $dbTheme['name'];
            } else {
                $data['themename'] = xarML('[ unknown ]');
            }

            // Set db version
            if (isset($dbTheme['version'])) {
                $data['dbversion'] = $dbTheme['version'];
            } else {
                $data['dbversion'] = xarML('[ unknown ]');
            }

            // Set file version number of module
            if (isset($fileTheme['version'])) {
                $data['fileversion'] = $fileTheme['version'];
            } else {
                $data['fileversion'] = xarML('[ unknown ]');
            }
            // set current BL Version
            $data['bl_cur'] = xarConfigGetVar('System.Core.BLVersionNum');
            // set required BL version
            $data['bl_req'] = $fileTheme['bl_version'];
        break;
        default:
            break;
    }

    // Return the template variables to BL
    return xarTplModule('themes', 'admin', $template, $data);
}

?>

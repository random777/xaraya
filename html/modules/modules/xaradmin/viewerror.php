<?php
/**
 * View an error with a module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */

/**
 * View an error with a module
 *
 * @author Xaraya Development Team
 * @param id the module's registered id
 * @returns bool
 * @return true on success, error message on failure
 */
function modules_admin_viewerror()
{
    // Get parameters
    xarVarFetch('id', 'id', $regId);

    //if (!xarSecConfirmAuthKey()) return;

    // Get module information from the database
    $dbModule = xarModAPIFunc('modules',
                              'admin',
                              'getdbmodules',
                              array('regId' => $regId));

    // Get module information from the filesystem
    $fileModule = xarModAPIFunc('modules',
                                'admin',
                                'getfilemodules',
                                array('regId' => $regId));

    // Get the module state and display appropriate template
    // for the error that was encountered with the module
    switch($dbModule['state']) {
        case XARMOD_STATE_ERROR_UNINITIALISED:
        case XARMOD_STATE_ERROR_INACTIVE:
        case XARMOD_STATE_ERROR_ACTIVE:
        case XARMOD_STATE_ERROR_UPGRADED: 
            // Set template to 'update'
            $template = 'errorupdate';

            // Set regId 
            $data['regId'] = $regId;

            // Set module name
            if (isset($dbModule['name'])) {
                $data['modname'] = $dbModule['name'];
            } else {
                $data['modname'] = xarML('[ unknown ]');
            }

            // Set db version
            if (isset($dbModule['version'])) {
                $data['dbversion'] = $dbModule['version'];
            } else {
                $data['dbversion'] = xarML('[ unknown ]');
            }

            // Set file version number of module
            if (isset($fileModule['version'])) {
                $data['fileversion'] = $fileModule['version'];
            } else {
                $data['fileversion'] = xarML('[ unknown ]');
            }
            break;

        case XARMOD_STATE_MISSING_FROM_UNINITIALISED:
        case XARMOD_STATE_MISSING_FROM_INACTIVE:
        case XARMOD_STATE_MISSING_FROM_ACTIVE:
        case XARMOD_STATE_MISSING_FROM_UPGRADED:
            // Set template to 'missing'
            $template = 'missing';

            // Set regId 
            $data['regId'] = $regId;

            // Set module name
            if (isset($dbModule['name'])) {
                $data['modname'] = $dbModule['name'];
            } else {
                $data['modname'] = xarML('[ unknown ]');
            }

            // Set db version
            if (isset($dbModule['version'])) {
                $data['dbversion'] = $dbModule['version'];
            } else {
                $data['dbversion'] = xarML('[ unknown ]');
            }

            // Set file version number of module
            if (isset($fileModule['version'])) {
                $data['fileversion'] = $fileModule['version'];
            } else {
                $data['fileversion'] = xarML('[ unknown ]');
            }
            break;

        default:
            break;
    }

    // Return the template variables to BL
    return xarTplModule('modules', 'admin', $template, $data);
}

?>

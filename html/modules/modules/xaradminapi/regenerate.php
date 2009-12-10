<?php
/**
 * Regenerate module list
 *
 * @package modules
 * @copyright (C) 2005-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Regenerate module list
 *
 * @author Xaraya Development Team
 * @param none
 * @return bool true on success, false on failure
 * @throws NO_PERMISSION
 */
function modules_adminapi_regenerate()
{
    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules', 1, 'All', 'All', 'modules')) {return;}

    //Finds and updates missing modules
    if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {return;}

    //Finds and adds new modules to the db
    if (!xarModAPIFunc('modules', 'admin', 'checknew')) {return;}

    //Get all modules in the filesystem
    $fileModules = xarModAPIFunc('modules', 'admin', 'getfilemodules');
    if (!isset($fileModules)) {return;}

    // Get all modules in DB
    $dbModules = xarModAPIFunc('modules', 'admin', 'getdbmodules');
    if (!isset($dbModules)) {return;}

    //Setup database object for module insertion
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $modules_table =& $xartable['modules'];
    // get current core version for dependency checks
    $core_cur = xarConfigGetVar('System.Core.VersionNum');

    // see if any modules have changed since last generation
    foreach ($fileModules as $name => $modinfo) {
        // check core dependency
        $core_min = isset($modinfo['dependencyinfo'][0]['version_ge']) ? $modinfo['dependencyinfo'][0]['version_ge'] : '';
        $core_max = isset($modinfo['dependencyinfo'][0]['version_le']) ? $modinfo['dependencyinfo'][0]['version_le'] : '';
        $core_req = isset($modinfo['dependencyinfo'][0]['version_eq']) ? $modinfo['dependencyinfo'][0]['version_eq'] : '';
        // module specified an exact core version requirement
        if (!empty($core_req)) {
            $vercompare = xarModAPIfunc(
                'base', 'versions', 'compare',
                array(
                    'ver1'=>$core_req,
                    'ver2'=>$core_cur,
                    'strict' => false
                )
            );
            $core_pass = $vercompare == 0 ? true : false;
        } else {
            if (!empty($core_min)) {
                $vercompare = xarModAPIfunc(
                    'base', 'versions', 'compare',
                    array(
                        'ver1'=>$core_cur,
                        'ver2'=>$core_min,
                        'strict' => false
                    )
                );
                $min_pass = $vercompare <= 0 ? true : false;
            } else {
                $min_pass = true;
            }
            if (!empty($core_max)) {
                $vercompare = xarModAPIfunc(
                    'base', 'versions', 'compare',
                    array(
                        'ver1'=>$core_cur,
                        'ver2'=>$core_max,
                        'strict' => false
                    )
                );
                $max_pass = $vercompare >= 0 ? true : false;
            } else {
                $max_pass = true;
            }
            $core_pass = $min_pass && $max_pass ? true : false;
        }
        if (!$core_pass) {
            // module is incompatible with current core version
            // We can't deactivate or remove the module as the user will
            // lose all of their data, so the module should be placed into
            // a holding state until the user has updated the files for
            // the module to a compatible version

            // Check if error state is already set
            if (($dbModules[$name]['state'] == XARMOD_STATE_CORE_ERROR_UNINITIALISED) ||
                ($dbModules[$name]['state'] == XARMOD_STATE_CORE_ERROR_INACTIVE) ||
                ($dbModules[$name]['state'] == XARMOD_STATE_CORE_ERROR_ACTIVE) ||
                ($dbModules[$name]['state'] == XARMOD_STATE_CORE_ERROR_UPGRADED)) {
                // Continue to next module
                continue;
            }
            // Set error state
            $modstate = XARMOD_STATE_ANY;
            switch ($dbModules[$name]['state']) {
                case XARMOD_STATE_UNINITIALISED:
                case XARMOD_STATE_ERROR_UNINITIALISED:
                    $modstate = XARMOD_STATE_CORE_ERROR_UNINITIALISED;
                    break;
                case XARMOD_STATE_INACTIVE:
                case XARMOD_STATE_ERROR_INACTIVE:
                    $modstate = XARMOD_STATE_CORE_ERROR_INACTIVE;
                    break;
                case XARMOD_STATE_ACTIVE:
                case XARMOD_STATE_ERROR_ACTIVE:
                    $modstate = XARMOD_STATE_CORE_ERROR_ACTIVE;
                    break;
                case XARMOD_STATE_UPGRADED:
                case XARMOD_STATE_ERROR_UPGRADED:
                    $modstate = XARMOD_STATE_CORE_ERROR_UPGRADED;
                    break;
            }
            if ($modstate != XARMOD_STATE_ANY) {
                if (!xarModAPIFunc(
                    'modules', 'admin', 'setstate',
                    array(
                        'regid' => $dbModules[$name]['regid'],
                        'state' => $modstate
                    )
                )) return;
            }
            // Continue to next module
            continue;
        } // End core dep checks

        // Check if the version strings are different.
        if ($dbModules[$name]['version'] != $modinfo['version']) {
            $vercompare = xarModAPIfunc(
                'base', 'versions', 'compare',
                array(
                    'ver1'=>$dbModules[$name]['version'],
                    'ver2'=>$modinfo['version'],
                    'strict' => false
                )
            );
            // Check that the new version is equal to, or greater than the db version
            if ($vercompare >= 0) {
                // Check if we're dealing with a core module
                $is_core = (substr($dbModules[$name]['class'], 0, 4) == 'Core') ? true : false;
                // found equivalent or newer version
                // Handle core module upgrades
                if ($is_core) {
                    // Bug 2879: Attempt to run the core module upgrade and activate functions.
                    xarModAPIFunc(
                        'modules', 'admin', 'upgrade',
                        array(
                            'regid' => $modinfo['regid'],
                            'state' => XARMOD_STATE_INACTIVE
                        )
                    );

                    xarModAPIFunc(
                        'modules', 'admin', 'activate',
                        array(
                            'regid' => $modinfo['regid'],
                            'state' => XARMOD_STATE_ACTIVE
                        )
                    );
                }
                // Automatically update the module version for uninstalled modules or
                // where the version number is equivalent (but could be a different format)
                // or if the module is a core module.
                if ($dbModules[$name]['state'] == XARMOD_STATE_UNINITIALISED ||
                    $dbModules[$name]['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
                    $dbModules[$name]['state'] == XARMOD_STATE_ERROR_UNINITIALISED ||
                    $vercompare == 0 || $is_core)
                {
                    // Update the module version number
                    $sql = "UPDATE $modules_table SET xar_version = ? WHERE xar_regid = ?";
                    $result = $dbconn->Execute($sql, array($modinfo['version'], $modinfo['regid']));
                    if (!$result) {return;}
                } else {
                    // Else set the module state to upgraded
                    if (!xarModAPIFunc(
                        'modules', 'admin', 'setstate',
                        array(
                            'regid' => $modinfo['regid'],
                            'state' => XARMOD_STATE_UPGRADED
                        )
                    )) return;
                }
            } else {
                // found regressed version
                // The database version is greater than the file version.
                // We can't deactivate or remove the module as the user will
                // lose all of their data, so the module should be placed into
                // a holding state until the user has updated the files for
                // the module and the module version is the same or greater
                // than the db version.

                // Check if error state is already set
                if (($dbModules[$name]['state'] == XARMOD_STATE_ERROR_UNINITIALISED) ||
                    ($dbModules[$name]['state'] == XARMOD_STATE_ERROR_INACTIVE) ||
                    ($dbModules[$name]['state'] == XARMOD_STATE_ERROR_ACTIVE) ||
                    ($dbModules[$name]['state'] == XARMOD_STATE_ERROR_UPGRADED)) {
                    // Continue to next module
                    continue;
                }

                // Set error state
                $modstate = XARMOD_STATE_ANY;
                switch ($dbModules[$name]['state']) {
                    case XARMOD_STATE_UNINITIALISED:
                        $modstate = XARMOD_STATE_ERROR_UNINITIALISED;
                        break;
                    case XARMOD_STATE_INACTIVE:
                        $modstate = XARMOD_STATE_ERROR_INACTIVE;
                        break;
                    case XARMOD_STATE_ACTIVE:
                        $modstate = XARMOD_STATE_ERROR_ACTIVE;
                        break;
                    case XARMOD_STATE_UPGRADED:
                        $modstate = XARMOD_STATE_ERROR_UPGRADED;
                        break;
                }
                if ($modstate != XARMOD_STATE_ANY) {
                    if (!xarModAPIFunc(
                        'modules', 'admin', 'setstate',
                        array(
                            'regid' => $dbModules[$name]['regid'],
                            'state' => $modstate
                        )
                    )) return;
                }
                // Continue to next module
                continue;
            }
        } // End version checks

        // From here on we have a module in the file system and the db
        $newstate = XARMOD_STATE_ANY;
        switch ($dbModules[$name]['state']) {
            case XARMOD_STATE_MISSING_FROM_UNINITIALISED:
            case XARMOD_STATE_ERROR_UNINITIALISED:
            case XARMOD_STATE_CORE_ERROR_UNINITIALISED:
                $newstate = XARMOD_STATE_UNINITIALISED;
                break;
            case XARMOD_STATE_MISSING_FROM_INACTIVE:
            case XARMOD_STATE_ERROR_INACTIVE:
            case XARMOD_STATE_CORE_ERROR_INACTIVE:
                $newstate = XARMOD_STATE_INACTIVE;
                break;
            case XARMOD_STATE_MISSING_FROM_ACTIVE:
            case XARMOD_STATE_ERROR_ACTIVE:
            case XARMOD_STATE_CORE_ERROR_ACTIVE:
                $newstate = XARMOD_STATE_ACTIVE;
                break;
            case XARMOD_STATE_MISSING_FROM_UPGRADED:
            case XARMOD_STATE_ERROR_UPGRADED:
            case XARMOD_STATE_CORE_ERROR_UPGRADED:
                $newstate = XARMOD_STATE_UPGRADED;
                break;
        }
        if ($newstate != XARMOD_STATE_ANY) {
            $set = xarModAPIFunc(
                'modules', 'admin', 'setstate',
                array(
                    'regid' => $dbModules[$name]['regid'],
                    'state' => $newstate
                )
            );
        }

        // BUG 2580 - check for changes in version info and update db accordingly
        $updatearray = array('class','category','admin_capable','user_capable');
        $updaterequired = false;
        foreach ($updatearray as $fieldname) {
            if ($dbModules[$name][$fieldname] != $modinfo[$fieldname]) {
                $updaterequired = true;
            }
        }
        if ($updaterequired) {
            //update all these fields to the database
            $updatemodule = xarModAPIFunc('modules','admin','updateproperties',
                      array('regid' => $dbModules[$name]['regid'],
                            'class' => $modinfo['class'],
                            'category' => $modinfo['category'],
                            'admincapable' => $modinfo['admin_capable'],
                            'usercapable' => $modinfo['user_capable']
                        )
                );
        }
    }

    // Finds and updates event handlers
    if (!xarModAPIFunc('modules', 'admin', 'geteventhandlers')) {return;}

    return true;
}

?>

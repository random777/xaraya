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

    // See if we have gained any modules since last generation,
    // or if any current modules have been upgraded
    foreach ($fileModules as $name => $modinfo) {

        // Check matching name and regid values
        foreach ($dbModules as $dbmodule) {
            // Bail if 2 modules have the same regid but not the same name
            if (($modinfo['regid'] == $dbmodule['regid']) &&
               ($modinfo['name'] != $dbmodule['name'])) {
                $msg = xarML('The same registered ID (#(1)) was found belonging to a #(2) module in the file system and a registered #(3) module in the database. Please correct this and regenerate the list.', $dbmodule['regid'], $modinfo['name'], $dbmodule['name']);
                xarErrorSet(
                    XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                    new SystemException($msg)
                );
                return;
            }

            // Bail if 2 modules have the same name but not the same regid
            if (($modinfo['name'] == $dbmodule['name']) &&
               ($modinfo['regid'] != $dbmodule['regid'])) {
                $msg = xarML('The module #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.', $modinfo['name'], $modinfo['regid'], $dbmodule['regid']);
                xarErrorSet(
                    XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                    new SystemException($msg)
                );
                return;
            }
        }

        // If this is a new module, i.e. not in the db list, add it
        assert('$modinfo["regid"] != 0; /* Reg id for the module is 0, something seriously wrong, probably corruption of files */');
        if (empty($dbModules[$name])) {
            // New module
            $modId = $dbconn->GenId($modules_table);
            $sql = "INSERT INTO $modules_table
                      (xar_id,
                       xar_name,
                       xar_regid,
                       xar_directory,
                       xar_version,
                       xar_mode,
                       xar_class,
                       xar_category,
                       xar_admin_capable,
                       xar_user_capable)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = array(
                $modId,
                $modinfo['name'],
                $modinfo['regid'],
                $modinfo['directory'],
                $modinfo['version'],
                $modinfo['mode'],
                $modinfo['class'],
                $modinfo['category'],
                $modinfo['admin_capable'],
                $modinfo['user_capable']
            );
            $result =& $dbconn->Execute($sql, $params);

            if (!$result) return;

            $set = xarModAPIFunc(
                'modules', 'admin', 'setstate',
                array(
                    'regid' => $modinfo['regid'],
                    'state' => XARMOD_STATE_UNINITIALISED
                )
            );
            if (!isset($set)) {return;}

        } else {
            if ($dbModules[$name]['version'] != $modinfo['version']) {
                // The version strings are different.
                // TODO: move the versions API from 'base' to 'modules' if we need to upgrade
                // the base module through this mechanism.
                // Compare the versions, only counting the major.minor.micro places.
                $vercompare = xarModAPIfunc(
                    'base', 'versions', 'compare',
                    array(
                        'ver1'=>$dbModules[$name]['version'],
                        'ver2'=>$modinfo['version'],
                        'strict' => false
                    )
                );
                // Check if database version is less than (or equal to) the file version
                // i.e. that the module is not being downgraded.
                if ($vercompare >= 0) {
                    // The new version is either the same (to 3 levels) or higher.
                    $is_core = (substr($dbModules[$name]['class'], 0, 4) == 'Core') ? true : false;

                    if ($is_core && $vercompare > 0) {
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

                        // First we check if this module belongs to class Core or not
                        if(substr($modinfo['class'], 0, 4)  == 'Core')
                        {
                            // Yup, this module either belongs to Core or maskarading as such..

                            // our main objective here, however, is to catch core modules that have been upgraded
                            // then we must try hard to upgrade and activate it transparently

                            // Get module ID
                            $regId = $modinfo['regid'];

                            $newstate = XARMOD_STATE_INACTIVE;
                            xarModAPIFunc('modules','admin','upgrade',
                                            array(    'regid'    => $regId,
                                                    'state'    => $newstate));

                            $newstate = XARMOD_STATE_ACTIVE;
                            xarModAPIFunc('modules','admin','activate',
                                            array(    'regid'    => $regId,
                                                    'state'    => $newstate));
                        }

                        // Update the module version number
                        $sql = "UPDATE $modules_table SET xar_version = ? WHERE xar_regid = ?";
                        $result = $dbconn->Execute($sql, array($modinfo['version'], $modinfo['regid']));
                        if (!$result) {return;}
                    } else {
                        // Else set the module state to upgraded
                        $set = xarModAPIFunc(
                            'modules', 'admin', 'setstate',
                            array(
                                'regid' => $modinfo['regid'],
                                'state' => XARMOD_STATE_UPGRADED
                            )
                        );

                        if (!isset($set)) {return;}
                    }
                } else {
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

                    // Clear cache to make sure we set the correct states
                    //if (xarVarIsCached('Mod.Infos', $modinfo['regid'])) {
                    //    xarVarDelCached('Mod.Infos', $modinfo['regid']);
                    //}

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
                        $set = xarModAPIFunc(
                            'modules', 'admin', 'setstate',
                            array(
                                'regid' => $dbModules[$name]['regid'],
                                'state' => $modstate
                            )
                        );
                        if (!isset($set)) {return;}

                        // Continue to next module
                        continue;
                    }
                }
            }

            // From here on we have something in the file system or the db
            $newstate = XARMOD_STATE_ANY;
            switch ($dbModules[$name]['state']) {
                case XARMOD_STATE_MISSING_FROM_UNINITIALISED:
                case XARMOD_STATE_ERROR_UNINITIALISED:
                    $newstate = XARMOD_STATE_UNINITIALISED;
                    break;
                case XARMOD_STATE_MISSING_FROM_INACTIVE:
                case XARMOD_STATE_ERROR_INACTIVE:
                    $newstate = XARMOD_STATE_INACTIVE;
                    break;
                case XARMOD_STATE_MISSING_FROM_ACTIVE:
                case XARMOD_STATE_ERROR_ACTIVE:
                    $newstate = XARMOD_STATE_ACTIVE;
                    break;
                case XARMOD_STATE_MISSING_FROM_UPGRADED:
                case XARMOD_STATE_ERROR_UPGRADED:
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
    }

    // Finds and updates event handlers
    if (!xarModAPIFunc('modules', 'admin', 'geteventhandlers')) {return;}

    return true;
}

?>

<?php
/**
 * Verifies if all dependencies of a module are satisfied.
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Verifies if all dependencies of a module are satisfied.
 * To be used before initializing a module.
 *
 * @author Xaraya Development Team
 * @param int $mainId ID of the module to look up the dependents for; in $args
 * @return bool true on dependencies verified and ok, false for not
 * @throws NO_PERMISSION
 */
function modules_adminapi_verifydependency($args)
{
    $mainId = $args['regid'];

    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules',1,'All','All','modules')) return;

    // Argument check
    if (!isset($mainId)) {
        $msg = xarML('Missing module regid (#(1)).', $mainId);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));return;
    }

    // Get module information
    $modInfo = xarModGetInfo($mainId);
    if (!isset($modInfo)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException(__FILE__."(".__LINE__."): Module (regid: $regid) does not exist."));
                       return;
    }


    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules','admin','checkmissing')) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', xarML('Missing Module'));
        return;
    }

    // Get all modules in DB
    // A module is able to fullfil a dependency only if it is activated at least.
    // So db modules should be a safe start to go looking for them
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', xarML('Unable to find modules in the database'));
        return;
    }

    $dbMods = array();

    //Find the modules which are active (should upgraded be added too?)
    foreach ($dbModules as $name => $dbInfo) {
        if ($dbInfo['state'] == XARMOD_STATE_ACTIVE ||
            $dbInfo['state'] == XARMOD_STATE_UPGRADED) { // upgrade added, it's satisfiable
            $dbMods[$dbInfo['regid']] = $dbInfo;
        }
    }

    if (!empty($modInfo['extensions'])) {
        foreach ($modInfo['extensions'] as $extension) {
            if (!empty($extension) && !extension_loaded($extension)) {
                xarErrorSet(
                    XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                    new SystemException(xarML("Required PHP extension '#(1)' is missing for module '#(2)'", $extension, $modInfo['displayname']))
                );
                //Need to add some info for the user
                return false;
            }
        }
    }

    $dependency = $modInfo['dependency'];
    $dependencyinfo = $modInfo['dependencyinfo'];

    if (empty($dependency) && !empty($dependencyinfo)) {
        $dependency = $dependencyinfo;
    }
    if (empty($dependency)) {
        $dependency = array();
    }
    // set current core version static, since it won't likely change
    static $core_cur = '';
    if (empty($core_cur)) {
        // get current core version for dependency checks
        $core_cur = xarConfigGetVar('System.Core.VersionNum');
    }
    foreach ($dependency as $module_id => $conditions) {
        if (!empty($conditions) && is_numeric($conditions)) {
            $modId = $conditions;
        } else {
            $modId = $module_id;
        }
        if (!empty($modId) && is_numeric($modId)) {
            //Required module inexistent
            if (!isset($dbMods[$modId])) {
                xarErrorSet(
                    XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                    new SystemException(xarML('Required module missing (ID #(1))', $modId))
                );
                //Need to add some info for the user
                return false;
            }
            if (!is_array($conditions) && isset($dependencyinfo[$modId]))
                $conditions = $dependencyinfo[$modId];
        }

        if ($modId == 0 && is_array($conditions)) {
             // dependency(info) = array('0' => array('name' => 'Core', 'version_(eq|le|ge)' => 'version'))
            // core dependency checks
            $core_req = isset($conditions['version_eq']) ? $conditions['version_eq'] : '';
            if (!empty($core_req)) {
                // match exact core version required
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
                $core_min = isset($conditions['version_ge']) ? $conditions['version_ge'] : '';
                $core_max = isset($conditions['version_le']) ? $conditions['version_le'] : '';
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
                // Current core version doesn't meet module requirements
                // Need to add some info for the user
                return false;
            }
        } elseif (is_array($conditions)) {
            // dependency(info) = array('module_id' => array('name' => 'modname', 'version_(eq|le|ge)' => 'version'))
            // module dependency checks
            $mver_cur = $dbMods[$modId]['version'];
            $mver_req = isset($conditions['version_eq']) ? $conditions['version_eq'] : '';
            if (!empty($mver_req)) {
                // match exact core version required
                $vercompare = xarModAPIfunc(
                    'base', 'versions', 'compare',
                    array(
                        'ver1'=>$mver_req,
                        'ver2'=>$mver_cur,
                        'strict' => false
                    )
                );
                $mver_pass = $vercompare == 0 ? true : false;
            }  else {
                $mver_min = isset($conditions['version_ge']) ? $conditions['version_ge'] : '';
                $mver_max = isset($conditions['version_le']) ? $conditions['version_le'] : '';
                // legacy declarations, deprecated as of 1.2.0
                if (empty($mver_min) && isset($conditions['minversion'])) {
                    $mver_min = $conditions['minversion'];
                }
                if (empty($mver_max) && isset($conditions['maxversion'])) {
                    $mver_max = $conditions['maxversion'];
                }
                if (!empty($mver_min)) {
                    $vercompare = xarModAPIfunc(
                        'base', 'versions', 'compare',
                        array(
                            'ver1'=>$mver_cur,
                            'ver2'=>$mver_min,
                            'strict' => false
                        )
                    );
                    $min_pass = $vercompare <= 0 ? true : false;
                } else {
                    $min_pass = true;
                }
                if (!empty($mver_max)) {
                    $vercompare = xarModAPIfunc(
                        'base', 'versions', 'compare',
                        array(
                            'ver1'=>$mver_cur,
                            'ver2'=>$mver_max,
                            'strict' => false
                        )
                    );
                    $max_pass = $vercompare >= 0 ? true : false;
                } else {
                    $max_pass = true;
                }
                $mver_pass = $min_pass && $max_pass ? true : false;
            }
            if (!$mver_pass) {
                // Current dependent module version doesn't meet module requirements
                // Need to add some info for the user
                return false;
            }
        }

    }

    return true;
}

?>

<?php
/**
 * Checks for new modules added to the filesystem
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Checks for new modules added to the filesystem, and adds any found to the database
 *
 * @author Xaraya Development Team
 * @param none
 * @return bool null on exceptions, true on sucess to update
 * @throws NO_PERMISSION
 */
function modules_adminapi_checknew()
{
    static $check = false;
    if ($check) return true;

    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminModules',1,'All','All','modules')) return;

    // Get all modules in the filesystem
    $fileModules = xarModAPIFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Get all modules in DB
    $dbModules = xarModAPIFunc('modules','admin','getdbmodules');
    if (!isset($dbModules)) return;

    //Setup database object for module insertion
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $modules_table =& $xartable['modules'];

    // See if we have gained any modules since last generation,
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

            // @TODO: check core dependency here?
            $set = xarModAPIFunc(
                'modules', 'admin', 'setstate',
                array(
                    'regid' => $modinfo['regid'],
                    'state' => XARMOD_STATE_UNINITIALISED
                )
            );
            if (!isset($set)) {return;}
        }
    }
    $check = true;
    return true;
}
?>
<?php
/**
 * Update module information
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Update module information
 *
 * @author Xaraya Development Team
 * @param $args['regid'] the id number of the module to update
 * @param $args['displayname'] the new display name of the module
 * @param admincapable the whether the module shows an admin menu
 * @param usercapable the whether the module shows a user menu
 * @returns bool
 * @return true on success, false on failure
 */
function modules_adminapi_updateproperties($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

// Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"All:All:$regid")) return;

// Update
    $xartable =& xarDB::getTables();
    // CHECKME: this comes falling out of nowhere (from roles module)
    $q = new xarQuery('UPDATE', $xartable['modules']);
//    if (isset($displayname)) $q->addfield('xar_directory', $displayname);
    if (isset($admincapable)) $q->addfield('xar_admin_capable', $admincapable);
    if (isset($usercapable)) $q->addfield('xar_user_capable', $usercapable);
    if (isset($version)) $q->addfield('xar_version', $version);
    if (isset($class)) $q->addfield('xar_class', $class);
    if (isset($category)) $q->addfield('xar_category', $category);
    $q->eq('xar_regid', $regid);
    if(!$q->run()) return;
    return true;
}

?>
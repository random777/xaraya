<?php
/**
 * Deactivate module and its dependents
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Deactivate module and its dependents
 * To be used after the user assured he wants to unitialize the module
 * and all its dependents (should show a list of them to the user)
 *
 * @param $maindId int ID of the module to look dependents for
 * @returns array
 * @return array with dependents
 * @raise NO_PERMISSION
 */
function modules_adminapi_deactivatewithdependents ($args)
{
    $mainId = $args['regid'];

    // Security Check
    // need to specify the module because this function is called by the installer module
    if (!xarSecurityCheck('AdminModules', 1, 'All', 'All', 'modules'))
        return;

    // Argument check
    if (!isset($mainId)) throw new EmptyParameterException('regid');

    // See if we have lost any modules since last generation
    if (!xarModAPIFunc('modules', 'admin', 'checkmissing')) {
        return;
    }

    // Make xarModGetInfo not cache anything...
    //We should make a funcion to handle this instead of seeting a global var
    //or maybe whenever we have a central caching solution...
    $GLOBALS['xarMod_noCacheState'] = true;

    // Get module information
    $modInfo = xarModGetInfo($mainId);
    if (!isset($modInfo)) throw new ModuleNotFoundException($regid,'Module (regid: #(1)) does not exist.');


    if ($modInfo['state'] != XARMOD_STATE_ACTIVE &&
        $modInfo['state'] != XARMOD_STATE_UPGRADED) {
        //We shouldnt be here
        //Throw Exception
        $msg = xarML('Module to be deactivated (#(1)) is not active nor upgraded', $modInfo['displayname']);
        throw new Exception($msg);
    }

    $dependents = xarModAPIFunc('modules','admin','getalldependents',array('regid'=>$mainId));

    foreach ($dependents['active'] as $active_dependent) {
        if (!xarModAPIFunc('modules', 'admin', 'deactivate', array('regid' => $active_dependent['regid']))) {
            $msg = xarML('Unable to deactivate module "#(1)".', $active_dependent['displayname']);
            throw new Exception($msg);
        }
    }
    
    return true;
}

?>
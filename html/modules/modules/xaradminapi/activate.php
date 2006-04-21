<?php
/**
 * Activate a module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Activate a module if it has an active function, otherwise just set the state to active
 *
 * @author Xaraya Development Team
 * @access public
 * @param regid module's registered id
 * @returns bool
 * @raise BAD_PARAM
 */
function modules_adminapi_activate ($args)
{
    //Shoudlnt we check first if the module is alredy INITIALISED????

    extract($args);

    // Argument check
    if (!isset($regid)) throw new EmptyParameterException('regid');

    $modInfo = xarModGetInfo($regid);

    if($modInfo['state'] == XARMOD_STATE_UNINITIALISED) {
        throw new Exception("Calling activate function while module is uninitialised");
    }
    // Module activate function
    if (!xarModAPIFunc('modules','admin', 'executeinitfunction',
                           array('regid'    => $regid,
                                 'function' => 'activate'))) {
        $msg = xarML('Unable to execute "activate" function in the xarinit.php file of module (#(1))');
        throw new Exception(array($modInfo['displayname']),$msg);
    }


    // Update state of module
    $res = xarModAPIFunc('modules','admin','setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_ACTIVE));

    if (function_exists('xarOutputFlushCached') && function_exists('xarModGetName') && xarModGetName() != 'installer') {
        xarOutputFlushCached('modules');
        xarOutputFlushCached('base-block');
    }

    return true;
}
?>
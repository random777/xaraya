<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules
 */
/**
 * Activate a module if it has an active function, otherwise just set the state to active
 *
 * @access public
 * @param regid module's registered id
 * @return bool
 * @throws BAD_PARAM
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
    if (!xarMod::apiFunc('modules','admin', 'executeinitfunction',
                           array('regid'    => $regid,
                                 'function' => 'activate'))) {
        $msg = xarML('Unable to execute "activate" function in the xarinit.php file of module (#(1))', $modInfo['displayname']);
        throw new Exception($msg);
    }

    // Update state of module
    $res = xarMod::apiFunc('modules','admin','setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_ACTIVE));

    if (function_exists('xarOutputFlushCached') && function_exists('xarModGetName') && xarModGetName() != 'installer') {
        xarOutputFlushCached('base');
        xarOutputFlushCached('modules');
        xarOutputFlushCached('base-block');
    }

    return true;
}
?>

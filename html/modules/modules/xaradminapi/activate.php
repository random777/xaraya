<?php
/**
 * Activate a module
 *
 * @package modules
 * @copyright (C) 2005-2008 The Digital Development Foundation
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
 * @param int regid module's registered id
 * @return bool true on successful activation
 * @throws BAD_PARAM
 */
function modules_adminapi_activate($args)
{
    //Shoudlnt we check first if the module is alredy INITIALISED????

    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }
    $modInfo = xarModGetInfo($regid);
    if (!isset($modInfo) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return NULL;
    }

    // Module activate function
    if (!xarMod::apiFunc('modules',
                           'admin',
                           'executeinitfunction',
                           array('regid'    => $regid,
                                 'function' => 'activate'))) {
        $msg = xarML('Unable to execute "activate" function in the xarinit.php file of module (#(1))',
            $modInfo['displayname']);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }


    // Update state of module
    $res = xarMod::apiFunc('modules',
                        'admin',
                        'setstate',
                        array('regid' => $regid,
                              'state' => XARMOD_STATE_ACTIVE));

    if (!isset($res) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return NULL;
    }

    if (function_exists('xarOutputFlushCached') && function_exists('xarModGetName') && xarModGetName() != 'installer') {
        xarOutputFlushCached('base');
        xarOutputFlushCached('base-block');
    }

    return true;
}
?>
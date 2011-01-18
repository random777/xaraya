<?php
/**
 * Update hooks by hook module
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
 * Update hooks by hook module
 *
 * @param none
 *
 * @author Xaraya Development Team
 */
function modules_admin_updatehooks()
{
// Security Check
    if(!xarSecurityCheck('AdminModules')) {return;}

    if (!xarSecConfirmAuthKey()) {return;}
    if (!xarVarFetch('curhook', 'str:1:', $curhook)) {return;}

    $regId = xarMod::getRegID($curhook);
    if (!isset($curhook) || !isset($regId)) {
        $msg = xarML('Invalid hook');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                        new SystemException($msg));
        return;
    }

    // Only update if the module is active.
    $modinfo = xarModGetInfo($regId);
    if (!empty($modinfo) && xarMod::isAvailable($modinfo['name'])) {
        // Pass to API
        $updated = xarMod::apiFunc(
            'modules', 'admin', 'updatehooks',
            array('regid' => $regId)
        );
        if (!isset($updated)) {return;}
    }

    if (!xarVarFetch('return_url', 'isset', $return_url, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!empty($return_url)) {
        xarResponse::redirect($return_url);
    } else {
        xarResponse::redirect(xarModURL('modules', 'admin', 'hooks',
                                      array('hook' => $curhook)));
    }
    return true;
}

?>
<?php
/**
 * Modify configuration
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * modify configuration
 */
function authsystem_admin_modifyconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminAuthsystem')) return;
    if (!xarVarFetch('phase',        'str:1:100', $phase,       'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('shorturls',    'checkbox',  $shorturls,   false,    XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('uselockout',   'checkbox',  $uselockout,  false,     XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('lockouttime',  'int:1:',    $lockouttime, 15,       XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;
    if (!xarVarFetch('lockouttries', 'int:1:',    $lockouttries, 3,       XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

    switch (strtolower($phase)) {
        case 'modify':
        default:
            $data['authid']           = xarSecGenAuthKey();
            $data['shorturlschecked'] = xarModVars::get('authsystem',  'SupportShortURLs') ? true : false;
            $data['uselockoutchecked']= xarModVars::get('authsystem', 'uselockout') ? true : false;
            $data['lockouttime']      = xarModVars::get('authsystem', 'lockouttime')? xarModVars::get('authsystem', 'lockouttime'): 15; //minutes
            $data['lockouttries']     = xarModVars::get('authsystem', 'lockouttries') ? xarModVars::get('authsystem', 'lockouttries'): 3;
						break;

        case 'update':
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) return;
            xarModVars::set('authsystem', 'SupportShortURLs', $shorturls);
            xarModVars::set('authsystem', 'uselockout', $uselockout);
            xarModVars::set('authsystem', 'lockouttime', $lockouttime);
            xarModVars::set('authsystem', 'lockouttries', $lockouttries);
            xarResponseRedirect(xarModURL('authsystem', 'admin', 'modifyconfig'));
            // Return
            return true;
            break;
    }
    return $data;
}
?>
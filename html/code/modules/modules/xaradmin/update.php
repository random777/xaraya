<?php
/**
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Update a module
 *
 * @author Xaraya Development Team
 * @param id the module's registered id
 * @param newdisplayname the new display name
 * @param newdescription the new description
 * @returns bool
 * @return true on success, error message on failure
 */
function modules_admin_update()
{
    // Get parameters
    xarVarFetch('id','id',$regId);
    xarVarFetch('newdisplayname','str::',$newDisplayName);

    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // Pass to API
    $updated = xarMod::apiFunc('modules',
                             'admin',
                             'update',
                              array('regid' => $regId,
                                    'displayname' => $newDisplayName));
    
    if (!isset($updated)) return;
    
    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);
    if (!empty($return_url)) {
        xarResponse::redirect($return_url);
    } else {
        xarResponse::redirect(xarModURL('modules', 'admin', 'list'));
    }
    
    return true;
}

?>

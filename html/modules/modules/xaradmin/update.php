<?php
/**
 * Update a module
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
    if(!xarVarFetch('id','id',$regId)) { return; }
    if(!xarVarFetch('newdisplayname','str::',$newDisplayName)) { return; }
    if(!xarVarFetch('hookorder', 'isset', $hookorder, NULL, XARVAR_DONT_SET)) { return; }

    if(isset($hookorder)) {
        $hookorder = explode(';', ';'.$hookorder);
        $hookorder = array_slice($hookorder, 1, count($hookorder), true);
        $hookorder = array_flip($hookorder);
    } else {
        $hookorder = array();
    }


    if (!xarSecConfirmAuthKey()) return;

    // Pass to API
    $updated = xarModAPIFunc('modules',
                             'admin',
                             'update',
                              array('regid' => $regId,
                                    'displayname' => $newDisplayName,
                                    'hookorder' => $hookorder));
    
    if (!isset($updated)) return;
    
    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);
    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
    } else {
        xarResponseRedirect(xarModURL('modules', 'admin', 'list'));
    }

    xarResponseRedirect(xarModURL('modules', 'admin', 'modify',array('id' => $regId)));

    return true;
}

?>

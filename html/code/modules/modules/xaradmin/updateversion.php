<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * Update the module version in the database
 *
 * @param 'regId' the id number of the module to update
 * @returns bool
 * @return true on success, false on failure
 *
 * @author Xaraya Development Team
 */
function modules_admin_updateversion()
{
    // Get parameters from input
    xarVarFetch('id', 'id', $regId);

    if (!isset($regId)) throw new EmptyParameterException('regid');

    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // Pass to API
    $updated = xarMod::apiFunc('modules',
                             'admin',
                             'updateversion',
                              array('regId' => $regId));

    if (!isset($updated)) return;

    // Redirect to module list
    xarResponse::Redirect(xarModURL('modules', 'admin', 'list'));

    return true;
}

?>

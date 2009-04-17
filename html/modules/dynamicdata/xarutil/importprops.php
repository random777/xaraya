<?php
/**
 * Import the dynamic properties 
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Import the dynamic properties for a module + itemtype from a static table
 */
function dynamicdata_util_importprops()
{
// Security Check
    if(!xarSecurityCheck('AdminDynamicData')) return;

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id',    'isset', $module_id,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}

    if (empty($module_id)) throw new EmptyParameterException('module_id');

    // Confirm authorisation code.  This checks that the form had a valid
    // authorisation code attached to it.  If it did not then the function will
    // proceed no further as it is possible that this is an attempt at sending
    // in false data to the system
    if (!xarSecConfirmAuthKey()) return;

    if (!xarModAPIFunc('dynamicdata','util','importproperties',
                       array('module_id' => $module_id,
                             'itemtype' => $itemtype,
                             'table' => $table,
                             'objectid' => $objectid))) {
        return;
    }

    xarResponse::Redirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                                  array('module_id' => $module_id,
                                        'itemtype' => $itemtype)));
}

?>

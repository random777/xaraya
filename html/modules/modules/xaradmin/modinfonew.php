<?php
/**
 * View complete module information/details
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * View complete module information/details
 * function passes the data to the template
 * opens in new window when browser is javascript enabled
 * @author Xaraya Development Team
 * @access public
 * @param none
 * @returns array
 * @todo some facelift
 */
function modules_admin_modinfonew()
{
    
    // Security check - not needed here, imo 
    // we just show some info here, not changing anything
/*     if (!xarSecConfirmAuthKey()) return; */

    $data = array();
    
    if (!xarVarFetch('id', 'notempty', $id)) {return;}

    // obtain maximum information about module
    $modinfo = xarModGetInfo($id);
    
    // data vars for template
    $data['modid']              = xarVarPrepForDisplay($id);
    $data['modname']            = xarVarPrepForDisplay($modinfo['name']);
    $data['moddescr']           = xarVarPrepForDisplay($modinfo['description']);
    $data['moddispname']        = xarVarPrepForDisplay($modinfo['displayname']);
    $data['moddispdesc']        = xarVarPrepForDisplay($modinfo['displaydescription']);
    $data['modlisturl']         = xarModURL('modules', 'admin', 'list');
    $data['moddir']             = xarVarPrepForDisplay($modinfo['directory']);
    $data['modclass']           = xarVarPrepForDisplay($modinfo['class']);
    $data['modcat']             = xarVarPrepForDisplay($modinfo['category']);
    $data['modver']             = xarVarPrepForDisplay($modinfo['version']);
    // check for proper icon, if not found display default
    // also displaying a generic icon now, if it was provided
    // additionally showing a short message if the icon is missing..
    // TODO icon not yet part of modinfo
    if (isset($modinfo['icon'])) {
      $modicon = xarVarPrepForDisplay($modinfo['icon']);
    } else {
      $modicon = 'modules/' . $data['moddir'] . '/xarimages/admin.gif';
    }
    $modicongeneric = 'modules/' . $data['moddir'] . '/xarimages/admin_generic.gif';
    if (file_exists($modicon)){
        $data['modiconurl']     = xarVarPrepForDisplay($modicon);
        $data['modiconmsg'] = xarVarPrepForDisplay(xarML('as provided by the author'));
    } elseif ($data['modname'] == 'authsystem'
              || substr($data['modclass'], 0, 4) == 'Core'){
        $data['modiconurl'] = 'modules/modules/xarimages/admin.gif';
        $data['modiconmsg'] = xarVarPrepForDisplay(xarML('Xaraya Core Module'));
    }elseif(file_exists($modicongeneric)){
        $data['modiconurl']     = xarVarPrepForDisplay($modicongeneric);
        $data['modiconmsg'] = xarVarPrepForDisplay(xarML('Only generic icon has been provided'));
    }else{
        $data['modiconurl']     = xarVarPrepForDisplay('modules/modules/xarimages/admin_generic.gif');
        $data['modiconmsg'] = xarVarPrepForDisplay(xarML('[Original icon is missing.. 
                                please ask this module developer to provide one in accordance with MDG]'));
    }
    $data['modauthor']          = preg_replace('/,/', '<br />', xarVarPrepForDisplay($modinfo['author']));
    $data['modcontact']         = preg_replace('/,/', '<br />',xarVarPrepForDisplay($modinfo['contact']));
    if(!empty($modinfo['dependency'])){
        $dependency             = xarML('Working on it...');
    } else {
        $dependency             = xarML('None');
    }
    $data['moddependency']      = xarVarPrepForDisplay($dependency);
    
    // Redirect
    return $data;
}

?>
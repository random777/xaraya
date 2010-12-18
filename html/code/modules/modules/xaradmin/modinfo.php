<?php
/**
 * View complete module information/details
 * @package modules
 * @subpackage modules module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * View complete module information/details
 * function passes the data to the template
 *
 * @author Xaraya Development Team
 * @access public
 * @return array data for the template display
 * @todo some facelift
 */
function modules_admin_modinfo()
{
    // Security
    if (!xarSecurityCheck('AdminModules')) return; 
        
    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    $data = array();
    
    if (!xarVarFetch('id', 'int:1:', $id, 0, XARVAR_NOT_REQUIRED)) return; 
    if (empty($id)) return xarResponse::notFound();

    // obtain maximum information about a module
    $modinfo = xarMod::getInfo($id);
    
    // data vars for template
    $data['modid']              = xarVarPrepForDisplay($id);
    $data['modname']            = xarVarPrepForDisplay($modinfo['name']);
    $data['moddescr']           = xarVarPrepForDisplay($modinfo['description']);
    $data['moddispname']        = xarVarPrepForDisplay($modinfo['displayname']);
    $data['moddispdesc']        = xarVarPrepForDisplay($modinfo['displaydescription']);
    $data['modlisturl']         = xarModURL('modules', 'admin', 'list');
    // check for proper icon, if not found display default
    // also displaying a generic icon now, if it was provided
    // additionally showing a short message if the icon is missing..
    $modicon = sys::code() . 'modules/'.$modinfo['directory'].'/xarimages/admin.gif';
    $modicongeneric = sys::code() . 'modules/'.$modinfo['directory'].'/xarimages/admin_generic.gif';
    if(file_exists($modicon)){
        $data['modiconurl']     = xarVarPrepForDisplay($modicon);
        $data['modiconmsg'] = xarVarPrepForDisplay(xarML('as provided by the author'));
    }elseif(file_exists($modicongeneric)){
        $data['modiconurl']     = xarVarPrepForDisplay($modicongeneric);
        $data['modiconmsg'] = xarVarPrepForDisplay(xarML('Only generic icon has been provided'));
    }else{
        $data['modiconurl']     = xarVarPrepForDisplay('modules/modules/xarimages/admin_generic.gif');
        $data['modiconmsg'] = xarVarPrepForDisplay(xarML('[Original icon is missing.. 
                                please ask this module developer to provide one in accordance with MDG]'));
    }
    $data['moddir']             = xarVarPrepForDisplay($modinfo['directory']);
    $data['modclass']           = xarVarPrepForDisplay($modinfo['class']);
    $data['modcat']             = xarVarPrepForDisplay($modinfo['category']);
    $data['modver']             = xarVarPrepForDisplay($modinfo['version']);
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

<?php
/**
 * Modify module settings
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
 * Modify module settings
 *
 * This function queries the database for
 * the module's information and then queries
 * for any hooks that the module could use
 * and passes the data to the template.
 *
 * @author Xaraya Development Team
 * @param id registered module id
 * @param string return_url optional return URL after updating the hooks
 * @return array an array of variables to pass to the template
 */
function modules_admin_modify($args)
{
    extract($args);

    // xarVarFetch does validation if not explicitly set to be not required
    xarVarFetch('id','id',$id);
    xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET);

    $modInfo = xarModGetInfo($id);
    if (!isset($modInfo)) return;

    $modName     = $modInfo['name'];
    $displayName = $modInfo['displayname'];

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',"$modName::$id")) return;

    $data['savechangeslabel'] = xarML('Save Changes');

    // Get the list of all hook modules, and the current hooks enabled for this module
    $hooklist = xarModAPIFunc('modules','admin','gethooklist',
                              array('modName' => $modName));

    // Get the list of all item types for this module (if any)
    $itemtypes = xarModAPIFunc($modName,'user','getitemtypes',
                               // don't throw an exception if this function doesn't exist
                               array(), 0);
    if (isset($itemtypes)) {
        $data['itemtypes'] = $itemtypes;
    } else {
        $data['itemtypes'] = array();
    }

    // $data[hooklist] is the master array which holds all info
    // about the registered hooks.
    $data['hooklist'] = array();
    $hooksort = array();
    // Loop over available $key => $value pairs in hooklist
    // $modname is assigned key (name of module)
    // $hooks is assigned object:action:area
    // MrB: removed the details check, it's simpler to have the same datastructure
    // allways, and I think there's not much of a performance hit.
    // TODO: make the different hooks selectable per type of hook
    foreach ($hooklist as $hookmodname => $hooks) {
        $data['hooklist'][$hookmodname]['modname'] = $hookmodname;
        $data['hooklist'][$hookmodname]['checked'] = array();
        $data['hooklist'][$hookmodname]['hooks'] = array();
        // Fill in the details for the different hooks
        foreach ($hooks as $hook => $modules) {
            if (!empty($modules[$modName])) {
                foreach ($modules[$modName] as $itemType => $val) {
                    $data['hooklist'][$hookmodname]['checked'][$itemType] = 1;
                    if(!isset($hooksort[$val])) {
                        $hooksort[$val] = $hookmodname;
                    }
                }
            }
            $data['hooklist'][$hookmodname]['hooks'][$hook] = 1;
        }
    }

    ksort($hooksort);
    if(count($hooksort) > 1) {
        $hookorder = array();
        foreach($hooksort as $hookindex => $hookname) {
            $hookorder[] = array('id' => $hookname, 'name' => $hookname);
        }
        $data['hookorder'] = $hookorder;
    }
    // End form

    if ($modName != 'modules') {
        $data['user_capable'] = $modInfo['user_capable'];
        $data['user_menu_link'] = $modInfo['user_capable'] ? xarModGetVar($modName, 'user_menu_link') : false;
        $data['admin_capable'] = $modInfo['admin_capable'];
        $data['admin_menu_link'] = $modInfo['admin_capable'] ? xarModGetVar($modName, 'admin_menu_link') : false;
    } else {
        // modules module admin menu link is always displayed
        $data['admin_capable'] = true;
        $data['user_capable'] = false;
    }

    $data['authid'] = xarSecGenAuthKey('modules');
    $data['id'] = $id;
    $data['displayname'] = $modInfo['displayname'];
    if (!empty($return_url)) {
        $data['return_url'] = $return_url;
    }
    return $data;
}

?>

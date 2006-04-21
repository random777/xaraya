<?php
/**
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
 * Modify an item
 *
 * This is a standard function that is called whenever an administrator
 * wishes to modify a current module item
 *
 * @param int objectid the id of the item to be modified
 * @param int modid the id of the module where the item comes from
 * @param int itemtype the id of the itemtype of the item
 * @param join
 * @param table
 * @return
 */
function dynamicdata_admin_modify($args)
{
    extract($args);

    if(!xarVarFetch('objectid', 'id',    $objectid, NULL,                               XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'id',    $modid,    xarModGetIDFromName('dynamicdata'), XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('itemtype', 'str:1', $itemtype, 0,                                  XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('notfresh', 'isset', $notfresh,  NULL, XARVAR_DONT_SET)) {return;}

    if(!xarVarFetch('itemid',   'isset', $itemid)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',    'isset', $preview,     NULL, XARVAR_DONT_SET)) {return;}
    
    // Security check - important to do this as early as possible to avoid
    // potential security holes or just too much wasted processing
    if(!xarSecurityCheck('EditDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

    $data = xarModAPIFunc('dynamicdata','admin','menu');

    if (isset($objectid)) {
	    $ancestor = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('objectid' => $objectid));
    } else {
	    $ancestor = xarModAPIFunc('dynamicdata','user','getbaseancestor',array('moduleid' => $modid,'itemtype' => $itemtype));
    }
    $itemtype = $ancestor['itemtype'];

    $myobject = & Dynamic_Object_Master::getObject(array('objectid' => $objectid,
                                         'moduleid' => $modid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid));
    if ($notfresh) {
	    $isvalid = $myobject->checkInput();
    } else {
		$myobject->getItem();
    }
    $data['object'] = & $myobject;

    // if we're editing a dynamic property, save its property type to cache
    // for correct processing of the validation rule (Dynamic_Validation_Property)
    if ($myobject->objectid == 2) {
        xarVarSetCached('dynamicdata','currentproptype', $myobject->properties['type']);
    }

    $data['objectid'] = $myobject->objectid;
    $data['itemid'] = $itemid;
    $data['authid'] = xarSecGenAuthKey();
    $data['preview'] = $preview;

    $modinfo = xarModGetInfo($myobject->moduleid);
    $item = array();
    foreach (array_keys($myobject->properties) as $name) {
        $item[$name] = $myobject->properties[$name]->value;
    }
    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $myobject->itemtype;
    $item['itemid'] = $myobject->itemid;
    $hooks = array();
    $hooks = xarModCallHooks('item', 'modify', $myobject->itemid, $item, $modinfo['name']);
    $data['hooks'] = $hooks;

    if(!isset($template)) {
        $template = $myobject->name;
    }
    return xarTplModule('dynamicdata','admin','modify',$data,$template);
}

?>

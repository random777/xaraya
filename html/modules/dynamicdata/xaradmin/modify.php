<?php
/**
 * @package modules
 * @copyright (C) copyright-placeholder
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

    if(!xarVarFetch('objectid', 'id',    $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',    'isset', $moduleid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('notfresh', 'isset', $notfresh,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, NULL, XARVAR_DONT_SET)) {return;}

    if(!xarVarFetch('itemid',   'isset', $itemid)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',  'isset', $preview,     NULL, XARVAR_DONT_SET)) {return;}

    $data = xarModAPIFunc('dynamicdata','admin','menu');

    if (empty($objectid) && empty($name)) $objectid = 1;
    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name' => $name,
                                         'moduleid' => $moduleid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid,
                                         'tplmodule' => $tplmodule));
    $args = $myobject->toArray();

    // Security check
    if(!xarSecurityCheck('EditDynamicDataItem',1,'Item',$args['moduleid'].":".$args['itemtype'].":".$args['itemid'])) return;

    if ($notfresh) {
        $isvalid = $myobject->checkInput();
    } else {
        $myobject->getItem();
    }
    $data['object'] = & $myobject;

    // if we're editing a dynamic property, save its property type to cache
    // for correct processing of the configuration rule (ValidationProperty)
    if ($myobject->objectid == 2) {
        xarVarSetCached('dynamicdata','currentproptype', $myobject->properties['type']);
    }

    $data['objectid'] = $args['objectid'];
    $data['itemid'] = $args['itemid'];
    $data['authid'] = xarSecGenAuthKey();
    $data['preview'] = $preview;
    $data['tplmodule'] = $args['tplmodule'];   //TODO: is this needed

    // $modinfo = xarModGetInfo($args['moduleid']);
    // Makes this hooks call explictly from DD
    $modinfo = xarModGetInfo(182);
    $item = array();
    foreach (array_keys($myobject->properties) as $name) {
        $item[$name] = $myobject->properties[$name]->value;
    }
    $item['module'] = $modinfo['name'];
    $item['itemtype'] = $args['itemtype'];
    $item['itemid'] = $args['itemid'];
    $hooks = array();
    $hooks = xarModCallHooks('item', 'modify', $args['itemid'], $item, $modinfo['name']);
    $data['hooks'] = $hooks;

    if (file_exists('modules/' . $args['tplmodule'] . '/xartemplates/admin-modify.xd') ||
        file_exists('modules/' . $args['tplmodule'] . '/xartemplates/admin-modify-' . $args['template'] . '.xd')) {
        return xarTplModule($args['tplmodule'],'admin','modify',$data,$args['template']);
    } else {
        return xarTplModule('dynamicdata','admin','modify',$data,$args['template']);
    }
}

?>

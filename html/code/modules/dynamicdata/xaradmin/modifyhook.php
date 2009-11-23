<?php
/**
 * Modify Dynamic data for an Item
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * modify dynamicdata for an item - hook for ('item','modify','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @return bool true on success, false on failure
 * @throws BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
 */
function dynamicdata_admin_modifyhook($args)
{
    extract($args);

    if (!isset($extrainfo)) throw new EmptyParameterException('extrainfo');
    if (!isset($objectid)) throw new EmptyParameterException('objectid');
    if (!is_numeric($objectid)) throw new VariableValidationException(array('objectid',$objectid,'numeric'));

    // When called via hooks, the module name may be empty, so we get it from
    // the current module
    if (empty($extrainfo['module'])) {
        $modname = xarModGetName();
    } else {
        $modname = $extrainfo['module'];
    }

    // don't allow hooking to yourself in DD
    if ($modname == 'dynamicdata') {
        return '';
    }

    $module_id = xarMod::getRegID($modname);
    if (empty($module_id)) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array('module name', 'admin', 'modifyhook', 'dynamicdata');
        throw new BadParameterException($vars,$msg);
    }

    if (isset($extrainfo['itemtype']) && is_numeric($extrainfo['itemtype'])) {
        $itemtype = $extrainfo['itemtype'];
    } else {
        $itemtype = null;
    }

    if (!empty($extrainfo['itemid']) && is_numeric($extrainfo['itemid'])) {
        $itemid = $extrainfo['itemid'];
    } else {
        $itemid = $objectid;
    }

    $object = & DataObjectMaster::getObject(array('moduleid' => $module_id,
                                       'itemtype' => $itemtype,
                                       'itemid'   => $itemid,
                                       'extend'   => false));
    if (!isset($object)) return;

    $object->getItem();

    // if we are in preview mode, we need to check for any preview values
    if (!xarVarFetch('preview', 'isset', $preview,  NULL, XARVAR_DONT_SET)) {return;}
    if (!empty($preview)) {
        $object->checkInput();
    }

    if (!empty($object->template)) {
        $template = $object->template;
    } else {
        $template = $object->name;
    }

    $properties = $object->getProperties();
    return xarTplModule('dynamicdata','admin','modifyhook',
                        array('properties' => $properties),
                        $template);
}

?>
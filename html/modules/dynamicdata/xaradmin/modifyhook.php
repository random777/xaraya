<?php
/**
 * Modify Dynamic data for an Item
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * modify dynamicdata for an item - hook for ('item','modify','GUI')
 *
 * @param $args['objectid'] ID of the object
 * @param $args['extrainfo'] extra information
 * @returns bool
 * @return true on success, false on failure
 * @raise BAD_PARAM, NO_PERMISSION, DATABASE_ERROR
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

    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
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

    $data = "";
    $tree = xarModAPIFunc('dynamicdata','user', 'getancestors', array('moduleid' => $modid, 'itemtype' => $itemtype, 'base' => false));
    foreach ($tree as $branch) {
    	if ($branch['objectid'] == 0) continue;
    	// TODO: this next line jumps over itemtypes that correspond to wrappers of native itemtypes
    	// TODO: make this more robust
    	if ($branch['itemtype'] < 1000) continue;

		$object = & Dynamic_Object_Master::getObject(array('moduleid' => $modid,
										   'itemtype' => $branch['itemtype'],
										   'itemid'   => $itemid));
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
		$data .= xarTplModule('dynamicdata','admin','modifyhook',
							array('properties' => & $object->properties),
							$template);
	}
    return $data;
}

?>
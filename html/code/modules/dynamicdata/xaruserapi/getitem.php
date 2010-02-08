<?php
/**
 * Get all data fields for an item
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * get all data fields (dynamic or static) for an item
 * (identified by module + item type + item id or table + item id)
 *
 * @author the DynamicData module development team
 * @param string $args['module'] module name of the item fields to get or
 * @param int $args['module_id'] module id of the item fields to get +
 * @param int $args['itemtype'] item type of the item fields to get, or
 * @param $args['table'] database table to turn into an object
 * @param int $args['itemid'] item id of the item fields to get
 * @param array $args['fieldlist'] array of field labels to retrieve (default is all)
 * @param $args['status'] limit to property fields of a certain status (e.g. active)
 * @param $args['join'] join a module table to the dynamic object (if it extends the table)
 * @param bool $args['getobject'] flag indicating if you want to get the whole object back
 * @param bool $args['preview'] flag indicating if you're previewing an item
 * @return array of (name => value), or false on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function &dynamicdata_userapi_getitem($args)
{
    extract($args);

    // Because this function returns a reference, the return statements
    // need an explicit var to return in case of a null return
    // we define that here. Both the ref return here and the null return in
    // other functions should be investigated.
    $nullreturn = NULL;

    if (empty($module_id) && empty($moduleid)) {
        $modname = empty($module) ? xarModGetName() : $module;
        $module_id   = is_numeric($modname) ? $modname : xarMod::getRegID($modname);
    } elseif (empty($module_id)) {
        $module_id = $moduleid;
    }
    $modinfo = xarMod::getInfo($module_id);

    if (empty($itemtype)) $itemtype = 0;

    $invalid = array();
    if (!isset($module_id) || !is_numeric($module_id) || empty($modinfo['name'])) {
        $invalid[] = 'module id';
    }
    if (!isset($itemtype) || !is_numeric($itemtype)) {
        $invalid[] = 'item type';
    }
    if (!isset($itemid) || !is_numeric($itemid)) {
        $invalid[] = 'item id';
    }
    if (count($invalid) > 0) {
        $msg = 'Invalid #(1) for #(2) function #(3)() in module #(4)';
        $vars = array(join(', ',$invalid), 'user', 'getall', 'DynamicData');
        throw new BadParameterException($vars,$msg);
    }

    if(!xarSecurityCheck('ViewDynamicDataItems',1,'Item',"$module_id:$itemtype:$itemid")) return $nullreturn;

    // check the optional field list
    if (empty($fieldlist)) {
        $fieldlist = null;
    } elseif (is_string($fieldlist)) {
        // support comma-separated field list
        $fieldlist = explode(',',$fieldlist);
    }

    // limit to property fields of a certain status (e.g. active)
    if (!isset($status)) $status = null;

    // join a module table to a dynamic object
    if (empty($join)) $join = '';

    // make some database table available via DD
    if (empty($table)) $table = '';

    $object =& DataObjectMaster::getObject(array('moduleid'  => $module_id,
                                       'itemtype'  => $itemtype,
                                       'itemid'    => $itemid,
                                       'fieldlist' => $fieldlist,
                                       'join'      => $join,
                                       'table'     => $table,
                                       'status'    => $status));
    if (!isset($object) || (empty($object->objectid) && empty($object->table))) return $nullreturn;

    // Get the item
    if (!empty($itemid)) $object->getItem();

    // ..check it
    if (!empty($preview)) $object->checkInput(array(),1);

    if (!empty($getobject)) {
        return $object;
    }
    $objectData = $object->getFieldValues();
    return $objectData;
}

?>

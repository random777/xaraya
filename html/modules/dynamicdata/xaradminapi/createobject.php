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
 * create a new dynamic object
 *
 * @author the DynamicData module development team
 * @param $args['name'] name of the object to create
 * @param $args['label'] label of the object to create
 * @param $args['moduleid'] module id of the object to create
 * @param $args['itemtype'] item type of the object to create
 * @param $args['urlparam'] URL parameter to use for the item (itemid, exid, aid, ...)
 * @param $args['config'] some configuration for the object (free to define and use)
 * @param $args['objectid'] object id of the object to create (for import only)
 * @param $args['maxid'] for purely dynamic objects, the current max. itemid (for import only)
 * @returns int
 * @return object ID on success, null on failure
 * @throws BAD_PARAM, NO_PERMISSION
 */
function dynamicdata_adminapi_createobject($args)
{
    $objectid = DataObjectMaster::createObject($args);
    return $objectid;
}
?>

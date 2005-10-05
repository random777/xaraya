<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamicdata module
 * @author mikespub <mikespub@xaraya.com>
 */
/**
// TODO: move this to some common place in Xaraya (base module ?)
 * return the properties for an item
 *
 * @param $args array containing the items or fields to show
 * @returns array
 * @return array containing a reference to the properties of the item
 */
function dynamicdata_userapi_getitemfordisplay($args)
{
    $args['getobject'] = 1;
    $object = xarModAPIFunc('dynamicdata','user','getitem',$args);
    $properties = array();
    if (isset($object)) {
        $properties = & $object->getProperties();
    }
    $item = array(& $properties);
    return $item;
}

?>

<?php
/**
 * Return the properties for an item
 *
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
 * return the properties for an item
 *
 * @param array $args array containing the items or fields to show
 * @return array containing a reference to the properties of the item
 * @TODO: move this to some common place in Xaraya (base module ?)
 */
function dynamicdata_userapi_getitemfordisplay($args)
{
    $args['getobject'] = 1;
    $object = xarMod::apiFunc('dynamicdata','user','getitem',$args);
    $properties = array();
    if (isset($object)) {
        $properties = & $object->getProperties();
    }
    $item = array(& $properties);
    return $item;
}

?>

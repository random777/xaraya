<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 * @todo move the xml generate code into a template based system.
 */
/**
 * Export an object definition or an object item to XML
 *
 * @author mikespub <mikespub@xaraya.com>
 */
function dynamicdata_utilapi_export($args)
{
    // restricted to DD Admins
    if(!xarSecurityCheck('AdminDynamicData')) return;

        $myobject = & DataObjectMaster::getObject(array('name' => 'objects'));
    if (isset($args['objectref'])) {
        $myobject->getItem(array('itemid' => $args['objectref']->objectid));

    } else {
        extract($args);

        if (empty($objectid)) {
            $objectid = null;
        }
        if (empty($modid)) {
            $modid = xarModGetIDFromName('dynamicdata');
        }
        if (empty($itemtype)) {
            $itemtype = 0;
        }
        if (empty($itemid)) {
            $itemid = null;
        }

        $myobject->getItem(array('itemid' => $itemid));
    }

    if (!isset($myobject) || empty($myobject->label)) {
        return;
    }

    // get the list of properties for a Dynamic Object
    $object_properties = DataPropertyMaster::getProperties(array('objectid' => 1));

    // get the list of properties for a Dynamic Property
    $property_properties = DataPropertyMaster::getProperties(array('objectid' => 2));

    $proptypes = DataPropertyMaster::getPropertyTypes();

    $prefix = xarDB::getPrefix();
    $prefix .= '_';

    $xml = '';

    $xml .= '<object name="'.$myobject->properties['name']->value.'">'."\n";
    foreach (array_keys($object_properties) as $name) {
        if ($name != 'name' && isset($myobject->properties[$name]->value)) {
            if (is_array($myobject->properties[$name]->value)) {
                $xml .= "  <$name>\n";
                foreach ($myobject->$name as $field => $value) {
                    $xml .= "    <$field>" . xarVarPrepForDisplay($value) . "</$field>\n";
                }
                $xml .= "  </$name>\n";
            } else {
                $value = $myobject->properties[$name]->value;
                $xml .= "  <$name>" . xarVarPrepForDisplay($value) . "</$name>\n";
            }
        }
    }
    $xml .= "  <properties>\n";
    $properties = DataPropertyMaster::getProperties(array('objectid' => $myobject->properties['objectid']->value));
    foreach (array_keys($properties) as $name) {
        $xml .= '    <property name="'.$name.'">' . "\n";
        foreach (array_keys($property_properties) as $key) {
            if ($key != 'name' && isset($properties[$name][$key])) {
                if ($key == 'type') {
                    // replace numeric property type with text version
                    $xml .= "      <$key>".xarVarPrepForDisplay($proptypes[$properties[$name][$key]]['name'])."</$key>\n";
                } elseif ($key == 'source') {
                    // replace local table prefix with default xar_* one
                    $val = $properties[$name][$key];
                    $val = preg_replace("/^$prefix/",'xar_',$val);
                    $xml .= "      <$key>".xarVarPrepForDisplay($val)."</$key>\n";
                } elseif ($key == 'configuration') {
                    // don't replace anything in the serialized value
                    $val = $properties[$name][$key];
                    $xml .= "      <$key>" . $val . "</$key>\n";
                } else {
                    $xml .= "      <$key>".xarVarPrepForDisplay($properties[$name][$key])."</$key>\n";
                }
            }
        }
        $xml .= "    </property>\n";
    }
    $xml .= "  </properties>\n";
    $xml .= "</object>\n";

    return $xml;
}

?>

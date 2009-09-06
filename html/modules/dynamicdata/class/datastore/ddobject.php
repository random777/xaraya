<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 */

sys::import('xaraya.datastores.interface');

/**
 * Base class for DD objects
 */
class DynamicData_Datastore_DDObject extends Object implements IDDObject
{

    public $name;

    function __construct($name=null)
    {
        $this->name = isset($name) ? $name : self::toString();
    }

    function loadSchema(Array $args = array())
    {
        $this->schemaobject = $this->readSchema($args);
    }

    function readSchema(Array $args = array())
    {
        extract($args);
        $module = isset($module) ? $module : '';
        $type = isset($type) ? $type : '';
        $func = isset($func) ? $func : '';
        if (!empty($module)) {
            $file = 'modules/' . $module . '/xar' . $type . '/' . $func . '.xml';
        }
        try {
            return simplexml_load_file($file);
        } catch (Exception $e) {
            throw new BadParameterException(array($file),'Bad or no xml file encountered: #(1)');
        }
    }

    //Stolen off http://it2.php.net/manual/en/ref.simplexml.php
    function toArray(SimpleXMLElement $schemaobject=null)
    {
        $schemaobject = isset($schemaobject) ? $schemaobject : $this->schemaobject;
        if (empty($schemaobject)) return array();
        $children = $schemaobject->children();
        $return = null;

        foreach ($children as $element => $value) {
            if ($value instanceof SimpleXMLElement) {
                $values = (array)$value->children();

                if (count($values) > 0) {
                    $return[$element] = $this->toArray($value);
                } else {
                    if (!isset($return[$element])) {
                        $return[$element] = (string)$value;
                    } else {
                       if (!is_array($return[$element])) {
                           $return[$element] = array($return[$element], (string)$value);
                       } else {
                            $return[$element][] = (string)$value;
                       }
                   }
                }
            }
        }

        if (is_array($return)) {
            return $return;
        } else {
            return false;
        }
    }

    function toXML(SimpleXMLElement $schemaobject=null)
    {
        $schemaobject = isset($schemaobject) ? $schemaobject : $this->schemaobject;
        if (empty($schemaobject)) return array();
        return $schemaobject->asXML();
    }
}

?>
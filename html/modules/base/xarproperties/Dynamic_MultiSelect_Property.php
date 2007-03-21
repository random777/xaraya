<?php
/**
 * Multiselect Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";
/**
 * handle the multiselect property
 * @author mikespub <mikespub@xaraya.com>
 * @package dynamicdata
 */
class Dynamic_MultiSelect_Property extends Dynamic_Select_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        $validlist = array();
        $options = $this->getOptions();
        foreach ($options as $option) {
            array_push($validlist,$option['id']);
        }
        foreach ($value as $val) {
            if (!in_array($val,$validlist)) {
                $this->invalid = xarML('selection');
                $this->value = null;
                return false;
            }
        }
        $this->value = serialize($value);
        return true;
    }

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->getOptions();
        }
        if (empty($value)) {
            $value = array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data['value']  = $value;
        $data['name']   = $name;
        $data['id']     = $id;
        $data['options']= $options;
        $data['single']= isset($args['single']) ? true : false;

        $data['tabindex'] =!empty($tabindex) ? $tabindex : 0;
        $data['invalid']  =!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';

        $template="";
        return xarTplProperty('base', 'multiselect', 'showinput', $data);
    }
    /**
     * Show the multiselect output
     */
    function showOutput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = array();
        } elseif (!is_array($value)) {
            $tmp = @unserialize($value);
            if ($tmp === false) {
                $value = array($value);
            } else {
                $value = $tmp;
            }
        }
        if (!isset($options)) {
            $options = $this->getOptions();
        }
        $data['value']= $value;
        $data['options']= $options;

        $template="";
        return xarTplProperty('base', 'multiselect', 'showoutput', $data);
    }


    /**
     * Get the base information for this property.
     *
     *
     * @return array Base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 39,
                              'name'       => 'multiselect',
                              'label'      => 'Multi Select',
                              'format'     => '39',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }


}
?>
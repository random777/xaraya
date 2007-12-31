<?php
/**
 * OrderSelect Property
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
 * handle the orderselect property
 * @author Dracos <dracos@xaraya.com>
 * @package dynamicdata
 */
class Dynamic_OrderSelect_Property extends Dynamic_Select_Property
{
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }

        $tmp = array();

        if (empty($value) || strstr($value, ';') === false) {
            foreach ($options as $k => $v) {
                $tmp[] = $v['id'];
            }
        } else {
            $tmp = explode(';', $value);
        }
        $validlist = array();
        $options = $this->getOptions();
        foreach ($options as $option) {
            array_push($validlist, $option['id']);
        }
        if(count(array_diff($validlist, $tmp)) != 0) {
            $this->invalid = xarML('value');
            $this->value = null;
            return false;
        } else {
            $this->value = implode(';', $tmp);
            return true;
        }
    }

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
        if (empty($value) || strstr($value, ';') === false) {
            $tmp = array();
            foreach ($options as $k => $v) {
                $tmp[] = $v['id'];
            }
            $value = implode(';', $tmp);
        } else {
            $tmpval = explode(';', $value);
            $tmpopts = array();
            foreach($tmpval as $v) {
                foreach($options as $k) {
                    if($k['id'] == $v) {
                        $tmpopts[] = $k;
                        continue;
                    }
                }
            }
            $options = $tmpopts;
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
        $data['up_arrow_src']   = xarTplGetImage('up.gif', 'blocks');
        $data['down_arrow_src'] = xarTplGetImage('down.gif', 'blocks');

        $data['tabindex'] =!empty($tabindex) ? $tabindex : 0;
        $data['invalid']  =!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';

        $template="";
        return xarTplProperty('base', 'orderselect', 'showinput', $data);
    }
    /**
     * Show the orderselect output
     */
    function showOutput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options)) {
            $options = $this->getOptions();
        }
        if (empty($value) || strstr($value, ';') === false) {
            $tmpval = array();
            foreach ($options as $k => $v) {
                $tmpval[] = $v['id'];
            }
            $value = implode(';', $tmpval);
        } else {
            $tmpval = explode(';', $value);
            $tmpopts = array();
            foreach($tmpval as $v) {
                foreach($options as $k) {
                    if($k['id'] == $v) {
                        $tmpopts[] = $k;
                        continue;
                    }
                }
            }
            $options = $tmpopts;
        }

        $data['value']= $tmpval;
        $data['options']= $options;

        $template="";
        return xarTplProperty('base', 'orderselect', 'showoutput', $data);
    }


    /**
     * Retrieve the list of options on demand
     */
    function getOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }

        $this->options = array();
        if (!empty($this->func)) {
            // we have some specific function to retrieve the options here
            eval('$items = ' . $this->func .';');
            if (isset($items) && count($items) > 0) {
                foreach ($items as $id => $name) {
                    if(is_array($name)){
                        $okeys = array_keys($name);

                        /**
                         * The following two lines make the potentially flawed
                         * assumption that the first key in the array is an 
                         * id, and the second is a descriptive string (ie, 
                         * name, title, etc).  This property is at the mercy of
                         * the called func to return an expected data construct.
                         */
                        $oid = $name[array_shift($okeys)];
                        $oname = $name[array_shift($okeys)];
                        array_push($this->options, array('id' => $oid, 'name' => $oname));
                        unset($okeys);
                        unset($oname);
                    }
                }
                unset($items);
            }

        } elseif (!empty($this->file) && file_exists($this->file)) {
            $fileLines = file($this->file);
            foreach ($fileLines as $option)
            {
                // allow escaping \, for values that need a comma
                if (preg_match('/(?<!\\\),/', $option)) {
                    // if the option contains a , we'll assume it's an id,name combination
                    list($id,$name) = preg_split('/(?<!\\\),/', $option);
                    $id = strtr($id,array('\,' => ','));
                    $name = strtr($name,array('\,' => ','));
                    array_push($this->options, array('id' => $id, 'name' => $name));
                } else {
                    // otherwise we'll use the option for both id and name
                    $option = strtr($option,array('\,' => ','));
                    array_push($this->options, array('id' => $option, 'name' => $option));
                }
            }

        } else {
          //  return array(); Do we need this return?
        }

        return $this->options;
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
                            'id'         => 50,
                            'name'       => 'orderselect',
                            'label'      => 'Order Select',
                            'format'     => '50',
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
<?php
/**
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 */
/* include the base class */
sys::import('modules.dynamicdata.class.properties');
/**
 * Handle Array Property
 */
class ArrayProperty extends DataProperty
{
    public $id         = 999;
    public $name       = 'array';
    public $desc       = 'Array';
    public $reqmodules = array('base');

    public $fields = array();

    public $display_columns = 30;
    public $display_rows = 4;
    public $initialization_rows = 0;
    public $initialization_column_types = 'a:0:{}';
    //default value of Key label
    public $display_key_label = "Key";
    //default value of value label
    public $display_value_label = "Label";
    //to store the value as associative array
    public $initialization_associative_array = 0;
    //suffix for the Add/Remove Button
    public $default_suffixlabel = "Row";       

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template = 'array';
        $this->filepath   = 'modules/base/xarproperties';
    }

    public function checkInput($name = '', $value = null)
    {
        $name = empty($name) ? 'dd_'.$this->id : $name;
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;

        if (!isset($value)) {
            if (!xarVarFetch($name . '_key', 'array', $keys, array(), XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch($name . '_value',   'array', $values, array(), XARVAR_NOT_REQUIRED)) return;
            $hasvalues = false;
            while (count($keys)) {
                try {
                    $thiskey = array_shift($keys);
                    $thisvalue = array_shift($values);
                    if (!empty($thiskey) && !empty($thisvalue)) {
                        $value[$thiskey] = $thisvalue;
                        $hasvalues = true;
                    }
                } catch (Exception $e) {}
            }
            if (!$hasvalues) $value = array();
        }
        return $this->validateValue($value);;
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        if (!is_array($value)) {
            $this->value = null;
            return false;
        }
        $this->setValue($value);
        return true;
    }

    function setValue($value=null)
    {
        if (!empty($value) && !is_array($value)) {
            $this->value = $value;
        } else {
            if (empty($value)) $value = array();
            //this code is added to store the values as value1,value2 in the DB for non-associative storage
            if(!$this->initialization_associative_array) {
                $items = $value;
                $elements = "";
                foreach ($items as $value) {
                    $element = $value;                  
                    if($elements == "") {
                        $elements = $element;
                    } else {
                        $elements = $elements . ";" . $element;
                    }                   
                }               
                $this->value = $elements .";";
            } else {
                $this->value = serialize($value);
            }
        }
    }

    public function getValue()
    {
        try {
            if(!$this->initialization_associative_array) {
                $value = $this->value;
            } else {
                $value = unserialize($this->value);
            }            
        } catch(Exception $e) {
            $value = null;
        }
        return $value;
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) $value = $this->value;
        else $value = $data['value'];
        
        if (!isset($data['suffixlabel'])) $data['suffixlabel'] = $this->default_suffixlabel;                
        if (!is_array($value)) {
            try {
                $value = unserialize($value);
                if (!is_array($value)) throw new Exception("Did not find a correct array value");
            } catch (Exception $e) {
                $elements = array();
                $lines = explode(';',$value);
                // remove the last (empty) element
                array_pop($lines);
                foreach ($lines as $element)
                {
                    // allow escaping \, for values that need a comma
                    if (preg_match('/(?<!\\\),/', $element)) {
                        // if the element contains a , we'll assume it's an key,value combination
                        list($key,$name) = preg_split('/(?<!\\\),/', $element);
                        $key = trim(strtr($key,array('\,' => ',')));
                        $val = trim(strtr($val,array('\,' => ',')));
                        $elements[$key] = $val;
                    } else {
                        // otherwise we'll assume no associative array
                        $element = trim(strtr($element,array('\,' => ',')));
                        array_push($elements, $element);
                    }
                }        
                $value = $elements;
            }
        }

        // Allow overriding of the field keys from the template
        if (isset($data['fields'])) $this->fields = $data['fields'];
        if (count($this->fields) > 0) {
            $fieldlist = $this->fields;
        } else {
            $fieldlist = array_keys($value);
        }

        $data['value'] = array();
        foreach ($fieldlist as $field) {
            if (!isset($value[$field])) {
                $data['value'][$field] = '';
            } else {
                $data['value'][$field] = xarVarPrepForDisplay($value[$field]);
            }
        }

        $data['rows'] = !empty($rows) ? $rows : $this->display_rows;
        $data['size'] = !empty($size) ? $size : $this->display_columns;
        
        $data['style'] = !empty($data['style']) ? $data['style'] : '';
        $data['class'] = !empty($data['class']) ? $data['class'] : '';
        $data['columntype'] = 'textbox';
        
        //Psspl: Added code for getting the text of 'Key' and 'Value' label
        $data['keylabel'] = !empty($keylabel) ? $keylabel : $this->display_key_label;
        $data['valuelabel'] = !empty($valuelabel) ? $valuelabel : $this->display_value_label;
        $data['allowinput'] = !empty($allowinput) ? $allowinput : $this->initialization_rows;
        $data['associative_array'] = !empty($associative_array) ? $associative_array : $this->initialization_associative_array;
        $data['numberofrows'] = count($data['value']);
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        $value = isset($data['value']) ? $data['value'] : $this->getValue();
        $data['associative_array'] = !empty($associative_array) ? $associative_array : $this->initialization_associative_array;     
        if (!is_array($value)) {
            //this is added to show the value with new line when storage is non-associative                     
            if(!$this->initialization_associative_array) {              
                $elements = array();
                $data['value'] = $value;
                $lines = explode(';',$value);
                // remove the last (empty) element
                 array_pop($lines);
                 foreach ($lines as $element) {
                     array_push($elements, $element);
                 }
                 $data['value'] = $elements;
            } else {
                 $data['value'] = $value;
            }           
        } else {
            if (empty($value)) $value = array();

            if (count($this->fields) > 0) {
                $fieldlist = $this->fields;
            } else {
                $fieldlist = array_keys($value);
            }

            $data['value'] = array();
            foreach ($fieldlist as $field) {
                if (!isset($value[$field])) {
                    $data['value'][$field] = '';
                } else {
                    $data['value'][$field] = xarVarPrepForDisplay($value[$field]);
                }
            }
        }
        return parent::showOutput($data);
    }
}
?>
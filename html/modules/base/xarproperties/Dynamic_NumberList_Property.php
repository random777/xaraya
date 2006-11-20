<?php
/**
 * Numberlist property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * @author mikespub <mikespub@xaraya.com>
*/
/* linoj: validation can also be max:min for descending list */
 
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * handle the numberlist property
 *
 * @package dynamicdata
 */
class Dynamic_NumberList_Property extends Dynamic_Select_Property
{
    var $min = null;
    var $max = null;
    var $order = 'asc';

    function Dynamic_NumberList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        // check validation for allowed min/max values
        if (count($this->options) == 0 && !empty($this->validation) && strchr($this->validation,':')) {
            list($min,$max) = explode(':',$this->validation);
            if ($min !== '' && is_numeric($min)) {
                $this->min = intval($min);
            }
            if ($max !== '' && is_numeric($max)) {
                $this->max = intval($max);
            }
            if (isset($this->min) && isset($this->max)) {
                if ($this->min > $this->max) {
                    $this->order = 'desc';
                    // swap values
                    $tmp = $this->min;
                    $this->min = $this->max;
                    $this->max = $tmp;
                    // descending options
                    for ($i = $this->max; $i >= $this->min; $i--) {
                        $this->options[] = array('id' => $i, 'name' => $i);
                    }                   
                }
                else {
                    // ascending options
                    for ($i = $this->min; $i <= $this->max; $i++) {
                        $this->options[] = array('id' => $i, 'name' => $i);
                    }
                }
            } else {
                // you're in trouble :)
            }
        }
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($value) || $value === '') {
            if (isset($this->min)) {
                $this->value = $this->min;
            } elseif (isset($this->max)) {
                $this->value = $this->max;
            } else {
                $this->value = 0;
            }
        } elseif (is_numeric($value)) {
            $this->value = intval($value);
        } else {
            $this->invalid = xarML('integer');
            $this->value = null;
            return false;
        }
        if (count($this->options) == 0 && (isset($this->min) || isset($this->max)) ) {
            if ( (isset($this->min) && $this->value < $this->min) ||
                 (isset($this->max) && $this->value > $this->max) ) {
                $this->invalid = xarML('integer in range');
                $this->value = null;
                return false;
            }
        } elseif (count($this->options) > 0) {
            foreach ($this->options as $option) {
                if ($option['id'] == $this->value) {
                    return true;
                }
            }
            $this->invalid = xarML('integer in selection');
            $this->value = null;
            return false;
        } else {
            $this->invalid = xarML('integer selection');
            $this->value = null;
            return false;
        }
    }

    // default showInput() from Dynamic_Select_Property

    // default showOutput() from Dynamic_Select_Property


    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 16,
                              'name'       => 'integerlist',
                              'label'      => 'Number List',
                              'format'     => '16',
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

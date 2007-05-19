<?php
/**
 * Float box property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
include_once "modules/base/xarproperties/Dynamic_TextBox_Property.php";

/**
 * Class to handle floatbox property
 *
 * @package modules
 * @subpackage Base module
 * @author mikespub <mikespub@xaraya.com>
 */
class Dynamic_FloatBox_Property extends Dynamic_TextBox_Property
{
    var $size = 10;
    var $maxlength = 30;
    var $datatype = 'number';

    /**
     * Validate the value for this property
     * @return bool true when validated, false when not validated
     */
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
            $this->value = (float) $value;
            if (isset($this->min) && isset($this->max) && ($this->min > $value || $this->max < $value)) {
                $this->invalid = xarML('float : allowed range is between #(1) and #(2)',$this->min,$this->max);
                $this->value = null;
                return false;
            } elseif (isset($this->min) && $this->min > $value) {
                $this->invalid = xarML('float : must be #(1) or more',$this->min);
                $this->value = null;
                return false;
            } elseif (isset($this->max) && $this->max < $value) {
                $this->invalid = xarML('float : must be #(1) or less',$this->max);
                $this->value = null;
                return false;
            }
        } else {
            $this->invalid = xarML('float');
            $this->value = null;
            return false;
        }
        return true;
    }

    // default showInput() from Dynamic_TextBox_Property

    /**
     * Show the output for the float property
     * @return mixed info for the template
     */
    function showOutput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value) && !empty($field->validation)) {
        // TODO: extract precision from field validation too ?
            //if (is_numeric($field->validation)) {
            //    $precision = $field->validation;
            //    return sprintf("%.".$precision."f",$value);
            //}
        }
        $data['value']= xarVarPrepForDisplay($value);
        
        $template="";
        return xarTplProperty('base', 'floatbox', 'showoutput', $data);
    }


    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
    function getBasePropertyInfo()
    {
        $args = array();
        $baseInfo = array(
                          'id'         => 17,
                          'name'       => 'floatbox',
                          'label'      => 'Number Box (float)',
                          'format'     => '17',
                          'validation' => '',
                          'source'         => '',
                          'dependancies'   => '',
                          'requiresmodule' => '',
                          'aliases'        => '',
                          'args'           => serialize($args),
                          // ...
                         );
        return $baseInfo;
    }

    // Trick: use the parent method with a different template :-)
    function showValidation($args = array())
    {
        // allow template override by child classes
        if (!isset($args['template'])) {
            $args['template'] = 'floatbox';
        }

        return parent::showValidation($args);
    }

    // default updateValidation() from Dynamic_TextBox_Property

}

?>
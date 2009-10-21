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

    // Precision of number, decimal places.
    // A negative precision works to the left of the decimal point.
    var $precision = NULL;

    // Decimal character (eg '.')
    var $grouping_sep = NULL;

    // Grouping separator character (eg ',' or ' ')
    var $decimal_sep = NULL;

    // Prefix and suffix character to add to the number.
    var $number_prefix = NULL;
    var $number_suffix = NULL;

    // Always show decimals, regardless of precision.
    var $always_show_decimal = NULL;

    // Trim trailing zeros of decimals.
    var $trim_decimals = NULL;

    // The datatype this property handles.
    var $datatype = 'number';

    // Bug 6422: add constructor for php > 5.2.6
    function Dynamic_FloatBox_Property($args)
    {
        return parent::Dynamic_TextBox_Property($args);
    }
    // Convert the current locale data into a nested array.
    // TODO: allow a subsection to be extracted without having
    // to process the whole thing.
    function _locale2array($data)
    {
        $return = array();
        $sub = array();

        foreach($data as $key => $value) {
            $key = trim($key, '/');
            if (strpos($key, '/') === false) {
                $return[$key] = $value;
            } else {
                $key_arr = explode('/', $key, 2);
                $sub[$key_arr[0]][$key_arr[1]] = $value;
            }
        }

        if (!empty($sub)) {
            foreach($sub as $key2 => $sub2) {
                $return[$key2] = $this->_locale2array($sub2);
            }
        }

        return $return;
    }


    /**
     * Validate the value for this property
     * @return bool true when validated, false when not validated
     */
    function validateValue($value = null)
    {
        if (!isset($value)) $value = $this->value;

        if (!isset($value) || $value === '') {
            if (isset($this->min)) {
                $this->value = $this->min;
            } elseif (isset($this->max)) {
                $this->value = $this->max;
            } else {
                $this->value = 0;
            }
        }

        if (!is_numeric($value)) {
            $this->invalid = xarML('invalid number');
            $this->value = $value;
            return false;
        }

        $this->value = (float)$value;

        if (isset($this->min) && isset($this->max) && ($this->min > $value || $this->max < $value)) {
            $this->invalid = xarML('value must be between #(1) and #(2)',
                $this->_format_number($this->min), $this->_format_number($this->max)
            );
            return false;
        } elseif (isset($this->min) && $this->min > $value) {
            $this->invalid = xarML('value must be no less than #(1)', $this->_format_number($this->min));
            return false;
        } elseif (isset($this->max) && $this->max < $value) {
            $this->invalid = xarML('value must be no greater than #(1)', $this->_format_number($this->max));
            return false;
        }

        return true;
    }


    /**
     * Show the output for the float property
     * @return mixed info for the template
     */
    function showOutput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) $value = $this->value;

        // TODO: prep the display in the template
        $data['value']= xarVarPrepForDisplay($this->_format_number($value));

        $template = "";
        return xarTplProperty('base', 'floatbox', 'showoutput', $data);
    }

    function showInput($args = array())
    {
        // Format the value, then pass it out to the parent class to display.
        $this->value = $this->_format_number($this->value);
        return parent::showInput($args);
    }

    /**
     * Format the numeric value into a string.
     */
    function _format_number($value)
    {
        // Only attempt to format a numeric value.
        // Return if we don't have one.
        if (!is_numeric($value)) return $value;

        // If the precision is 0 or less, and we are not forced to include the decimal,
        // then make the decimal part optional, otherwise pad it out to at least min-length
        // with zeros.
        // Precision can be negative.
        if (isset($this->precision) && isset($this->decimal_sep) && isset($this->grouping_sep)) {
            $value = number_format($value, $this->precision, $this->decimal_sep, $this->grouping_sep);

            // Strip the decimals if required.
            // Using a preg_match seems clumsy, but gets the job done.
            if ($this->precision <= 0 && empty($this->always_show_decimal) && $this->decimal_sep != '') {
                $value = preg_replace('/' . preg_quote($this->decimal_sep) . '.*/', '', $value);
            }
        } elseif (isset($this->number_format)) {
            // TODO: support a more generic and flexible number format string
        }

        if (!empty($this->trim_decimals)) {
            // Trailing zeros after the decimal are to be trimmed.
            $decimal_point = (isset($this->decimal_sep) ? $this->decimal_sep : '.');
            if (strpos($value, $decimal_point) !== false) $value = trim($value, '0' . $decimal_point);
        }

        // Add on any prefix or suffix (e.g. $ or %).
        if (!empty($this->number_prefix)) $value = $this->number_prefix . $value;
        if (!empty($this->number_suffix)) $value = $value . $this->number_suffix;

        return $value;
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
        );
        return $baseInfo;
    }

    // Trick: use the parent method with a different template :-)
    function showValidation($args = array())
    {
        // allow template override by child classes
        if (!isset($args['template'])) $args['template'] = 'floatbox';

        return parent::showValidation($args);
    }

    // default updateValidation() from Dynamic_TextBox_Property

}

?>
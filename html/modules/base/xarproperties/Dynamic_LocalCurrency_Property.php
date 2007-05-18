<?php
/**
 * Local Currency property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author Jason Judge <judgej@xaraya.com>
 */
include_once "modules/base/xarproperties/Dynamic_FloatBox_Property.php";

/**
 * Class to handle floatbox property
 *
 * @author mikespub <mikespub@xaraya.com>
 */
class Dynamic_LocalCurrency_Property extends Dynamic_FloatBox_Property
{
    var $size = 10;
    var $maxlength = 30;
    var $precision = NULL;

    // The localeMonetary structure will expand to:
    //  currencySymbol
    //  internationalCurrencySymbol
    //  decimalSeparator
    //  isDecimalSeparatorAlwaysShown
    //  groupingSeparator
    //  groupingSize
    //  fractionDigits +
    //      maximum
    //      minimum
    //  integerDigits +
    //      maximum
    //      minimum

    var $localeMonetary = array();


    // Convert the locale data into a nested array.
    // TODO: allow a subsection to be extracted without having
    // to process the whole thing.
    // TODO: more to the point, can we do this in the core somewhere?
    function locale2array($data)
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
                $return[$key2] = $this->locale2array($sub2);
            }
        }

        return $return;
    }

    /**
     * Constructor.
     * Fetch currency informatino from the current locale.
     * FIXME: we should be able to fetch from the *default* locale,
     * and not be limited by the locale the user happens to have chosen,
     * perhaps to select their language.
     */
    function Dynamic_LocalCurrency_Property()
    {
        $localeData = xarMLSLoadLocaleData();
        $data = $this->locale2array($localeData);
        $this->localeMonetary = $data['monetary'];

        if (isset($this->localeMonetary['fractionDigits']['maximum']) && is_numeric($this->localeMonetary['fractionDigits']['maximum'])) {
            $this->precision = (int)$this->localeMonetary['fractionDigits']['maximum'];
        }
    }

    // Check validation for allowed min/max values and precision
    // Syntax is:
    //  min:max:precision
    // Where the precision is the number of digits after the decimal point (negative precision is allowed).
    // All modifiers are optional.
    function parseValidation($validation = '')
    {
        if (is_string($validation) && strchr($validation, ':')) {
            $fields = explode(':', $validation);

            if (isset($fields[0]) && is_numeric($fields[0])) $this->min = $fields[0];
            if (isset($fields[1]) && is_numeric($fields[1])) $this->max = $fields[1];
            if (isset($fields[2]) && is_numeric($fields[2])) $this->precision = $fields[2];
        }
    }

    /**
     * Validate the value for this property
     * @return bool true when validated, false when not validated
     * 
     * To validate, strip out separator characters, strip out the currency symbol,
     * convert what is left to a float (taking into account the decimal character)
     * and that should provide the number value to store.
     */
    function validateValue($value = null)
    {
        if (!isset($value)) $value = $this->value;

        if (!isset($value) || $value === '') {
            // If not set, then default to min, max or zero
            if (isset($this->min)) {
                $this->value = $this->min;
            } elseif (isset($this->max)) {
                $this->value = $this->max;
            } else {
                $this->value = 0;
            }
        } else {
            // Strip out currency symbol, e.g. '$' in '$100'
            if (!empty($this->localeMonetary['currencySymbol'])) {
                $value = preg_replace('/' . preg_quote($this->localeMonetary['currencySymbol']) . '/', '', $value);
            }

            // Strip out separators, e.g. ',' in '100,000'
            if (!empty($this->localeMonetary['groupingSeparator'])) {
                $value = preg_replace('/' . preg_quote($this->localeMonetary['groupingSeparator']) . '/', '', $value);
            }

            // Convert the decimal separator to a '.'
            if (!empty($this->localeMonetary['decimalSeparator']) && $this->localeMonetary['decimalSeparator'] != '.') {
                $value = str_replace($this->localeMonetary['decimalSeparator'], '.', $value);
            }

            // Now we should have a number.

            if (!is_numeric($value)) {
                $this->invalid = xarML('invalid number');
                return false;
            }

            // Make sure we treat it as a number
            $value = (float)$value;

            // Check the precision, and round up/down as required.
            if (isset($this->precision)) {
                $value = round($value, $this->precision);
            }

            // Write the value back after making any changes to it.
            $this->value = $value;

            // Check min/max ranges
            if (isset($this->min) && isset($this->max) && ($this->min > $value || $this->max < $value)) {
                $this->invalid = xarML('value must be between #(1) and #(2)', $this->min, $this->max);
                return false;
            } elseif (isset($this->min) && $this->min > $value) {
                $this->invalid = xarML('value must be no less than #(1)', $this->min);
                return false;
            } elseif (isset($this->max) && $this->max < $value) {
                $this->invalid = xarML('value must be no greater than #(1)', $this->max);
                return false;
            }
        }

        return true;
    }

    /**
     * Format the currency value for displaying.
     */
    function format_currency($value)
    {
        // Only attempt to format a numeric value.
        // Return if we don't have one.
        if (!is_numeric($value)) return $value;

        // If the precision is 0 or less, and we are not forced to include the decimal,
        // then make the decimal part optional, otherwise pad it out to at least min-length
        // with zeros.
        if (isset($this->localeMonetary['groupingSeparator'])) {
            $grouping_sep = $this->localeMonetary['groupingSeparator'];
        } else {
            $grouping_sep = '';
        }
        if (isset($this->localeMonetary['decimalSeparator'])) {
            $decimal_sep = $this->localeMonetary['decimalSeparator'];
        } else {
            $decimal_sep = '.';
        }
        $value = number_format($value, 2, $decimal_sep, $grouping_sep);

        // The currency symbol goes at the start (this is an assumption, and
        // there is nothing in the locale data to tell us otherwise).
        if (isset($this->localeMonetary['currencySymbol'])) $value = $this->localeMonetary['currencySymbol'] . $value;

        return $value;
    }

    /**
     * Show the input form
     */
    function showInput($args = array())
    {
        // Format the value according to the locale data and supplied rules.
        // TODO: move the format to a shared method for use with showOutput() too.
        // TODO: Can only format the value if it is numeric. If it is non-numeric, then
        // display it as it comes.
        $this->value = $this->format_currency($this->value);

        // Pass to the parent class to handle the rest.
        // TODO: the field class should indicate it is numeric, so the columns are aligned correctly.
        return parent::showInput($args);
        // or return Dynamic_TextBox_Property::showInput($args);
    }

    /**
     * Show the input for the float property
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
        }

        // TODO: do the XML encoding in the template, since we may be sending
        // data to a template that does not require such encoding.
        $data['value'] = xarVarPrepForDisplay($this->format_currency($value));

        $template = '';

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
            'id'            => 48,
            'name'          => 'localcurrency',
            'label'         => 'Local Currency',
            'format'        => '48',
            'validation'    => '',
            'source'        => '',
            'dependancies'  => '',
            'requiresmodule' => '',
            'aliases'       => '',
            'args'          => serialize($args),
        );

        return $baseInfo;
    }

    // Ese the parent method with a different template
    function showValidation($args = array())
    {
        // Allow template override by child classes
        if (!isset($args['template'])) {
            $args['template'] = 'floatbox';
        }

        return parent::showValidation($args);
    }

}

?>
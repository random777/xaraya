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
 * @author judgej <judgej@xaraya.com>
 */

class Dynamic_LocalCurrency_Property extends Dynamic_FloatBox_Property
{
    var $size = 12;
    var $maxlength = 30;

    var $datatype = 'currency';

    // TODO: indicate field alignment = 'right'
    // TODO: the grouping seperator, decimal separator and precision could be
    // handled by the the 'float' property, with just the currency character being
    // added and removed (on submit) by this property.

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

    /**
     * Constructor.
     * Fetch currency informatino from the current locale.
     * FIXME: we should be able to fetch from the *default* locale,
     * and not be limited by the locale the user happens to have chosen,
     * perhaps to select their language.
     */
    function Dynamic_LocalCurrency_Property($args)
    {
        $localeData = xarMLSLoadLocaleData();
        $data = $this->_locale2array($localeData);
        $this->localeMonetary = $data['monetary'];

        if (!isset($this->precision) && isset($this->localeMonetary['fractionDigits']['maximum']) && is_numeric($this->localeMonetary['fractionDigits']['maximum'])) {
            $this->precision = (int)$this->localeMonetary['fractionDigits']['maximum'];
        }

        // Set some defaults from the locale.
        // These could be set (overridden) by a descendant class if required.
        if (!isset($this->grouping_sep)) {
            $this->grouping_sep = (isset($this->localeMonetary['groupingSeparator']) ? $this->localeMonetary['groupingSeparator'] : '');
        }

        if (!isset($this->decimal_sep)) {
            $this->decimal_sep = (isset($this->localeMonetary['decimalSeparator']) ? $this->localeMonetary['decimalSeparator'] : '.');
        }

        if (!isset($this->number_prefix)) {
            $this->number_prefix = (isset($this->localeMonetary['currencySymbol']) ? $this->localeMonetary['currencySymbol'] : '?');
        }

        if (!isset($this->always_show_decimal)) {
            $this->always_show_decimal = (!empty($this->localeMonetary['isDecimalSeparatorAlwaysShown']) ? true : false);
        }

        // Call parent constructor to finish off the job.
        return parent::Dynamic_FloatBox_Property($args);
    }

    // Check validation for allowed min/max values and precision
    // Syntax is:
    //  min:max:precision:regex
    // Where the precision is the number of digits after the decimal point (negative precision is allowed).
    // All modifiers are optional, but at least one ':' must appear.
    // TODO: move all this parsing to the core, so it does not need to be done in each
    // property separately.
    function parseValidation($validation = '')
    {
        if (is_string($validation) && strchr($validation, ':')) {
            $fields = explode(':', $validation, 4);

            if (isset($fields[0]) && is_numeric($fields[0])) $this->min = $fields[0];
            if (isset($fields[1]) && is_numeric($fields[1])) $this->max = $fields[1];
            if (isset($fields[2]) && is_numeric($fields[2])) $this->precision = $fields[2];
            if (isset($fields[3]) && is_numeric($fields[3])) $this->regex = $fields[3];
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
            // Strip out prefix or suffix symbol, e.g. '$' in '$100' or '%' in '0.2%'
            if (!empty($this->number_prefix)) {
                $value = preg_replace('/^' . preg_quote($this->number_prefix, '/') . '/', '', $value);
            }

            if (!empty($this->number_suffix)) {
                $value = preg_replace('/' . preg_quote($this->number_suffix, '/') . '$/', '', $value);
            }

            // Strip out separators, e.g. ',' in '100,000'
            if (!empty($this->grouping_sep)) {
                $value = preg_replace('/' . preg_quote($this->grouping_sep) . '/', '', $value);
            }

            // Convert the decimal separator to a '.'
            if (!empty($this->decimal_sep) && $this->decimal_sep != '.') {
                $value = str_replace($this->decimal_sep, '.', $value);
            }

            // Now we should have a number.

            if (!is_numeric($value)) {
                $this->invalid = xarML('invalid number');
                $this->value = $value;
                return false;
            }

            // Make sure we treat it as a number
            $value = (float)$value;

            // Check the precision, and round up/down as required.
            if (isset($this->precision) && is_numeric($this->precision)) {
                $value = round($value, $this->precision);
            }

            // Write the value back after making any changes to it.
            $this->value = $value;

            // Check min/max ranges
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
        }

        return true;
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
        $this->value = $this->_format_number($this->value);

        // Pass to the parent class to handle the rest.
        // TODO: the field class should indicate it is numeric, so the columns are aligned correctly.
        return parent::showInput($args);
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

        if (!empty($value) && !empty($field->validation)) {}

        // TODO: do the XML encoding in the template, since we may be sending
        // data to a template that does not require such encoding.
        $data['value'] = xarVarPrepForDisplay($this->_format_number($value));

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
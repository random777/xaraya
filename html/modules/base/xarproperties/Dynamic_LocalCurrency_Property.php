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
}

?>
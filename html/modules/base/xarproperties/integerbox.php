<?php
/**
 * @package modules
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
sys::import('modules.base.xarproperties.textbox');
/**
 * Handle the numberbox property
 */
class NumberBoxProperty extends TextBoxProperty
{
    public $id         = 15;
    public $name       = 'integerbox';
    public $desc       = 'Number Box';
    public $basetype   = 'number';

    public $validation_min_value           = null;
    public $validation_min_value_invalid;
    public $validation_max_value           = null;
    public $validation_max_value_invalid;
    public $display_size                   = 10;
    public $display_maxlength              = 30;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        if (!is_numeric($this->value) && !empty($this->value)) throw new Exception(xarML('The default value of a #(1) must be numeric',$this->name));
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        // We might have picked up empty string values in the configuration
        if ($this->validation_min_value == "") $this->validation_min_value = null;
        if ($this->validation_max_value == "") $this->validation_max_value = null;

        if (!isset($value) || $value === '') {
            if (isset($this->validation_min_value)) {
                $this->setValue($this->validation_min_value);
            } elseif (isset($this->validation_max_value)) {
                $this->setValue($this->validation_max_value);
            } else {
                $this->setValue();
            }
        } elseif (is_numeric($value)) {
            $value = $this->castType($value);
            if (isset($this->validation_min_value) && isset($this->validation_max_value) && ($this->validation_min_value > $value || $this->validation_max_value < $value)) {
                $this->invalid = xarML('numnber: allowed range is between #(1) and #(2)',$this->validation_min_value,$this->validation_max_value);
                $this->setValue();
                return false;
            } elseif (isset($this->validation_min_value) && $this->validation_min_value > $value) {
                if (!empty($this->validation_min_value_invalid)) {
                    $this->invalid = xarML($this->validation_min_value_invalid);
                } else {
                    $this->invalid = xarML('numnber: must be #(1) or more',$this->validation_min_value);
                }
                $this->setValue();
                return false;
            } elseif (isset($this->validation_max_value) && $this->validation_max_value < $value) {

                if (!empty($this->validation_max_value_invalid)) {
                    $this->invalid = xarML($this->validation_max_value_invalid);
                } else {
                    $this->invalid = xarML('numnber: must be #(1) or less',$this->validation_max_value);
                }
                $this->setValue();
                return false;
            }
        } else {
            $this->invalid = xarML('numnber: #(1)', $this->name);
            $this->setValue();
            return false;
        }
        $this->value = $value;
        return true;
    }

    public function castType($value = null)
    {
        if (!is_null($value)) return (int) $value;
    }
}
?>

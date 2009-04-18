<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
/* include the base class */
sys::import('modules.base.xarproperties.checkboxlist');
/**
 * Handle checkbox mask property
 */
class CheckboxMaskProperty extends CheckboxListProperty
{
    public $id         = 1114;
    public $name       = 'checkboxmask';
    public $desc       = 'Checkbox Mask';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template =  'checkboxmask';
    }

    public function validateValue($value = null)
    {
        if (!SelectProperty::validateValue($value)) return false;

        if(is_array($value)) {
            $this->value = maskImplode($value);
        }

        return true;
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        else $this->value = $data['value'];

        if (!isset($data['value'])) $data['value'] = $this->value;
        else $this->value = $data['value'];

        if (empty($data['value'])) {
            $data['value'] = array();
        } elseif (!is_array($data['value']) && is_string($data['value'])) {
            $data['value'] = maskExplode(',', $data['value']);
        }
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;
        if (!is_array($value)) $value = maskExplode($value);

        $this->getOptions();
        $numOptionsSelected = 0;
        $options = array();
        foreach($this->options as $key => $option)
        {
            $option['checked'] = in_array($option['id'], $value);
            $options[$key] = $option;
            if ($option['checked']) {
                $numOptionsSelected++;
            }
        }

        $data['options'] = $options;
        $data['numOptionsSelected'] = $numOptionsSelected;

        return parent::showOutput($data);
    }
}

function maskImplode($anArray)
{
    $output = '';
    if(is_array($anArray)) {
        foreach($anArray as $entry) {
            $output .= $entry;
        }
    }
    return $output;
}

function maskExplode($aString)
{
    return explode(',', substr(chunk_split($aString, 1, ','), 0, -1));
}
?>

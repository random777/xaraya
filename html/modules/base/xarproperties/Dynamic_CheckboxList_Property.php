<?php
/**
 * Checkbox List Property
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/*
 * @author mikespub <mikespub@xaraya.com>
 */

include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Class to handle check box list property
 *
 * @package dynamicdata
 */
class Dynamic_CheckboxList_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'checkboxlist';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id   = 1115;
        $info->name = 'checkboxlist';
        $info->desc = 'Checkbox List';
		$info->filepath   = 'modules/base/xarproperties';

        return $info;
    }

    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            xarVarFetch($name, 'isset', $value,  NULL, XARVAR_NOT_REQUIRED);
        }
        return $this->validateValue($value);
    }


    function validateValue($value = null)
    {
        // this won't do for check boxes !
        //if (!isset($value)) {
        //    $value = $this->value;
        //}

        if (!isset($value)) {
            $this->value = '';
        } elseif ( is_array($value) ) {
            $this->value = implode ( ',', $value);
        } else {
            $this->value = $value;
        }

        return true;
    }

    function showInput($data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;

        if (empty($data['value'])) {
            $data['value'] = array();
        } elseif (!is_array($data['value']) && is_string($data['value'])) {
            $data['value'] = explode(',', $data['value']);
        }

        if (!isset($data['options']) || count($data['options']) == 0) {
            $options = $this->getOptions();
        } else {
            $options = $data['options'];
        }
        foreach ($options as $key => $option) {
            $option['checked'] = in_array($option['id'],$data['value']);
            $data['options'][$key] = $option;
        }
        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        if (!isset($data['value'])) $data['value'] = $this->value;
        if (is_array($data['value']) ) $data['value'] = implode(',',$data['value']);
        return parent::showOutput($data);
    }
}

?>
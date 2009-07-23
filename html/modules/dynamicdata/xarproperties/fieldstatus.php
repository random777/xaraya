<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
*/
sys::import('modules.base.xarproperties.dropdown');

/**
 * Handle field status property
 */
class FieldStatusProperty extends SelectProperty
{
    public $id         = 25;
    public $name       = 'fieldstatus';
    public $desc       = 'Field Status';
    public $reqmodules = array('dynamicdata');

    // CHANGEME: make this a configuration?
    public $initialization_display_status = DynamicData_Property_Master::DD_DISPLAYSTATE_ACTIVE;
    public $initialization_input_status   = DynamicData_Property_Master::DD_INPUTSTATE_ADDMODIFY;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath   = 'modules/dynamicdata/xarproperties';
        $this->tplmodule  =  'dynamicdata';
        $this->template   =  'fieldstatus';
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }

        $valuearray['display'] = $value & DynamicData_Property_Master::DD_DISPLAYMASK;
        $valuearray['input'] = $value & 992;

        // if the input part is 0 then we need to display default values
        if (empty($valuearray['input'])) {
            $valuearray['display'] = $this->initialization_display_status;
            $valuearray['input'] = $this->initialization_input_status;
        }

        $data['value'] = $valuearray;

        if(!isset($data['onchange'])) $data['onchange'] = null; // let tpl decide what to do
        $data['extraparams'] =!empty($extraparams) ? $extraparams : "";
        return parent::showInput($data);
    }

    public function checkInput($name = '', $value = null)
    {
        if (empty($name)) {
            $inputname = 'input_dd_'.$this->id;
            $displayname = 'display_dd_'.$this->id;
        } else {
            $inputname = 'input_'.$name;
            $displayname = 'display_'.$name;
        }
        // store the fieldname for configurations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if(!xarVarFetch($displayname, 'isset', $display_status, NULL, XARVAR_DONT_SET)) {return;}
            if(!xarVarFetch($inputname,   'isset', $input_status,   NULL, XARVAR_DONT_SET)) {return;}
        }
        $value = $display_status + $input_status;
        return $this->validateValue($value);
    }

    public function validateValue($value = null)
    {
        // FIXME: rework the dataproperty so that the output of getOptions has a correct form
        // and ew can call the parent method here
        // if (!parent::validateValue($value)) return false;

        if (empty($value)) {
            $value = DynamicData_Property_Master::DD_DISPLAYSTATE_ACTIVE + DynamicData_Property_Master::DD_INPUTSTATE_ADDMODIFY;
        }

        // Just really check whether we're in bounds. Don't think more is required
        if (($value >= DynamicData_Property_Master::DD_DISPLAYSTATE_DISABLED) &&
            ($value <= DynamicData_Property_Master::DD_INPUTSTATE_MODIFY)) {
            return true;
        }
        return false;
    }

    function getOptions()
    {
        $options['display'] = array(
                             array('id' => DynamicData_Property_Master::DD_DISPLAYSTATE_ACTIVE, 'name' => xarML('Active')),
                             array('id' => DynamicData_Property_Master::DD_DISPLAYSTATE_VIEWONLY, 'name' => xarML('View only')),
                             array('id' => DynamicData_Property_Master::DD_DISPLAYSTATE_DISPLAYONLY, 'name' => xarML('Display only')),
                             array('id' => DynamicData_Property_Master::DD_DISPLAYSTATE_HIDDEN, 'name' => xarML('Hidden')),
                             array('id' => DynamicData_Property_Master::DD_DISPLAYSTATE_IGNORED, 'name' => xarML('Ignored')),
                             array('id' => DynamicData_Property_Master::DD_DISPLAYSTATE_DISABLED, 'name' => xarML('Disabled')),
                         );
        $options['input'] = array(
                             array('id' => DynamicData_Property_Master::DD_INPUTSTATE_NOINPUT, 'name' => xarML('No input allowed')),
                             array('id' => DynamicData_Property_Master::DD_INPUTSTATE_ADD, 'name' => xarML('Can be added')),
                             array('id' => DynamicData_Property_Master::DD_INPUTSTATE_MODIFY, 'name' => xarML('Can be changed')),
                             array('id' => DynamicData_Property_Master::DD_INPUTSTATE_ADDMODIFY, 'name' => xarML('Can be added/changed')),
                         );
        return $options;
    }
    function getOption($check = false)
    {
        //TODO: get this working
    }
}
?>

<?php

sys::import('modules.dynamicdata.class.properties.master');

/**
 * Base Class for Dynamic Properties
 *
 * @package Xaraya eXtensible Management System
 * @subpackage dynamicdata module
 * @todo is this abstract?
 * @todo the visibility of most of the attributes can probably be protected
**/
class DataProperty extends Object
{
    // Attributes for registration
    public $id             = 0;
    public $name           = 'propertyName';
    public $desc           = 'propertyDescription';
    public $label          = 'Property Label';
    public $type           = 1;
    public $default        = '';
    public $source         = 'dynamic_data';
    public $status         = DataPropertyMaster::DD_DISPLAYSTATE_ACTIVE;
    public $order          = 0;
    public $format         = '0'; //<-- eh?
    public $reqmodules     = array();  // these modules must be available before this property is enabled (optional)
    public $aliases        = '';  // If the same property class is reused directly with just different base info, supply the alternate base properties here (optional)
    public $filepath       = 'modules/dynamicdata/xarproperties';         // where is our class for it?

    // Attributes for runtime
    public $template = '';
    public $layout = '';
    public $tplmodule = 'dynamicdata';
    public $validation = '';
    public $dependancies = '';    // semi-colon seperated list of files that must be present for this property to be available (optional)
    public $args           = array();

    public $datastore = '';   // name of the data store where this property comes from

    public $value = null;     // value of this property for a particular DataObject
    public $invalid = '';     // result of the checkInput/validateValue methods

    public $_objectid = null; // objectid this property belongs to
    public $_moduleid = null; // moduleid this property belongs to
    public $_itemtype = null; // itemtype this property belongs to

    public $_itemid;          // reference to $itemid in DataObject, where the current itemid is kept
    public $_items;           // reference to $items in DataObjectList, where the different item values are kept

    /**
     * Default constructor setting the variables
     */
    function __construct(array $args)
    {
        $this->template = $this->getTemplate();
        $this->args = serialize(array());

        if(!empty($args) && count($args) > 0)
            foreach($args as $key => $val)
                $this->$key = $val;

        if(!isset($args['value']))
        {
            // if the default field looks like xar<something>(...), we'll assume that this is
            // a function call that returns some dynamic default value
            if(!empty($this->default) && preg_match('/^xar\w+\(.*\)$/',$this->default))
            {
                eval('$value = ' . $this->default .';');
                if(isset($value))
                    $this->default = $value;
                else
                    $this->default = null;
            }
            $this->value = $this->default;
        }
    }

    /**
     * Get the value of this property (= for a particular object item)
     *
     * @returns mixed
     * @return the value for the property
     */
    function getValue()
    {
        return $this->value;
    }

    /**
     * Set the value of this property (= for a particular object item)
     *
     * @param $value the new value for the property
     */
    function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Check the input value of this property
     *
     * @param $name name of the input field (default is 'dd_NN' with NN the property id)
     * @param $value value of the input field (default is retrieved via xarVarFetch())
     */
    function checkInput($name = '', $value = null)
    {
        if(!isset($value))
        {
            $isvalid = true;
            xarVarFetch('dd_'.$this->id, 'isset', $ddvalue,  NULL, XARVAR_DONT_SET);
            if(isset($ddvalue))
                $value = $ddvalue;
            else
            {
                xarVarFetch($this->name, 'isset', $fieldvalue,  NULL, XARVAR_DONT_SET);
                if(isset($fieldvalue))
                    $value = $fieldvalue;
                else
                {
                    xarVarFetch($name, 'isset', $namevalue,  NULL, XARVAR_DONT_SET);
                    if(isset($namevalue))
                        $value = $namevalue;
                    else
                        $isvalid = false;
                }
            }
            /*
            // TODO: review this. The thinking is that an exception cannot be reliably thrown
            // from within this method.
            if(!$isvalid) {
                $msg = 'Field "#(1)" (dd_#(2)) is missing.';
                if(!empty($name)) {
                    $vars = array($name,$this->id);
                } else {
                    $vars = array($this->name,$this->id);
                }
                throw new BadParameterException($vars,$msg);
                return false;
            }
            */
            // store the fieldname for validations who need them (e.g. file uploads)
            $name = empty($name) ? 'dd_'.$this->id : $name;
            $this->fieldname = $name;
        }
       return $this->validateValue($value);
    }

    /**
     * Validate the value of this property
     *
     * @param $value value of the property (default is the current value)
     */
    function validateValue($value = null)
    {
        if(!isset($value))
            $value = $this->value;

        $this->value = null;
        $this->invalid = xarML('unknown property');
        return false;
    }

    /**
     * Get the value of this property for a particular item (= for object lists)
     *
     * @param $itemid the item id we want the value for
     */
    function getItemValue($itemid)
    {
        return $this->_items[$itemid][$this->name];
    }

    /**
     * Set the value of this property for a particular item (= for object lists)
     */
    function setItemValue($itemid, $value)
    {
        $this->_items[$itemid][$this->name] = $value;
    }

    /**
     * Show an input field for setting/modifying the value of this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @param $args['module'] which module is responsible for the templating
     * @param $args['template'] what's the partial name of the showinput template.
     * @param $args[*] rest of arguments is passed on to the templating method.
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showInput($data = array())
    {
        if(($this->status & DataPropertyMaster::DD_DISPLAYMASK) == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
            return $this->showHidden($data);

        // Our common items we need
        if(!isset($data['name']))     $data['name']     = 'dd_'.$this->id;
        if(!isset($data['id']))       $data['id']       = $data['name'];
        // mod for the tpl and what tpl the prop wants.

        if(!isset($data['module']))   $data['module']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->layout;

        if(!isset($data['tabindex'])) $data['tabindex'] = 0;
        if(!isset($data['value']))    $data['value']    = '';
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        // debug($data);
        // Render it
        return xarTplProperty($data['module'], $data['template'], 'showinput', $data);
    }

    /**
     * Show some default output for this property
     *
     * @param $args['value'] value of the property (default is the current value)
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showOutput($data = array())
    {
        if(($this->status & DataPropertyMaster::DD_DISPLAYMASK) == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
            return $this->showHidden($data);

        $data['id']   = $this->id;
        $data['name'] = $this->name;

        if(!isset($data['value'])) $data['value'] = $this->value;
        // TODO: does this hurt when it is an array?
        if(!isset($data['module']))   $data['module']   = $this->tplmodule;
        if(!isset($data['template'])) $data['template'] = $this->template;
        if(!isset($data['layout']))   $data['layout']   = $this->layout;
        // Render it
        return xarTplProperty($data['module'], $data['template'], 'showoutput', $data);
    }

    /**
     * Show the label for this property
     *
     * @param $args['label'] label of the property (default is the current label)
     * @param $args['for'] label id to use for this property (id, name or nothing)
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showLabel($args = array())
    {
        if(($this->status & DataPropertyMaster::DD_DISPLAYMASK) == DataPropertyMaster::DD_DISPLAYSTATE_HIDDEN)
            return $this->showHidden($args);

        if(empty($args))
        {
            // old syntax was showLabel($label = null)
        }
        elseif(is_string($args))
            $label = $args;
        elseif(is_array($args))
            extract($args);

        $data = array();
        $data['id']    = $this->id;
        $data['name']  = $this->name;
        $data['label'] = isset($label) ? xarVarPrepForDisplay($label) : xarVarPrepForDisplay($this->label);
        $data['for']   = isset($for) ? $for : null;

        if(!isset($template))
            $template = null;

        return xarTplProperty('dynamicdata', $template, 'label', $data);
    }

    /**
     * Show a hidden field for this property
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showHidden($args = array())
    {
        extract($args);

        $data = array();
        $data['name']     = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']       = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if(!isset($template))
            $template = null;
        return xarTplProperty('dynamicdata', $template, 'showhidden', $data);
    }

    /**
     * For use in DD tags : preset="yes" - this can typically be used in admin-new.xd templates
     * for individual properties you'd like to automatically preset via GET or POST parameters
     *
     * Note: don't use this if you already check the input for the whole object or in the code
     * See also preview="yes", which can be used on the object level to preview the whole object
     *
     * @access private (= do not sub-class)
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['value'] value of the field (default is the current value)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function _showPreset($args = array())
    {
        // Check for empty here instead of isset, e.g. for <xar:data-input ... value="" />
        if(empty($args['value']))
        {
            if(empty($args['name']))
                $isvalid = $this->checkInput();
            else
                $isvalid = $this->checkInput($args['name']);

            if($isvalid)
                // remove the original input value from the arguments
                unset($args['value']);
            else
                // clear the invalid message for preset
                $this->invalid = '';
        }

        if(!empty($args['hidden']))
            return $this->showHidden($args);
        else
            return $this->showInput($args);
    }

    /**
     * Parse the validation rule
     */
    function parseValidation($validation = '')
    {
        // if(... $validation ...) {
        //     $this->whatever = ...;
        // }
    }

    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
    function getBasePropertyInfo()
    {
        $baseInfo = array(
            'id'         => $this->id,
            'name'       => $this->name,
            'label'      => $this->label,
            'format'     => $this->format,
            'template'   => $this->template,
            'tplmodule'  => $this->tplmodule,
            'validation' => $this->validation,
            'source'     => $this->source,
            'dependancies' => $this->dependancies,
            'requiresmodule' => $this->requiresmodule,
            'aliases' => $this->aliases,
            'args' => $this->args
                          // ...
        );
        return $baseInfo;
    }

    /**
     * The following methods provide an interface to show and update validation rules
     * when editing dynamic properties. They should be customized for each property
     * type, based on its specific format and interpretation of the validation rules.
     *
     * This allows property type developers to support more complex validation rules,
     * while keeping them easy to modify for the site admins afterwards.
     *
     * If no validation methods are specified for a particular property type, the
     * corresponding methods from its parent class will be used.
     *
     * Note: the methods can be called by DD's showpropval() function, or if you set the
     *       type of the 'validation' property (21) to ValidationProperty also
     *       via DD's modify() and update() functions if you edit some dynamic property.
     */

    /**
     * Show the current validation rule in a specific form for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @returns string
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showValidation($args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;
        $data['size']       = !empty($size) ? $size : 50;

        if(isset($validation))
        {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }
        // some known validation rule format
        // if(... $this->whatever ...) {
        //     $data['whatever'] = ...
        //
        // if we didn't match the above format
        // } else {
        $data['other'] = xarVarPrepForDisplay($this->validation);
        // }

        // allow template override by child classes
        if(!isset($template))
            $template = null;

        return xarTplProperty('dynamicdata', $template, 'validation', $data);
    }

    /**
     * Update the current validation rule in a specific way for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @return bool true if the validation rule could be processed, false otherwise
     */
    function updateValidation($args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        if(empty($name))
            $name = 'dd_'.$this->id;

        // do something with the validation and save it in $this->validation
        if(isset($validation))
        {
            if(is_array($validation))
            {
                // handle arrays as you like in your property type
                // $this->validation = serialize($validation);
                $this->validation = '';
                $this->invalid = 'array';
                return false;
            }
            else
                $this->validation = $validation;
        }

        // tell the calling function that everything is OK
        return true;
    }

    /**
     * Return the module this property belongs to
     *
     * @returns string module name
     */
    function getModule()
    {
        $modulename = empty($this->tplmodule) ? $info['tplmodule'] : $this->tplmodule;
        return $modulename;
    }
    /**
     * Return the name this property uses in its templates
     *
     * @returns string template name
     */
    function getTemplate()
    {
        // If not specified, default to the registered name of the prop
        $template = empty($this->template) ? $this->name : $this->template;
        return $template;
    }
    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = $this->reqmodules;
        $info->id   = $this->id;
        $info->name = $this->name;
        $info->desc = $this->desc;

        return $info;
    }
    function aliases()
    {
        return array();
    }
}
?>
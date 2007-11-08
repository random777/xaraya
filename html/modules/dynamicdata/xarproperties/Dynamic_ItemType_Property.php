<?php
/**
 * Dynamic Item Type property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * Include the base class
 */
include_once "modules/base/xarproperties/Dynamic_NumberBox_Property.php";

/**
 * Handle the item type property
 *
 * @package dynamicdata
 */
class Dynamic_ItemType_Property extends Dynamic_NumberBox_Property
{
    var $module   = ''; // get itemtypes for this module with getitemtypes()
    var $itemtype = null; // get items for this module+itemtype with getitemlinks()
    var $func     = null; // specific API call to retrieve a list of items
    var $options  = null;
    var $multiselect = false; // Allow multiple pubtypes to be selected.

    function Dynamic_ItemType_Property($args)
    {
        $this->Dynamic_NumberBox_Property($args);

        // options may be set in one of the child classes
        if (count($this->options) == 0 && !empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
    }

    function validateValue($value = null)
    {
        if (isset($value)) {
            if (is_array($value)) {
                $this->value = implode(',', $value);
            } else {
                $this->value = $value;
            }
        }

        if (empty($this->module)) {
            // No module name given, so this will be a simple text box.
            if ($this->multiselect) {
                // Validate as a list of integers.
                $result = xarVarValidate('strlist:, :id', $this->value, true);
                if (!$result) $this->invalid = xarML('List of integers required, got #(1)', $this->value);
            } else {
                // Validate as a single integer.
                $result = xarVarValidate('id', $this->value, true);
                if (!$result) $this->invalid = xarML('Single integer required, got #(1)', $this->value);
            }
            return $result;
        }

        // Check if this option or options really exist.
        if ($this->multiselect) {
            $options = explode(',', $this->value);
            foreach($options as $option) {
                $result = $this->checkOption($option);
                if (!$result) {
                    $this->invalid = xarML('Not a valid itemtype #(1)', $option);
                    break;
                }
            }
        } else {
            $result = $this->checkOption($this->value);
            if (!$result) $this->invalid = xarML('Not a valid itemtype #(1)', $this->value);
        }

        return $result;
    }

    function showInput($args = array())
    {
        if (!empty($args)) $this->setArguments($args);

        if (empty($this->module)) {
            // Let Dynamic_NumberBox_Property handle the rest (just a number box)
            return parent::showInput($args);
        }

        $data = array();
        $data['options']  = $this->getOptions();
        if (empty($data['options'])) {
            // Let Dynamic_NumberBox_Property handle the rest
            return parent::showInput($args);
        }

        $data['value']    = ($this->multiselect ? explode(',', $this->value) : $this->value);

        $data['name']     = (!empty($this->fieldname) ? $this->fieldname : 'dd_' . $this->id);
        $data['id']       = (!empty($args['id']) ? $args['id'] : $data['name']);
        $data['tabindex'] = (!empty($args['tabindex']) ? $args['tabindex'] : 0);
        $data['invalid']  = (!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '');
        $data['multiselect'] = $this->multiselect;
        $data['size'] = $this->size;

        if (empty($args['template'])) {
            $args['template'] = 'itemtype';
        }

        return xarTplProperty('dynamicdata', $args['template'], 'showinput', $data);
    }

    function showOutput($args = array())
    {
        if (!empty($args)) $this->setArguments($args);

        if (empty($this->module)) {
            // let Dynamic_NumberBox_Property handle the rest
            return parent::showOutput($args);
        }

        $data = array();
        $data['value'] = $this->value;
        $data['multiselect'] = $this->multiselect;

        if ($this->multiselect) {
            $options = explode(',', $this->value);
            $data['options'] = array();
            foreach($options as $option) {
                $data['options'][] = array('id' => $this->value, 'name' => $this->getOption($option['id']));
            }
        } else {
            $data['option'] = array('id' => $this->value, 'name' => $this->getOption($this->value));
        }

        if (empty($args['template'])) {
            $args['template'] = 'itemtype';
        }

        return xarTplProperty('dynamicdata', $args['template'], 'showoutput', $data);
    }

    // It is not clear what this method does.
    function setArguments($args = array())
    {
        if (!empty($args['module']) &&
            preg_match('/^\w+$/',$args['module']) &&
            xarModIsAvailable($args['module'])) {

            $this->module = $args['module'];
            if (isset($args['itemtype']) && is_numeric($args['itemtype'])) {
                $this->itemtype = $args['itemtype'];
            }
            if (isset($args['func']) && is_string($args['func'])) {
                $this->func = $args['func'];
            }
        }

        if (!empty($args['name'])) {
            $this->fieldname = $args['name'];
        }

        // could be 0 here
        if (isset($args['value']) && is_numeric($args['value'])) {
            $this->value = $args['value'];
        }

        if (!empty($args['options']) && is_array($args['options'])) {
            $this->options = $args['options'];
        }
    }

    /**
     * Possible formats
     *
     *   module
     *       show the list of itemtypes for that module via getitemtypes()
     *       E.g. "articles" = the list of publication types in articles
     *
     *   module.itemtype
     *       show the list of items for that module+itemtype via getitemlinks()
     *       E.g. "articles.1" = the list of articles in publication type 1 News Articles
     *
     *   module.itemtype:xarModAPIFunc(...)
     *       show some list of "item types" for that module via xarModAPIFunc(...)
     *       and use itemtype to retrieve individual items via getitemlinks()
     *       E.g. "articles.1:xarModAPIFunc('articles','user','dropdownlist',array('ptid' => 1, 'where' => ...))"
     *       = some filtered list of articles in publication type 1 News Articles
     *
     *   An additional third parameter is supported, with a value of 0 or 1 (defaut 0)
     *   This parameter allows multiple publication types to be selected if set (1).
     *
     *   TODO: support 2nd API call to retrieve the item in case getitemlinks() isn't supported
     */
    function parseValidation($validation = '')
    {
        $parts = explode(':', $validation, 4);

        // See if the validation field contains a valid module name
        if (!empty($parts[0])) {
            $modparts = explode('.', $parts[0], 2);
            // The module is valid
            if (xarModIsAvailable($modparts[0])) {
                $this->module = $modparts[0];

                // The module has an itemtype specified.
                if (isset($modparts[1]) && is_numeric($modparts[1])) $this->itemtype = $modparts[1];

                // The custom function is specified.
                if (!empty($parts[1]) && preg_match('/^xarModAPIFunc.*/i', $parts[1])) {
                    $this->func = $parts[1];
                }
            }
        }

        // The multiselect flag is set.
        if (!empty($parts[2]) && preg_match('/^[1YT]$/i', $parts[2])) $this->multiselect = true;
    }

    /**
     * Retrieve the list of itemtype options or a single option.
     */
    function getOptions($p_id = NULL)
    {
        if (!empty($this->options) && !isset($p_id)) {
            // Return the full list available.
            return $this->options;
        }

        // Nothing to return.
        if (empty($this->module)) $this->options = array();

        if (!isset($this->options)) {
            // We have no options yet - attempt to get some.
            $options = array();
            if (!isset($this->itemtype)) {
                // We are interested in the module itemtypes (= default behaviour)
                // Do not throw an exception if this function does not exist.
                $itemtypes = xarModAPIFunc($this->module, 'user', 'getitemtypes', array(), 0);

                if (!empty($itemtypes)) {
                    foreach ($itemtypes as $typeid => $typeinfo) {
                        if (isset($typeid) && isset($typeinfo['label'])) {
                            $options[] = array('id' => $typeid, 'name' => $typeinfo['label']);
                        }
                    }
                }
            } elseif (empty($this->func)) {
                // We are interested in the items for module+itemtype
                // Do not throw an exception if this function does not exist
                $itemlinks = xarModAPIFunc($this->module, 'user', 'getitemlinks',
                    array('itemtype' => $this->itemtype, 'itemids'  => null), 0
                );

                if (!empty($itemlinks)) {
                    foreach ($itemlinks as $itemid => $linkinfo) {
                        if (isset($itemid) && isset($linkinfo['label'])) {
                            $options[] = array('id' => $itemid, 'name' => $linkinfo['label']);
                        }
                    }
                }
            } else {
                // We have some specific function to retrieve the items here.
                eval('$items = ' . $this->func . ';');
                if (isset($items) && count($items) > 0) {
                    foreach ($items as $id => $name) {
                        // Skip empty items from e.g. dropdownlist() API
                        if (empty($id) && empty($name)) continue;
                        array_push($options, array('id' => $id, 'name' => $name));
                    }
                }
            }

            $this->options = $options;
        }

        // Return either the full list or an individual item.
        if (!empty($p_id)) {
            if (isset($this->options[$p_id])) {
                // Return individual item.
                foreach ($this->options as $option) {
                    if ($option['id'] == $p_id) return $option['name'];
                }

                // Individual item was not found.
                return;
            }
        } else {
            return $this->options;
        }
    }

    /**
     * Check whether a single option is valid.
     */
    function checkOption($id)
    {
        // An empty id (zero or not set) is valid.
        if (empty($id)) return true;

        // Look up the option.
        $name = $this->getOptions($id);

        // If set, then the option is valid.
        return isset($name);
    }

    
    /**
     * Retrieve or check an individual option on demand
     */
    function getOption($id)
    {
        return $this->getOptions($id);
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
            'id'         => 20,
            'name'       => 'itemtype',
            'label'      => 'Item Type',
            'format'     => '20',
            'validation' => '',
            'source'         => '',
            'dependancies'   => '',
            'requiresmodule' => 'dynamicdata',
            'aliases'        => '',
            'args'           => serialize($args),
        );
        return $baseInfo;
     }


    /**
     * Show the current validation rule in a specific form for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @param $args['tabindex'] tab index of the field
     * @return string containing the HTML (or other) text to output in the BL template
     */
    function showValidation($args = array())
    {
        extract($args);

        $data = array();
        $data['name']      = (!empty($name) ? $name : 'dd_' . $this->id);
        $data['id']        = (!empty($id)   ? $id   : 'dd_' . $this->id);
        $data['tabindex']  = (!empty($tabindex) ? $tabindex : 0);
        $data['size']      = (!empty($size) ? $size : 50);
        $data['invalid']   = (!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '');

        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }

        $data['modname']   = '';
        $data['modid']     = '';
        $data['itemtype']  = '';
        $data['func']      = '';
        $data['multiselect'] = ($this->multiselect ? 1 : 0);

        if (!empty($this->module)) {
            $data['modname'] = $this->module;
            $data['modid']   = xarModGetIDFromName($this->module);
            if (isset($this->itemtype)) {
                $data['itemtype'] = $this->itemtype;
                if (isset($this->func)) {
                    $data['func'] = $this->func;
                }
            }
        }
        $data['other'] = '';

        // Allow template override by child classes
        if (!isset($template)) $template = 'itemtype';

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

        // In case we need to process additional input fields based on the name
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }

        // Do something with the validation and save it in $this->validation
        if (isset($validation)) {
            if (is_array($validation)) {
                $this->validation = '';

                if (!empty($validation['modid'])) {
                    // Create en empty structure to put the validation options.
                    $val = array_fill(0, 2, '');

                    $modinfo = xarModGetInfo($validation['modid']);
                    if (empty($modinfo)) return false;
                    $val[0] = $modinfo['name'];

                    if (!empty($validation['itemtype'])) {
                        $val[0] .= '.' . $validation['itemtype'];

                        if (!empty($validation['func'])) $val[1] = $validation['func'];
                    }
                    if (!empty($validation['multiselect'])) $val[2] = '1';

                    // Flatten the options structure back to a string, removing trailing colons.
                    $this->validation = rtrim(implode(':', $val), ':');
                } elseif (!empty($validation['other'])) {
                    $this->validation = $validation['other'];
                }
            } else {
                $this->validation = $validation;
            }
        }

        // Tell the calling function that everything is OK
        return true;
    }
}
?>
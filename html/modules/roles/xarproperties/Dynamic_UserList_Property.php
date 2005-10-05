<?php
/**
 * Dynamic userlist property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */

/* 
 * Dynamic userlist property
 * @author mikespub <mikespub@xaraya.com>
 */

/* Include the base class */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

class Dynamic_UserList_Property extends Dynamic_Select_Property
{
    var $grouplist = array();
    var $userstate = -1;
    var $showlist = array();
    var $orderlist = array();
    var $showglue = '; ';

    /*
    * Options available to user selection
    * ===================================
    * Options take the form:
    *   option-type:option-value;
    * option-types:
    *   group:name[,name] - select only users who are members of the given group(s)
    *   state:value - select only users of the given state
    *   show:field[,field] - show the specified field(s) in the select item
    *   showglue:string - string to join multiple fields together
    *   order:field[,field] - order the selection by the specified field
    * where
    *   field - name|uname|email|uid
    */

    function Dynamic_UserList_Property($args)
    {
        // Don't initialise the parent class as it handles the
        // validation in an inappropriate way for user lists.
        // $this->Dynamic_Select_Property($args);
        $this->Dynamic_Property($args);

        // Initialise the select option list.
        $this->options = array();

        // Handle user options if supplied.
        if (!empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
    }

    // TODO: validate the selected user against the specified group(s).
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            // check if this is a valid user id
            $uname = xarUserGetVar('uname', $value);
            if (isset($uname)) {
                $this->value = $value;
                return true;
            } else {
                xarErrorHandled();
            }
        } elseif (empty($value)) {
            $this->value = $value;
            return true;
        }
        $this->invalid = xarML('selection');
        $this->value = null;
        return false;
    }

    function showInput($args = array())
    {
        $select_options = array();

        extract($args);

        $data= array();
        //$users=array(0;
        
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->options;
        }
        if (count($options) == 0) {
            if ($this->userstate <> -1) {
                $select_options['state'] = $this->userstate;
            }
            if (!empty($this->orderlist)) {
                $select_options['order'] = implode(',', $this->orderlist);
            }
            if (!empty($this->grouplist)) {
                $select_options['group'] = implode(',', $this->grouplist);
            }

            $users = xarModAPIFunc('roles', 'user', 'getall', $select_options);

            // Loop for each user retrived and populate the options array.
            if (empty($this->showlist)) {
                // Simple case (default) - 
                foreach ($users as $user) {
                    $options[] = array('id' => $user['uid'], 'name' => $user['name']);
                }
            } else {
                // Complex case: allow specific fields to be selected.
                foreach ($users as $user) {
                    $namevalue = array();
                    foreach ($this->showlist as $showfield) {
                        $namevalue[] = $user[$showfield];
                    }
                    $options[] = array('id' => $user['uid'], 'name' => implode($this->showglue, $namevalue));
                }
            }
        }

        if (empty($name)) {
            $data['name'] = 'dd_' . $this->id;
        } else {
            $data['name'] = $name;
        }

        if (empty($id)) {
            // TODO: strip out characters that are not allowed in a name.
            $data['id'] = xarVarPrepForDisplay($data['name']);
        } else {
            $data['id']= $id;
        }
       //$data['select_options']=$select_options;
        $data['value']=$value;
        $data['options']=$options;
        $data['users']=$users;
        $data['tabindex']=!empty($tabindex) ? $tabindex : 0;
        $data['invalid']=!empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';

        /*$out = '<select' .
               ' name="' . $name . '"' .
               ' id="'. $id . '"' .
               (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
               '>';

        foreach ($options as $option) {
            $out .= '<option';
            if (empty($option['id']) || $option['id'] != $option['name']) {
                $out .= ' value="'.$option['id'].'"';
            }
            if ($option['id'] == $value) {
                $out .= ' selected="selected">'.$option['name'].'</option>';
            } else {
                $out .= '>'.$option['name'].'</option>';
            }
        }

        $out .= '</select>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
       */

        return xarTplProperty('roles', 'userlist', 'showinput', $data);
    }

    // TODO: format the output according to the 'showlist'.
    // TODO: provide an option to allow admin to decide whether to wrap the user
    // in a link or not.
    function showOutput($args = array())
    {
        extract($args);
        $data= array();

        if (!isset($value)) {
            $value = $this->value;
        }

        if (empty($value)) {
            $user = '';
        } else {
            $user = xarUserGetVar('name', $value);
            if (empty($user)) {
                if (!isset($user)) xarErrorHandled();
                $user = xarUserGetVar('uname', $value);
                if (!isset($user)) xarErrorHandled();
            }
        }
        $data['value']=$value;
        $data['user']=$user;
        /*
        if ($value > 1) {
            return '<a href="'.xarModURL('roles', 'user', 'display',
                                         array('uid' => $value))
                    . '">'.xarVarPrepForDisplay($user).'</a>';
        } else {
            return xarVarPrepForDisplay($user);
        }
        */

        return xarTplProperty('roles', 'userlist', 'showoutput', $data);
    }

    function parseValidation($validation = '')
    {
        foreach(preg_split('/(?<!\\\);/', $validation) as $option) {
            // Semi-colons can be escaped with a '\' prefix.
            $option = str_replace('\;', ';', $option);
            // An option comes in two parts: option-type:option-value
            if (strchr($option, ':')) {
                list($option_type, $option_value) = explode(':', $option, 2);
                if ($option_type == 'state' && is_numeric($option_value)) {
                    $this->userstate = $option_value;
                }
                if ($option_type == 'showglue') {
                    $this->showglue = $option_value;
                }
                if ($option_type == 'group') {
                    $this->grouplist = array_merge($this->grouplist, explode(',', $option_value));
                }
                if ($option_type == 'show') {
                    $this->showlist = array_merge($this->showlist, explode(',', $option_value));
                    // Remove invalid elements (fields that are not valid).
                    $showfilter = create_function(
                        '$a', 'return preg_match(\'/^[-]?(name|uname|email|uid|state|date_reg)$/\', $a);'
                    );
                    $this->showlist = array_filter($this->showlist, $showfilter);
                }
                if ($option_type == 'order') {
                    $this->orderlist = array_merge($this->orderlist, explode(',', $option_value));
                }
            }
        }
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
                          'id'         => 37,
                          'name'       => 'userlist',
                          'label'      => 'User List',
                          'format'     => '37',
                          'validation' => '',
                          'source'     => '',
                          'dependancies' => '',
                          'requiresmodule' => 'roles',
                          'aliases' => '',
                          'args'         => '',
                          // ...
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
        $data['size']       = !empty($size) ? $size : 50;

        if (isset($validation)) {
            $this->validation = $validation;
        // CHECKME: reset grouplist et al. first if we call this from elsewhere ?
            $this->parseValidation($validation);
        }

    // TODO: adapt if the template uses a multi-select for groups
        $data['grouplist'] = join(',', $this->grouplist);
        $data['userstate'] = $this->userstate;
    // TODO: adapt if the template uses a multi-select for fields
        $data['showlist']  = join(',', $this->showlist);
        $data['orderlist'] = join(',', $this->orderlist);
        $data['showglue']  = xarVarPrepForDisplay($this->showglue);
        $data['other']     = '';

        // allow template override by child classes
        if (!isset($template)) {
            $template = '';
        }
        return xarTplProperty('roles', 'userlist', 'validation', $data);
    }

    /**
     * Update the current validation rule in a specific way for this property type
     *
     * @param $args['name'] name of the field (default is 'dd_NN' with NN the property id)
     * @param $args['validation'] validation rule (default is the current validation)
     * @param $args['id'] id of the field
     * @returns bool
     * @return bool true if the validation rule could be processed, false otherwise
     */
    function updateValidation($args = array())
    {
        extract($args);

        // in case we need to process additional input fields based on the name
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // do something with the validation and save it in $this->validation
        if (isset($validation)) {
            if (!is_array($validation)) {
                $this->validation = $validation;

            } elseif (!empty($validation['other'])) {
                $this->validation = $validation['other'];

            } else {
                $options = array();
                if (!empty($validation['grouplist'])) {
                // TODO: adapt if the template uses a multi-select for groups
                    $options[] = 'group:' . $validation['grouplist'];
                }
                if (!empty($validation['userstate']) && is_numeric($validation['userstate'])) {
                    $options[] = 'state:' . $validation['userstate'];
                }
                if (!empty($validation['showlist'])) {
                // TODO: adapt if the template uses a multi-select for fields
                    $templist = explode(',', $validation['showlist']);
                    // Remove invalid elements (fields that are not valid).
                    $showfilter = create_function(
                        '$a', 'return preg_match(\'/^[-]?(name|uname|email|uid|state|date_reg)$/\', $a);'
                    );
                    $templist = array_filter($templist, $showfilter);
                    if (count($templist) > 0) {
                        $options[] = 'show:' . join(',', $templist);
                    }
                }
                if (!empty($validation['orderlist'])) {
                // TODO: adapt if the template uses a multi-select for fields
                    $options[] = 'order:' . $validation['orderlist'];
                }
                if (!empty($validation['showglue'])) {
                    $validation['showglue'] = str_replace(';', '\;', $validation['showglue']);
                    $options[] = 'showglue:' . $validation['showglue'];
                }
                $this->validation = join(';', $options);
            }
        }

        // tell the calling function that everything is OK
        return true;
    }

}

?>

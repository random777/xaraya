<?php
/**
 * Dynamic Calendar Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Class for dynamic calendar property
 *
 * This property shows an input text field, and a clickable calendar next to it.
 * The calendar is a js popup in which users can choose a date
 * The date will get converted into a datetime or timestamp value, depending on the validation chosen.
 *
 * @package modules
 * @subpackage Base module
 * @author
 * @param
 */
class Dynamic_Calendar_Property extends Dynamic_Property
{
    /**
     * Check the input for the calendar
     * @param string name
     * @param mixed value
     */
    function checkInput($name='', $value = null)
    {
        return $this->_checkInput_optional($name, $value);
    }

    /**
     * See if the value put into the property is valid
     * @param mixed value
     */
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        // default time is unspecified
        if (empty($value)) {
              $this->value = -1;
        } elseif (is_numeric($value)) {
            $this->value = $value;
        } elseif (is_array($value) && !empty($value['year'])) {
            if (!isset($value['sec'])) {
                $value['sec'] = 0;
            }
            $this->value = mktime($value['hour'],$value['min'],$value['sec'],
                                  $value['mon'],$value['mday'],$value['year']);
        } elseif (is_string($value)) {
            // assume dates are stored in UTC format
            // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            // this returns -1 when we have an invalid date (e.g. on purpose)
            $this->value = strtotime($value);
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if ($this->value === false) $this->value = -1;
            if ($this->value >= 0) {
                // adjust for the user's timezone offset
                $this->value -= xarMLS_userOffset($this->value) * 3600;
            }
        } else {
            $this->invalid = xarML('date');
            $this->value = null;
            return false;
        }
        // TODO: improve this
        // store values in a datetime field
        if ($this->validation == 'datetime') {
            $this->value = gmdate('Y-m-d H:i:s', $this->value);
        // store values in a date field
        } elseif ($this->validation == 'date') {
            $this->value = gmdate('Y-m-d', $this->value);
        }
        return true;
    }

//    function showInput($name = '', $value = null, $id = '', $tabindex = '')
    /**
     * Show an input form for the property
     * This shows a text input and a clickable js item next to it
     * The popup will show a js calendar, with or without a timeselector
     */
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        // default time is unspecified
        if (empty($value)) {
            $value = -1;
        } elseif (!is_numeric($value) && is_string($value)) {
            // assume dates are stored in UTC format
            // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            // this returns -1 when we have an invalid date (e.g. on purpose)
            $value = strtotime($value);
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if ($value === false) $value = -1;
        }
        if (!isset($dateformat)) {
            $dateformat = '%Y-%m-%d %H:%M:%S';
            if ($this->validation == 'date') {
                $dateformat = '%Y-%m-%d';
            } else {
                $dateformat = '%Y-%m-%d %H:%M:%S';
            }
        }

        // include calendar javascript
//        xarModAPIFunc('base','javascript','modulefile',
//                      array('module' => 'base',
//                            'filename' => 'calendar.js'));

        // $timeval = xarLocaleFormatDate($dateformat, $value);
        $data['baseuri']    = xarServerGetBaseURI();
        $data['dateformat'] = $dateformat;
        $data['jsID']       = str_replace(array('[', ']'), '_', $id);
        // $data['timeval']    = $timeval;
        $data['name']       = $name;
        $data['id']         = $id;
        $data['value']      = $value;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        return xarTplProperty('base', 'calendar', 'showinput', $data);
    }
    /**
     * Show the date formatted according to the validation type
     */
    function showOutput($args = array())
    {
        extract($args);

        $data=array();

        if (!isset($value)) {
            $value = $this->value;
        }
        // default time is unspecified
        if (empty($value)) {
            $value = -1;
        } elseif (!is_numeric($value) && is_string($value)) {
            // assume dates are stored in UTC format
            // TODO: check if we still need to add "00" for PostgreSQL timestamps or not
            if (!preg_match('/[a-zA-Z]+/',$value)) {
                $value .= ' GMT';
            }
            // this returns -1 when we have an invalid date (e.g. on purpose)
            $value = strtotime($value);
            // starting with PHP 5.1.0, strtotime returns false instead of -1
            if ($value === false) $value = -1;
        }
        if (!isset($dateformat)) {
            $dateformat = '%a, %d %B %Y %H:%M:%S %Z';
        }

        $data['dateformat'] = $dateformat;
        $data['value'] = $value;
        // $data['returnvalue']= xarLocaleFormatDate($dateformat, $value);

        return xarTplProperty('base', 'calendar', 'showoutput', $data);
    }

    /**
     * Get the base information for this property.
     *
     *
     * @return array Base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 8,
                              'name'       => 'calendar',
                              'label'      => 'Calendar',
                              'format'     => '8',
                              'validation' => '',
                              'source'         => '',
                              'dependancies'   => '',
                              'requiresmodule' => '',
                              'aliases'        => '',
                              'args'           => serialize($args),
                            // ...
                           );
        return $baseInfo;
     }
     /**
      * Show a validation input form. The user can choose a valid validation for this property
      */
    function showValidation($args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (isset($validation)) {
            $this->validation = $validation;
        }
        if (empty($this->validation) || $this->validation == 'datetime' || $this->validation == 'date') {
            $data['dbformat'] = $this->validation;
            $data['other'] = '';
        } else {
            $data['dbformat'] = '';
            $data['other'] = $this->validation;
        }
        // Note : timestamp is not an option for ExtendedDate
        $data['class'] = get_class($this);

        // allow template override by child classes
        if (empty($template)) {
            $template = 'calendar';
        }
        return xarTplProperty('base', $template, 'validation', $data);
    }
    /**
     * Update the validation for this property
     * @return bool true on success
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
            if (is_array($validation)) {
                if (!empty($validation['other'])) {
                    $this->validation = $validation['other'];

                } elseif (isset($validation['dbformat'])) {
                    $this->validation = $validation['dbformat'];

                } else {
                    $this->validation = '';
                }
            } else {
                $this->validation = $validation;
            }
        }

        // tell the calling function that everything is OK
        return true;
    }

}

?>

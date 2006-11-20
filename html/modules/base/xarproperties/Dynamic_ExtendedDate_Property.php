<?php
/**
 * Extended Date property
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * @author Roger Keays <roger.keays@ninthave.net>
 */

include_once "modules/base/xarproperties/Dynamic_Calendar_Property.php";

/**
 * The extended date property converts the value provided by the javascript
 * calendar into a universal YYYY-MM-DD format for storage in most databases
 * supporting the 'date' type.
 *
 * The problem with the normal Calendar property is that it converts
 * everything into a UNIX timestamp, and for most C librarys this does not
 * include dates before 1970. (see Xaraya bugs 2013 and 1428)
 */
class Dynamic_ExtendedDate_Property extends Dynamic_Calendar_Property
{
    /**
     * We allow two validations: date, and datetime (corresponding to the
     * database's date and datetime data types.
     *
     * We also don't make any modifications for the timezone (too hard).
     */
    function validateValue($value = null)
    {
        if (empty($this->validation)) {
            $this->validation = 'datetime';
        }
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $this->value = $value;
            return true;

        } elseif (is_array($value)) {

            if (!empty($value['year']) && !empty($value['mon']) && !empty($value['day'])) {
                if (is_numeric($value['year']) && is_numeric($value['mon']) && is_numeric($value['day']) &&
                    $value['mon'] > 0 && $value['mon'] < 13 && $value['day'] > 0 && $value['day'] < 32) {
                    $this->value = sprintf('%04d-%02d-%02d',$value['year'],$value['mon'],$value['day']);
                    if ($this->validation == 'datetime') {
                        if (isset($value['hour']) && isset($value['min']) && isset($value['sec']) &&
                            is_numeric($value['hour']) && is_numeric($value['min']) && is_numeric($value['sec']) &&
                            $value['hour'] > -1 && $value['hour'] < 24 && $value['min'] > -1 && $value['min'] < 61 && $value['sec'] > -1 && $value['sec'] < 61) {
                            $this->value .= ' ' . sprintf('%02d:%02d:%02d',$value['hour'],$value['min'],$value['sec']);
                        } else {
                            $this->invalid = xarML('date');
                            $this->value = null;
                            return false;
                        }
                    }
                } else {
                    $this->invalid = xarML('date');
                    $this->value = null;
                    return false;
                }
            } else {
                $this->value = '';
            }
            return true;

        /* sample value: 2004-06-18 18:47:33 */
        } elseif (is_string($value) &&

            /* check it matches the correct regexp */
            ($this->validation == 'date' &&
            preg_match('/\d{4}-\d{1,2}-\d{1,2}/', $value)) ||

            ($this->validation == 'datetime' &&
            preg_match('/\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}:\d{1,2}/', $value))
            ) {

            /* TODO: use xaradodb to format the date */
            $this->value = $value;
            return true;

        } else {
            $this->invalid = xarML('date');
            $this->value = null;
            return false;
        }
    } /* validateValue */

    /**
     * Show the input according to the requested dateformat.
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

        $data['year'] = '';
        $data['mon']  = '01';
        $data['day']  = '01';
        $data['hour'] = '00';
        $data['min']  = '00';
        $data['sec']  = '00';

        // default time is unspecified
        if (empty($value)) {
            $value = '';

        } elseif ($this->validation == 'date' &&
            preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $value, $matches)) {
            $data['year'] = $matches[1];
            $data['mon']  = $matches[2];
            $data['day']  = $matches[3];

        } elseif ($this->validation == 'datetime' &&
            preg_match('/(\d{4})-(\d{1,2})-(\d{1,2}) (\d{1,2}):(\d{1,2}):(\d{1,2})/', $value, $matches)) {
            $data['year'] = $matches[1];
            $data['mon']  = $matches[2];
            $data['day']  = $matches[3];
            $data['hour'] = $matches[4];
            $data['min']  = $matches[5];
            $data['sec']  = $matches[6];
        }
        $data['format']   = $this->validation;

        if (!isset($dateformat)) {
            if ($this->validation == 'date') {
                $dateformat = '%Y-%m-%d';
            } else {
                $dateformat = '%Y-%m-%d %H:%M:%S';
            }
        }
        $data['dateformat'] = $dateformat;
        $data['name']       = $name;
        $data['id']         = $id;
        $data['value']      = $value;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        return xarTplProperty('base', 'extendeddate', 'showinput', $data);
    }

    /**
     * Show the output according to the requested dateformat.
     */
    function showOutput($args = array())
    {
        extract($args);

        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }

        $data['year'] = '';
        $data['mon']  = '';
        $data['day']  = '';
        $data['hour'] = '';
        $data['min']  = '';
        $data['sec']  = '';

        // default time is unspecified
        if (empty($value)) {
            $value = '';

        } elseif ($this->validation == 'date' &&
            preg_match('/(\d{4})-(\d{1,2})-(\d{1,2})/', $value, $matches)) {
            $data['year'] = $matches[1];
            $data['mon']  = $matches[2];
            $data['day']  = $matches[3];

        } elseif ($this->validation == 'datetime' &&
            preg_match('/(\d{4})-(\d{1,2})-(\d{1,2}) (\d{1,2}):(\d{1,2}):(\d{1,2})/', $value, $matches)) {
            $data['year'] = $matches[1];
            $data['mon']  = $matches[2];
            $data['day']  = $matches[3];
            $data['hour'] = $matches[4];
            $data['min']  = $matches[5];
            $data['sec']  = $matches[6];
        }
        $data['format']   = $this->validation;

        if (!isset($dateformat)) {
            if ($this->validation == 'date') {
                $dateformat = '%a, %d %B %Y %Z';
            } else {
                $dateformat = '%a, %d %B %Y %H:%M:%S %Z';
            }
        }

        $data['dateformat'] = $dateformat;
        $data['value']      = $value;

        return xarTplProperty('base', 'extendeddate', 'showoutput', $data);
    } /* showOutput */

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 47,
                              'name'       => 'extendeddate',
                              'label'      => 'Extended Date',
                              'format'     => '47',
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

}


?>
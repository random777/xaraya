<?php
/**
 * Dynamic TimeZone Property
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
/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * handle the timezone property
 *
 * @package dynamicdata
 */
class Dynamic_TimeZone_Property extends Dynamic_Select_Property
{
    function __construct($args)
    {
        parent::__construct($args);
        $this->tplmodule = 'base';
        $this->template  = 'timezone';
    }

    static function getRegistrationInfo()
    {
        $info = new PropertyRegistration();
        $info->reqmodules = array('base');
        $info->id    = 32;
        $info->name  = 'timezone';
        $info->desc  = 'Time Zone';
		$info->filepath   = 'modules/base/xarproperties';

        return $info;
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            // no timezone selected
            $this->value = $value;
            return true;

        } elseif (is_numeric($value)) {
            // keep old numeric format
            $this->value = $value;
            return true;

        } elseif (is_string($value)) {
            // check what kind of string we have here
            $out = @unserialize($value);
            if ($out !== false) {
                // we have a serialized value
                if (empty($out['timezone'])) {
                    $this->value = '';
                    return true;
                }
                $timezone = $out['timezone'];
            } else {
                // we have a text value
                $timezone = $value;
            }

        } elseif (is_array($value)) {
            if (empty($value['timezone'])) {
                $this->value = '';
                return true;
            }
            $timezone = $value['timezone'];
        }

        // check if the timezone exists
        $info = xarModAPIFunc('base','user','timezones',
                              array('timezone' => $timezone));
        if (empty($info)) {
            $this->invalid = xarML('timezone');
            $this->value = null;
            return false;
        }
        list($hours,$minutes) = explode(':',$info[0]);
        // tz offset is in hours
        $offset = (float) $hours + (float) $minutes / 60;
        // save a serialized array with timezone and offset
        $value = array('timezone' => $timezone,
                       'offset'   => $offset);
        $this->value = serialize($value);
        return true;
    }

    function showInput($data = array())
    {
        if (!isset($data['value'])) {
            $value = $this->value;
        } else {
            $value = $data['value'];
        }

        if (!isset($data['options']) || count($data['options']) == 0) {
            $data['options'] = $this->options;
        }

        if (!empty($value) && is_numeric($value)) {
            $data['style'] = 'offset';
            if (empty($data['options'])) {
                $data['options'] = $this->getOldOptions();
            }
        } else {
            $data['style'] = 'timezone';
            if (empty($data['options'])) {
                $data['options'] = $this->getNewOptions();
            }
            if (empty($value)) {
                $data['timezone'] = '';
            } elseif (is_array($value)) {
                if (!empty($value['timezone'])) {
                    $data['timezone'] = $value['timezone'];
                }
            } elseif (is_string($value)) {
                // check what kind of string we have here
                $out = @unserialize($value);
                if ($out !== false) {
                    // we have a serialized value
                    if (!empty($out['timezone'])) {
                        $data['timezone'] = $out['timezone'];
                    }
                } else {
                    // we have a text value
                    $data['timezone'] = $value;
                }
            }
        }

        $data['value']   = $value;
        $data['now']     = time();

        return parent::showInput($data);
    }

    function showOutput($data = array())
    {
        extract($data);
        if (!isset($value))  $value = $this->value;

        $offset = null;
        $timezone = null;
        if (empty($value)) {
            $value = 'GMT';
        } elseif (is_numeric($value)) {
            $offset = $value;
        } elseif (is_array($value)) {
            if (isset($value['offset'])) {
                $offset = $value['offset'];
            }
            if (isset($value['timezone'])) {
                $timezone = $value['timezone'];
            }

        } else {
            $out = @unserialize($value);
            if ($out !== false) {
                $value = $out;
                if (isset($value['offset'])) {
                    $offset = $value['offset'];
                }
                if (isset($value['timezone'])) {
                    $timezone = $value['timezone'];
                }
            } else {
                $value = '';
            }
        }

        $data['value'] = $value;
        if (isset($timezone)) {
            $data['timezone'] = strtr($timezone, array('/' => ' - ', '_' => ' '));
        }
        if (isset($offset)) {
            $hours = intval($offset);
            if ($hours != $offset) {
                $minutes = abs($offset - $hours) * 60;
            } else {
                $minutes = 0;
            }
            if ($hours > 0) {
                $data['offset'] = sprintf("+%d:%02d",$hours,$minutes);
            } else {
                $data['offset'] = sprintf("%d:%02d",$hours,$minutes);
            }
        }
        // old timezone output format
        $data['option']['name'] = $value;
        return parent::showOutput($data);
    }

    function getOldOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        $options = array(
                         array('id' => -12, 'name' => xarML('GMT #(1)','-12:00')),
                         array('id' => -11, 'name' => xarML('GMT #(1)','-11:00')),
                         array('id' => -10, 'name' => xarML('GMT #(1)','-10:00')),
                         array('id' => -9, 'name' => xarML('GMT #(1)','-09:00')),
                         array('id' => -8, 'name' => xarML('GMT #(1)','-08:00')),
                         array('id' => -7, 'name' => xarML('GMT #(1)','-07:00')),
                         array('id' => -6, 'name' => xarML('GMT #(1)','-06:00')),
                         array('id' => -5, 'name' => xarML('GMT #(1)','-05:00')),
                         array('id' => -4, 'name' => xarML('GMT #(1)','-04:00')),
                         array('id' => -3.5, 'name' => xarML('GMT #(1)','-03:30')),
                         array('id' => -3, 'name' => xarML('GMT #(1)','-03:00')),
                         array('id' => -2, 'name' => xarML('GMT #(1)','-02:00')),
                         array('id' => -1, 'name' => xarML('GMT #(1)','-01:00')),
                         array('id' => '0', 'name' => xarML('GMT')),
                         array('id' => 1, 'name' => xarML('GMT #(1)','+01:00')),
                         array('id' => 2, 'name' => xarML('GMT #(1)','+02:00')),
                         array('id' => 3, 'name' => xarML('GMT #(1)','+03:00')),
                         array('id' => 3.5, 'name' => xarML('GMT #(1)','+03:30')),
                         array('id' => 4, 'name' => xarML('GMT #(1)','+04:00')),
                         array('id' => 4.5, 'name' => xarML('GMT #(1)','+04:30')),
                         array('id' => 5, 'name' => xarML('GMT #(1)','+05:00')),
                         array('id' => 5.5, 'name' => xarML('GMT #(1)','+05:30')),
                         array('id' => 6, 'name' => xarML('GMT #(1)','+06:00')),
                         array('id' => 6.5, 'name' => xarML('GMT #(1)','+06:30')),
                         array('id' => 7, 'name' => xarML('GMT #(1)','+07:00')),
                         array('id' => 8, 'name' => xarML('GMT #(1)','+08:00')),
                         array('id' => 9, 'name' => xarML('GMT #(1)','+09:00')),
                         array('id' => 9.5, 'name' => xarML('GMT #(1)','+09:30')),
                         array('id' => 10, 'name' => xarML('GMT #(1)','+10:00')),
                         array('id' => 11, 'name' => xarML('GMT #(1)','+11:00')),
                         array('id' => 12, 'name' => xarML('GMT #(1)','+12:00')),
                         array('id' => 13, 'name' => xarML('GMT #(1)','+13:00')),
                        );
        return $options;
    }

    function getNewOptions()
    {
        if (count($this->options) > 0) {
            return $this->options;
        }
        $timezones = xarModAPIFunc('base','user','timezones');
        $options = array();
        $options[] = array('id' => '', 'name' => '');
        foreach ($timezones as $timezone => $info) {
            $name = strtr($timezone, array('/' => ' - ', '_' => ' '));
            $options[] = array('id' => $timezone, 'name' => $name, 'offset' => $info[0]);
        }
        return $options;
    }
}
?>
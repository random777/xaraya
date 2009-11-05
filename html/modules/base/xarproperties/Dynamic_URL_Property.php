<?php
/**
 * Dynamic URL Property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Include the base class
 *
 */
include_once "modules/base/xarproperties/Dynamic_TextBox_Property.php";

/**
 * handle the URL property
 *
 * @package dynamicdata
 *
 */
class Dynamic_URL_Property extends Dynamic_TextBox_Property
{
    /**
     * Validate a value as an URL
     * @param value
     * @return bool true
     */
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }

        // Make sure $value['link'] is set, has a length > 0 and does not equal simply 'http://'
        $value = trim($value);

        if (!empty($value) && $value != 'http://')  {
            //let's process futher then
            //check it is not invalid eg html tag
            if (preg_match('/[<>"]/', $value)) {
                $this->invalid = xarML('URL');
                return false;
            } else {
                // If we have a scheme but nothing following it,
                // then consider the link empty :-)
                if (preg_match('!^[a-z]+\:\/\/$!i', $value)) {
                    $this->value = '';
                } else {
                    // Do some URL validation below. Separate for better understanding
                    // Still not perfect. Add as seen fit.
                    $uri = parse_url($value);
                    if (empty($uri['scheme']) && empty($uri['host']) && empty($uri['path'])) {
                        $this->invalid = xarML('URL');
                        return false;
                    } elseif (empty($uri['scheme'])) {
                        // No scheme, so add one.
                        $this->value = 'http://' . $value;
                    } else {
                        // It has at least a scheme (http/ftp/etc) and a host (domain.tld)
                        $this->value = $value;
                    }
                }
            } //end checks for other schemes
        } else {
            // Set the empty value of the property.
            $this->value = '';
        }

        return true;
    }

    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = 'http://';
        }
        if (empty($name) || !isset($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id) || !isset($id)) {
            $id = $name;
        }
        $data=array();

        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;

        $template = '';
        return xarTplProperty('base', 'url', 'showinput', $data);
    }

    function showOutput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }

        $data=array();
        // TODO: use redirect function here ?
        if (!empty($value) && $value != 'http://') {
            $data['value'] = xarVarPrepForDisplay($value);

            $template="";
            return xarTplProperty('base', 'url', 'showoutput', $data);
        }
        return '';
    }

    /**
     * Get the base information for this property.
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
         $baseInfo = array(
            'id'         => 11,
            'name'       => 'url',
            'label'      => 'URL',
            'format'     => '11',
            'validation' => '',
            'source'     => '',
            'dependancies' => '',
            'requiresmodule' => '',
            'aliases' => '',
            'args'         => '',
            // ...
        );
        return $baseInfo;
     }
}

?>
<?php
/**
 * Image Property
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
include_once "modules/base/xarproperties/Dynamic_TextBox_Property.php";

/**
 * handle the image property
 *
 * @package dynamicdata
 */
class Dynamic_Image_Property extends Dynamic_TextBox_Property
{
    /**
     * Validate the input
     * @param string value The URL to the image
     * @return bool true on valid input
     */
    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value) && $value != 'http://') {
        // TODO: add some image validation routine !
            if (preg_match('/[<>"]/',$value)) {
                $this->invalid = xarML('image URL');
                $this->value = null;
                return false;
            } else {
                $this->value = $value;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

    /**
     * usage: showInput($name = '', $value = null,  $size = 0, $maxlength = 0, $id = '', $tabindex = '')
     * @param string name
     * @param string value
     * @param int tabindex
     * @param size
     * @param maxlength
     * @return
     */
    function showInput($args = array())
    {
        extract($args);
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($value)) {
            $value = 'http://';
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data['name']     = $name;
        $data['id']       = $id;
        $data['value']    = isset($value) ? xarVarPrepForDisplay($value) : xarVarPrepForDisplay($this->value);
        $data['tabindex'] = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';
        $data['maxlength']= !empty($maxlength) ? $maxlength : $this->maxlength;
        $data['size']     = !empty($size) ? $size : $this->size;

        $template="";
        return xarTplProperty('base', 'image', 'showinput', $data);
    }
    /**
     * Show the image in a template
     * @param array @args
     */
    function showOutput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            $value = xarVarPrepForDisplay($value);
        // TODO: add size/alt here ?
            //return '<img src="'.$value.'" />';
        }
        if (!isset($name)){
            $name=$this->name;
        }
        $data['value'] = $value;
        $data['name']  = $name;
        $data['id']    = $this->id;

        $template="";
        return xarTplProperty('base', 'image', 'showoutput', $data);
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
                              'id'         => 12,
                              'name'       => 'image',
                              'label'      => 'Image',
                              'format'     => '12',
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
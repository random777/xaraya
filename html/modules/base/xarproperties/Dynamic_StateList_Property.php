<?php
/**
 * Dynamic State List Property
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author John Cox
*/
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * handle the userlist property
 *
 * @package dynamicdata
 *
 */
class Dynamic_StateList_Property extends Dynamic_Select_Property
{

    function Dynamic_StateList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($value)) {
            if (is_string($value)) {
                $this->value = $value;
            } else {
                $this->invalid = xarML('State Listing');
                $this->value = null;
                return false;
            }
        } else {
            $this->value = '';
        }
        return true;
    }

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        $data = array();
        if (!isset($value)) {
            $value = $this->value;
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }
        $data['value'] = $value;
        $data['name']  = $name;
        $data['id']    = $id;
       
       /*
        $out = '<select' .
       ' name="' . $name . '"' .
       ' id="'. $id . '"' .
       (!empty($tabindex) ? ' tabindex="'.$tabindex.'" ' : '') .
       '>';
       */
        $soptions = array();
        $soptions[] = array('id' =>'Please select', 'name' =>'Please select' );
        $soptions[] = array('id' =>'Alabama', 'name' =>'Alabama');
        $soptions[] = array('id' =>'Alaska', 'name' =>'Alaska');
        $soptions[] = array('id' =>'Arizona', 'name' =>'Arizona');
        $soptions[] = array('id' =>'Arkansas', 'name' =>'Arkansas');
        $soptions[] = array('id' =>'California', 'name' =>'California');
        $soptions[] = array('id' =>'Colorado', 'name' =>'Colorado');
        $soptions[] = array('id' =>'Connecticut', 'name' =>'Connecticut');
        $soptions[] = array('id' =>'Delaware', 'name' =>'Delaware');
        $soptions[] = array('id' =>'Florida', 'name' =>'Florida');
        $soptions[] = array('id' =>'Georgia', 'name' =>'Georgia');
        $soptions[] = array('id' =>'Hawaii', 'name' =>'Hawaii');
        $soptions[] = array('id' =>'Idaho', 'name' =>'Idaho');
        $soptions[] = array('id' =>'Illinois', 'name' =>'Illinois');
        $soptions[] = array('id' =>'Indiana', 'name' =>'Indiana');
        $soptions[] = array('id' =>'Iowa     ', 'name' =>'Iowa');
        $soptions[] = array('id' =>'Kansas     ', 'name' =>'Kansas');
        $soptions[] = array('id' =>'Kentucky     ', 'name' =>'Kentucky');
        $soptions[] = array('id' =>'Louisiana     ', 'name' =>'Louisiana');
        $soptions[] = array('id' =>'Maine     ', 'name' =>'Maine');
        $soptions[] = array('id' =>'Maryland     ', 'name' =>'Maryland');
        $soptions[] = array('id' =>'Massachusetts     ', 'name' =>'Massachusetts');
        $soptions[] = array('id' =>'Michigan     ', 'name' =>'Michigan');
        $soptions[] = array('id' =>'Minnesota     ', 'name' =>'Minnesota');
        $soptions[] = array('id' =>'Mississippi     ', 'name' =>'Mississippi');
        $soptions[] = array('id' =>'Missouri     ', 'name' =>'Missouri');
        $soptions[] = array('id' =>'Montana     ', 'name' =>'Montana');
        $soptions[] = array('id' =>'Nebraska     ', 'name' =>'Nebraska');
        $soptions[] = array('id' =>'Nevada     ', 'name' =>'Nevada');
        $soptions[] = array('id' =>'New Hampshire     ', 'name' =>'New Hampshire');
        $soptions[] = array('id' =>'New Jersey     ', 'name' =>'New Jersey');
        $soptions[] = array('id' =>'New Mexico     ', 'name' =>'New Mexico');
        $soptions[] = array('id' =>'New York     ', 'name' =>'New York');
        $soptions[] = array('id' =>'North Carolina     ', 'name' =>'North Carolina');
        $soptions[] = array('id' =>'North Dakota     ', 'name' =>'North Dakota');
        $soptions[] = array('id' =>'Ohio     ', 'name' =>'Ohio');
        $soptions[] = array('id' =>'Oklahoma     ', 'name' =>'Oklahoma');
        $soptions[] = array('id' =>'Oregon     ', 'name' =>'Oregon');
        $soptions[] = array('id' =>'Pennsylvania     ', 'name' =>'Pennsylvania');
        $soptions[] = array('id' =>'Rhode Island     ', 'name' =>'Rhode Island');
        $soptions[] = array('id' =>'South Carolina     ', 'name' =>'South Carolina');
        $soptions[] = array('id' =>'South Dakota     ', 'name' =>'South Dakota');
        $soptions[] = array('id' =>'Tennessee     ', 'name' =>'Tennessee');
        $soptions[] = array('id' =>'Texas     ', 'name' =>'Texas');
        $soptions[] = array('id' =>'Utah     ', 'name' =>'Utah');
        $soptions[] = array('id' =>'Vermont     ', 'name' =>'Vermont');
        $soptions[] = array('id' =>'Virginia     ', 'name' =>'Virginia');
        $soptions[] = array('id' =>'Washington     ', 'name' =>'Washington');
        $soptions[] = array('id' =>'West Virginia     ', 'name' =>'West Virginia');
        $soptions[] = array('id' =>'Wisconsin     ', 'name' =>'Wisconsin');
        $soptions[] = array('id' =>'Wyoming     ', 'name' =>'Wyoming');
        $soptions[] = array('id' =>'Alberta     ', 'name' =>'Alberta');
        $soptions[] = array('id' =>'British Columbia     ', 'name' =>'British Columbia');
        $soptions[] = array('id' =>'Manitoba     ', 'name' =>'Manitoba');
        $soptions[] = array('id' =>'New Brunswick     ', 'name' =>'New Brunswick');
        $soptions[] = array('id' =>'Newfoundland and Labrador', 'name' =>'Newfoundland and Labrador');
        $soptions[] = array('id' =>'Northwest Territories     ', 'name' =>'Northwest Territories');
        $soptions[] = array('id' =>'Nova Scotia     ', 'name' =>'Nova Scotia');
        $soptions[] = array('id' =>'Nunavut     ', 'name' =>'Nunavut');
        $soptions[] = array('id' =>'Ontario     ', 'name' =>'Ontario');
        $soptions[] = array('id' =>'Prince Edward Island     ', 'name' =>'Prince Edward Island');
        $soptions[] = array('id' =>'Quebec     ', 'name' =>'Quebec');
        $soptions[] = array('id' =>'Saskatchewan     ', 'name' =>'Saskatchewan');
        $soptions[] = array('id' =>'Yukon Territory     ', 'name' =>'Yukon Territory');
        $soptions[] = array('id' =>'Australian Capital Territory     ', 'name' =>'Australian Capital Territory');
        $soptions[] = array('id' =>'New South Wales     ', 'name' =>'New South Wales');
        $soptions[] = array('id' =>'Northern Territory     ', 'name' =>'Northern Territory');
        $soptions[] = array('id' =>'Queensland     ', 'name' =>'Queensland');
        $soptions[] = array('id' =>'South Australia     ', 'name' =>'South Australia');
        $soptions[] = array('id' =>'Tasmania     ', 'name' =>'Tasmania');
        $soptions[] = array('id' =>'Victoria     ', 'name' =>'Victoria');
        $soptions[] = array('id' =>'Western Australia', 'name' =>'Western Australia');
        $soptions[] = array('id' =>'Other', 'name' =>'Other');
        /*for($i=0; isset($soptions[$i]); $i++) {
            $out .= '<option';
            $out .= ' value="'.$soptions[$i]['name'].'"';
            if ($value == $soptions[$i]['name']) {
                $out .= ' selected="selected">'.$soptions[$i]['name'].'</option>';
            } else {
                $out .= '>'.$soptions[$i]['name'].'</option>';
            }
        }*/

        /*$out .= '</select>' .
               (!empty($this->invalid) ? ' <span class="xar-error">'.xarML('Invalid #(1)', $this->invalid) .'</span>' : '');
        */

        $data['soptions'] = $soptions;
        $data['invalid']  = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';
        $data['tabindex'] =! empty($tabindex) ? $tabindex : 0;


        $template="";
        return xarTplProperty('base', 'statelist', 'showinput', $data);

        //return $out;
    }

    function showOutput($args = array())
    {
         extract($args);
         $data = array();

        if (isset($value)) {
             $data['value']=xarVarPrepHTMLDisplay($value);
         } else {
             $data['value']=xarVarPrepHTMLDisplay($this->value);
         }
         if (isset($name)) {
           $data['name']=$name;
         }
         if (isset($id)) {
             $data['id']=$id;
         }
         $template="";
         return xarTplProperty('base', 'statelist', 'showoutput', $data);

    }

    /**
     * Get the base information for this property.
     *
     * @returns array
     * @return base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $baseInfo = array(
                              'id'         => 43,
                              'name'       => 'statelisting',
                              'label'      => 'State Dropdown',
                              'format'     => '43',
                              'validation' => '',
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => '',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }



}

?>

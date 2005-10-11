<?php
/**
 * Imagelist property
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/*
 * @author mikespub <mikespub@xaraya.com>
*/
include_once "modules/base/xarproperties/Dynamic_Select_Property.php";

/**
 * Handle the imagelist property
 *
 * @package dynamicdata
 */
class Dynamic_ImageList_Property extends Dynamic_Select_Property
{
    var $basedir = '';
    var $baseurl = null;
    var $filetype = '(gif|jpg|jpeg|png|bmp)';

    function Dynamic_ImageList_Property($args)
    {
        $this->Dynamic_Select_Property($args);
        if (empty($this->basedir) && !empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
        // Note : {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/Xaraya_Classic/images
        if (!empty($this->basedir) && preg_match('/\{theme\}/',$this->basedir)) {
            $curtheme = xarTplGetThemeDir();
            $this->basedir = preg_replace('/\{theme\}/',$curtheme,$this->basedir);
            if (isset($this->baseurl)) {
                $this->baseurl = preg_replace('/\{theme\}/',$curtheme,$this->baseurl);
            }
        }
    }

    function validateValue($value = null)
    {
        if (!isset($value)) {
            $value = $this->value;
        }
        $basedir = $this->basedir;
        $filetype = $this->filetype;
        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            $this->value = $value;
            return true;
        } elseif (empty($value)) {
            $this->value = $value;
            return true;
        }
        $this->invalid = xarML('selection');
        $this->value = null;
        return false;
    }

//    function showInput($name = '', $value = null, $options = array(), $id = '', $tabindex = '')
    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!isset($options) || count($options) == 0) {
            $options = $this->getOptions();
        }
        if (count($options) == 0 && !empty($this->basedir)) {
            $files = xarModAPIFunc('dynamicdata','admin','browse',
                                   array('basedir' => $this->basedir,
                                         'filetype' => $this->filetype));
            if (!isset($files)) {
               $files = array();
            }
            natsort($files);
            array_unshift($files,'');
            foreach ($files as $file) {
                $options[] = array('id' => $file,
                                   'name' => $file);
            }
            unset($files);
        }
        if (empty($name)) {
            $name = 'dd_' . $this->id;
        }
        if (empty($id)) {
            $id = $name;
        }

        $data['basedir'] = $this->basedir;
        $data['baseurl'] = isset($this->baseurl) ? $this->baseurl : $this->basedir;
        $data['name']    = $name;
        $data['value']   = $value;
        $data['id']      = $id;
        $data['options'] = $options;
        $data['tabindex']= !empty($tabindex) ? $tabindex : 0;
        $data['invalid'] = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid)  : '';

        $template="";
        return xarTplProperty('base', 'imagelist', 'showinput', $data);

    }

    function showOutput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        $basedir = $this->basedir;
        $baseurl = isset($this->baseurl) ? $this->baseurl : $basedir;
        $filetype = $this->filetype;

        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
        //    return '<img src="'.$baseurl.'/'.$value.'" alt="" />';
           $srcpath=$baseurl.'/'.$value;
        } else {
            //return '';
           $srcpath='';
        }

        $data['value']=$value;
        $data['basedir']=$basedir;
        $data['baseurl'] = $baseurl;
        $data['filetype']=$filetype;
        $data['srcpath']=$srcpath;

        $template="";
        return xarTplProperty('base', 'imagelist', 'showoutput', $data);

    }

    function parseValidation($validation = '')
    {
        if (empty($validation)) return;
        // specify base directory in validation field, or basedir|baseurl (not ; to avoid conflicts with old behaviour)
        if (strpos($validation,'|') !== false) {
            $parts = split('\|',$validation);
            if (count($parts) < 2) return;
            $this->basedir = array_shift($parts);
            $this->baseurl = array_shift($parts);
            if (count($parts) > 0) {
                $this->filetype = '(' . join('|',$parts) . ')';
            }
        } else {
            $this->basedir = $validation;
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
         $args = array();
         $baseInfo = array(
                              'id'         => 35,
                              'name'       => 'imagelist',
                              'label'      => 'Image List',
                              'format'     => '35',
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

    function showValidation($args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        $data['size']       = !empty($size) ? $size : 50;
        $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;

        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }

        $data['basedir'] = $this->basedir;
        $data['baseurl'] = isset($this->baseurl) ? $this->baseurl : $this->basedir;
        if (!empty($this->filetype)) {
            $this->filetype = strtr($this->filetype, array('(' => '', ')' => ''));
            $data['filetype'] = explode('|',$this->filetype);
        } else {
            $data['filetype'] = array();
        }
        $numtypes = count($data['filetype']);
        if ($numtypes < 4) {
            for ($i = $numtypes; $i < 4; $i++) {
                $data['filetype'][] = '';
            }
        }
        $data['other'] = '';

        // allow template override by child classes
        if (empty($template)) {
            $template = 'imagelist';
        }
        return xarTplProperty('base', $template, 'validation', $data);
    }

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

                } else {
                    $this->validation = '';
                    if (!empty($validation['basedir'])) {
                        $this->validation = $validation['basedir'];
                    }
                    if (!empty($validation['baseurl'])) {
                        $this->validation .= '|' . $validation['baseurl'];
                    }
                    if (!empty($validation['filetype'])) {
                        $todo = array();
                        foreach ($validation['filetype'] as $ext) {
                            if (empty($ext)) continue;
                            $todo[] = $ext;
                        }
                        if (count($todo) > 0) {
                            $this->validation .= '|(';
                            $this->validation .= join('|',$todo);
                            $this->validation .= ')';
                        }
                    }
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

<?php
/**
 * Imagelist property
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

include_once "modules/base/xarproperties/Dynamic_ImageList_Property.php";

/**
 * Handle the imagelist property
 *
 * @package dynamicdata
 */
class Dynamic_FileList_Property extends Dynamic_ImageList_Property
{
    var $basedir = '';
    var $baseurl = null;
    var $filetype = '(pdf|pps|ods|doc|xls)';
    var $layout = 'default';
    var $tplmodule = 'base';
    var $display = false;

    
    function Dynamic_FileList_Property($args)
    {
         parent::Dynamic_ImageList_Property($args);
        if (empty($this->basedir) && !empty($this->validation)) {
             parent::parseValidation($this->validation);
        }
      
    }

    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations that need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return  parent::validateValue($value);
    }

   
    /*
     *
     */
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
        $data['onchange']   = isset($onchange) ? $onchange : null; // let tpl decide what to do
        $data['tplmodule']  = !isset($tplmodule) ? $this->tplmodule : $tplmodule;
        $data['layout']     = !isset($layout) ?  $this->layout : $layout;
        $data['display']    = isset($display) ?  $display: $this->display;        
        $data['class']      = !isset($class) ?  $this->class : $class;
        $template = (!isset($template) || empty($template)) ? 'filelist' : $template;

        return xarTplProperty($data['tplmodule'], $template, 'showinput', $data);

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
           $filepath=$baseurl.'/'.$value;
        } else {
           $filepath='';
        }
        if (file_exists($basedir.'/'.$value) && is_file($basedir.'/'.$value)) {
            $data['size'] = filesize($basedir.'/'.$value);
            $data['kbsize'] = $data['size']/1024;
            $data['kbsizeformatted'] = sprintf("%01.2f",$data['kbsize']);
        } else {
            $data['size'] = '';
            $data['kbsize'] = '';
            $data['kbsizeformatted'] = '';            
        }
        $data['value']=$value;
        $data['basedir']=$basedir;
        $data['baseurl'] = $baseurl;
        $data['filetype']=$filetype;
        $data['filepath']=$filepath;

        $template = (!isset($template) || empty($template)) ? 'filelist' : $template;
        return xarTplProperty('base', $template, 'showoutput', $data);

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
                              'id'         => 1200,
                              'name'       => 'filelist',
                              'label'      => 'File List',
                              'format'     => '1200',
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
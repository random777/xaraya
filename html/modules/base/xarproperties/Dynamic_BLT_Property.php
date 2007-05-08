<?php
/**
 * BL Template property
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
 * @package modules
 * @subpackage Base module
 * @author jonathan linowes
 * Class to handle dynamic blocklayout template property
 */
class Dynamic_BLT_Property extends Dynamic_Property
{
    var $options;
    var $basedir = '';
    var $filetype = '((xd)|(xt))?';
    var $basename = '';
    var $blttype = 'theme'; // 'module'
    var $bltmodule = '';    //  'base'
    var $bltsubdir = '';
    var $bltsubdata = '';

    function Dynamic_BLT_Property($args)
    {
        $this->Dynamic_Property($args);
        // options list and basedir
        if (!empty($this->validation)) {
            $this->parseValidation($this->validation);
        }
    }

    function checkInput($name='', $value = null)
    {
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            if (!xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET)) {return;}
        }
        return $this->validateValue($value);
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

    function showInput($args = array())
    {
        extract($args);
        $data = array();

        if (!isset($value)) {
            $value = $this->value;
        }
        if (!empty($this->basedir)) {
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

        $data['name']    = $name;
        $data['value']    = $value;
        $data['id']      = $id;
        $data['options'] = $options;
        $data['tabindex']= !empty($tabindex) ? $tabindex : 0;
        $data['invalid'] = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';
        $data['blttype'] = $this->blttype;
        $data['bltmodule'] = $this->bltmodule;
        $data['bltsubdir'] = $this->bltsubdir;
        $data['bltsubdata'] = $this->bltsubdata;
        $data['bltbasedir'] = $this->basedir;

        $template="";
        return xarTplProperty('base', 'bltemplate', 'showinput', $data);

    }

    function showOutput($args = array())
    {
        extract($args);
        $data = array();

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

        } else {
        //    return xarVarPrepForDisplay($value);
            $value='FILE-NOT-EXISTS';
            //return '';
        }

        // prepare value for output
        $value = basename( $value, '.xt' );
        if (!empty($this->bltsubdir)) {
            $value = $this->bltsubdir . '/' . $value;
        }
        $data['bltfile']=$value;
        $data['blttype']=$this->blttype;
        $data['bltmodule']=$this->bltmodule;
        $data['bltsubdata']=$this->bltsubdata;
        $data['bltbasedir'] = $this->basedir;

        $template="";
        return xarTplProperty('base', 'bltemplate', 'showoutput', $data);

    }

    function parseValidation($validation = '')
    {
        if (!empty($validation)) {
            // type:module:subdir:subdata
            $fields = explode(':',$validation);
            $this->blttype = trim($fields[0]);
            if (count($fields) > 1) {
                $this->bltmodule = trim($fields[1]);
                if (count($fields) > 2) {
                    $this->bltsubdir = trim($fields[2]);
                    if (count($fields) > 3) {
                        $this->bltsubdata = trim($fields[3]);
                    }
                }
            }
        }

        // default to theme (NOT MODULE)
        if (empty($this->blttype)) {
            $this->blttype = 'theme';
        }

        // set basedir
        switch ($this->blttype) {
            case 'module' :
                $curtheme = xarTplGetThemeDir();
                if (empty($this->bltmodule)) {
                    // default base
                    $this->bltmodule = 'base';
                }
                $this->basedir = $curtheme . '/modules/' . $this->bltmodule . '/includes';
                break;
            case 'theme' :
                $curtheme = xarTplGetThemeDir();
                $this->basedir = $curtheme . '/includes';
                break;
            case 'system' :
                $this->basedir = '';
                break;
        }
        if (!empty($this->bltsubdir)) {
            $this->basedir = $this->basedir . '/' . $this->bltsubdir;
        }
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
                              'id'         => 666,
                              'name'       => 'bltemplate',
                              'label'      => 'BL Template',
                              'format'     => '666',
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
        $data['name']       = !empty($name) ? $name : 'dd_'.$this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_'.$this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 1;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation);
        }
        $data['blttype'] = $this->blttype;
        $data['bltmodule'] = $this->bltmodule;
        $data['bltsubdir'] = $this->bltsubdir;
        $data['bltsubdata'] = $this->bltsubdata;
        $data['bltbasedir'] = $this->basedir;

        // allow template override by child classes (or in BL tags/API calls)
        if (empty($template)) {
            $template = 'bltemplate';
        }

        return xarTplProperty('base', $template, 'validation', $data);
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

        // in case we need to process additional input fields based on the name
        if (empty($name)) {
            $name = 'dd_'.$this->id;
        }
        // do something with the validation and save it in $this->validation
        $this->blttype = $this->bltmodule = $this->bltsubdir = $this->bldsubdata = '';
        if (isset($validation)) {
            if (is_array($validation)) {
                if (!empty($validation['blttype'])) {
                    $this->blttype = $validation['blttype'];
                }
                if (!empty($validation['bltmodule'])) {
                    $this->bltmodule = $validation['bltmodule'];
                }
                if (!empty($validation['bltsubdir'])) {
                    $this->bltsubdir = $validation['bltsubdir'];
                }
                if (!empty($validation['bltsubdata'])) {
                    $this->bltsubdata = $validation['bltsubdata'];
                }
                // build it if any exists
                if ($this->blttype !== '' || $this->bltmodule !== '' || $this->bltsubdir !== '' || $this->bltsubdata !== '') {
                    $this->validation = $this->blttype .':'. $this->bltmodule .':'. $this->bltsubdir .':'. $this->bltsubdata;
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

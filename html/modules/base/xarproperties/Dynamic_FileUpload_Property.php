<?php
/**
 * File upload property
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @todo Handle URL decoding of files for storage, and encoding in links.
 *       This is important as URL encoding should *not* appear in a stored filename.
 *
 * Dynamic File Upload Property
 */
/* Include parent class */
include_once "modules/dynamicdata/class/properties.php";

/**
 * Class to handle file upload properties
 *
 * @package dynamicdata
 */

class Dynamic_FileUpload_Property extends Dynamic_Property
{
    // Standard properties.
    var $size = 40;
    var $maxsize = 1000000;
    var $basePath;
    var $basedir = '';
    var $filetype;
    var $file_mode = 0777;

    // 'uploads' module properties.
    var $UploadsModule_isHooked = FALSE;
    var $importdir = null;
    var $multiple = TRUE;
    var $methods = array(
        'trusted'  => false,
        'external' => false,
        'upload'   => false,
        'stored'   => false
    );

    // This is used by Dynamic_Property_Master::addProperty() to set the $object->upload flag.
    var $upload = true;

    // Constructor method.
    function Dynamic_FileUpload_Property($args)
    {
        // Parent constructor.
        parent::Dynamic_Property($args);

        if (empty($this->id)) $this->id = $this->name;

        // Determine if the uploads module is hooked to the calling module.
        // If so, we will use the uploads modules functionality.
        if (xarVarGetCached('Hooks.uploads', 'ishooked')) {
            $this->UploadsModule_isHooked = TRUE;
        } else {
            // FIXME: this doesn't take into account the itemtype or non-main module objects.
            $list = xarModGetHookList(xarModGetName(), 'item', 'transform');
            foreach ($list as $hook) {
                if ($hook['module'] == 'uploads') {
                    $this->UploadsModule_isHooked = TRUE;
                    break;
                }
            }
        }

        if (!isset($this->validation)) $this->validation = '';

        // Always parse validation to preset methods here.
        $this->parseValidation($this->validation);
    }

    function checkInput($name='', $value = null)
    {
        if (empty($name)) $name = 'dd_' . $this->id;

        // Store the fieldname for validations who need them (e.g. file uploads)
        $this->fieldname = $name;
        if (!isset($value)) {
            xarVarFetch($name, 'isset', $value,  NULL, XARVAR_DONT_SET);
        }

        return $this->validateValue($value);
    }

    function validateValue($value = null)
    {
        // The variable corresponding to the file upload field is no longer set in PHP 4.2.1+
        // but we're using a hidden field to keep track of any previously uploaded file here
        if (!isset($value)) $value = $this->value;

        if (isset($this->fieldname)) {
            $name = $this->fieldname;
        } else {
            $name = 'dd_' . $this->id;
        }

        // Retrieve new value for preview + new/modify combinations.
        if (xarVarIsCached('DynamicData.FileUpload', $name)) {
            $this->value = xarVarGetCached('DynamicData.FileUpload', $name);
            return true;
        }

        // Get the filename from the value, which could contain some path information
        $fileName = basename($value);

        // If the uploads module is hooked in, use it's functionality instead.
        // Move this to another method, to keep the non-hooked method cleaner.
        if ($this->UploadsModule_isHooked == TRUE) {
            return $this->_validateValueUploadsHooked($name);
        }

        // The form item for uploading new files.
        $upname = $name . '_upload';
        $filetype = $this->filetype;

        if (isset($_FILES[$upname])) {
            $file =& $_FILES[$upname];
        } else {
            $file = array();
        }

        if (isset($file['tmp_name']) && is_uploaded_file($file['tmp_name']) && $file['size'] > 0) {
            if ($file['size'] > $this->maxsize) {
                $this->invalid = xarML('file too big');
                return false;
            }

            // if the uploads module is hooked (to be verified and set by the calling module)
            if (!empty($_FILES[$upname]['name'])) {
                $fileName = xarVarPrepForOS(basename(strval($file['name'])));

                if (!empty($filetype) && !preg_match("/\.$filetype$/", $fileName)) {
                    $this->invalid = xarML('unsupported file type');
                    return false;
                } else {
                    $destination = str_replace('//', '/', $this->basePath . '/' . $this->basedir . '/'. $fileName);

                    // Make sure the directory exists, create it if not.
                    if (!file_exists(dirname($destination))) {
                        // If the basePath does not exist, then stop.
                        // We will only create the basedir under the basePath.
                        if (!is_dir($this->basePath)) {
                            $this->invalid = xarML('configuration error: no base path');
                            return false;
                        }

                        // Allow for recursion (the recursion flag is available from PHP5 only)
                        // Loop through each level in the basedir, creating a folder.
                        $basedir_parts = explode('/', trim($this->basedir, '/'));
                        $destination_walk = $this->basePath;
                        foreach($basedir_parts as $basedir_part) {
                            $destination_walk .= '/' . $basedir_part;

                            // Directory may already exist.
                            if (is_dir($destination_walk)) continue;

                            // A file may stand in our way.
                            if (file_exists($destination_walk)) {
                                $this->invalid = xarML('file exists in place of directory');
                                $this->value = null;
                                return false;
                            }

                            // We may need to change umask or set the permissions explicitly, so that files
                            // can be managed outside of the web processes.
                            if (!mkdir($destination_walk, $this->file_mode)) {
                                $this->invalid = xarML('failed to create directory for file ');
                                $this->value = null;
                                return false;
                            }
                            @chmod($destination_walk, $this->file_mode);
                        }
                    }

                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        $this->invalid = xarML('file upload failed');
                        $this->value = null;
                        return false;
                    }
                    @chmod($destination, $this->file_mode);
                }

                $this->value = $fileName;

                // save new value for preview + new/modify combinations
                xarVarSetCached('DynamicData.FileUpload', $name, $fileName);
            } else {
                // TODO: assign random name + figure out mime type to add the right extension ?
                $this->invalid = xarML('file name for upload');
                $this->value = null;
                return false;
            }
        } elseif (xarVarIsCached('DynamicData.FileUpload', $name)) {
            // Retrieve new value for preview + new/modify combinations.
            $this->value = xarVarGetCached('DynamicData.FileUpload', $name);
        } elseif (!empty($value) && !is_numeric($value) && !stristr($value, ';')) {
            if (!empty($filetype) && !preg_match("/\.${filetype}$/", $fileName)) {
                $this->invalid = xarML('file type');
                $this->value = null;
                return false;
            } elseif (!file_exists($this->basePath . '/' . $this->basedir . '/'. $fileName) || !is_file($this->basePath . '/' . $this->basedir . '/'. $fileName)) {
                $this->invalid = xarML('file');
                $this->value = null;
                return false;
            }
            $this->value = $value;
        } else {
            $this->value = '';
        }

        // Make sure the value has the basedir prepended, if available.
        $this->value = $this->basedir . ($this->basedir == '' ? '' : '/') . $fileName;

        return true;
    }

    // Validate functionality for when uploads are hooked.
    function _validateValueUploadsHooked($name)
    {
        // set override for the upload/import paths if necessary
        if (!empty($this->basedir) || !empty($this->importdir)) {
            $override = array();
            if (!empty($this->basedir)) $override['upload'] = array('path' => $this->basedir);
            if (!empty($this->importdir)) $override['import'] = array('path' => $this->importdir);
        } else {
            $override = null;
        }

        $return = xarModAPIFunc(
            'uploads', 'admin', 'validatevalue',
            array(
                'id' => $name, // not $this->id
                'value' => $value,
                // pass the module id, item type and item id (if available) for associations
                'moduleid' => $this->_moduleid,
                'itemtype' => $this->_itemtype,
                'itemid'   => !empty($this->_itemid) ? $this->_itemid : null,
                'multiple' => $this->multiple,
                'format' => 'fileupload',
                'methods' => $this->methods,
                'override' => $override,
                'maxsize' => $this->maxsize
            )
        );

        if (!isset($return) || !is_array($return) || count($return) < 2) {
            $this->value = null;
            // CHECKME: copied from autolinks :)
            // 'text' rendering will return an array
            $errorstack = xarErrorGet();
            $errorstack = array_shift($errorstack);
            $this->invalid = $errorstack['short'];
            xarErrorHandled();
            return false;
        }

        if (empty($return[0])) {
            $this->value = null;
            $this->invalid = xarML('value');
            return false;
        } else {
            if (empty($return[1])) {
                $this->value = '';
            } else {
                $this->value = $return[1];
            }

            // save new value for preview + new/modify combinations
            xarVarSetCached('DynamicData.FileUpload', $name, $this->value);
            return true;
        }
    }

    function showInput($args = array())
    {
        extract($args);

        if (empty($name)) $name = 'dd_' . $this->id;
        if (empty($id)) $id = $name;
        if (!isset($value)) $value = $this->value;
        $upname = $name . '_upload';

        // inform anyone that we're showing a file upload field, and that they need to use
        // <form ... enctype="multipart/form-data" ... > in their input form
        xarVarSetCached('Hooks.dynamicdata', 'withupload', 1);

        if ($this->UploadsModule_isHooked == TRUE) {
            // user must have hooked the uploads module after uploading files directly
            // CHECKME: remove any left over values - or migrate entries to uploads table ?
            if (!empty($value) && !is_numeric($value) && !stristr($value, ';')) $value = '';

            // set override for the upload/import paths if necessary
            if (!empty($this->basedir) || !empty($this->importdir)) {
                $override = array();
                if (!empty($this->basedir)) {
                    $override['upload'] = array('path' => $this->basedir);
                }
                if (!empty($this->importdir)) {
                    $override['import'] = array('path' => $this->importdir);
                }
            } else {
                $override = null;
            }
            return xarModAPIFunc('uploads', 'admin', 'showinput',
                array(
                    'id' => $name, // not $this->id
                    'value' => $value,
                    'multiple' => $this->multiple,
                    'format' => 'fileupload',
                    'methods' => $this->methods,
                    'override' => $override,
                    'invalid' => $this->invalid
                )
            );
        }

        // Only non-hooked functionality below.

        // Remove any left over values.
        if (!empty($value) && (is_numeric($value) || stristr($value, ';'))) $value = '';

        if (!empty($this->filetype)) {
            $extensions = $this->filetype;
            // TODO: get rid of the break (not used anyway)
            $allowed = '<br />' . xarML('Allowed file types : #(1)', $extensions); // DEPRECATED
        } else {
            $extensions = '';
            $allowed = ''; // DEPRECATED
        }

        $data = array();

        // Extract the filename, to be displayed in the template, since
        // there could be a path component to the value.
        $data['fileName'] = basename($value);

        // Pass the basePath and basedir values into the template, in case
        // someone wants to use them to provide a drop-down selection of
        // existing files to select.
        $data['basePath']   = $this->basePath;
        $data['basedir']    = $this->basedir;

        $data['name']       = $name;
        $data['value']      = $value;
        $data['id']         = $id;
        $data['upname']     = $upname;
        $data['size']       = !empty($size) ? $size : $this->size;
        $data['maxsize']    = !empty($maxsize) ? $maxsize : $this->maxsize;
        $data['tabindex']   = !empty($tabindex) ? $tabindex  : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)',  $this->invalid) : '';
        $data['allowed']    = $allowed; // DEPRECATED
        $data['extensions'] = $extensions;

        return xarTplProperty('base', 'fileupload', 'showinput', $data);
    }

    function showOutput($args = array())
    {
        extract($args);

        if (!isset($value)) {
            $value = $this->value;
        }

        if ($this->UploadsModule_isHooked) {
            return xarModAPIFunc('uploads', 'user', 'showoutput',
                array(
                    'value' => $value,
                    'format' => 'fileupload',
                    'multiple' => $this->multiple
                )
            );
        }

        // Non upload-hooked code below.

        // Note: you can't access files directly in the document root here
        if (!empty($value)) {
            if (is_numeric($value) || stristr($value, ';')) {
                // User must have unhooked the uploads module.
                // Remove any left over values.
                // i.e. the value stored when hooked is incomptible with the value
                // stored when not hooked.
                return '';
            }

            $data = array();

            // The 'value' contains any variable parts of the full path, so we only
            // need to append it to the basePath to get the physical file.
            // FIXME: the template treats the presence of the basedir as the all-go to
            // provide a link to the file. The file may *not* be in a web-accessible
            // directory, so the assumption cannot be made.
            $file_path = $this->basePath . '/' . $value;
            if (!file_exists($file_path) || !is_file($file_path)) {
                // The file is no longer there.
                $value = NULL;
            }

            // The directory the file is (or should be) in.
            $data['dir'] = $this->basePath . ($this->basedir == '' ? '' : '/') . $this->basedir;

            // If the file is under the web root, then let the template know by 
            // passing it the virtual directory (usefully called 'basedir' in the template;-)
            // To make a good guess, we will see if the current working directory, assumed to 
            // be the web root, fits into the physical location of the file.
            // Notes:
            // - This does not detect a web-root-available directory that has been locked down through .htaccess
            // - The current working directory is assumed to be the web root, i.e. the entry-point directory.
            $web_root = getcwd();
            if (preg_match('/' . preg_quote($web_root . '/', '/') . '/', $file_path)) {
                $data['basedir'] = dirname(preg_replace('/^' . preg_quote($web_root . '/', '/') . '/', '', $file_path));
            }

            $data['fileName'] = basename($value);
            $data['value'] = $value;

            $template = '';
            return xarTplProperty('base', 'fileupload', 'showoutput', $data);
        } else {
            return '';
        }
    }


    // Explanation of the parts of the file upload path, as set up here:
    //  basePath/basedir/filename
    // - basePath: the non-variable absolute path on the server (this directory should already exist)
    // - basedir: the variable part (subdirectories that may or may not exist)
    // - fileName: the physical file
    // Note: the basedir is stored with the filename when 'uploads' is not hooked.
    // When 'uploads' is hooked, it does its own thing.
    function parseValidation($validation = '', $transform_fields = true)
    {
        if ($this->UploadsModule_isHooked == TRUE) {
            // Fetch uploads module hook-specific configuration.
            list($this->multiple, $this->methods, $this->basedir, $this->importdir)
                = xarModAPIFunc('uploads', 'admin', 'dd_configure', $validation);
            $this->maxsize = xarModGetVar('uploads', 'file.maxsize');
            // TODO: the uploads module *probably* wants to store its files under the
            // system var area, but this is how it handles it for now, with the
            // assumption that 'var' will be in the web root folder.
            $this->basePath = getcwd();
            $this->filetype = '';
        } else {
            // Fetch configuration from the property.
            // Unusually, this property uses ';' for a field separator, rathern than ':' as for most
            // other properties. Watch out for that when configuring this property.

            // specify base directory and optional file types in validation
            // field - e.g. this/dir or this/dir;(gif|jpg|png|bmp) or this/dir;(gif|jpg|png|bmp);1500000
            $fields = explode(';', $validation);

            // The first field is the base directory.
            if (isset($fields[0]) && trim($fields[0]) != '') {
                $prop_path = rtrim(trim($fields[0]), '/');
                $prop_dir = '';

                // If the basedir supplied contains {var} then expand that.
                // We discard anything that comes before '{var}' and anything up to the next '/' after it.
                // We expect {var} to be used like this: {var}/custom_path
                if (preg_match('/{var}/', $prop_path)) {
                    $prop_path = preg_replace('#{var}[^/]*/#', realpath(xarCoreGetVarDirPath()) . '/', $prop_path);
                    $prop_path = rtrim($prop_path, '/');
                }

                // If the path is relative, then make it absolute (relative to the current working directory)
                if (substr($prop_path, 0, 1) != '/') $prop_path = getcwd() . '/' . $prop_path;

                // Now we should have an absolute path to where files will be uploaded,
                // but the path may still contain variable parts. Extract these now, and move
                // them to the basedir.
                if (preg_match('#{[^/}]+}#', $prop_path)) {
                    // For prop_dir (the variable part) chop off path components up to the first {field}
                    $prop_dir = preg_replace('#^/*([^/{]+/)+(?=[^{/]*{)#', '', $prop_path);
                    // The prop path is everything up to the path component containing the {field}
                    $prop_path = preg_replace('#/*[^/]*{.*$#', '', $prop_path);
                }

                // TODO: Do we need to check whether the basedir has already been set
                // elsewhere, before overwriting it?
                $this->basePath = $prop_path;
                $this->basedir = $prop_dir;
            } else {
                // No base directory supplied, so default to '{var}/uploads', with no basedir.
                $this->basePath = realpath(xarCoreGetVarDirPath()) . '/uploads';
                $this->basedir = '';
            }

            // TODO: allow descendant class to override filetype.
            if (isset($fields[1])) $this->filetype = trim($fields[1]); else $this->filetype = '';
            if (isset($fields[2])) $this->maxsize = trim($fields[2]);
        }

        if ($transform_fields) {
            // Note: {theme} will be replaced by the current theme directory - e.g. {theme}/images -> themes/Xaraya_Classic/images
            if (!empty($this->basedir) && preg_match('/\{theme\}/', $this->basedir)) {
                $curtheme = xarTplGetThemeDir();
                $this->basedir = preg_replace('/\{theme\}/', $curtheme, $this->basedir);
            }

            // Note: {user} will be replaced by the current user uploading the file
            // e.g. {var}/uploads/{user} -> var/uploads/myusername_123
            $uname = xarVarPrepForOS(xarUserGetVar('uname'));
            $uid = xarUserGetVar('uid');

            // The basedir for both standalone and uploads-hooked operation.
            if (!empty($this->basedir) && preg_match('/\{user\}/', $this->basedir)) {
                // We add the userid just to make sure it's unique e.g. when filtering
                // out unwanted characters through xarVarPrepForOS, or if the database makes
                // a difference between upper-case and lower-case and the OS doesn't...
                $this->basedir = preg_replace('/\{user\}/', $uname . '_' . $uid, $this->basedir);
            }
            if (!empty($this->basedir) && preg_match('/\{userid\}/', $this->basedir)) {
                $this->basedir = preg_replace('/\{user\}/', "$uid", $this->basedir);
            }

            // This one for uploads-hooked operation only.
            if (!empty($this->importdir) && preg_match('/\{user\}/', $this->importdir)) {
                $this->importdir = preg_replace('/\{user\}/', $uname . '_' . $uid, $this->importdir);
            }
            if (!empty($this->importdir) && preg_match('/\{userid\}/', $this->importdir)) {
                $this->importdir = preg_replace('/\{user\}/', "$uid", $this->importdir);
            }
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
            'id'         => 9,
            'name'       => 'fileupload',
            'label'      => 'File Upload',
            'format'     => '9',
            'validation' => '',
            'source'         => '',
            'dependancies'   => '',
            'requiresmodule' => '',
            'aliases'        => '',
            'args'           => serialize($args),
        );

        return $baseInfo;
     }

    function showValidation($args = array())
    {
        extract($args);

        $data = array();
        $data['name']       = !empty($name) ? $name : 'dd_' . $this->id;
        $data['id']         = !empty($id)   ? $id   : 'dd_' . $this->id;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) :'';

        $data['size']       = !empty($size) ? $size : 50;
        $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;

        if (isset($validation)) {
            $this->validation = $validation;
            $this->parseValidation($validation, false);
        }

        if (xarVarGetCached('Hooks.uploads','ishooked')) {
            $data['ishooked'] = true;
        } else {
            $data['ishooked'] = false;
        }
        if ($data['ishooked']) {
            $data['multiple'] = $this->multiple;
            $data['methods'] = $this->methods;
            $data['basedir'] = $this->basedir;
            $data['importdir'] = $this->importdir;
        } else {
            $data['basedir'] = $this->basedir;
            if (!empty($this->filetype)) {
                $this->filetype = strtr($this->filetype, array('(' => '', ')' => ''));
                $data['filetype'] = explode('|', $this->filetype);
            } else {
                $data['filetype'] = array();
            }
            $numtypes = count($data['filetype']);
            if ($numtypes < 4) {
                for ($i = $numtypes; $i < 4; $i++) {
                    $data['filetype'][] = '';
                }
            }
            $data['maxsize'] = $this->maxsize;
        }
        $data['other'] = '';

        // allow template override by child classes
        if (empty($template)) {
            $template = 'fileupload';
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

        // Do something with the validation and save it in $this->validation
        if (isset($validation)) {
            if (is_array($validation)) {
                if (!empty($validation['other'])) {
                    $this->validation = $validation['other'];

                } elseif ($this->UploadsModule_isHooked) {
                    $this->validation = '';
                    if (!empty($validation['multiple'])) {
                        $this->validation = 'multiple';
                    } else {
                        $this->validation = 'single';
                    }

                    // CHECKME: verify format of methods(...) part
                    if (!empty($validation['methods'])) {
                        $todo = array();
                        foreach (array_keys($this->methods) as $method) {
                            if (!empty($validation['methods'][$method])) {
                                $todo[] = '+' .$method;
                            } else {
                                $todo[] = '-' .$method;
                            }
                        }
                        if (count($todo) > 0) {
                            $this->validation .= ';methods(';
                            $this->validation .= join(',',$todo);
                            $this->validation .= ')';
                        }
                    }
                    if (!empty($validation['basedir'])) {
                        $this->validation .= ';basedir(' . $validation['basedir'] . ')';
                    }
                    if (!empty($validation['importdir'])) {
                        $this->validation .= ';importdir(' . $validation['importdir'] . ')';
                    }
                } else {
                    $this->validation = '';
                    if (!empty($validation['basedir'])) {
                        $this->validation = $validation['basedir'];
                    }
                    if (!empty($validation['filetype'])) {
                        $todo = array();
                        foreach ($validation['filetype'] as $ext) {
                            if (empty($ext)) continue;
                            $todo[] = $ext;
                        }
                        if (count($todo) > 0) {
                            $this->validation .= ';(';
                            $this->validation .= join('|', $todo);
                            $this->validation .= ')';
                        }
                    }
                    if (!empty($validation['maxsize'])) {
                        if (empty($todo)) {
                            $this->validation .= ';';
                        }
                        $this->validation .= ';' . $validation['maxsize'];
                    }
                }
            } else {
                $this->validation = $validation;
            }
        }

        // Return 'success'
        return true;
    }

}

?>
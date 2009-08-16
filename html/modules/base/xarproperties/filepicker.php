<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marc Lutolf <mfl@netspan.ch>
 */
sys::import('modules.base.xarproperties.dropdown');
sys::import('xaraya.structures.relativedirectoryiterator');

/**
 * Handle file picker property
 */
class FilePickerProperty extends SelectProperty
{
    public $id         = 30052;
    public $name       = 'filepicker';
    public $desc       = 'File Picker';

    public $initialization_basedirectory;
    public $validation_file_extensions   = '';
    public $validation_matches           = '';
    public $display_fullname             = false;

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->filepath = 'modules/base/xarproperties';
        if (empty($this->initialization_basedirectory)) $this->initialization_basedirectory = realpath('var');
    }

    public function showInput(Array $data = array())
    {
        if (isset($data['basedir'])) $this->initialization_basedirectory = $data['basedir'];
        if (isset($data['matches'])) $this->validation_matches = $data['matches'];
        if (isset($data['extensions'])) $this->validation_file_extensions = $data['extensions'];
        if (isset($data['display_fullname'])) $this->display_fullname = $data['display_fullname'];
        return parent::showInput($data);
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        $basedir = $this->initialization_basedirectory;
        $filetypes = $this->validation_file_extensions;
        $pos = strrpos($value, '.');
        $extension = substr($value,$pos+1);
        if (!empty($value) &&
            //slight change to allow spaces
            preg_match('/^[a-zA-Z0-9_\/.\-\040]+$/',$value) &&
            preg_match("/^$extension/",$filetypes) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            return true;
        } elseif (empty($value)) {
            return true;
        }
        $this->invalid = xarML('incorrect selection: #(1) for #(2)', $value, $this->name);
        $this->value = null;
        return false;
    }

    function getOptions()
    {
        $options = $this->getFirstline();
        if (count($this->options) > 0) {
            if (!empty($firstline)) $this->options = array_merge($options,$this->options);
            return $this->options;
        }
        
        if (empty($this->initialization_basedirectory)) return array();
        $dir = new RelativeDirectoryIterator($this->initialization_basedirectory);

        if (!is_array($this->validation_file_extensions)) $extensions = explode(',',$this->validation_file_extensions);
        else $extensions = $this->validation_file_extensions;
        
        for($dir->rewind();$dir->valid();$dir->next()) {
            if($dir->isDir()) continue; // no dirs
            if(!empty($this->validation_file_extensions) && !in_array($dir->getExtension(),$extensions)) continue;
            if($dir->isDot()) continue; // temp for emacs insanity and skip hidden files while we're at it
            $name = $dir->getFileName();
            if (!$this->display_fullname) $name = substr($name, 0, strlen($name) - strlen($dir->getExtension()) - 1);
            if(!empty($this->validation_matches) && (strpos($this->validation_matches,$name) === false)) continue;
            $options[] = array('id' => $name, 'name' => $name);
        }
        return $options;
    }
}
?>
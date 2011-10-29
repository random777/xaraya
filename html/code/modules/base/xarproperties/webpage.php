<?php
/**
 * @package modules
 * @subpackage base module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/68.html
 *
 * @author mikespub <mikespub@xaraya.com>
*/
sys::import('modules.base.xarproperties.dropdown');
/**
 * Class to handle dynamic html page property
 */
class HTMLPageProperty extends SelectProperty
{
    public $id         = 13;
    public $name       = 'webpage';
    public $desc       = 'HTML Page';

    public $basedir  = '';
    public $filetype = '((xml)|(html))?';

    function __construct(ObjectDescriptor $descriptor)
    {
        parent::__construct($descriptor);
        $this->tplmodule = 'base';
        $this->template = 'webpage';
        // specify base directory in configuration field
        if (empty($this->basedir) && !empty($this->configuration)) {
            // Hack for passing this thing into transform hooks
            // validation may start with 'transform:' and we
            // obviously dont want that in basedir
            if(substr($this->configuration,0,10) == 'transform:') {
                $basedir = substr($this->configuration,10,strlen($this->configuration)-10);
            } else {
                $basedir = $this->configuration;
            }
            $this->basedir = $basedir;
        }
    }

    public function validateValue($value = null)
    {
        if (!parent::validateValue($value)) return false;

        $basedir = $this->basedir;
        $filetype = $this->filetype;
        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            return true;
        } elseif (empty($value)) {
            return true;
        }
        $this->invalid = xarML('selection: #(1)', $this->name);
        $this->value = null;
        return false;
    }

    public function showInput(Array $data = array())
    {
        if (!isset($data['value'])) {
            $data['value'] = $this->value;
        }
/*        if (!isset($data['options']) || count($data['options']) == 0) {
            $data['options'] = $this->getOptions();
        }
        if (count($data['options']) == 0 && !empty($this->basedir)) {
            $files = xarMod::apiFunc('dynamicdata','admin','browse',
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
*/
        return parent::showInput($data);
    }

    public function showOutput(Array $data = array())
    {
        extract($data);

        if (!isset($value)) $value = $this->value;

        $basedir = $this->basedir;
        $filetype = $this->filetype;
        if (!empty($value) &&
            preg_match('/^[a-zA-Z0-9_\/.-]+$/',$value) &&
            preg_match("/$filetype$/",$value) &&
            file_exists($basedir.'/'.$value) &&
            is_file($basedir.'/'.$value)) {
            $srcpath = join('', @file($basedir.'/'.$value));
        } else {
            $srcpath='';
        }
        $data['value']    = $value;
        $data['basedir']  = $basedir;
        $data['filetype'] = $filetype;
        $data['srcpath']  = $srcpath;
        return parent::showOutput($data);
    }
    public function getOptions()
    {
        $options = parent::getOptions();
        if (count($options) == 0 && !empty($this->basedir)) {
            $files = xarMod::apiFunc('dynamicdata','admin','browse',
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
        return $options;
    }
}
?>
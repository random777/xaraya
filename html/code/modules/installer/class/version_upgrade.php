<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

class RevisionUpgrade extends Object
{
    public $errormessage;
    public $message;
    public $version;
    
    public function __construct($version) {
        $this->version = $version;
        $this->errormessage = xarML('Some parts of the upgrade failed. Check the reference(s) above to determine the cause.');
        $this->message = xarML('The upgrade to version #(1) was successfully completed',$version);
    }
    
    public function get_steps($version=null) {
        if (empty($version)) $version = $this->version;
        $version = $this->get_numeric_version($version);
        $path = sys::code() . 'modules/installer/upgrades/' . $version;
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
        $steps = array();
        foreach ($items as $item) {
            // Ignore directories and non-PHP files
//            if ($item->getExtension() != 'php') continue;  // Only works with PHP 5.3.6
            $ext = substr($item->getFileName(),strlen($item->getFileName())-3);
            if ($ext != 'php') continue;  // Only works with PHP 5.3.6
            //Ignore the main.php file
            if ($item->getFileName() == 'main.php') continue;
            $steps[] = $item;
        }
        return $steps;
    }

    protected function get_numeric_version($version) {
        sys::import('xaraya.version');
        $versionarray = xarVersion::parse($version);
        $numeric_version = $versionarray['major'] . $versionarray['minor'] .$versionarray['micro'];
        return $numeric_version;
    }

    public function run_upgrade($version=null)
    {
        if (empty($version)) $version = $this->version;
        $steps = $this->get_steps();

        $data['message'] = "";
        $data['tasks'] = array();
        $data['errormessage'] = "";
        $data['failures'] = array();
        
        sys::import('modules.installer.class.upgrade');
        foreach ($steps as $step) {
            if (!Upgrader::loadFile($step->getPathname())) {
                $data['failures'][] = array(
                    'reply' => xarML('Failed!'),
                    'description' => Upgrader::$errormessage,
                    'reference' => $step->getFileName(),
                    'success' => false,
                );
                $data['errormessage'] = xarML('Some checks failed. Check the reference(s) above to determine the cause.');
                continue;
            }
            $classname = substr($step->getFileName(),0,strlen($step->getFileName()) - 4);
            $class = new $classname();
            $result = $class->runx();
            $data['tasks'][] = $class;        
            if (!$result) {
                $data['errormessage'] = xarML('Some parts of the upgrade failed. Check the reference(s) above to determine the cause.');
            }

        }
        return $data;
    }
}

?>
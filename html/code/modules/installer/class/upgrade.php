<?php
/**
 * Xaraya Jamaica Upgrade
 *
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marc Lutolf <mfl@netspan.ch>
 */

class Upgrader extends Object
{
    private static $instance          = null;
    public static $errormessage       = '';

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function loadFile($path)
    {
        if (file_exists($path)) {
        } else {
            $path = sys::code() . 'modules/installer/' . $path;
            if (!file_exists($path)) {
                self::$errormessage = xarML("The required file '#(1)' was not loaded.", $path);
                return false;
            }
        }
        include_once($path);
        return true;
    }

    public static function getComponents()
    {
        $checkpath = sys::code() . 'modules/installer/checks';
        $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($checkpath), RecursiveIteratorIterator::SELF_FIRST);
//        $iterator = new RecursiveDirectoryIterator($checkpath);
//        $items = dir_to_array($iterator);
        return $items;
    }
}

function dir_to_array(RecursiveDirectoryIterator $iterator)
{
    $array = array();
    foreach ($iterator as $fileinfo) {
        // Get the info on the current object
        $current['info'] = $fileinfo;
        // If this is a dir, recurse
        if ($fileinfo->isDir()) $current['children'] = dir_to_array($iterator->getChildren());
        // Append the current item to this level
        $array[] = $current;
    }
    return $array;
}

// Preparations complete. Call the upgrader now
$upgrader = Upgrader::getInstance();    

?>

<?php
// {{{ Header
/*
 * -File     $Id: Properties.php,v 1.13 2003/05/02 14:31:56 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Andrzej Nowodworski, a.nowodworski@learn.pl
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import("phing.system.io.File");

/**
 * Convenience class for reading property files.
 *  @package  phing.system.util
 *
 */

class Properties {

    var $properties = array();

    /** empty constructor */
    function Properties() {}

    function load(&$file) {
        if (isInstanceOf($file, 'File')) {
            if ($file->canRead()) {
                //$Logger =& System::GetLogger();
                $ini_array = better_parse_ini_file($file->getPath(), false);
                // parse_ini_file() returns 0 not FALSE as of PHP 4.1.1 so don't use ===
                if ($ini_array !== 0 || $ini_array !== false) {
                    $this->properties = $ini_array;
                    //$Logger->Log(PH_LOG_EVENT, "Read properties file ".$file->getPath());
                    return true;
                } else {
                    $msg = "Properties::load() FAILED. Cannot parse $pathname. $php_errormsg";
                    throw (new RuntimeException($msg));
                }
            } else {
                throw (new IOException("Can not read file ".$file->getPath()));
                return;
            }
        } else {
            throw (new RuntimeException("Argument is not a File object"), __FILE__, __LINE__);
            return;
        }
    }

    function store(&$file) {
        // stores the properties in this object in the file denoted
        // if file is not given and the properties were loaded from a
        // file prior, this method stores them in the file used by load()
    }

    function getProperty($strKey) {
        if (isset($this->properties[$strKey])) {
            return $this->properties[$strKey];
        }
        return null;
    }

    function setProperty($strKey, $strValue) {
        $strKey   = (string) $strKey;
        $strValue = (string) $strValue;
        $oldValue = true;
        if (isset($this->properties[$strKey])) {
            $oldValue = $this->properties[$strKey];
        }
        $this->properties[$strKey] = $strValue;
        return $oldValue;
    }

    function propertyNames() {
        return $this->keys();
    }

    function containsKey($key) {
        return isset($this->properties[(string)$key]);
    }

    function keys() {
        return array_keys($this->properties);
    }

}
?>

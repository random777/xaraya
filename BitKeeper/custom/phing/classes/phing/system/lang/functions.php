<?php
// {{{ Header
/*
 * -File     $Id: functions.php,v 1.14 2003/06/04 12:22:36 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

/**
 * This file contains some procedural functions required throughout
 *
 * @package   phing.system.lang
 */


/** todo: comment this */
function is_trie($my_array) {
    foreach($my_array as $key => $value) {
        if(is_array($value)) {
            return 1;
            break;
        }
    }
}

/** tests if a string starts with a given string */
function strStartsWith($_check, $_string) {
    if (empty($_check) || $_check === $_string) {
        return true;
    } else {
        return (strpos((string) $_string, (string) $_check) === 0) ? true : false;
    }
}

/** tests if a string ends with a given string */
function strEndsWith($_check, $_string) {
    if (empty($_check) || $_check === $_string) {
        return true;
    } else {
        return (strpos(strrev($_string), strrev($_check)) === 0) ? true : false;
    }
}

function strIndexOf($needle, $hystack, $offset = 0) {
    return ((($res = strpos($hystack, $needle, $offset)) === false) ? -1 : $res);
}
function strLastIndexOf($needle, $hystack, $offset = 0) {
    // FIXME, use offset
    //$pos = strlen($hystack) - (strpos(strrev($hystack), strrev($needle)) + strlen($needle));
    //return ($pos === false ? -1 : $res);
    return ((($res = strrpos($hystack, $needle)) === false) ? -1 : $res);
}

/** converts a string to an indexed array of chars */
function strToCharArray($_string) {
    $ret = array();
    for ($i=0; $i<strlen($_string); array_push($ret, $_string[$i]), ++$i)
        ;
    return $ret;
}

function isInstanceOf(&$object, $classname) {
    if (is_object($object) && (get_class($object) === strtolower($classname))) {
        return true;
    }
    return false;
}

/* a natural way of getting a subtring, php's circular string buffer and strange
return values suck if you want to program strict as of C or friends */
function substring($string, $startpos, $endpos = -1) {
    $len    = strlen($string);
    $endpos = (int) (($endpos === -1) ? $len-1 : $endpos);
    if ($startpos > $len-1 || $startpos < 0) {
        trigger_error("substring(), Startindex out of bounds must be 0<n<$len", E_USER_ERROR);
    }
    if ($endpos > $len-1 || $endpos < $startpos) {
        trigger_error("substring(), Endindex out of bounds must be $startpos<n<".($len-1), E_USER_ERROR);
    }
    if ($startpos === $endpos) {
        return (string) $string{$startpos};
    } else {
        $len = $endpos-$startpos;
    }
    return (string) substr($string, $startpos, $len+1);
}

// workaround to compare two references if they are referring the same objcect
function compareReferences(&$a, &$b) {
    $tmp = uniqid("");
    $a->$tmp = (boolean) true;
    $result  = @ ($b->$tmp === true);
    unset($a->$tmp);
    return $result;
}
function getMicrotime() {
    list($usec, $sec) = explode(" ",microtime());
    return ((float)$usec + (float)$sec);
}

/* workaround for php <a 4.2.0 */
if (!function_exists('is_a')) {
    function is_a(&$object, $class_name) {
        if (get_class($object) === strtolower($class_name)) {
            return true;
        } else {
            return is_subclass_of($object, $class_name);
        }
    }
}

/**
 * array better_parse_ini_file (string $filename [, boolean $process_sections] )
 *
 * Purpose: Load in the ini file specified in filename, and return
 *          the settings in an associative array. By setting the
 *          last $process_sections parameter to true, you get a
 *          multidimensional array, with the section names and
 *          settings included. The default for process_sections is
 *          false.
 *
 * Return: - An associative array containing the data
 *         - false if any error occured
 *
 * Author: Sebastien Cevey <seb@cine7.net>
 *         Original Code base : <info@megaman.nl>
 *         changes for Phing: Manuel Holtgrewe <grin@gmx.net> 
 */
function better_parse_ini_file($filename, $process_sections = false) {
    $ini_array = array();
    $sec_name = "";
    $lines = file($filename);
    foreach($lines as $line) {
        $line = trim($line);

        if($line == "")
            continue;

        if($line[0] == "[" && $line[strlen($line) - 1] == "]") {
            $sec_name = substr($line, 1, strlen($line) - 2);
        } else if ($line[0] === "#" or $line[0] === ";") {
            continue;
        } else {
            $pos = strpos($line, "=");
            $property = substr($line, 0, $pos);
            $value = substr($line, $pos + 1);

            if($process_sections) {
                $ini_array[$sec_name][$property] = $value;
            } else {
                $ini_array[$property] = $value;
            }
        }
    }
    return $ini_array;
}

// (c) jean-christophe michel 2002
// changed by manuel holtgrewe
// patch with recent php functions

function better_var_export($var, $return = true, $depth = 0) {
    if (is_null($var))
        return "null";

    $result = str_repeat(" ", $depth);
    if (is_array($var)) {
        $result .= "array(\n";
        $depth += 4;
        foreach ($var as $key => $value) {
            $result .= better_var_export($key, true, $depth);
            $result .= " => ";
            $result .= better_var_export($value, true, 0);
            $result .= ",\n";
        }
        $result .= str_repeat(" ", $depth);
        $result .= ")";
        $depth -= 4;
    } elseif (is_string($var)) {
        $result .= "\"" . str_replace("\"", "\\\"", $var) . "\"";
    } elseif (is_bool($var)) {
        $result .= $var ? "true" : "false";
    } else {
        $result .= $var;
    }

    return $result;
}

?>

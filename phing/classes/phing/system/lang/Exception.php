<?php
// {{{ Header
/*
 * -File     $Id: Exception.php,v 1.15 2003/04/09 15:58:11 thyrell Exp $
 * -License LGPL (http://www.gnu.org/copyleft/lesser.html)   
 * -Copyright  2001, Thyrell
 * -Author   Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import("phing.system.lang.RuntimeException");

/**
 * The class Exception and its subclasses are used in conjunction with
 * the throw() function to throw errors.
 *
 * This file contains procedural functions
 * Remove them as soon as stable PHP supports try/catch and throw
 *  @package   phing.system.lang
 */

class Exception {

    var $msg;
    var $cause;
    var $file;
    var $line;

    function Exception($strMessage = null, $cause = null) {
        static $stackTrace = null;
        if ($stackTrace === null) {
            $stackTrace = array();
        }
        $this->msg   = (string) $strMessage;
        $this->cause = $cause;
    }
    function getMessage() {
        return (string) $this->msg;
    }

    function setFile($filename) {
        $this->file = (string) $filename;
    }

    function setLine($line) {
        $this->line = (int) $line;
    }

    function toString() {
        return " in file {$this->file} line {$this->line}: " . $this->getMessage();
    }

    function printStackTrace() {
        print("Method exception::prints tracktrace() not implemented\n");
        System::halt(-1);
    }

}

/* -- procedural part of exceptions -- */
/* -- to be removed with php5       -- */

/** Throws an exception. */
function throw($throwable, $file = null, $line = null) {
    global $gStackTrace;

    // type check
    if (!is_a($throwable, 'Exception')) {
        // syntax error
        throw (new RuntimeException("IllegalArgument type throw() needs an exception object as argument"), __FILE__, __LINE__);
        System::halt(-1);
    }

    // init stacktrace if necessary
    if (!isset($gStackTrace) || $gStackTrace === null) {
        $gStackTrace = array();
    }

    $throwable->setFile($file);
    $throwable->setLine($line);

    // push it to stacktrace
    array_push($gStackTrace, $throwable);

    // that's not logical, remove the halt later
    if (is_a($throwable, 'RuntimeException')) {
        //System::halt(-1);
    }
}

/** catch a exception of a specific type and copy it to $exception */
function catch($type, &$exception) {
    global $gStackTrace;
    $type = (string) strtolower($type);
    $num = count($gStackTrace);
    if ($num < 1) {
        // no exception in trace
        // yield false
        return false;
    } else {
        for ($i=0; $i<$num; $i++) {
            if (is_a($gStackTrace[$i], $type)) {
                // exception caught, write into reference and
                // yield true
                $exception = $gStackTrace[$i];
                $newarray = array();
                for ($k=0; $k<count($gStackTrace); ++$k) {
                    if (compareReferences($gStackTrace[$i], $gStackTrace[$k])) {
                        continue;
                    }
                    $newarray[] =& $gStackTrace[$k];
                }

                $gStackTrace = $newarray;
                //	$gStackTrace[$i] = null;
                return true;
            }
        }
    }
}
?>

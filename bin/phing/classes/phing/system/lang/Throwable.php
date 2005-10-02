<?php
// {{{ Header
/*
 * -File     $Id: Throwable.php,v 1.6 2003/04/09 15:58:11 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Andrzej Nowodworski, a.nowodworski@learn.pl
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

/**
 * The Throwable class is the superclass of all errors and exceptions.
 * Only objects that are instances of this class (or one of its subclasses)
 * can be thrown by the throw function. Similarly, only this class or one
 * of its subclasses can be the argument type in a catch clause.
 *
 *Todo:
 * - create static vars/access methods for stacktrace
 * - create method init() that is called by system and
 *   sets up stacktrace as well as runtime define methods
 *   throw and catch
 */

class Throwable {

    var $file = null;
    var $line = null;
    var $detailMessage = null;
    var $stackTrace;

    function Throwable($arg1 = null) {
        $this->fillInStackTrace();
        if ($arg1 !== null && is_string($arg1)) {
            $this->detailMessage = (string) $arg1;
        } else if ($arg1 !== null && is_a("Throwable", $arg1)) {
            $this->detailMessage = $arg1->toString();
        } else if ($arg1 == null) {
            $this->detailMessage = "no details provided";
        } else {
            print("Illegal argument set provided to Throwable().. dying\n");
            System::halt(-1);
        }
    }

    /** Throws an exception. */
    function throw(&$throwable, $file = null, $line = null) {
        // type check
        if (!is_a($throwable, 'Throwable')) {
            // syntax error
            System::println("IllegalArgument type trow() needs an exception object as argument" . __FILE__ . __LINE__);
            System::halt(-1);
        }
        $throwable->setFile($file);
        $throwable->setLine($line);
        // push it to stacktrace
        Throwable::getStackTrace();
        array_push($this->stackTrace, $throwable);
    }

    /** catch a exception of a specific type and copy it to $exception */
    function catch($type, &$exception) {
        $type = (string) strtolower($type);
        $num  = count($gThrowables);
        if ($num === 0) {
            // no exception in trace
            // yield false
            return false;
        } else {
            --$num;
            for ($i=0; $i<$num; ++$i) {
                if (is_a($gThrowables[$num], $type)) {
                    // exception caught, write into reference and
                    // yield true
                    $exception = $gThrowables[$num];
                    unset($gThrowables[$num]);
                    return true;
                }
            }
        }
    }



    function provideThrowables() {}

    /** Returns the detail message string of this throwable. */
    function getMessage() {
        return $this->detailMessage;
    }

    function setFile($name) {
        $this->file = (string) $name;
    }

    function getFile() {
        return $this->file;
    }

    function setLine($line) {
        $this->line = (int) $line;
    }

    function getLine() {
        return $this->line;
    }

    function toString() {
        $s = get_class($this);
        $message = $this->getMessage();
        return ($message !== null) ? ($s . ": " . $message) : $s;
    }

    function fillInStackTrace() {
        $this->stackTrace =& Throwable::getStackTrace();
    }

    function printStackTrace() {
        System::println($this->toString());
        $trace =& $this->getStackTrace();
        for ($i=0; $i < count($trace); ++$i) {
            System::println("\tat " . $trace[$i]->toString());
        }
    }

    function &getStackTrace() {
        static $stackTrace = null;
        if ($stackTrace === null) {
            $stackTrace = array();
        }
        return $stackTrace;
    }

    /**
     * Returns the number of elements in the stack trace (or 0 if the stack
     * trace is unavailable).
     */
    function getStackTraceDepth() {
        return count(Throwable::getStackTrace());
    }

    /** Returns the specified element of the stack trace. */
    function &getStackTraceElement($i) {
        $depth = $this->getStackTraceDepth();
        if ($i<0 || $i >= $depth) {
            throw (new ArrayIndexOutOfBoundsExcpetion(), __FILE__, __LINE__);
            System::halt(-1);
        }
        return $this->stackTrace[$i];
    }
}

/* -- procedural part of exceptions -- */
/* -- to be removed with php5       -- */

/** Throws an exception. */
function throw(&$throwable, $file = null, $line = null) {
    global $gStackTrace;
    global $gThrowables;

    // type check
    if (!is_a($throwable, 'Throwable')) {
        // syntax error
        throw (new RuntimeException("IllegalArgument type trow() needs an exception object as argument"), __FILE__, __LINE__);
        System::halt(-1);
    }

    // init stacktrace if necessary
    /*
    	if (!isset($gStackTrace) || $gStackTrace === null) {
    		$gStackTrace = array();
    	}
    */
    // init throwable container
    if (!isset($gThrowables) || $gThrowables === null) {
        $gThrowables = array();
    }

    $throwable->setFile($file);
    $throwable->setLine($line);

    // push it to stacktrace
    array_push($gThrowables, $throwable);

    // that's not logical, remove the halt later
    if (is_a($throwable, 'RuntimeException')) {
        //System::halt(-1);
    }
}

/** catch a exception of a specific type and copy it to $exception */
function catch($type, &$exception) {
    global $gThrowables;
    $type = (string) strtolower($type);
    $num = count($gThrowables);
    if ($num === 0) {
        // no exception in trace
        // yield false
        return false;
    } else {
        --$num;
        for ($i=0; $i<$num; ++$i) {
            if (is_a($gThrowables[$num], $type)) {
                // exception caught, write into reference and
                // yield true
                $exception = $gThrowables[$num];
                unset($gThrowables[$num]);
                return true;
            }
        }
    }
}

?>

<?php
/**
 * File: $Id$
 *
 * Unit testing framework 
 *
 * @package quality
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage testing
 * @author Jan Schrage <jan@xaraya.com>
 * @author Frank Besler <besfred@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 */

// Prefix of testmethods
define('UT_PREFIXTESTMETHOD','test');
define('UT_SUCCESS',1);
define('UT_FAILURE',2);
define('UT_EXCEPTION',3);

/**
  * class xarTestSuite
  * 
  */

class xarTestSuite
{
    var $_name;
    
    /**
     * Array of testCase objects in this testsuite
     */
    var $_testcases = array();
    
    
    /**Attributes: */
    

    function xarTestSuite($name='default') {
        $this->_name=$name;
    }
    
    function addtestcase($testCase,$name='') {
        if (class_exists($testCase)) {
            $this->TestCases[]=new xarTestCase($testcase,$name);
        }
    }

    function run() {
        foreach($this->_testcases as $testcase) {
            $testcase->runTests();
        }
    }
    function report($type='text') {
        foreach ($this->_testcases as $tests) {
            foreach ($tests as $test ) {
                echo "$test->label : $test->_result \n";
            }
        }
    }

}

/**
 * class xarTestCase gathers info for the tests for a certain class
 *
 *
 */
class xarTestCase {
    
    var $name;
    var $testClass;
    var $_tests;  // xarTest
    var $_result; // xarTestResult
  
    /**
     * Construct the testCase, make sure we only construct the 
     * array of test objects once 
     */
    function xarTestCase($testClass,$name='') {
        $this->name=$name;
        $this->testClass=$testClass;
        $this->_collecttests();
    }
    
    function runTests(){
        foreach ($this->_tests as $test) {
            $test->run();
        }
    }

    // private functions
    function _collecttests() {      
        if (class_exists($this->testClass)) {
            $methods = get_class_methods($testClass);
            print_r($methods); die(": methods");
            foreach ($methods as $method) {
                if (substr($method, 0, strlen(UT_PREFIXTESTMETHOD) == UT_PREFIXTESTMETHOD)) {
                    $this->addTest(new xarTest($testClass, $method, true));
                }
            }
        }
    }
    
}

/**
 * Class to hold the actual tests 
 */
class xarTest extends xarTestAssert {
    var $_testmethod;
    var $_label;
    var $_result;

    function xarTest($testClass, $method, $init=false) {
        if ($init) {
            if (class_exists($testClass)) {
                $tmp= new $testClass($testClass, $method, false);
                $tmp->_testmethod=$method;
                return $tmp;
            } else {
                // make this test except, wrong derivation
            }
        }
    }
    // Abstract functions
    function setup() {}

    // Precondition default to true, meaning, preconditions passed
    function precondition() {
        return true;
    }

    function teardown() {}
 
    function pass() {
        return true;
    }

    function fail() {
        return false;
    }

    function label($thelabel) {
        $this->_label=$thelabel;
    }

    function run() {
        // does this work?
        $this->setup();
        $this->_result = new TestResult($this);
    }
}


class xarTestResult {
    var $_message;
    var $_type;
    
    function xarTestResult($testObject) {
        if($testObject->precondition()) {
            $result=$testObject->_testmethod();
            if ($result) {
                $ret =new xarTestSucces();
            } else {
                $ret = new xarTestFailure();
            }
        } else {
            $ret= new xarTestException();
        }
        return $ret;
        
    }
}

class xarTestSuccess extends xarTestResult {
    
    function xarTestSucces() {
        $this->_type=UT_SUCCESS;
    }
}

class xarTestFailure extends xarTestResult {
    function xarTestFailure() {
        $this->_type=UT_FAILURE;
    }
}

class xarTestException extends xarTestResult {
    function xarTestException() {
        $this->_type=UT_EXCEPTION;
    }
}
    
    
/**
 *  xarTestAssert
 *
 */
class xarTestAssert {
    
    /**
     * Asserts that two variables are equal.
     *
     * @param  mixed
     * @param  mixed
     * @param  string
     * @param  mixed
     * @access public
     */
    function assertEquals($expected, $actual, $delta = 0) {
        if ((is_array($actual)  && is_array($expected)) ||
            (is_object($actual) && is_object($expected))) {
            if (is_array($actual) && is_array($expected)) {
                ksort($actual);
                ksort($expected);
            }
            
            $actual   = serialize($actual);
            $expected = serialize($expected);
            
            if (serialize($actual) != serialize($expected)) {
                return $this->fail($message);
            }
        }

        elseif (is_numeric($actual) && is_numeric($expected)) {
            if (!($actual >= ($expected - $delta) && $actual <= ($expected + $delta))) {
                return $this->fail();
            }
        }
        
        else {
            if ($actual != $expected) {
                return $this->fail($message);
            }
        }
        
        return $this->pass();
    }
    
    /**
    * Asserts that an object isn't null.
    *
    * @param  object
    * @param  string
    * @access public
    */
    function assertNotNull($object) {
        if ($object === null) {
            return $this->fail();
        }

        return $this->pass();
    }

    /**
    * Asserts that an object is null.
    *
    * @param  object
    * @param  string
    * @access public
    */
    function assertNull($object) {
        if ($object !== null) {
            return $this->fail();
        }

        return $this->pass();
    }

    /**
    * Asserts that two objects refer to the same object.
    * This requires the Zend Engine 2 (to work properly).
    *
    * @param  object
    * @param  object
    * @param  string
    * @access public
    */
    function assertSame($expected, $actual) {
        if ($actual !== $expected) {
            return $this->fail();
        }

        return $this->pass();
    }

    /**
    * Asserts that two objects refer not to the same object.
    * This requires the Zend Engine 2 (to work properly).
    *
    * @param  object
    * @param  object
    * @param  string
    * @access public
    */
    function assertNotSame($expected, $actual) {

        if ($actual === $expected) {
            return $this->fail();
        }

        return $this->pass();
    }

    /**
    * Asserts that a condition is true.
    *
    * @param  boolean
    * @param  string
    * @access public
    */
    function assertTrue($condition) {
        if (!$condition) {
            return $this->fail();
        }

        return $this->pass();
    }

    /**
    * Asserts that a condition is false.
    *
    * @param  boolean
    * @param  string
    * @access public
    */
    function assertFalse($condition) {
        if ($condition) {
            return $this->fail();
        }

        return $this->pass();
    }

    /**
    * Asserts that a string matches a given
    * regular expression.
    *
    * @param string
    * @param string
    * @access public
    * @author Sébastien Hordeaux <marms@marms.com>
    */
    function assertRegExp($expected, $actual) {
        if (!preg_match($expected, $actual)) {
            return $this->fail();
        }

        return $this->pass();
    }
        
    function fail() { /* abstract */ }
    function pass() { /* abstract */ }


}

?>
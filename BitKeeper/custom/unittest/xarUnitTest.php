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

/**
 * Defines which offer some rudimentory support for
 * customizing the test framework.
 *
 */
define('UT_PREFIXTESTMETHOD','test'); // functions starting with this prefix are considered tests
define('UT_OUTLENGTH',50);            // width of the text output in text reporting mode

/**
  * class xarTestSuite
  * 
  */
class xarTestSuite {
    var $_name;                // Name of this testsuite
    var $_testcases = array(); // array which holds all testcases
    
    /**
     * Constructor just sets the name attribute
     */
    function xarTestSuite($name='default') {
        $this->_name=$name;
    }
    
    /**
     * Add a testcase object to the testsuite
     */
    function AddTestCase($testClass,$name='') {
        // Make sure the class exist
        if (class_exists($testClass) && 
            (get_parent_class($testClass) == 'xartestcase')) {
            if ($name=='') { $name=$testClass; }
            $this->_testcases[$name]=new xarTestCase($testClass,$name,true);
        }
    }

    /**
     * Run the testcase 
     */
    function run() {
        foreach($this->_testcases as $testcase) {
            $testcase->runTests();
        }
    }

    /**
     * Report the results of this suite
     */
    function report($type='text') {
        echo "TestSuite: ".$this->_name."\n";
        foreach (array_keys($this->_testcases) as $casekey) {
            echo "|- TestCase: ".$this->_testcases[$casekey]->_name."\n";
            $tests =& $this->_testcases[$casekey]->_tests;
            foreach (array_keys($tests) as $key ) {
                $result =& $tests[$key]->_result;
                if (!empty($result->_message)) {
                    echo "  |- ". str_pad($result->_message,UT_OUTLENGTH,".",STR_PAD_RIGHT) . 
                        (get_class($result)=="xartestsuccess"?"Passed":"FAILED") . "\n";
                } else {
                   echo "  |- ". str_pad("WARNING: invalid result in $key()",UT_OUTLENGTH,".",STR_PAD_RIGHT) .
                        (get_class($result)=="xartestsuccess"?"Passed":"FAILED") . "\n"; 
                }
            }
        }
    }

}

/**
 * class xarTestCase gathers info for the tests for a certain class
 *
 *
 */
class xarTestCase extends xarTestAssert {
    var $_name;           // Name of this testcase
    var $_tests=array();  // xarTest objects
  
    /**
     * Construct the testCase, make sure we only construct the 
     * array of test objects once 
     */
    function xarTestCase($testClass='',$name='',$init=false) {
        if (get_parent_class($testClass) == 'xartestcase') {
            if ($init) {
                $this = new $testClass();
                $this->_name=$name;
                $this->_collecttests();
            }
        }
    }

    // Abstract functions, these should be implemented in the actual test class
    function setup() {} 
    // Precondition for a testcase default to true when not defined 
    function precondition() { return true; }
    function teardown() {}

    function runTests() {
        foreach(array_keys($this->_tests) as $key) {
            $this->_tests[$key]->run();
        }
    }

    function pass($msg='Passed') {
        $res = array('value' => true, 'msg' => $msg);
        return $res;
    }

    function fail($msg='Failed') {
        $res = array('value' => false, 'msg' => $msg);
        return $res;
    }

    // private functions
    function _collecttests() {
        $methods = get_class_methods($this);
            
        foreach ($methods as $method) {
            if (substr($method, 0, strlen(UT_PREFIXTESTMETHOD)) == UT_PREFIXTESTMETHOD && 
                strtolower($method) != strtolower(get_class($this))) {
                $this->_tests[$method] =& new xarTest($this, $method);
            }
        }
    }
    
}

/**
 * Class to hold the actual test
 */
class xarTest {
    var $_parentobject;
    var $_testmethod;
    var $_result;

    function xarTest(&$container, $method) {
        $this->_parentobject=& $container;
        $this->_testmethod=$method;
    }

    function run() {
        $testcase=& $this->_parentobject;
        $testmethod=$this->_testmethod;
        $testcase->setup();
        
        if($testcase->precondition()) {
            // Run the actual testmethod
            $result=$testcase->$testmethod();
            $this->_result = new xarTestResult($result);
        } else {
            $this->_result = new xarTestException($result);
        }
        
        $testcase->teardown();
    }
}

/**
 * Testresults
 *
 * This class constructs the xarTestResult object in the xarTest object
 * depending on the outcome of the called testmethod a different object
 * is created
 *
 */
class xarTestResult {
    var $_message;

    function xarTestResult($result) {
        if ($result['value'] === true) {
            $this = new xarTestSuccess($result['msg']);
        } else {
            $this = new xarTestFailure($result['msg']);
        }
    }
}

class xarTestSuccess extends xarTestResult {
    function xarTestSuccess($msg) { 
        $this->_message=$msg;
    }
}

class xarTestFailure extends xarTestResult {
    function xarTestFailure($msg) {
        $this->_message=$msg;
    }
}

class xarTestException extends xarTestResult {
    function xarTestException($result) { 
        $this->_message=$result['msg'];
    }
}
    
class xarTestAssert {
    
    // Abstract functions which should be implemented in subclasses
    // function fail($msg='no message') {}
    // function pass($msg='no message') {}

    function assertEquals($expected, $actual, $delta = 0,$msg='Test for Equals') {
        if ((is_array($actual)  && is_array($expected)) ||
            (is_object($actual) && is_object($expected))) {
            if (is_array($actual) && is_array($expected)) {
                ksort($actual);
                ksort($expected);
            }
            
            $actual   = serialize($actual);
            $expected = serialize($expected);
            
            if (serialize($actual) == serialize($expected)) {
                return $this->pass($msg);
            }
        } 

        // Compare delta values
        if (is_numeric($actual) && is_numeric($expected)) {
            if (($actual >= ($expected - $delta) && $actual <= ($expected + $delta))) {
                return $this->pass($msg);
            }
        } 

        // Compare the direct values
        if ($actual == $expected) {
            return $this->pass($msg);
        } 

        // Couldn't find a combination which works
        return $this->fail($msg);
    }

    
    function assertNotNull($object,$msg='Test for Not Null') {
        if ($object !== null) { 
            return $this->pass($msg); 
        }
        return $this->fail($msg);
    }


    function assertNull($object,$msg='Test for Null') {
        if ($object === null) {
            return $this->pass($msg);
        } 
        return $this->fail($msg);
    }


    function assertSame($expected, $actual,$msg='Test for Same') {
        if ($actual === $expected) {
            return $this->pass($msg);
        }
        return $this->pass($msg);
    }

    function assertNotSame($expected, $actual,$msg='Test for Not Same') {
        if ($actual !== $expected) {
            return $this->pass($msg);
        } 
        return $this->fail($msg);
    }
    

    function assertTrue($condition,$msg='Test for True') {
        if ($condition) {
            return $this->pass($msg);
        }
        return $this->fail($msg);
    }


    function assertFalse($condition,$msg='Test for False') {
        if (!$condition) {
            return $this->pass($msg);
        } 
        return $this->fail($msg);
    }


    function assertRegExp($expected, $actual,$msg='Test for Regular Expression') {
        if (preg_match($expected, $actual)) {
            return $this->pass($msg);
        }
        return $this->pass($msg);
    }

    function assertEmpty($expected,$msg='Test for empty array') {
        if (is_array($expected) && empty($expected)) {
            return $this->pass($msg);
        }
        return $this->fail($msg);
    }
} 
?>

<?php

class testUnitTest extends xarTestCase {
   
    var $mymethodlist=array(); 

    function setup() {
        $this->mymethodlist[]="testtrue";
        $this->mymethodlist[]="testfailure";
        $this->mymethodlist[]="testmethodlist";
    }
    
    function precondition() {
        return true;
    }

    function teardown () {
        // As each test runs in it individual environment
        // we need to reset the array
        $this->mymethodlist=array();
    }
    
    function testTrue() {
        return $this->assertTrue(true,"assertTrue on true assertion");
    }
    
    function testFailure() {
        $res = $this->assertTrue(1==2,"assertTrue on false assertion");
	if ($res["value"] == false) {
            $res["value"] = true;
        } elseif ($res["value"] == true) {
            $res["value"] = false;
        }
	return $res;
    }
    
    function testMethodList() {
        return $this->assertEquals($this->mymethodlist,array_keys($this->_tests),0,"Test method retrieval");
    }
}

$suite->AddTestCase('testUnitTest','Testing Unit Test Framework Part I');


class noTestCase {
    function testbogus() { 
        return $this->assertTrue(true,"***ERROR***"); 
    }
}

class testMoreUnitTest extends xarTestCase {
   
    
    var $mytestsuite;
 
    var $invalidsuite = 'thissuitedoesnotexist';
    var $empty = array();

    function setup() {
        $this->mytestsuite = new xarTestSuite();
    }
    
    function precondition() {
        if (class_exists($this->invalidsuite)) {
            return false;
        }
        if (! class_exists('noTestCase')) {
            return false;
        }
        return true;
    }

    function teardown () {
        $this->mytestsuite = '';
        $this->invalidsuite = '';
    }

    function testinvalidsuite() {
        $this->mytestsuite->AddTestCase($this->invalidsuite,'This is invalid');
        return $this->assertEquals($this->mytestsuite->_testcases,$this->empty,0,'Non-existing test suite');
    }
    function testsubclassing()
    {
        $this->mytestsuite->AddTestCase('noTestCase','This is invalid');   
        return $this->assertEquals($this->mytestsuite->_testcases,$this->empty,0,'No subclass of xarTestCase');
    }
    
    function testemptyarray()
    {
        $testarray = array();
        return $this->assertEmpty($testarray,'Empty array is empty');
    }    
}

$suite->AddTestCase('testMoreUnitTest','Testing Unit Test Framework Part II');

?>

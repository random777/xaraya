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
        return $this->assertTrue(true,"Should always pass");
    }
    
    function testFailure() {
        return $this->assertTrue(1==2,"Should always fail");
    }
    
    function testMethodList() {
        return $this->assertEquals($this->mymethodlist,array_keys($this->_tests),0,"Test Methods");
    }
}

$suite->AddTestCase('testUnitTest','Testing Unit Test Framework');

?>

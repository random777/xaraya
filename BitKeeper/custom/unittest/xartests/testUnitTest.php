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
        // not needed here
    }
    
    function testTrue() {
        return $this->assertTrue(true,"Should always pass");
    }
    
    function testFailure() {
        return $this->assertTrue(1==2,"Should always fail");
    }
    
    function testMethodList() {
        print_r($this->mymethodlist);
        print_r(array_keys($this->_tests));
        return $this->assertEquals($this->mymethodlist,array_keys($this->_tests),0,"Test Methods");
    }
}

$suite->AddTestCase('testUnitTest','Testing Unit Test Framework');

?>

<?php

class testxarVar extends xarTestCase {
    var $myBLC;
    
    function setup() {
        include_once 'xarCore.php';
        include_once 'xarVar.php';
        include_once 'xarServer.php';
    }
    
    function precondition() {
        return true;
    }

    function teardown () {
        // not needed here
    }
    
    
    function testCleanUntrusted() {
        $var = '<script>';
        return $this->assertFalse(xarVarCleanUntrusted($var),"Clean var from untrusted source");
    }

    function testCleanFromInput() {
        $var = '<script>';
        return $this->assertFalse(xarVarCleanFromInput($var),"Clean var from input");
    }
}

$suite->AddTestCase('testxarVar','Testing xarVar.php');

?>
<?php

class testxarVar extends xarTestCase {
    var $myBLC;
    
    function setup() {
        include_once 'xarCore.php';
        include_once 'xarVar.php';
        include_once 'xarServer.php';
    }
    
    function testCleanUntrusted() {
        // TODO: define more input and make them all pass
        $var = '<script>';
        $res =xarVarCleanUntrusted($var);
        return $this->assertEquals(strlen($res),0,0,"<script> is cleaned from untrusted");
    }

    function testCleanFromInput() {
        // TODO: define more input and make them all pass
        $var = '<script>';
        $res=xarVarCleanFromInput($var);
        return $this->assertEquals(strlen($res),0,0,"<script> is cleaned from input");
    }
}

$suite->AddTestCase('testxarVar','Testing xarVar.php');

?>
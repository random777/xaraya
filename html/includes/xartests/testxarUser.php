<?php

class testxarUser extends xarTestCase {
    var $myBLC;
    
    function setup() {
        include_once 'xarCore.php';
        include_once 'xarVar.php';
        include_once 'xarMLS.php';
        include_once 'xarTemplate.php';
        include_once 'xarLog.php';
        include_once 'xarUser.php';
        include_once 'xarSession.php';
        include_once 'xarServer.php';
    }
    
    function testEmptyUserVar() {
        return $this->assertNull(xarUserGetVar(''),"Passing empty user var should return null");
    }

}

$suite->AddTestCase('testxarUser','Testing xarUser.php');

?>
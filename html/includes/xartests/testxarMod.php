<?php

class testxarMod extends xarTestCase {
    var $myBLC;
    
    function setup() {
    include_once 'xarCore.php';
    include_once 'xarLog.php';
    include_once 'xarDB.php';
    include_once 'xarServer.php';
    include_once 'xarMod.php';
    }
    
    function testInit() {
        return $this->assertTrue(xarMod_init('',''),"Module System Initialisation");
    }
    
    /* Doesn't work.
    function testLoadAPI() {
        return $this->assertTrue(xarModAPIFunc($modName = 'base', $modType = 'user', $funcName = 'handleBaseURLTag'),"Load an API File");
    }
    */
    function testLoadAPI() {
        return $this->assertTrue(xarMod_getFileInfo('base'),"Check File Info");
    }

    /*
    function testLoadAPI() {
        return $this->assertTrue(xarModIsAvailable('base'),"Check File Info");
    }
    */
}

$suite->AddTestCase('testxarMod','Testing xarMod.php');

?>
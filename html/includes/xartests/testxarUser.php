<?php

class testxarUser extends xarTestCase {
    var $myBLC;
    
    function setup() {
        include_once 'xarCore.php';
        include_once 'xarVar.php';
        include_once 'xarUser.php';
        include_once 'xarSession.php';
        include_once 'xarServer.php';
    }
    
    function precondition() {
        return true;
    }

    function teardown () {
        // not needed here
    }
    
    /*
    function testUserLogin() {
        $GLOBALS['xarUser_authenticationModules'] = '';
        $username = 'Admin';
        $password = 'password';
        return $this->assertTrue(xarUserLogIn($username, $password, $rememberme = 0),"Check a user logging in");
    }
    */
    function testUserVar() {
        return $this->assertFalse(xarUserGetVar(''),"Check for a non-existant user var");
    }

    function testLogOut() {
        return $this->assertTrue(xarUserLogOut('1'),"Test for logout");
    }
}

$suite->AddTestCase('testxarUser','Testing xarUser.php');

?>
<?php

class testBLCompiler extends xarTestCase {
    var $myBLC;
    
    function setup() {
        include_once 'xarCore.php';
        include_once 'xarVar.php';
        include_once 'xarException.php';
        include_once 'xarBLCompiler.php';
        $this->myBLC = new xarTpl__Compiler;
    }
    
    function precondition() {
        // Abort on bogus file: must not exist
        if (file_exists('xartests/doesntexist')) return false;
        // Testdata for BL
        if (!file_exists('xartests/test.xt')) return false;
        return true;
    }

    function teardown () {
        // not needed here
    }
    
    
    function testnotNull() { 
        return $this->assertNotNull($this->myBLC,"BL Compiler Instantiation");
    }
    
    function testnoData() {
        return $this->assertNull($this->myBLC->compileFile('doesntexist'),"Compile on bogus file");
    }
    
    function testCompilenotnull() {
        return $this->assertnotNull($this->myBLC->compileFile('xartests/test.xt'),"Compile valid file");
    }
    
    function testCompile() {
        return $this->assertTrue($this->myBLC->compileFile('xartests/test.xt'),"Compile valid file(2)");
    }
}

$suite->AddTestCase('testBLCompiler','Testing Blocklayout compiler');

?>

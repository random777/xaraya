<?php

class testBLCompiler extends xarTest {

    var $myBLC;

    function setup() {
        include_once '../xarCore.php';
        include_once '../xarVar.php';
        include_once '../xarException.php';
        include_once '../xarBLCompiler.php';
        $this->myBLC = new xarTpl__Compiler;
    }

    function precondition() {
        // Abort on bogus file: must not exist
        if (file_exists('doesntexist')) return false;
        // Testdata for BL
        if (!file_exists('test.xt')) return false;
        return true;
    }

    function testnotNull() { 
	$this->label('BL Compiler Instantiation');
        $this->assertNotNull($this->myBLC);
    }

    function testnoData() {
	$this->label('Abort on bogus file');
        $this->assertNull($this->myBLC->compileFile('doesntexist'));
    }
    
    function testCompilenotnull() {
        $this->assertnotNull($this->myBLC->compileFile('test.xt'));
    }

    function testCompile() {
        $this->assertTrue($this->myBLC->compileFile('test.xt'));
    }
}

$suite->AddTestCase('testBLCompiler');


?>

<?php

class testBLCompiler extends xarTestCase {
    var $myBLC;
    
    function setup() {
		$GLOBALS['xarDebug'] = false;
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
        return $this->assertNull($this->myBLC->compileFile('doesntexist'),"Don't compile on bogus file");
    }
    
    function testCompilenotnull() {
        return $this->assertnotNull($this->myBLC->compileFile('xartests/test.xt'),"Return not null on compile of a valid file");
    }
    
}
//$tmp = new xarTestSuite('Blocklayout compiler tests');
//$tmp->AddTestCase('testBLCompiler','Instantiation and file compiling');
$suite->AddTestCase('testBLCompiler','Instantiation and file compiling');
//$suites[] = $tmp;

?>
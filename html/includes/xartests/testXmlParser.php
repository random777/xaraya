<?php

class testXmlMisc extends xarTestCase {
    var $xarXml;
    var $savedir;

    function setup() {
        $this->savedir=getcwd();
        chdir('..');
        include_once 'includes/xarXML.php';
        $this->xarXml = new xarXmlParser();
    }

    function teardown() {
        chdir($this->savedir);
    }

    function testDifficult() {
        $result = $this->xarXml->parseFile('includes/xartests/test.xml');
        return $this->AssertTrue($result,'Parse an ugly, yet valid document');
    }
}    

class testXmlTestSuite extends xarTestCase {
    var $xarXml;
    var $savedir;
    var $errors=array();
    var $testcases;
    var $xmltestbase;

    // Ok, we're gonna do something interesting here. We're gonna try to 
    // run our parser over the xml-test-suite. 
    function setup() {
        $this->savedir=getcwd();
        chdir('..');
        include_once 'includes/xarXML.php';
        $this->xarXml = new xarXmlParser();
        // The test-suite has an xml file which contains all the tests
        // located in ./xmltestsuite/xmlconf/xmltests/xmltest.xml
        // First let's parse that file, kind of a prerequisite
        $this->testcases=array();
        $this->xmltestbase='includes/xartests/xmltestsuite/xmlconf/xmltest/';
        if($this->xarXml->parseFile($this->xmltestbase . 'xmltest.xml')) {
            $this->testcases= $this->xarXml->tree[0]['children'][0]['children'];
        } else {
            return false;
        }
    }

    function teardown() {
        chdir($this->savedir);
    }

  
    function testParseValidFromW3TestSuite() {
        $this->errors=array();
        foreach($this->testcases as $test) {
            if($test['attributes']['TYPE'] =='valid') {
                // Valid documents should at least be parseable ;-)
                $testfile = $this->xmltestbase . $test['attributes']['URI'];
                
                if(!$this->xarXml->parseFile($testfile)) {
                    $this->errors[]= $test['attributes']['URI'].":".$this->xarXml->lastmsg;
                }
                //echo $testfile;
                //print_r($this->xarXml->tree);
            }
        }
        
        $msgtoreturn="Valid documents should parse without errors";
        if(!empty($this->errors)) $msgtoreturn .= "\n" . implode("\n",$this->errors);
        return $this->AssertTrue(empty($this->errors),$msgtoreturn);
    }

    function testParseNotWellFormedFromW3TestSuite() {
        $this->errors=array();
        $testcounter=0; $errorcounter=0;
        foreach($this->testcases as $test) {
            if($test['attributes']['TYPE'] =='not-wf') {
                // Not wellformed document *should* produce errors
                $testfile = $this->xmltestbase . $test['attributes']['URI'];
                if($this->xarXml->parseFile($testfile)) {
                    $errorcounter++;
                    $this->errors[]= $test['attributes']['URI'].": parsed ok, but is not well-formed\n"
                        . $test['content'];
                    //echo "$testfile\n";
                    //print_r($this->xarXml->tree);
                    //die();
                } else {
                    $testcounter++;
                }
                
            }
        }
        
        $msgtoreturn="Not well formed documents should give errors ($errorcounter/$testcounter)";
        //if(!empty($this->errors)) $msgtoreturn .= "\n" . implode("\n",$this->errors);
        return $this->AssertTrue(empty($this->errors),$msgtoreturn);
    }

    function testParseInvalidFromW3TestSuite() {
        $this->errors=array();
        $testcounter=0; $errorcounter=0;
        foreach($this->testcases as $test) {
            if($test['attributes']['TYPE'] =='invalid') {
                // Invalid documents *should* produce errors
                $testfile = $this->xmltestbase . $test['attributes']['URI'];
                $testcounter++;
                if($this->xarXml->parseFile($testfile)) {
                    $errorcounter++;
                    $this->errors[]= $test['attributes']['URI'].": parsed ok, but is invalid\n"
                        . $test['content'];
                }
            }
        }
        
        $msgtoreturn="Invalid documents should give errors ($errorcounter/".$testcounter.")";
        //if(!empty($this->errors)) $msgtoreturn .= "\n" . implode("\n",$this->errors);
        return $this->AssertTrue(empty($this->errors),$msgtoreturn);
    }

}


$tmp = new xarTestSuite('XML parser tests');
$tmp->AddTestCase('testXmlMisc','Weird XML documents');
$tmp->AddTestCase('testXmlTestSuite','Running the W3 XML Test suite');
$suites[] = $tmp;
?>
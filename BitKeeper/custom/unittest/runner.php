<?php

/**
 * File: $Id$
 *
 * Runner for the unit testing framework
 *
 * It is assumed that this file runs with the 
 * current directory set to the directory which
 * contains the 'xartests' subdirectory
 *
 * @package quality
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage testing
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @author Jan Schrage <jan@xaraya.com>
 * @author Frank Besler <besfred@xaraya.com>
*/

define('UT_TESTSDIR','xartests'); // subdirectory holding tests
define('UT_FRAMWDIR','BitKeeper/custom/unittest/'); 

// Get the command line args in here, trus the callee to have parsed
// it properly into neatly space separated stuff
$args=$_SERVER['argv'];
array_shift($args);

// Rudimentary parsing of what we need
$testfilter=array();
$testfilter['output']='text';
for($index=0;$index<count($args);$index++) {
    switch($args[$index]) {
    case '-c':
    case'--cases':
        $cases=explode(",",trim($args[$index+1],"'"));
        foreach($cases as $case) $testfilter['cases'][]=$case;
        break;
    case '-s':
    case '--suites':
        $suites=explode(",",trim($args[$index+1],"'"));
        foreach($suites as $suite) $testfilter['suites'][]=$suite;
        break;
    case '-o': 
    case '--output':
        $testfilter['output'] = $args[$index+1];
        break;
    }
}

// Get the current repository root directory
exec('bk root',$output,$return_status);
$bkroot=$output[0];
// As we run this from a bk repository the above should always succeed
assert($bkroot!='');

/**
 * Include the framework
 *
 * We can/should the absolute path here as we don't know where we are
 */
exec("bk get -Sq $bkroot/".UT_FRAMWDIR."xarUnitTest.php");
include_once "$bkroot/".UT_FRAMWDIR."xarUnitTest.php";

/** 
 * Define the array which holds the testsuites
 *
 * Define a default suite which normally holds tests which are not 
 * put into another testsuite explicitly
 */
$suites= array();
$suites[] = new xarTestSuite();
$suite=&$suites[0];

/**
 * Include all files found in the UT_TESTSDIR directories 
 * it is assumed they are conforming files.
 *
 */

// Traverse the subtree from the current directory downwards
// For each tests directory include the tests found
$findcmd='bk sfiles -gd | grep ' .UT_TESTSDIR;
$dirs=array();
exec($findcmd,$dirs,$return_status);

while (list($key, $dir) = each($dirs)) {
    // In this dir, check for the existance of the special tests folder UT_TESTSDIR
    if (is_dir($dir)) {
        // Open the dir and include the testfiles
        if ($testsdir = opendir($dir)) {
            exec("bk get -Sq $dir");
            while ($file = readdir($testsdir) ) {
                // Now, we get also ., .. and subdirs, let's filter out some stuff
                // the testfiles are php scripts, so let's require them to have the
                // php extension
                
                if (preg_match('/\.php$/',$file)) {
                    if ($file !='') {
                        // The chdir juggling is necessary to set the 
                        // property _basedir of each testcase.
                        $savedir = getcwd();
                        chdir($savedir."/".$dir);
                        exec("bk get -Sq $file");
                        include_once($file);
                        chdir($savedir);
                    }
                }
            }
        } else {
            // No tests found, skip this directory
            // Do not die, as we might have other tests in 
            // other directories
            //die("No tests found\n");
        }
    }
}

/**
 * Cycle through all suites found 
 *
 * The foreach loop gathers the results from all testsuites
 * 
 */
foreach ($suites as $torun) {
    // Run the testsuite
    // If $testfilter['suites'] is empty, run all suites
    // if it's not run only the suites specified
    if(empty($testfilter['suites'])) {
        $torun->run();
        $torun->report($testfiler['output']);
    } else {
        // Only run the testsuite if it's mentions
        if(in_array($torun->_name,$testfilter['suites'])) {
            $torun->run();
            $torun->report($testfilter['output']);
        }
    } 
    
}

?>
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
include_once "$bkroot/".UT_FRAMWDIR."xarUnitTest.php";

/** 
 * Define the array which holds the testsuites
 *
 * Define a default suite which normally holds tests which are not in another testsuite
 */
$suites= array();
$suites[] = new xarTestSuite();
$suite=&$suites[0];

/**
 * Include all files found in the UT_TESTSDIR directory
 * it is assumed they are conforming files.
 */
if (is_dir(UT_TESTSDIR)) {
    // Open the dir and include the testfiles
    if ($dir = opendir(UT_TESTSDIR)) {
        while ($file = readdir($dir) ) {
            // Now, we get also ., .. and subdirs, let's filter out some stuff
            // the testfiles are php scripts, so let's require them to have the
            // php extension
            if (preg_match('/\.php$/',$file)) {
                //echo $file."\n";
                include_once(UT_TESTSDIR."/$file");
            }
        }
    }
} else {
    die("No tests found\n");
}

// Cycle through all suites
foreach ($suites as $torun) {
    $torun->run();
    $torun->report('text');
}

?>
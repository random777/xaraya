<?php

/**
 * File: $Id$
 *
 * Runner for the unit testing framework
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

echo "Starting tests...\n";

/**
 * Include the framework
 *
 */
include_once "./xarUnitTest.php";

/** 
 * Define the array which holds the testsuites
 *
 * Define a default suite which normally holds tests which are not in another testsuite
 */
$suites= array();
$suites[] = new xarTestSuite();
$suite=&$suites[0];


//Include all specified testcases
include_once "./xartests/testUnitTest.php";


include_once "../../../html/includes/xartests/testBLCompiler.php";
chdir("../../../html/includes/xartests");

// Cycle through all suites
foreach ($suites as $torun) {
    $torun->run();
    $torun->report('text');
}

?>

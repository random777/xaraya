<?php
echo "Starting tests...\n";

include_once "../xarUnitTest.php";

$suites= array();
$suites[] = new xarTestSuite();
$suite=&$suites[0];


//Include all specified testcases
include_once "testBLCompiler.php";

// Cycle through all suites
foreach ($suites as $torun) {
    $torun->run();
    $torun->report('text');
}


?>
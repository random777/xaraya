<?php
echo "Starting tests...";

include_once "../xarUnitTest.php";

$suites= array();
$suites[] = new xarTestSuite();


//Include all specified testcases
include_once "testBLCompiler.php";

// Cycle through all suites
foreach ($suites as $suite) {
    $suite->run();
    $suite->report('text');
}


?>
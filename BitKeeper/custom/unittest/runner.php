<?php
echo "Starting tests...\n";

include_once "./xarUnitTest.php";

$suites= array();
$suites[] = new xarTestSuite();
$suite=&$suites[0];


//Include all specified testcases
include_once "../../../html/includes/xartests/testBLCompiler.php";
chdir("../../../html/includes/xartests");

// Cycle through all suites
foreach ($suites as $torun) {
    $torun->run();
    $torun->report('text');
}


?>
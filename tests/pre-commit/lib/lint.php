<?php
// Workaround for mtn's inability to suppress output
// this php file takes a filename and lints it without ever
// producing output (test on exitcode here)
// Strict argument order:
// $argv[0] this file
// $argv[1] string name of php executable
// $argv[2] string name of file to lint
assert('$argc==3; /* lint.php called wrong!! */');
ob_start();
passthru("$argv[1] -ql $argv[2] ",$ret);
ob_clean();
exit($ret);
?>

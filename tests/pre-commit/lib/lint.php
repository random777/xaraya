<?php
// Workaround for mtn's inability to suppress output
// this php file takes a filename and lints it without ever
// producing output (test on exitcode here)
ob_start();
$ret = @eval('?>'.file_get_contents($argv[1]));
ob_clean();
?>
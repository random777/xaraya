<?php
/*
 * This is the Phing command line launcher. It starts up the system evironment
 * tests for all important paths and properties and kicks of the main command-
 * line entry point of phing located in phing.Main
 */

/* DEBUG */
if (getenv('PHP_APDTRACE')) {
    echo "Enabling APD backtrace...\n";
    apd_set_session_trace(3);
}

/* adding package support */
// deprecated, this will be removed and import() below replaced by @import * ;
require_once(getenv("PHING_HOME").DIRECTORY_SEPARATOR."classes".DIRECTORY_SEPARATOR."packagesupport.php");

/* import core stuff */
import("phing.system.lang.System");
import("phing.Main");

/* set classpath */
if (getenv('PHP_CLASSPATH')) {
    define('PHP_CLASSPATH', getenv('PHP_CLASSPATH'));
} else {
    System::println("PHING: Environment PHP_CLASSPATH not set");
    System::halt(-1);
}

/* startup the OO system */
// deprecated, System should be autostarting
System::Startup();

/* find phing home directory */
if (getenv('PHING_HOME')) {
	define('PHING_HOME', getenv('PHING_HOME'));
	System::setProperty("phing.home", PHING_HOME);
} else {
	System::println("PHING: Environment PHING_HOME not set");
	System::halt(-2); // environment is not set properly
}

/* polish CLI arguments */
$args = $_SERVER['argv'];
array_shift($args);

/* fire main application */
Main::fire($args);

/* exit OO system if not already called by Main
 * basically we should not need this due to register_shutdown_function in System
 * But im not sure that works under all circumstances
 */
// deprecated, system should be auto terminating
 System::halt(0);

/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

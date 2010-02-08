<?php
/**
 * Xaraya Local Services Interface
 *
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @package entrypoint
 * @author Marcel van der Boom
 * @todo move this into /bin
 * @todo add site instance parameter
 * @todo centralize user/password entry in here and outside the xarcliapi
 */
set_include_path(dirname(dirname(__FILE__)) . PATH_SEPARATOR . get_include_path());
include 'lib/bootstrap.php';
sys::import('xaraya.core');

// We need a (fake) ip address to run xar.
if(!isset($_SERVER['REMOTE_ADDR'])) putenv("REMOTE_ADDR=127.0.0.1");

// @todo: don't load the whole core
xarCoreInit(XARCORE_SYSTEM_ALL);

/* Make sure we handle boney instead of fancy */
set_exception_handler(array('ExceptionHandlers','bone'));
exit(xarLocalServicesMain($argc, $argv));

/**
 * Entry point for local services
 *
 * Also know as the command line entry point
 *
 * call sign: php ./ws.php <type> [args]
 *
 * @todo use cli/argument parsing library (perhaps getOpt from PEAR)
**/

function xarLocalServicesMain($argc, $argv)
{
    // Main check
    if(!isset($argv[1])) return usage();
    $handler = $argv[1];
    if(xarModIsAvailable($handler))
        return xarModApiFunc($handler,'cli','process',array('argc'=>$argc, 'argv'=>$argv));
    else
        return usage();
}

function usage()
{
    fwrite(STDERR,"Usage for local services entry point:
    php5 ./".basename(__FILE__)." <type> [-u <user>][-p <pass>] [args]

    <type>   : required designator for request type (module name)
               Currently Supported:
               - 'mail'  : a mail message is supplied at stdin
    -u <user>: optional username to pass in
    -p <pass>: optional cleartext password to pass in
    [args]   : arguments specific to the supplied <type>
    NOTES:
       - if PHP doesnt have REMOTE_ADDR available, it will assume 127.0.0.1.
         if that is not correct, make sure that PHP can determine your ip address
         (for example by setting REMOTE_ADDR in the environment)
         \n");
    return 1;
}
?>

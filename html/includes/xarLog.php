<?php
/**
 * Logging Facilities
 *
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage logging
 * @author Marco Canini <marco@xaraya.com>
 * @author Flavio Botelho <nuncanada@ig.com.br>
 * @todo  Document functions
 *        Add options to simple & html logger
 *        When calendar & xarLocaleFormatDate is done complete simple logger
 *        and html logger
 *        When xarMail is done do email logger
 */

/**
 * Logging package defines
 */

define('XARLOG_LEVEL_EMERGENCY', 1);
define('XARLOG_LEVEL_ALERT',     2);
define('XARLOG_LEVEL_CRITICAL',  4);
define('XARLOG_LEVEL_ERROR',     8);
define('XARLOG_LEVEL_WARNING',   16);
define('XARLOG_LEVEL_NOTICE',    32);
define('XARLOG_LEVEL_INFO',      64);
define('XARLOG_LEVEL_DEBUG',     128);
// This is a special define that includes all the levels defined above
define('XARLOG_LEVEL_ALL',       255);

function xarLog_init(&$args, &$whatElseIsGoingLoaded)
{

    $GLOBALS['xarLog_loggers'] = array();
    $xarLogConfig = array();

    if (xarLogConfigReadable())
    {
        //We can't use xarInclude here.
        if (!include_once (xarLogConfigFile())) {
            xarCore_die('xarLog_init: Log configuration file is invalid!');
        }

    } elseif (xarLogFallbackPossible()) {
        //Fallback mechanism to allow some logging in important cases when
        //the user might now have logging yet installed, or for some reason we
        //should be able to have a way to get error messages back => installation?!
        $logFile = xarLogFallbackFile();
        if ($logFile) {
            $xarLogConfig[] = array(
                'type'      => 'simple',
                'config'    => array(
                    'fileName' => $logFile,
                    'logLevel'  => XARLOG_LEVEL_ALL));
        }
    }

    // If none of these => do nothing.
     foreach ($xarLogConfig as $logger) {
        $config = array_merge(array(
            'loadLevel' => &$whatElseIsGoingLoaded), $logger['config']);
         xarLog__add_logger($logger['type'], $config);
     }

    // Subsystem initialized, register a shutdown function
    register_shutdown_function('xarLog__shutdown_handler');

    return true;
}

/**
 * Will return the log configuration file directory and name
 */
function xarLogConfigFile()
{
    static $logConfigFile;

    if (isset($logConfigFile)) return $logConfigFile;

    $logConfigFile = xarCoreGetVarDirPath() . '/logs/config.log.php';

    if (file_exists($logConfigFile)) {
        $logConfigFile = realpath($logConfigFile);
    }

    return $logConfigFile;
}

/**
 * Will return true if the log config file exists and is readable, and false if not
 */
function xarLogConfigReadable()
{
    $logConfigFile = xarLogConfigFile();

    if (file_exists($logConfigFile) && is_readable($logConfigFile)) {
        return true;
    }

    return false;
}

/**
 * Will return the log file directory and name
 */
function xarLogFallbackFile()
{
    static $logFile;

    if (isset($logFile)) return $logFile;

    $logFile = xarCoreGetVarDirPath() . '/logs/log.txt';

    if (file_exists($logFile)) {
        $logFile = realpath($logFile);
    }

    return $logFile;
}

/**
 * Will check if the fallback mechanism can be used
 * @return bool
 */
function xarLogFallbackPossible()
{
    $logFile = xarLogFallbackFile ();
    if (file_exists($logFile) && is_writeable($logFile)) {
        return true;
    }

    return false;
}

/**
 * Shutdown handler for the logging system
 *
 *
 */
function xarLog__shutdown_handler()
{
     xarLogMessage("xarLog shutdown handler.");

    // If the debugger was active, we can dispose it now.
    if($GLOBALS['xarDebug'] & XARDBG_SQL) {
        xarLogMessage("Total SQL queries: $GLOBALS[xarDebug_sqlCalls].");
    }

    if ($GLOBALS['xarDebug'] & XARDBG_ACTIVE) {
        $lmtime = explode(' ', microtime());
        $endTime = $lmtime[1] + $lmtime[0];
        $totalTime = ($endTime - $GLOBALS['xarDebug_startTime']);
        xarLogMessage("Response was served in $totalTime seconds.");
    }

//During register_shutdown, it's already too late.
//fwrite presents problems during it.
//you can't use it with javascript/mozilla loggers...
//Maybe there should be a xaraya shutdown event?
/*
     xarLogMessage("xarLog shutdown handler: Ending all logging.");

    foreach (array_keys($GLOBALS['xarLog_loggers']) as $id) {
       $GLOBALS['xarLog_loggers'][$id]->;
    }
 */
}

function xarLog__add_logger($type, $config_args)
{
    if (!xarInclude ('includes/log/loggers/'.$type.'.php')) {
        xarCore_die('xarLog_init: Unable to load driver for logging: '.$type);
    }

    $type = 'xarLogger_'.$type;

     if (!$observer = new $type()) {
        xarCore_die('xarLog_init: Unable to instanciate class for logging: '.$type);
     }

      $observer->setConfig($config_args);

      $GLOBALS['xarLog_loggers'][] = &$observer;
}
/**
 * Log a message
 * @param string message. The message to log
 * @param string level. The level for this message OPTIONAL Defaults to XARLOG_LEVEL_DEBUG
 *
 */
function xarLogMessage($message, $level = XARLOG_LEVEL_DEBUG)
{

    if (($level == XARLOG_LEVEL_DEBUG) && !xarCoreIsDebuggerActive()) return;
    // this makes a copy of the object, so the original $this->_buffer was never updated
    //foreach ($_xarLoggers as $logger) {
    foreach (array_keys($GLOBALS['xarLog_loggers']) as $id) {
       $GLOBALS['xarLog_loggers'][$id]->notify($message, $level);
    }
}

function xarLogVariable($name, $var, $level = XARLOG_LEVEL_DEBUG)
{
    $args = array('name'=>$name, 'var'=>$var, 'format'=>'text');

    //Lazy load these functions... With php5 this will be easier.
    //Encapsulate core libraries in classes and let __call work lazy loading
    xarInclude('includes/log/functions/dumpvariable.php');
    xarLogMessage(xarLog__dumpVariable($args),$level);
}

?>

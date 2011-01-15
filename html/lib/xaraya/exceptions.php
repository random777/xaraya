<?php
/**
 * Exception Handling System
 *
 * For all documentation about exceptions see RFC-0054
 *
 * @package exceptions
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini <marco@xaraya.com>
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo the exception handler receives the instantiated Exception class.
 *       How do we know there what is available in the derived object so we can
 *       specialize handling? To only allow deriving from XARExceptions and
 *       standardize there is probably not enough, but lets do that for now.
**/

// Import all our exception types and the core exception handlers
sys::import('xaraya.exceptions.types');
sys::import('xaraya.exceptions.handlers');

/**
 * Deprecation Row
 */
function xarErrorSet($major, $errorID, $value = NULL) {return $errorID;}
function xarCurrentErrorID() { return false; }
function xarErrorGet($stacktype = "ERROR",$format='data') { return array(); }
function xarCurrentErrorType() { return false; }
function xarCurrentError() { return false; }
function xarErrorFree() { return true; }
function xarExceptionFree() { return true; }

/**
 * Default settings for:
 * exceptions: send to 'default' handler
 * errors    : send to 'phperrors' handler
 *
 * Of course, any piece of code can set their own handler after this
 * is loaded, which is almost what we want.
 *
 * @todo do we want this abstracted?
**/
//set_exception_handler(array('ExceptionHandlers','debughandler'));
//set_error_handler(array('ExceptionHandlers','phperrors'));

/**
 * General exception to cater for situation where the called function should
 * really raise one and the callee should catch it, instead of the callee
 * raising the exception. To prevent hub-hopping* all over the code
 *
 * @todo we need a way to determine the usage of this, because each use
 *       signals a 'code out of place' error
**/
class GeneralException extends xarExceptions
{
    protected $message = "An unknown error occurred.";
    protected $hint    = "The code raised an exception, but the nature of the error could not be determind";
}

/**
 * Debug function, artificially throws an exception
 *
 * @access public
 * @return void
 * @throws DebugException
**/
function debug($anything)
{
    throw new DebugException('DEBUGGING',var_export($anything,true));
}




/**
 * Exception Handling System
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage exceptions
 * @author Marco Canini <marco@xaraya.com>
 */
/**
 * Public errors
 */
define('XAR_NO_EXCEPTION', 0);
define('XAR_USER_EXCEPTION', 1);
define('XAR_SYSTEM_EXCEPTION', 2);
define('XAR_SYSTEM_MESSAGE', 3);

/**
 * Private core exceptions
 */
define('XAR_PHP_EXCEPTION', 10);
define('XAR_CORE_EXCEPTION', 11);
define('XAR_DATABASE_EXCEPTION', 12);
define('XAR_TEMPLATE_EXCEPTION', 13);

// {ML_include 'lib/xaraya/exceptions/defaultuserexception.php'}
// {ML_include 'lib/xaraya/exceptions/errorcollection.php'}
// {ML_include 'lib/xaraya/exceptions/exceptionstack.php'}
// {ML_include 'lib/xaraya/exceptions/htmlexceptionrendering.php'}
// {ML_include 'lib/xaraya/exceptions/noexception.php'}
// {ML_include 'lib/xaraya/exceptions/systemexception.php'}
// {ML_include 'lib/xaraya/exceptions/systemmessage.php'}
// {ML_include 'lib/xaraya/exceptions/textexceptionrendering.php'}

// {ML_include 'lib/xaraya/exceptions/defaultuserexception.defaults.php'}
// {ML_include 'lib/xaraya/exceptions/exception.php'}
// {ML_include 'lib/xaraya/exceptions/exceptionrendering.php'}
// {ML_include 'lib/xaraya/exceptions/systemexception.defaults.php'}
// {ML_include 'lib/xaraya/exceptions/systemmessage.defaults.php'}

$here=dirname(__FILE__);
sys::import('xaraya.exceptions.exceptionstack');

sys::import('xaraya.exceptions.systemmessage');
sys::import('xaraya.exceptions.systemexception');
sys::import('xaraya.exceptions.defaultuserexception');
sys::import('xaraya.exceptions.noexception');
sys::import('xaraya.exceptions.errorcollection');

global $CoreStack, $ErrorStack;

/* Error Handling System implementation */

/**
 * Initializes the Error Handling System
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @return bool true
 * @todo   can we move the stacks above into the init?
 */
function xarError_init(&$systemArgs, $whatToLoad)
{
    global $CoreStack,$ErrorStack;

    // The check for xdebug_enable is not necessary here, we want the handler enabled on the flag, period.
    if ($systemArgs['enablePHPErrorHandler'] == true ) { // && !function_exists('xdebug_enable')) {
        set_error_handler('xarException__phpErrorHandler');
    }

    $CoreStack = new xarExceptionStack();
    $CoreStack->initialize();

    $ErrorStack = new xarExceptionStack();
    xarErrorFree();

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarError__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for error subsystem
 *
 * @access private
 */
function xarError__shutdown_handler()
{
    //xarLogMessage("xarError shutdown handler");
}


/**
 * Handles the current error
 *
 * You must always call this function when you handle a caught error.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return voidx
 */
function xarErrorHandled()
{
//    if (xarCurrentErrorType() == XAR_NO_EXCEPTION) {
//            xarCore_die('xarErrorHandled: Invalid major value: XAR_NO_EXCEPTION');
//    }

    global $ErrorStack;
    if (!$ErrorStack->isempty())
    $ErrorStack->pop();
}

/**
 * Renders the current error.
 *
 * The error can be shown in a template specific to it. To override it modify
 * the exception templates in Base module.
 * Returns a string formatted according to the $format parameter which provides
 * all information available on current error.
 * If there is no error currently raised an empty string is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @param string format    one of 'template', 'rawhtml' or 'text'
 * @param string stacktype one of 'ERROR' (default) or 'CORE'
 * @param bool   shortmsg  makes message without stack  (default = false)
 * @return string representing the raised error
 */
function xarErrorRender($format, $stacktype = 'ERROR', $shortmsg = false)
{
    assert('$format == "template" || $format == "rawhtml" || $format == "text"; /* Improper format passed to xarErrorRender */');

    // 2009-06-11 JDJ Save the current error ID so it can be made available to the error templates.
    $CurrentErrorID = xarCurrentErrorID();

    $msgs = xarException__formatStack($format,$stacktype);
    $error = $msgs[0];

    switch ($error->getMajor()) {
        case XAR_SYSTEM_EXCEPTION:
            $template = "systemerror";
            break;
        case XAR_USER_EXCEPTION:
            $template = "usererror";
            break;
        case XAR_SYSTEM_MESSAGE:
            $template = "systeminfo";
            break;
        case XAR_NO_EXCEPTION:
            break;
        default:
            break;
    }
    if (headers_sent() == false) {
        $httpResponse = 'HTTP/1.1';
        switch ($CurrentErrorID) {
            case 'FORBIDDEN_OPERATION':
            case 'NO_PRIVILEGES':
            case 'NOT_LOGGED_IN':
                $httpResponse .= ' 403 Forbidden';
            break;
            case 'NOT_FOUND':
            case 'FILE_NOT_EXIST':
            case 'MODULE_NOT_ACTIVE':
            case 'MODULE_FUNCTION_NOT_EXIST':
                $httpResponse .= ' 404 Not Found';
            break;
            case 'ALREADY_EXISTS':
            case 'BAD_DATA':
            case 'CANNOT_CONTINUE':
            case 'DUPLICATE_DATA':
            case 'MISSING_DATA':
            case 'MULTIPLE_INSTANCES':
            case 'WRONG_VERSION':
            case 'EMPTY_PARAM':
            case 'BAD_PARAM':
            default:
                $httpResponse .= ' 503 Service Unavailable';
            break;
        }
        header($httpResponse);
    }
    $data = array();
    $data['id'] = $CurrentErrorID;
    $data['major'] = $error->getMajor();
    $data['type'] = $error->getType();
    $data['title'] = $error->getTitle();
    $data['short'] = $error->getShort();
    $data['long'] = $error->getLong();
    if (!$shortmsg) {
        $data['hint'] = $error->getHint();
        $data['stack'] = $error->getStack();
        $data['product'] = $error->getProduct();
        $data['component'] = $error->getComponent();
    } else {
        $data['hint'] = '';
        $data['stack'] = '';
        $data['product'] = '';
        $data['component'] = '';
    }

    if ($format == 'template') {
        $theme_dir = xarTplGetThemeDir();
        if(file_exists($theme_dir . '/modules/base/message-' . $error->id . '.xt')) {
            return xarTplFile($theme_dir . '/modules/base/message-' . $error->id . '.xt', $data);
        } elseif(file_exists($theme_dir . '/modules/base/message-' . $template . '.xt')) {
            return xarTplFile($theme_dir . '/modules/base/message-' . $template . '.xt', $data);
        } elseif(file_exists('modules/base/xartemplates/message-' . $template . '.xt')) {
            return xarTplFile('modules/base/xartemplates/message-' . $template . '.xt', $data);
        } else {
            return xarTplFile('modules/base/xartemplates/message-' . $template . '.xt', $data);
        }
    }
    elseif ($format == 'rawhtml') {
        $msg = "<b><u>" . $data['title'] . "</u></b><br /><br />";
        $msg .= "<b>Description:</b> " . $data['short'] . "<br /><br />";
        $msg .= "<b>Explanation:</b> " . $data['long'] . "<br /><br/>";
        if ($data['hint'] != '') $msg .= "<b>Hint:</b> " . $data['hint'] . "<br /><br/>";
        if ($data['stack'] != '') $msg .= "<b>Stack:</b><br />" . $data['stack'] . "<br /><br />";
        if ($data['product'] != '') $msg .= "<b>Product:</b> " . $data['product'] . "<br /><br />";
        if ($data['component'] != '') $msg .= "<b>Component:</b> " . $data['component'] . "<br /><br />";
        return $msg;
    }
    elseif ($format == 'text') {
        $msg = $data['title'] . "\n";
        $msg .= "Description: " . $data['short'] . "\n";
        $msg .= "Explanation: " . $data['long'] . "\n";
        if ($data['hint'] != '') $msg .= "Hint: " . $data['hint'] . "\n";
        if ($data['stack'] != '') $msg .= "Stack:\n" . $data['stack'] . "\n";
        if ($data['product'] != '') $msg .= "Product: " . $data['product'] . "\n";
        if ($data['component'] != '') $msg .= "Component: " . $data['component'] . "\n";
        return $msg;
    }
}




// PRIVATE FUNCTIONS

/**
 * Adds formatting to the raw error messages
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access private
 * @param format string one of html or text
 * @return array of formatted error msgs
 */
function xarException__formatStack($format,$stacktype = "ERROR")
{
    global $ErrorStack;
    global $CoreStack;

    if ($stacktype == "ERROR") $stack = $ErrorStack;
    else $stack = $CoreStack;

    $formattedmsgs = array();
    while (!$stack->isempty()) {

        $error = $stack->pop();

        // FIXME: skip noexception because it's not rendered well
        if (empty($error->major)) continue;

        if ($format == 'template' || $format == 'rawhtml') {
            if (!class_exists('HTMLExceptionRendering')) {
                sys::import('xaraya.exceptions.htmlexceptionrendering');
            }
            $msg = new HTMLExceptionRendering($error);
        }
        else {
            if (!class_exists('TextExceptionRendering')) {
                sys::import('xaraya.exceptions.exceptionrendering');
            }
            $msg = new TextExceptionRendering($error);
        }
        $formattedmsgs[] = $msg;
    }
    return $formattedmsgs;
}

/**
 * Error handlers section
 *
 * For several areas there are specific bridges to route errors into
 * the exception subsystem:
 *
 * Handlers:
 * 1. assert failures -> xarException__assertErrorHandler($script,$line,$code)
 * 2. ado db errors   -> xarException__dbErrorHandler($databaseName, $funcName, $errNo, $errMsg, $param1 = fail, $param2 = false)
 * 3. php Errors      -> xarException__phpErrorHandler($errorType, $errorString, $file, $line)
 *
 * @todo Use trigger_error functionality for them all and take php5 into account
 */

/**
 * Error handler for assert failures
 *
 * This handler is called when assertions in code fail.
 *
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @access private
 * @param  string  $script filename in which assertion failed
 * @param  integer $line   linenumber on which assertion is made
 * @param  string  $code   the assertion expressed in code which evaluated to false
 * @return void
 */
function xarException__assertErrorHandler($script,$line,$code)
{
    // Redirect the assertion to a system exception
    $msg = "ASSERTION FAILED: $script [$line] : $code";
    xarErrorSet(XAR_SYSTEM_EXCEPTION,'ASSERT_FAILURE',$msg);
}

/**
 * ADODB error handler bridge
 *
 * @access private
 * @param  string databaseName
 * @param  string funcName
 * @param  integer errNo
 * @param  string errMsg
 * @param  bool param1
 * @param  bool param2
 * @throws  DATABASE_ERROR
 * @return void
 * @todo   <marco> complete it
 */
function xarException__dbErrorHandler($databaseName, $funcName, $errNo, $errMsg, $param1 = false, $param2 = false)
{
    if ($funcName == 'EXECUTE') {
        if (function_exists('xarML')) {
            $msg = xarML('Database error while executing: \'#(1)\'; error description is: \'#(2)\'.', $param1, $errMsg);
        } else {
            $msg = 'Database error while executing: '. $param1 .'; error description is: ' . $errMsg;
        }
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR_QUERY', new SystemException("ErrorNo: ".$errNo.", Message:".$msg));
    } else {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'DATABASE_ERROR', $errMsg);
    }
}

/**
 * PHP error handler bridge to Xaraya exceptions
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access private
 * @return void
 */
function xarException__phpErrorHandler($errorType, $errorString, $file, $line)
{
    global $CoreStack;

    //Checks for a @ presence in the given line, should stop from setting Xaraya or DB errors
    $errLevel = xarCore_getSystemVar('Exception.ErrorLevel',true);
    $logLevel = xarCore_getSystemVar('Exception.ErrorLogLevel', true);
    if(!isset($errLevel)) $errLevel = E_ALL;
    if(!isset($logLevel)) $logLevel = E_ALL;

    // Check if we want to log this type of errors
    if ($errorType & $logLevel) {
        // TODO: make this message available to calling functions that suppress
        // errors through '@'.
        $msg = "PHP error code $errorType at line $line of $file: $errorString";
        xarLogMessage($msg);
    }
    // Check if error handling should end here, see bug 1828
    if (!error_reporting() || !($errorType & $errLevel)) {
        return;
    }

    //Newer php versions have a 5th parameter that will give us back the context
    //The variable values during the error...
    $msg = "At: " . $file." (Line: " . $line.")\n". $errorString ;

    // Trap for errors that are on the so-called "safe path" for rendering
    // Need to revert to raw HTML here
    if (isset($_GET['func']) && $_GET['func'] == 'systemexit') {
        echo '<font color="red"><b>^Error Condition<br /><br />see below<br /><br /></b></font>';
        $rawmsg = "</table><div><hr /><b>Recursive Error</b><br /><br />";
        $rawmsg .= "Normal Xaraya error processing has stopped because of a recurring PHP error. <br /><br />";
        $rawmsg .= "The last registered error message is: <br /><br />";
        $rawmsg .= "PHP Error code: " . $errorType . "<br /><br />";
        $rawmsg .= $msg . "</div>";
        if (headers_sent() == false)
            header('HTTP/1.1 503 Service Unavailable');
        echo $rawmsg;
        exit;
    }

    // Make cached files also display their source file if it's a template
    // This is just for convenience when giving support, as people will probably
    // not look in the CACHEKEYS file to mention the template.
    if(isset($GLOBALS['xarTpl_cacheTemplates'])) {
        $sourcetmpl='';
        $base = basename(strval($file),'.php');
        $varDir = xarCoreGetVarDirPath();
        if (file_exists($varDir . '/cache/templates/CACHEKEYS')) {
            $fd = fopen($varDir . '/cache/templates/CACHEKEYS', 'r');
            while($cache_entry = fscanf($fd, "%s\t%s\n")) {
                list($hash, $template) = $cache_entry;
                // Strip the colon
                $hash = substr($hash,0,-1);
                if($hash == $base) {
                    // Found the file, source is $template
                    $sourcetmpl = $template;
                    break;
                }
            }
            fclose($fd);
        }
    }

    if(isset($sourcetmpl) && $sourcetmpl != '') $msg .= "<br/><br/>[".$sourcetmpl."]";
    if (!function_exists('xarModURL')) {
        $rawmsg = "Normal Xaraya error processing has stopped because of an error encountered. <br /><br />";
        $rawmsg .= "The last registered error message is: <br /><br />";
        $rawmsg .= "PHP Error code: " . $errorType . "<br /><br />";
        $rawmsg .= $msg;
        if (headers_sent() == false)
            header('HTTP/1.1 503 Service Unavailable');
        echo $rawmsg;
        exit;
    }
    else {
        if (xarRequest::$allowShortURLs && isset(xarRequest::$shortURLVariables['module'])) {
            $module = xarRequest::$shortURLVariables['module'];
        // Then check in $_GET
        } elseif (isset($_GET['module'])) {
            $module = $_GET['module'];
        // Try to fallback to $HTTP_GET_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_GET_VARS']['module'])) {
            $module = $GLOBALS['HTTP_GET_VARS']['module'];
        // Nothing found, return void
        } else {
            $module = '';
        }
        $product = '';
        $component = '';
        if ($module != '') {
            // load relative to the current file (e.g. for shutdown functions)
            sys::import('xaraya.exceptions.xarayacomponents');
            foreach ($core as $corecomponent) {
                if ($corecomponent['name'] == $module) {
                    $component = $corecomponent['fullname'];
                    $product = "App - Core";
                    break;
                }
            }
            if ($component != '') {
                foreach ($apps as $appscomponent) {
                    if ($appscomponent['name'] == $module) {
                        $component = $appscomponent['fullname'];
                        $product = "App - Modules";
                    }
                }
            }
        }
        // Fall-back in case it's too late to redirect
        if (headers_sent() == true) {
            $rawmsg = "Normal Xaraya error processing has stopped because of an error encountered. <br /><br />";
            $rawmsg .= "The last registered error message is: <br /><br />";
            $rawmsg .= "Product: " . $product . "<br />";
            $rawmsg .= "Component: " . $component . "<br />";
            $rawmsg .= "PHP Error code: " . $errorType . "<br /><br />";
            $rawmsg .= $msg;
            echo $rawmsg;
            return;
        }
        // CHECKME: <mrb> This introduces a dependency to 2 subsystems
        xarResponseRedirect(xarModURL('base','user','systemexit',
        array('code' => $errorType,
              'exception' => $msg,
              'product' => $product,
              'component' => $component)));
    }
}

/**
 * Returns a debug back trace
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access private
 * @return array back trace
 */
function xarException__backTrace()
{
    $btFuncName = array();

    if (function_exists('xdebug_enable')) {
        xdebug_enable();
        $btFuncName = xarException__xdebugBackTrace();
    } elseif (function_exists('debug_backtrace')) {
        $btFuncName = debug_backtrace();
    }
    return $btFuncName;
}

/**
 * Returns a debug back trace using xdebug
 *
 * Converts a xdebug stack trace to a valid back trace.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access private
 * @return array back trace
 */
function xarException__xdebugBackTrace()
{
    $stack = xdebug_get_function_stack();
    // Performs some action to make $stack conformant with debug_backtrace
    array_shift($stack); // Drop {main}
    array_pop($stack); // Drop xarException__xdebugBackTrace
    if (xarCoreIsDebugFlagSet(XARDBG_SHOW_PARAMS_IN_BT)) {
        for($i = 0, $max = count($stack); $i < $max; $i++) {
            $stack[$i]['args'] = $stack[$i]['params'];
        }
    }
    return array_reverse($stack);
}


function xarCoreExceptionFree()
{
    global $CoreStack;
    $CoreStack->initialize();
}

function xarIsCoreException()
{
    global $CoreStack;
    return $CoreStack->size() > 1;
}

//NOT GPLed CODE: (Probably Public Domain? or PHP's?)
//Code from PHP's manual on function print_r
//So this can work for versions lower than php 4.3 http://br.php.net/function.print_r
//Code by ???? matt at crx4u dot com??? Not clear from the manual
function xarException__formatBacktrace ($vardump,$key=false,$level=0)
{
    if (version_compare("4.3.0", phpversion(), "<=")) return print_r($vardump, true);
    //else
    //Getting afraid some of the arrays might reference itself... Dont know what will happen
    if ($level == 16) return '';

    $tabsize = 4;

    //make layout
    $return .= str_repeat(' ', $tabsize*$level);
    if ($level != 0) $key = "[$key] =>";

    //look for objects
    if (is_object($vardump))
        $return .= "$key ".get_class($vardump)." ".$vardump."\n";
    else
        $return .= "$key $vardump\n";

     if (gettype($vardump) == 'object' || gettype($vardump) == 'array') {
        $level++;
        $return .= str_repeat(' ', $tabsize*$level);
        $return .= "(\n";

        if (gettype($vardump) == 'object')
            $vardump = (array) get_object_vars($vardump);

        foreach($vadump as $key => $value)
            $return .= xarException__formatBacktrace($value,$key,$level+1);

        $return .= str_repeat(' ', $tabsize*$level);
        $return .= ")\n";
        $level--;
    }

     //return everything
     return $return;
}

?>

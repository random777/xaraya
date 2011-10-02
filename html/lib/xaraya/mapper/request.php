<?php
/**
 * Request class
 *
 * @package core
 * @subpackage controllers
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/

class xarRequest extends Object
{
    protected $url;
    protected $actionstring;
    protected $dispatched = false;
    protected $modulekey = 'module';
    protected $typekey   = 'type';
    protected $funckey   = 'func';
    protected $module    = 'base';
    protected $modulealias = '';
    protected $type      = 'user';
    protected $func      = 'main';
    protected $funcargs  = array();
    protected $object    = 'objects';
    protected $method    = 'view';
    protected $route     = 'default';
    
    public $defaultRequestInfo = array();
    public $isObjectURL        = false;

    public $entryPoint;
    public $separator    = '&';
    
    function __construct($url=null)
    {
        // Make this load lazily
        $this->setModule(xarModVars::get('modules', 'defaultmodule'));
        $this->setType(xarModVars::get('modules', 'defaultmoduletype'));
        $this->setFunction(xarModVars::get('modules', 'defaultmodulefunction'));

        $this->entryPoint = xarController::$entryPoint;
        $this->setURL($url);
    }

    function setURL($url=null)
    {
        if (is_array($url)) {
            // This is an array representing a traditional Xaraya URL array
            if (!empty($url['module'])) {
                // Resolve if this is an alias for some other module
                $this->setModule(xarModAlias::resolve($url['module']));
                if ($this->getModule() != $url['module']) $this->setModuleAlias($url['module']);
                unset($url['module']);
            }
            if (!empty($url['type'])) {
                $this->setType($url['type']);
                unset($url['type']);
            }
            if (!empty($url['func'])) {
                $this->setFunction($url['func']);
                unset($url['func']);
            }
            $this->setFunctionArgs($url);
        } else {
            if (null == $url) {
                // This is a string representing a URL
                // Try and get it from the current request path
                $url = xarServer::getCurrentURL();
                $params = $_GET;
            } else {
                $params = xarController::parseQuery($url);
            }
            // We now have a URL. Set it.
            $this->url = $url;
            
            // Get hte current route
            $router = xarController::getRouter();
            $router->route($this);
            
            
            // var_dump($this->route);exit;
            /*
            $parts = $this->validate($params);

            if (!empty($parts[0])) $this->setModule($parts[0]);
            if (!empty($parts[1])) $this->setType($parts[1]);
            if (!empty($parts[2])) $this->setFunction($parts[2]);

            // See if this is an object call; easiest to start like this 
            xarVarFetch('object', 'regexp:/^[a-z][a-z_0-9]*$/', $objectName, NULL, XARVAR_NOT_REQUIRED);
            // Found a module object name
            if (null != $objectName) {
                $this->setModule('object');
                $this->setType($objectName);
                $this->setFunction($this->method);
            } else {
                // Try and get the module the traditional Xaraya way
                xarVarFetch('module', 'regexp:/^[a-z][a-z_0-9]*$/', $modName, NULL, XARVAR_NOT_REQUIRED);

                // Else assume a form of short urls. The module name or the object keyword will be the first item
                if (null == $modName) {
                    $path = substr($url,strlen(xarServer::getBaseURL() . $this->entryPoint . xarController::$delimiter));
                    $tokens = explode('/', $path);
                    $modName = array_shift($tokens);
                    
                    // This is an object call
                    if ($modName == 'object') {
                        $this->setModule('object');
                        $this->setType(array_shift($tokens));
                        $this->setFunction($this->method);
                    
                    // This is a module name
                    } else {
                        // Resolve if this is an alias for some other module
                        if (!empty($modName)) $this->setModule(xarModAlias::resolve($modName));
                        if ($this->getModule() != $modName) $this->setModuleAlias($modName);
                    }
                } else {
                    // Resolve if this is an alias for some other module
                    if (!empty($modName)) $this->setModule(xarModAlias::resolve($modName));
                    if ($this->getModule() != $modName) $this->setModuleAlias($modName);
                }

            }
            // Get the query parameters too
            // Module, type, func, object and method are reserved names, so remove them from the array
            unset($params['module']);
            unset($params['type']);
            unset($params['func']);
            unset($params['object']);
            unset($params['method']);
            $this->setFunctionArgs($params);
            // At this point the request has assembled the module or object it belongs to and any query parameters.
            // What is still to be defined by routing are the type (for modules) and function/function arguments or method (for objects).            
        */}
    }
    
    /**
     * Gets request info for current page or a given url.
     */
    public function getInfo($url='')
    {        
        static $currentRequestInfo = NULL;
        static $loopHole = NULL;
        if (is_array($currentRequestInfo) && empty($url)) {
            return $currentRequestInfo;
        } elseif (is_array($loopHole)) {
            // FIXME: Security checks in functions used by decode_shorturl cause infinite loops,
            //        because they request the current module too at the moment - unnecessary ?
            xarLogMessage('Avoiding loop in xarController::$request->getInfo()');
            return $loopHole;
        }
        
    // ---------------------------------------
        if (empty($url)) {
            // No URL: get values stored in this request and move on
            // CHECKME: is this correct?
            $info = array(
                $this->getModule(),
                $this->getType(),
                $this->getFunction(),
            );
            // Save the current info in case we call this function again
            $currentRequestInfo = $info;
            return $info;
        }
        
    // ---------------------------------------
        $params = xarController::parseQuery($url);
        $parts = $this->validate($params);

        if (!empty($parts[0])) {
            // We found a module
            // Type and func were either found or the defauts added. We can move on
            $info = $parts;
            return $info;
        }
        
    // ---------------------------------------
        // No module found
        // Check if we have an object to work with for object URLs
        xarVarFetch('object', 'regexp:/^[a-zA-Z0-9_-]+$/', $objectName, NULL, XARVAR_NOT_REQUIRED);
        if (!empty($objectName)) {
            // Check if we have a method to work with for object URLs
            xarVarFetch('method', 'regexp:/^[a-zA-Z0-9_-]+$/', $methodName, NULL, XARVAR_NOT_REQUIRED);
            // Specify 'dynamicdata' as module for xarTpl_* functions etc.
            $info = array('object', $objectName, $methodName);
            if (empty($url)) $this->isObjectURL = true;
            return $info;
            
        }
            
    // ---------------------------------------
        // Still no valid URL; return the default values
        if (empty($this->defaultRequestInfo)) {
            $this->defaultRequestInfo = array($this->getModule(),
                                              $this->getType(),
                                              $this->getFunction());
        }
        return $this->defaultRequestInfo;
    }
    
    function validate($params) {
        if (!empty($params)) {
            sys::import('xaraya.validations');
            $regex = ValueValidations::get('regexp');
        }
        if (isset($params['module'])) {
            try {
                $isvalid =  $regex->validate($params['module'], array('/^[a-z][a-z_0-9]*$/'));
            } catch (Exception $e) {
                $isvalid =  false;
            }
            $modName = $isvalid ? $params['module'] : null;
        } else {
            $modName = null;
        }
        
        if (isset($params['type'])) {
            try {
                $isvalid =  $regex->validate($params['type'], array('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/'));
            } catch (Exception $e) {
                $isvalid =  false;
            }
            $modType = $isvalid ? $params['type'] : $this->getType();
        } else {
            $modType = $this->getType();
        }

        if (isset($params['func'])) {
            try {
                $isvalid =  $regex->validate($params['func'], array('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/'));
            } catch (Exception $e) {
                $isvalid =  false;
            }
            $funcName = $isvalid ? $params['func'] : $this->getFunction();
        } else {
            $funcName = $this->getFunction();
        }
        return array($modName,$modType,$funcName);
    }
    
    /**
     * Check to see if this request is an object URL
     *
     * 
     * @return boolean true if object URL, false if not
     */
    function isObjectURL() { return $this->isObjectURL; }

    function getProtocol()       { return xarServer::getProtocol(); }
    function getHost()           { return xarServer::getHost(); }
    function getModuleKey()      { return $this->modulekey; }
    function getTypeKey()        { return $this->typekey; }
    function getFunctionKey()    { return $this->funckey; }
    function getModule()         { return $this->module; }
    function getModuleAlias()    { return $this->modulealias; }
    function getType()           { return $this->type; }
    function getFunction()       { return $this->func; }
    function getObject()         { return $this->object; }
    function getMethod()         { return $this->method; }
    function getActionString()   { return $this->actionstring; }
    function getFunctionArgs()   { return $this->funcargs; }
    function getURL()            { return $this->url; }
    function getRoute()          { return $this->route; }

    function setModule($p)               { $this->module = $p; }
    function setModuleAlias($p)          { $this->modulealias = $p; }
    function setType($p)                 { $this->type = $p; }
    function setFunction($p)             { $this->func = $p; }
    function setObject($p)               { $this->object = $p; }
    function setMethod($p)               { $this->method = $p; }
    function setRoute($r)                { $this->route = $r; }
    function setActionString($p)         { $this->actionstring = $p; }
    function setFunctionArgs($p=array()) { $this->funcargs = $p; }

    public function isDispatched()
    {
        return $this->dispatched;
    }

    public function setDispatched($flag=true)
    {
        $this->dispatched = $flag ? true : false;
        return true;
    }
}

?>
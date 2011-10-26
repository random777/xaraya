<?php
sys::import('xaraya.mapper2.interfaces');
sys::import('xaraya.mapper2.url');
class xarController extends Object
{
    // @todo evaluate the need for these    
    public static $allowShortURLs = true;
    public static $shortURLVariables;

    protected static $entrypath;    // BaseURI 
    protected static $entrypoint;   // BaseModURL
        
    // static objects, ideally these should be protected and accessed via get* methods
    public static $request;
    public static $router;
    public static $dispatcher;
    public static $response;
        
    public static function init(Array $args=array())
    {
        //self::getRouter()->decode(self::getRequest());
        self::getResponse();
    }

    public static function getEntryPath()
    {
        if (isset(self::$entrypath))
            return self::$entrypath;
        try {
            self::$entrypath =  xarSystemVars::get(sys::LAYOUT, 'BaseURI');
        } catch(Exception $e) {
            self::$entrypath = xarServer::getBaseURI();
        }            
        return self::$entrypath;
    }

    public static function getEntryPoint()
    {
        if (isset(self::$entrypoint))
            return self::$entrypoint;
        try {
            self::$entrypoint =  xarSystemVars::get(sys::LAYOUT, 'BaseModURL');
        } catch(Exception $e) {
            self::$entrypoint = 'index.php';
        }          
        return self::$entrypoint;
    }
        
    public static function getRequest($object=null)
    {
        if (isset(self::$request))
            return self::$request;
        if (is_string($object) && class_exists($object) && is_subclass_of($object, 'ixarRequest'))
            return self::$request = new $object();
        if (is_object($object) && is_subclass_of($object, 'ixarRequest'))
            return self::$request = $object;
        sys::import('xaraya.mapper2.request');
        return self::$request = new xarRequest();
    }

    public static function normalizeRequest()
    {
        self::getRouter()->decode(self::getRequest());
        // SEO: if the url wasn't decoded by the default route, re-encode using default
        if (xarServer::getVar('REQUEST_METHOD') == 'GET' && 
            self::getRequest()->getDecoder() != self::getRouter()->getDefaultRoute() &&
            self::getRouter()->encode(self::getRequest())) {
            // if we're not already there, redirect 
            if (self::getRequest()->getUrl() != xarServer::getCurrentUrl())
                self::redirect(self::getRequest()->getUrl(), 301);
        }
    }

    public static function dispatch()
    {
        self::getDispatcher()->dispatch(self::getRequest());
    }

    public static function getResponse($object=null)
    {
        if (isset(self::$response))
            return self::$response;
        if (is_string($object) && class_exists($object) && is_subclass_of($object, 'xarResponse'))
            return self::$response = new $object();
        if (is_object($object) && is_subclass_of($object, 'xarResponse'))
            return self::$response = $object;
        sys::import('xaraya.mapper2.response');
        return self::$response = new xarResponse();    
    }
    
    public static function getRouter($object=null)
    {
        if (isset(self::$router))
            return self::$router;
        if (is_string($object) && class_exists($object) && is_subclass_of($object, 'ixarRouter'))
            return self::$router = new $object();
        if (is_object($object) && is_subclass_of($object, 'ixarRouter'))
            return self::$router = $object;
        sys::import('xaraya.mapper2.router');
        return self::$router = new xarRouter(array('short'));    
    }
    
    public static function getDispatcher($object=null)
    {
        if (isset(self::$dispatcher))
            return self::$dispatcher;
        if (is_string($object) && class_exists($object) && is_subclass_of($object, 'ixarDispatcher'))
            return self::$dispatcher = new $object();
        if (is_object($object) && is_subclass_of($object, 'ixarDispatcher'))
            return self::$dispatcher = $object;
        sys::import('xaraya.mapper2.dispatcher');
        return self::$dispatcher = new xarDispatcher();    
    }

    public static function URL($module=null, $type='user', $func='main', $args=array(), $xmlurls=null, $target=null, $entrypoint=null)
    {
        $encoded = self::getRouter()->encode(new xarUrl2($module, $type, $func, $args, $xmlurls, $target, $entrypoint));
        return $encoded->getUrl();
    }

    // copy/pasted functions from mapper/main 
    // @todo: these need review

    /**
     * Get request variable
     *
     * 
     * @param name string
     * @param allowOnlyMethod string
     * @return mixed
     * @todo change order (POST normally overrides GET)
     * @todo have a look at raw post data options (xmlhttp postings)
     * chris, this doesn't belong here
     */
    static function getVar($name, $allowOnlyMethod = NULL)
    {
        if (strpos($name, '[') === false) {
            $poststring = '$_POST["' . $name . '"]';            
        } else {
            $position = strpos($name, '[');
            $poststring = '$_POST["' . substr($name,0,$position) . '"]' . substr($name,$position);            
        }
        eval("\$isset = isset($poststring);");

        if ($allowOnlyMethod == 'GET') {
            // Short URLs variables override GET variables
            if (self::$allowShortURLs && isset(self::$shortURLVariables[$name])) {
                $value = self::$shortURLVariables[$name];
            } elseif (isset($_GET[$name])) {
                // Then check in $_GET
                $value = $_GET[$name];
            } else {
                // Nothing found, return void
                return;
            }
            $method = $allowOnlyMethod;
        } elseif ($allowOnlyMethod == 'POST') {
            if ($isset) {
                // First check in $_POST
                eval("\$value = $poststring;");
            } else {
                // Nothing found, return void
                return;
            }
            $method = $allowOnlyMethod;
        } else {
            if (self::$allowShortURLs && isset(self::$shortURLVariables[$name])) {
                // Short URLs variables override GET and POST variables
                $value = self::$shortURLVariables[$name];
                $method = 'GET';
            } elseif ($isset) {
                // Then check in $_POST
                eval("\$value = $poststring;");
                $method = 'POST';
            } elseif (isset($_GET[$name])) {
                // Then check in $_GET
                $value = $_GET[$name];
                $method = 'GET';
            } else {
                // Nothing found, return void
                return;
            }
        }

        $value = xarMLS_convertFromInput($value, $method);

        if (get_magic_quotes_gpc()) {
            $value = self::__stripslashes($value);
        }
        return $value;
    }

    static function __stripslashes($value)
    {
        $value = is_array($value) ? array_map(array('self','__stripslashes'), $value) : stripslashes($value);
        return $value;
    }

    /**
     * Check to see if this is a local referral
     *
     * 
     * @return boolean true if locally referred, false if not
     * chris, this belongs in request object or xarServer
     */
    static function isLocalReferer()
    {
        $server  = xarServer::getHost();
        $referer = xarServer::getVar('HTTP_REFERER');

        if (!empty($referer) && preg_match("!^https?://$server(:\d+|)/!", $referer)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Carry out a redirect
     *
     * 
     * @param redirectURL string the URL to redirect to
     * chris, this belongs in request object or xarServer
     */
    static function redirect($url, $httpResponse=NULL)
    {
        xarCache::noCache();
        $redirectURL = urldecode($url); // this is safe if called multiple times.
        if (headers_sent() == true) return false;

        // Remove &amp; entities to prevent redirect breakage
        $redirectURL = str_replace('&amp;', '&', $redirectURL);

        if (substr($redirectURL, 0, 4) != 'http') {
            // Removing leading slashes from redirect url
            $redirectURL = preg_replace('!^/*!', '', $redirectURL);

            // Get base URL
            $baseurl = xarServer::getBaseURL();

            $redirectURL = $baseurl.$redirectURL;
        }

        if (preg_match('/IIS/', xarServer::getVar('SERVER_SOFTWARE')) && preg_match('/CGI/', xarServer::getVar('GATEWAY_INTERFACE')) ) {
            $header = "Refresh: 0; URL=$redirectURL";
        } else {
            $header = "Location: $redirectURL";
        }// if

        // default response is temp redirect
        if (!preg_match('/^301|302|303|307/', $httpResponse)) {
            $httpResponse = 302;
        }

        // Start all over again
        header($header, TRUE, $httpResponse);

        // NOTE: we *could* return for pure '1 exit point' but then we'd have to keep track of more,
        // so for now, we exit here explicitly. Besides the end of index.php this should be the only
        // exit point.
        exit();
    }
    
}
?>
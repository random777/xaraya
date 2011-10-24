<?php
sys::import('xaraya.mapper2.interfaces');
class xarController extends Object
{
    // @todo evaluate the need for these    
    public static $allowShortURLs = true;
    public static $shortURLVariables;
    
    // static objects 
    public static $request;
    public static $router;
    public static $dispatcher;
        
    public static function init(Array $args=array())
    {
        //self::getRouter()->decode(self::getRequest());
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
    }

    public static function dispatch()
    {
        self::getDispatcher()->dispatch(self::getRequest());
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
        return self::$router = new xarRouter();    
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
    
}
?>
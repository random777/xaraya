<?php
/**
 * The Router
 * Responsible for actioning routing requests
**/
class xarRouter extends Object implements ixarRouter
{
    private $routes;    
    private $suffix = 'Route';
    protected $defaultroute;

/**
 * constructor
 *
 * @param array $routes array of routes to try (usually from base config)
 * @return void
**/ 
    public function __construct($routes=array())
    {
        $this->defaultroute = xarConfigVars::get(null, 'Site.Core.DefaultRoute');
        // attach any routes supplied in the order they were given (used when decoding) 
        foreach ($routes as $route) 
            $this->loadRoute($route);
        // the default route is always available        
        $this->loadRoute('default');
    }

/**
 * encoder
 *
 * @param object URL object
 * @return object URL object 
**/    
    public function encode(ixarUrl $url)
    {
        // we want to keep encoding as tight as possible 
        // if the default encoding is available, use it 
        if ($this->isAttached($this->defaultroute)) {
            if ($this->getRoute($this->defaultroute)->encode($url)) {
                $url->setEncoder($this->defaultroute);
                return $url;
            }
        }
        // if default encode failed, try the others for a match 
        foreach ($this->routes as $route) 
            if ($route->encode($url)) {
                $url->setEncoder($route->getName());           
                break;
            }
        // pass back the url object
        return $url;   
    }
/**
 * decoder
 *
 * @param object URL object
 * @return object URL object 
**/      
    public function decode(ixarUrl $url)
    {
        // we want to keep decoding as loose as possible 
        // try each route in turn, break on first positive response
        foreach ($this->routes as $route) 
            if ($route->decode($url)) {
                $url->setDecoder($route->getName());
                break;
            }
        // pass back the url object
        return $url;
    }
    
    public function attach(ixarRoute $route)
    {
        $key = $route->getName();
        $this->routes[$key] = $route;
    }
    
    public function detach(ixarRoute $route)
    {
        $key = $route->getName();
        unset($this->routes[$key]);    
    }
    
    public function isAttached($name)
    {
        return isset($this->routes[$name]);
    }
    
    public function getRoute($name)
    {
        if (!$this->isAttached($name)) return;
        return $this->routes[$name];
    }

    public function loadRoute($route)
    {
        if (!$this->isAttached($route)) {
            try {
                sys::import("xaraya.mapper2.routes.$route");
                $routeClass = ucfirst($route).$this->suffix;
                if (class_exists($routeClass) && is_subclass_of($routeClass, 'ixarRoute'))
                    $this->attach(new $routeClass());
            } catch (Exception $e) { }
        }
        return $this->isAttached($route);
    }
    
    public static function getFiles()
    {
        static $files = array();
        if (!empty($files)) return $files;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(sys::lib().'xaraya/mapper2/routes')) as $file) {
            if ($file->isDir() || 
                pathinfo($file, PATHINFO_EXTENSION) != 'php' ||
                $file->getBaseName('.php') == 'base') continue;
            $route = $file->getBaseName('.php');
            $files[$route] = "xaraya.mapper2.routes.$route";
        }
        return $files;
    }
}
?>
<?php
/**
 * The Router
 * Responsible for actioning routing requests
**/
class xarRouter extends Object
{
    private $routes;    
    private $suffix = 'Route';

/**
 * constructor
 *
 * @param array $routes array of routes to try (usually from base config)
 * @return void
**/ 
    public function __construct($routes=array())
    {
        // attach any routes supplied in the order they were given 
        foreach ($routes as $route) {
            sys::import('xaraya.mapper2.routes.'.$route);
            $routeCls = ucfirst($route).$this->suffix;
            $this->attach(new $routeCls());
        }
        // the default route is always available
        if (!$this->isAttached('default')) {
            sys::import('xaraya.mapper2.routes.default');
            $routeCls = 'Default'.$this->suffix;
            $this->attach(new $routeCls());
        }
    }

/**
 * encoder
 *
 * @param object URL object
 * @return object URL object 
**/    
    public function encode(ixarUrl $url)
    {
        // try each route in turn, break on first positive response
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
}
?>
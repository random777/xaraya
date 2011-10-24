<?php
sys::import('xaraya.mapper2.router');
class BaseRoute extends xarRouter implements ixarRoute
{
    protected $name = 'base';
    protected $dispatcher = 'default';
    
    public function __construct()
    {
    
    }
    
    public function decode(ixarUrl $url)
    {
        return $url;
    }
    
    public function encode(ixarUrl $url)
    {
        return $url;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    public function loadRoute($module)
    {
        // try loading module specific route
        // assumes the path and class names conform to this pattern
        if (!$this->isAttached($module)) {
            try {
                sys::import("modules.{$module}.routes.{$this->name}");
                $routeClass = ucfirst($module).'Route'.ucfirst($this->name);
                if (class_exists($routeClass) && is_subclass_of($routeClass, 'ixarRoute'))
                    $this->attach(new $routeClass());
            } catch (Exception $e) { }
        }
        return $this->isAttached($module);
    }
    
    public static function getFiles()
    {
    
    }
}
?>
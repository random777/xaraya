<?php
sys::import('xaraya.mapper2.router');
abstract class BaseRoute extends object implements ixarRoute
{
    protected $name = 'base';
    protected $dispatcher = 'default';
    
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
}
?>
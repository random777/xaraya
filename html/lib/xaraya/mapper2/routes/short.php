<?php
sys::import('xaraya.mapper2.routes.base');
class ShortRoute extends BaseRoute
{
    protected $name = 'short';
    protected $dispatcher = 'default';
    
    public function encode(ixarUrl $url)
    {
        return $url;
    }
    
    public function decode(ixarUrl $url)
    {
        // @todo finish this
        return $url;        
    }
}
?>

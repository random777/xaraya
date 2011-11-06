<?php
class BaseDispatcher extends Object implements ixarResponse
{
    public $name = 'base';
    
    protected $output;
    
    public function dispatch(ixarRequest $request)
    {
    
    }
    
    public function getOutput()
    {
        return $this->output;
    }
}
?>
<?php
sys::import('xaraya.mapper2.dispatchers.base');
class ObjectDispatcher extends BaseDispatcher implements ixarResponse
{
    public $name = 'object';
    
    public function dispatch(ixarRequest $request)
    {
        if (!isset($request))
            $request = xarController::getRequest();
        if ($this->getOutput()) return;
        sys::import('xaraya.objects');
        $this->output = xarObject::guiMethod($request->getType(), $request->getFunction(), $request->getFunctionArgs());  
    }

}
?>
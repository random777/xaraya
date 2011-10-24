<?php
sys::import('xaraya.mapper2.dispatchers.base');
class DefaultDispatcher extends BaseDispatcher implements ixarResponse
{
    public $name = 'default';
    
    public function dispatch(ixarRequest $request)
    {
        if (!isset($request))
            $request = xarController::getRequest();
        if ($this->getOutput()) return;
        $this->output = xarMod::guiFunc($request->getModule(), $request->getType(), $request->getFunc(), $request->getArgs());    
    }

}
?>
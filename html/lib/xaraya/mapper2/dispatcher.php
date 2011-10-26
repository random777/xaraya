<?php
class xarDispatcher extends Object
{
    public function dispatch(ixarRequest $request=null)
    {
        if (!isset($request))
            $request = xarController::getRequest();
        // if this request was dispatched just return the output
        if ($request->isDispatched())
            return $request->getResponse()->getOutput();
        $_GET = array_merge($_GET, $request->getArgs());
        // get the dispatcher the decoding route uses 
        $dispatcher = $request->getDispatcher();
        sys::import('xaraya.mapper2.dispatchers.'.$dispatcher);
        $responseClass = ucfirst($dispatcher).'Dispatcher';
        $response = new $responseClass();
        $response->dispatch($request);
        $request->setResponse($response);
        $request->setDispatched(true);
    }
}
?>
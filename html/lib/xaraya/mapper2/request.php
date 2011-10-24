<?php
sys::import('xaraya.mapper2.url');
class xarRequest extends xarUrl2 implements ixarRequest
{
    protected $dispatcher;
    protected $dispatched;
    protected $response;

    public function __construct()
    {
        parent::__construct(xarServer::getCurrentURL());
        // $this->setUrl(xarServer::getCurrentURL());
    }

    public function setResponse(ixarResponse $response)
    {
        $this->response = $response;
    }
    
    public function getResponse()
    {
        return $this->response;
    }    
    
    public function setFunction($function)
    {
        return $this->setFunc($function);
    }

    public function isDispatched()
    {
        return (bool) $this->dispatched;
    }
    
    public function setDispatched($flag)
    {
        $this->dispatched = (bool) $flag;
    }
    
    public function getInfo()
    {            
        return array($this->getModule(), $this->getType(), $this->getFunc());
    }
    
    public function getFunction()
    {
        return $this->getFunc();
    }
    
    public function getFunctionArgs()
    {
        return $this->getArgs();
    }
}
?>
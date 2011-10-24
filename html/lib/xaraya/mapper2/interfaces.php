<?php
interface ixarController 
{
    function getRequest();
    function getDispatcher();
    function getRouter();
    function normalizeRequest();
    function dispatch();
}

interface ixarUrl 
{
    function __construct();
    function getUrl();
    function getPath();
    function getPathString();
    function getQuery();
    function getQueryString();
    function getModule();
    function getType();
    function getFunc();
    function getArgs();
    function getArgsString();
    function getParams();
    function getXmlUrls();
    function getTarget();
    function getEntryPoint();
    
    function setUrl($url);
    function setPath($path);
    function setQuery($query);
    function setModule($module);
    function setType($type);
    function setFunc($func);
    function setArgs($args);
    function setParams($module=null, $type=null, $func=null, $args=array(), $xmlurls=null, $target=null, $entrypoint=null);
    function setXmlUrls($flag);
    function setTarget($target);
    function setEntryPoint($entrypoint);
}

interface ixarRequest extends ixarUrl
{
    function setResponse(ixarResponse $response);
    function getResponse();
    function getFunction();
    function getFunctionArgs();
    function setFunction($func);
}

interface ixarRouter
{
    function __construct($routes);
    function encode(ixarUrl $url);
    function decode(ixarUrl $url);
    function attach(ixarRoute $route);
    function detach(ixarRoute $route);
    function isAttached($name);
    function getRoute($name);
}

interface ixarRoute
{
    function encode(ixarUrl $url);
    function decode(ixarUrl $url);
}

interface ixarDispatcher
{
    function dispatch(ixarRequest $request);
}

interface ixarResponse
{
    function dispatch(ixarRequest $request);
    function getOutput();
}
?>
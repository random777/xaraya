<?php
function base_admin_mapper(Array $args=array())
{
    if (!xarSecurityCheck('AdminBase')) return;

    if (!xarVarFetch('tab', 'pre:trim:lower:str:1:',
        $data['tab'], 'decode', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'pre:trim:lower:enum:update',
        $phase, null, XARVAR_NOT_REQUIRED)) return;
 
    sys::import('xaraya.mapper2.controller');
    sys::import('xaraya.mapper2.url');
    $routes = xarController::getRouter()->getFiles();
    switch ($data['tab']) {
        default:
            // index.php simulation
            //xarController2::getRequest(); 
            //xarController2::normalizeRequest(); 
            //xarController2::dispatch();  // can't call this here, recursion     
        break;
        case 'decode':
            if (!xarVarFetch('url', 'pre:trim:str:1:',
                $url, '', XARVAR_NOT_REQUIRED)) return;
            if (!empty($url)) {
                $decoded = xarController::getRouter()->decode(new xarUrl2($url));
                $data['decoded'] = $decoded->getParams();
                $data['decoded']['args'] = $decoded->getArgsString();
                $data['decoded']['route'] = $decoded->getDecoder();
                $data['decoded']['url'] = $decoded->getUrl();
            }
            $data['url'] = $url;
            $data['formaction'] = 'get';
        break;
        case 'encode':
            if (!xarVarFetch('encode_module', 'pre:trim:str:1:',
                $encode_module, null, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('encode_type', 'pre:trim:str:1:',
                $encode_type, null, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('encode_func', 'pre:trim:str:1:',
                $encode_func, null, XARVAR_NOT_REQUIRED)) return;           
            if (!xarVarFetch('encode_args', 'pre:trim:str:1:',
                $encode_args, null, XARVAR_NOT_REQUIRED)) return;  
            if (!xarVarFetch('encode_target', 'pre:trim:str:1:',
                $encode_target, null, XARVAR_NOT_REQUIRED)) return; 
            if (!xarVarFetch('encode_entrypoint', 'pre:trim:str:1:',
                $encode_entrypoint, null, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('encode_route', 'pre:trim:str:1:',
                $encode_route, 'default', XARVAR_NOT_REQUIRED)) return;
            if (!empty($encode_module)) {
                $encoded = xarController::getRouter()->getRoute($encode_route)->encode(new xarUrl2($encode_module, $encode_type, $encode_func, $encode_args, xarServer::$generateXMLURLs, $encode_target, $encode_entrypoint));
                $data['encoded'] = $encoded->getParams();
                $data['encoded']['url'] = $encoded->getUrl();
                $data['encoded']['route'] = $encode_route;
            }
            $data['encode_module'] = $encode_module;
            $data['encode_type'] = $encode_type;
            $data['encode_func'] = $encode_func;
            $data['encode_args'] = $encode_args;
            $data['encode_target'] = $encode_target;
            $data['encode_entrypoint'] = $encode_entrypoint;
            $data['encode_route'] = $encode_route;
            $data['formaction'] = 'get';
        break;
        case 'config':
            if ($phase == 'update') {
                if (!xarVarFetch('default_route', 'pre:trim:lower:str:1',
                    $default_route, 'default', XARVAR_NOT_REQUIRED)) return;
                if (!isset($routes[$default_route]))
                    $default_route = 'default';
                xarConfigVars::set(null, 'Site.Core.DefaultRoute', $default_route);
                xarController::redirect(xarModURL('base', 'admin', 'mapper', array('tab' => 'config')));
            }
            $data['default_route'] = xarConfigVars::get(null, 'Site.Core.DefaultRoute');
            $data['formaction'] = 'post';
        break;
    }

    foreach (array_keys($routes) as $route) 
        $data['routes'][$route] = array('id' => $route, 'name' => ucfirst($route));

    $data['tabs'] = array(
        'decode' => array('label' => 'Decoder', 'title' => 'Decode a URL'),
        'encode' => array('label' => 'Encoder', 'title' => 'Encode a URL'),
        'config' => array('label' => 'Config', 'title' => 'Mapper Configuration'),
    );
    
    return $data; 
    
}
?>
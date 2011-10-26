<?php
/**
 * URL Object
 * This object models a xaraya url 
 * This object gets passed to the router 
**/
class xarUrl2 extends Object implements ixarUrl
{
    // the url 
    protected $url;       

    // the url parts 
    protected $path = array();          // url path part
    protected $query = array();         // url query string
    protected $fragment = '';      // url fragment 
    
    // xarModURL parameters 
    protected $module;        // module name
    protected $type;          // function type
    protected $func;          // function name
    protected $args = array();          // function args
    protected $xmlurls = true;       // gen xml url 
    protected $target;        // fragment

    protected $entrypath;     // BaseURI
    protected $entrypoint;    // BaseModURL

    protected $decoder;
    protected $encoder;
    protected $dispatcher;

/**
 * constructor
 *
 * @params mixed, either
 * @param string $url         a url to be decoded
 * or params to be encoded 
 * @param string $module      name of module 
 * @param string $type        name of function type
 * @param string $func        name of function
 * @param array  $args        array of function arguments
 * @param bool   $xmlurls     flag to indicate use of &amp; vs &
 * @param string $target      url #fragment to append
 * @param string $entrypoint  todo: see checkme
 * @throws none
 * @return void
**/
    public function __construct()
    {
        if (func_num_args() == 1) {
            $this->setUrl(func_get_arg(0));
        } elseif (func_num_args() > 1) {
            list($module, $type, $func, $args, $xmlurls, $target, $entrypoint) = array_pad(func_get_args(), 7, null);
            $this->setParams($module, $type, $func, $args, $xmlurls, $target, $entrypoint);
        }
    }
    
    public function isDecoded()
    {
        return !empty($this->decoder);
    }

    public function setDecoder($decoder)
    {
        $this->decoder = $decoder;
    }
    
    public function getDecoder()
    {
        return $this->decoder;
    }
    
    public function isEncoded()
    {
        return !empty($this->encoder);
    }

    public function setEncoder($encoder)
    {
        $this->encoder = $encoder;
    }
    
    public function getEncoder()
    {
        return $this->encoder;
    }

    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

/**
 * URL Setters
**/
    public function setUrl($url)
    {
        if (empty($url)) return;
        // sanitize url - allows a-z0-9 $ - _ . + ! * ' ( ) , { } | \ \ ^ ~ [ ] ` > < # % " ; / ? : @ & = .
        $url = filter_var($url,  FILTER_SANITIZE_URL);
        // @checkme: allow relative urls to be decoded here ?
        // strip leading slash if we were given a relative path
        //if (substr($url, 0, 1) == '/') $url = substr($url, 1);
        // add the baseurl if this is a relative path 
        //if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED & FILTER_FLAG_HOST_REQUIRED))
        //    $url = xarServer::getBaseURL() . $url;
        // @checkme: or, just throw back urls without a scheme/hostname ?
        if (!filter_var($url, FILTER_VALIDATE_URL, 
            FILTER_FLAG_SCHEME_REQUIRED & FILTER_FLAG_HOST_REQUIRED)) return;
        // we're only interested in decoding local urls  
        $server = xarServer::getHost();
        if (!preg_match("!^https?://$server!", $url)) return;
        // ok, we have a local url, let's see if it's a xaraya url
        $urlparts = parse_url($url);
        
        if ($this->getEntryPath() || $this->getEntryPoint()) {
            // no path parts, not a xar url
            if (empty($urlparts['path'])) return;
            $entryparts = $this->getEntryPath().'/'.$this->getEntryPoint();
            // first part of path should match BaseURI/BaseModURL
            if (strpos($urlparts['path'], $entryparts) !== 0) return;
            $urlparts['path'] = substr($urlparts['path'], strlen($entryparts));
        }
        /*            
        // see if we have an entry point defined 
        if (!empty($this->entrypoint)) {
            // transform the path into an array 
            $urlparts['path'] = !empty($urlparts['path']) ? 
                array_map('trim', explode('/', trim($urlparts['path'], '/'))) : array();
            // if the first path part doesn't match, it's not a xaraya url 
            if (empty($urlparts['path'][0]) || $urlparts['path'][0] != $this->entrypoint) return;
            // the entry point is accounted for, remove it from the path
            array_shift($urlparts['path']);
        }
        */
        // if we're here, we're assuming this is a xaraya url, set the URL parts
        if (!empty($urlparts['path']))
            $this->setPath($urlparts['path']);
        if (!empty($urlparts['query']))
            $this->setQuery($urlparts['query']);
        // @checkme: not sure how useful fragment is here :-?
        if (!empty($urlparts['fragment']))
            $this->setFragment($urlparts['fragment']);
        // finally, set the url 
        $this->url = $url;        
    }

    public function setPath($path)
    {
        if (!is_array($path))
            $path = array_map('trim', explode('/', trim($path, '/')));
        $this->path = $path;
    }
    
    public function setQuery($query)
    {
        if (!is_array($query))
            parse_str(str_replace('&amp;', '&', $query), $query);
        $this->query = $query;
    }

/**
 * Param setters
**/

    public function setParams($module=null, $type=null, $func=null, $args=array(), $xmlurls=null, $target=null, $entrypoint=null)
    {
        $this->setModule($module);
        $this->setType($type);
        $this->setFunc($func);
        $this->setArgs($args);
        $this->setXmlUrls($xmlurls);
        $this->setTarget($target);
        $this->setEntryPoint($entrypoint);
    }
    
    public function setModule($module)
    {
        if (preg_match('/^[a-z][a-z_0-9]*$/', $module))
            $this->module = $module;
    }
    
    public function setType($type)
    {
        if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $type))
            $this->type = $type;
    }
    
    public function setFunc($func)
    {
        if (preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $func))
            $this->func = $func;
    }
    
    public function setArgs($args)
    {
        if (!is_array($args))
            parse_str(str_replace('&amp;', '&', $args), $args);
        $this->args = $args;
        //$this->args = array_merge_recursive($this->args, $args);
        //$this->args = $args;
    }
    
    public function setXmlUrls($xmlurls)
    {
        $this->xmlurls = $xmlurls;
    }
    
    public function setTarget($target)
    {
        if (is_string($target))
            $this->target = $target;
    }

    public function setEntryPath($entrypath=null)
    {
        if (is_string($entrypath)) {
            $this->entrypath = $entrypath;
        } else {
            $this->entrypath = xarController::getEntryPath();
        }
    }

    public function setEntryPoint($entrypoint=null)
    {
        if (is_string($entrypoint)) {
            $this->entrypoint = $entrypoint;
        } else {
            $this->entrypoint = xarController::getEntryPoint();
        }
    }

/**
 * URL Getters
**/

    public function getUrl()
    {
        $url = xarServer::getBaseURL();
        $path = $this->getPathString();
        if (!empty($path))
            $url .= $path;
        $query = $this->getQueryString();
        if (!empty($query))
            $url .= '?' . $query;
        $target = $this->getTarget();
        if (!empty($target))
            $url .= '#' . $target;
        return $this->url = $url;
    }

    public function getPath()
    {
        return $this->path;
    }
    
    public function getPathString()
    {
        $path = $this->path;
        // put the entrypoint back in the path 
        if ($this->getEntryPoint())
            array_unshift($path, $this->getEntryPoint());
        if (!empty($path))
            return implode('/', array_map('rawurlencode', $path));
        return '';
    }
    
    public function getQuery()
    {
        return $this->query;
    }
    
    public function getQueryString()
    {
        if (!empty($this->query))
            return http_build_query($this->query, '', $this->xmlurls ? '&amp;' : '&');
        return '';
    }
    
    public function getFragment()
    {
        if (!empty($this->fragment))
            return $this->fragment;
        return '';
    }

/**
 * Param Getters 
**/    

    public function getParams()
    {
        return array(
            'module' => $this->getModule(),
            'type' => $this->getType(),
            'func' => $this->getFunc(),
            'args' => $this->getArgs(),
            'xmlurls' => $this->getXmlUrls(),
            'target' => $this->getTarget(),
            'entrypoint' => $this->getEntryPoint(),
        );
    }

    public function getModule()
    {
        return $this->module;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getFunc()
    {
        return $this->func;
    }

    public function getArgs()
    {
        if (empty($this->args))
            return $this->args = array();
        return $this->args;
    }
    
    public function getArgsString()
    {
        if (!empty($this->args))
            return http_build_query($this->args, '', $this->xmlurls ? '&amp;' : '&');
        return '';    
    }

    public function getXmlUrls()
    {
        return $this->xmlurls;
    }

    public function getTarget()
    {
        return $this->target;
    }


    public function getEntryPath()
    {
        if (!isset($this->entrypath))
            return xarController::getEntryPath();
        return $this->entrypath;
    }

    public function getEntryPoint()
    {
        if (!isset($this->entrypoint))
            return xarController::getEntryPoint();
        return $this->entrypoint;
    }
        
}
?>
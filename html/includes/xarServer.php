<?php
/**
 * HTTP Protocol Server/Request/Response utilities
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage server
 * @author Marco Canini <marco@xaraya.com>
 */

/**
 * Initializes the HTTP Protocol Server/Request/Response utilities
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access protected
 * @global bool xarRequest_allowShortURLs
 * @global array xarRequest_defaultModule
 * @global array xarRequest_shortURLVariables
 * @param bool args['generateShortURLs']
 * @param string args['defaultModuleName']
 * @param string args['defaultModuleName']
 * @param string args['defaultModuleName']
 * @param integer whatElseIsGoingLoaded
 * @return bool true
 */

function xarSerReqRes_init(&$args, $whatElseIsGoingLoaded)
{
    $GLOBALS['xarServer_generateXMLURLs']       = $args['generateXMLURLs'];
    $GLOBALS['xarRequest_allowShortURLs']       = $args['enableShortURLsSupport'];
    $GLOBALS['xarRequest_defaultRequestInfo']   = array($args['defaultModuleName'],
                                                        $args['defaultModuleType'],
                                                        $args['defaultModuleFunction']);
    $GLOBALS['xarRequest_shortURLVariables']    = array();
    $GLOBALS['xarResponse_closeSession']        = $whatElseIsGoingLoaded & XARCORE_SYSTEM_SESSION;
    $GLOBALS['xarResponse_redirectCalled']      = false;

    // Register the ServerRequest event
    xarEvt_registerEvent('ServerRequest');

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarServer__shutdown_handler');
    return true;
}

// SERVER FUNCTIONS

/**
 * Shutdown handler for the xarServer subsystem
 *
 * @access private
 */
function xarServer__shutdown_handler()
{
    //xarLogMessage("xarServer shutdown handler");
}

/**
 * Gets a server variable
 *
 * Returns the value of $name server variable.
 * Accepted values for $name are exactly the ones described by the
 * {@link http://www.php.net/manual/en/reserved.variables.html#reserved.variables.server PHP manual}.
 * If the server variable doesn't exist void is returned.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @author Michel Dalle
 * @access public
 * @param name string the name of the variable
 * @return mixed value of the variable
 */
function xarServerGetVar($name)
{
    assert('version_compare("4.4.7",phpversion()) <= 0; /* The minimum PHP version supported by Xaraya is 4.4.7 */');
    if (isset($_SERVER[$name])) {
        return $_SERVER[$name];
    }
    if($name == 'PATH_INFO') return;
    
    if (isset($_ENV[$name])) {
        return $_ENV[$name];
    }

    if ($val = getenv($name)) {
        return $val;
    }
    return; // we found nothing here
}

/**
 * Get base URI for Xaraya
 *
 * @access public
 * @return string base URI for Xaraya
 * @todo remove whatever may come after the PHP script - TO BE CHECKED !
 * @todo See code comments.
 */
function xarServerGetBaseURI()
{
  // Allows overriding the Base URI from config.php
  // it can be used to configure Xaraya for mod_rewrite by
  // setting BaseURI = '' in config.php
  $BaseURI =  xarCore_getSystemVar('BaseURI',true);
  if( isset( $BaseURI) )
  {
    // If BaseURI set, just use it
    return  $BaseURI;
  }
  // Otherwise build it dynamically


    // Get the name of this URI
    $path = xarServerGetVar('REQUEST_URI');

    //if ((empty($path)) ||
    //    (substr($path, -1, 1) == '/')) {
    //what's wrong with a path (cfr. Indexes index.php, mod_rewrite etc.) ?
    if (empty($path)) {
        // REQUEST_URI was empty or pointed to a path
        // adapted patch from Chris van de Steeg for IIS
        // Try SCRIPT_NAME
        $path = xarServerGetVar('SCRIPT_NAME');
        if (empty($path)) {
            // No luck there either
            // Try looking at PATH_INFO
            $path = xarServerGetVar('PATH_INFO');
        }
    }

    $path = preg_replace('/[#\?].*/', '', $path);

    $path = preg_replace('/\.php\/.*$/', '', $path);
    if (substr($path, -1, 1) == '/') {
        $path .= 'dummy';
    }
    $path = dirname($path);

    //FIXME: This is VERY slow!!
    if (preg_match('!^[/\\\]*$!', $path)) {
        $path = '';
    }

    return $path;
}

/**
 * Gets the host name
 *
 * Returns the server host name fetched from HTTP headers when possible.
 * The host name is in the canonical form (host + : + port) when the port is different than 80.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string HTTP host name
 */

function xarServerGetHost()
{
    $server = xarServerGetVar('HTTP_HOST');
    if (empty($server)) {
        // HTTP_HOST is reliable only for HTTP 1.1
        $server = xarServerGetVar('SERVER_NAME');
        $port = xarServerGetVar('SERVER_PORT');
        if ($port != '80') $server .= ":$port";
    }
    return $server;
}

/**
 * Gets the current protocol
 *
 * Returns the HTTP protocol used by current connection, it could be 'http' or 'https'.
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string current HTTP protocol
 */
function xarServerGetProtocol()
{
    if (function_exists('xarConfigGetVar')){
        if (xarConfigGetVar('Site.Core.EnableSecureServer') == true){
            if (preg_match('/^http:/', $_SERVER['REQUEST_URI'])) {
                return 'http';
            }
            $HTTPS = xarServerGetVar('HTTPS');
            // IIS seems to set HTTPS = off for some reason
            return (!empty($HTTPS) && $HTTPS != 'off') ? 'https' : 'http';
        } else {
            return 'http';
        }
    } else {
        return 'http';
    }
}

/**
 * get base URL for Xaraya
 *
 * @access public
 * @return string base URL for Xaraya
 */
function xarServerGetBaseURL()
{
    static $baseurl = null;

    if (isset($baseurl))  return $baseurl;

    $server = xarServerGetHost();
    $protocol = xarServerGetProtocol();
    $path = xarServerGetBaseURI();

    $baseurl = "$protocol://$server$path/";
    return $baseurl;
}

/**
 * Create a query string from an array.
 * @todo For PHP5, this can be handled by http_build_query()
 */
function xarServer__array2query($args, $prefix = '')
{
    $query = '';
    if ($prefix == '') {
        // First time around the loop, i.e. the top level, handling
        // the main parameter names.
        foreach ($args as $k=>$v) {
            if (is_array($v)) {
                // Recursively walk the array tree to as many levels as necessary
                // e.g. ...&foo[bar][dee][doo]=value&...
                $query .= xarServer__array2query($v, (!empty($query) ? '&' : '') . $k);
            } elseif (isset($v)) {
                // TODO: rather than rawurlencode, use a xar function to encode
                $query .= (!empty($query) ? '&' : '') . rawurlencode($k) . '=' . rawurlencode($v);
            }
        }
    } else {
        // Subsequent times around the loop, handling parameter key values.
        // If the keys are sequential numeric, then leave out the keys.
        $i = 0;
        foreach($args as $key => $arg) {
            if ($key >= 0 && $key == $i) {
                // The keys are in the sequence 0, 1, 2, so use an empty key.
                $encoded_key = '';
                $i += 1;
            } else {
                // The numeric sequence has been broken, so include all the key values now.
                $encoded_key = rawurlencode($key);
                $i = -1;
            }

            if (is_array($arg)) {
                $query .= xarServer__array2query($arg, $prefix . '['.$encoded_key.']');
            } else {
                $query .= $prefix . '['.$encoded_key.']' . '=' . rawurlencode($arg);
            }
        }
    }
    return $query;
}

/**
 * Get current URL (and optionally add/replace some parameters)
 *
 * @access public
 * @param args array additional parameters to be added to/replaced in the URL (e.g. theme, ...)
 * @param generateXMLURL boolean over-ride Server default setting for generating XML URLs (true/false/NULL)
 * @param fragment string add a fragment identifier to the URL for pointing to an anchor
 * @return string current URL
 * @todo cfr. BaseURI() for other possible ways, or try PHP_SELF
 */
function xarServerGetCurrentURL($args = array(), $generateXMLURL = NULL, $fragment = NULL)
{
    static $callback_isset = NULL;

    $server = xarServerGetHost();
    $protocol = xarServerGetProtocol();
    $baseurl = "$protocol://$server";

    // get current URI
    $request = xarServerGetVar('REQUEST_URI');

    if (empty($request)) {
        // adapted patch from Chris van de Steeg for IIS
        $scriptname = xarServerGetVar('SCRIPT_NAME');
        $pathinfo = xarServerGetVar('PATH_INFO');
        if ($pathinfo == $scriptname) $pathinfo = '';
        if (!empty($scriptname)) {
            $request = $scriptname . $pathinfo;
            $querystring = xarServerGetVar('QUERY_STRING');
            if (!empty($querystring)) $request .= '?' . $querystring;
        } else {
            $request = '/';
        }
    }

    // Remove any magic quotes nonsense.
    // TODO: move this to xarServerGetVar() so it is dealt with at the lowest level.
    if (get_magic_quotes_gpc()) $request = stripslashes($request);

    // If the request has a '#' fragment, then remove it now, since the server
    // is never meant to see the fragment.
    if (strpos($request, '#') > 0) $request = substr($request, 0, strpos($request, '#'));

    // add optional parameters
    if (count($args) > 0) {
        // Parse the current URL, ensure we are not parsing a relative url
        $parsed_url = parse_url($baseurl.$request);

        // Parse the query string into an array of parameters.
        $query = (!empty($parsed_url['query']) ? $parsed_url['query'] : '');
        // CHECKME: parse_str() can return unset variables as NULL, and these will be
        // stripped out later. However, for xarVarFetch(), 'foo=' is quivalent to ''.
        // Is this behaviour desirable?
        parse_str($query, $parsed_query);

        foreach ($args as $k => $v) {
            if (is_array($v)) {
                // The parameter value is an array.
                // Replace the existing parameter in the URL outright.
                $parsed_query[$k] = $v;
            } elseif (preg_match('/\[\]/', $k)) {
                // Key points to an array element.
                // Evaluate the element and change just that key.
                // - If the value is NULL, then remove that element.
                // - If the key is not set, then merge that element into the array.
                @parse_str(urlencode($k) . '=' . $v, $array_param);
                if (!empty($array_param) && isset($v)) {
                    // Merge in this element.
                    // TODO: check for duplicate values - we don't want to add an element
                    // value that is already there.
                    $parsed_query = array_merge_recursive($parsed_query, $array_param);
                }
            } else {
                // Value is a scalar.
                // Do a straight replace. If the value is NULL then these
                // will be trimmed later.
                $parsed_query[$k] = $v;
            }
        }

        // Iteratively remove all NULL elements.
        if (!isset($callback_isset)) $callback_isset = create_function('$x', 'return !is_null($x);');
        $parsed_query = array_filter($parsed_query, $callback_isset);

        // TODO: convert the array back into a query string and insert back into the URL.
        $new_query = xarServer__array2query($parsed_query);

        // Strip off any existing query parameters.
        $request = preg_replace('/[?].*/', '', $request);

        // Add on the new query parameters (everything after the first '?').
        if (!empty($new_query)) $request .= '?' . $new_query;
    }

    if (!isset($generateXMLURL)) {
        $generateXMLURL = $GLOBALS['xarServer_generateXMLURLs'];
    }

    if (isset($fragment)) {
        $request .= '#' . urlencode($fragment);
    }

    if ($generateXMLURL) {
        $request = htmlspecialchars($request);
    }

    return $baseurl . $request;
}

// REQUEST FUNCTIONS

/**
 * Get request variable
 *
 * @access public
 * @global xarRequest_shortURLVariables array
 * @global xarRequest_allowshortURLs bool
 * @param name string
 * @param allowOnlyMethod string
 * @return mixed
 * @todo change order (POST normally overrides GET)
 * @todo have a look at raw post data options (xmlhttp postings)
 */
function xarRequestGetVar($name, $allowOnlyMethod = NULL)
{
    if ($allowOnlyMethod == 'GET') {
        // Short URLs variables override GET variables
        if ($GLOBALS['xarRequest_allowShortURLs'] && isset($GLOBALS['xarRequest_shortURLVariables'][$name])) {
            $value = $GLOBALS['xarRequest_shortURLVariables'][$name];
        // Then check in $_GET
        } elseif (isset($_GET[$name])) {
            $value = $_GET[$name];
        // Try to fallback to $HTTP_GET_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_GET_VARS'][$name])) {
            $value = $GLOBALS['HTTP_GET_VARS'][$name];
        // Nothing found, return void
        } else {
            return;
        }
        $method = $allowOnlyMethod;
    } elseif ($allowOnlyMethod == 'POST') {
        // First check in $_POST
        if (isset($_POST[$name])) {
            $value = $_POST[$name];
        // Try to fallback to $HTTP_POST_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_POST_VARS'][$name])) {
            $value = $GLOBALS['HTTP_POST_VARS'][$name];
        // Nothing found, return void
        } else {
            return;
        }
        $method = $allowOnlyMethod;
    } else {

        // Short URLs variables override GET and POST variables
        if ($GLOBALS['xarRequest_allowShortURLs'] && isset($GLOBALS['xarRequest_shortURLVariables'][$name])) {
            $value = $GLOBALS['xarRequest_shortURLVariables'][$name];
            $method = 'GET';
        // Then check in $_POST
        } elseif (isset($_POST[$name])) {
            $value = $_POST[$name];
            $method = 'POST';
        // Try to fallback to $HTTP_POST_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_POST_VARS'][$name])) {
            $value = $GLOBALS['HTTP_POST_VARS'][$name];
            $method = 'POST';
        // Then check in $_GET
        } elseif (isset($_GET[$name])) {
            $value = $_GET[$name];
            $method = 'GET';
        // Try to fallback to $HTTP_GET_VARS for older php versions
        } elseif (isset($GLOBALS['HTTP_GET_VARS'][$name])) {
            $value = $GLOBALS['HTTP_GET_VARS'][$name];
            $method = 'GET';
        // Nothing found, return void
        } else {
            return;
        }
    }

    $value = xarMLS_convertFromInput($value, $method);

    if (get_magic_quotes_gpc()) {
        xarVar_stripSlashes($value);
    }

    return $value;
}

/**
 * Gets request info for current page.
 *
 * Example of short URL support :
 *
 * index.php/<module>/<something translated in xaruserapi.php of that module>, or
 * index.php/<module>/admin/<something translated in xaradminapi.php>
 *
 * We rely on function <module>_<type>_decode_shorturl() to translate PATH_INFO
 * into something the module can work with for the input variables.
 * On output, the short URLs are generated by <module>_<type>_encode_shorturl(),
 * that is called automatically by xarModURL().
 *
 * Short URLs are enabled/disabled globally based on a base configuration
 * setting, and can be disabled per module via its admin configuration
 *
 * TODO: evaluate and improve this, obviously :-)
 * + check security impact of people combining PATH_INFO with func/type param
 *
 * @author Marco Canini, Michel Dalle
 * @access public
 * @global xarRequest_allowShortURLs bool
 * @global xarRequest_defaultModule array
 * @return array requested module, type and func
 * @todo <marco> Do we want to use xarVarCleanUntrusted here?
 * @todo <mikespub> Allow user select start page
 * @todo <marco> Do we need to do a preg_match on $params[1] here?
 * @todo <mikespub> you mean for upper-case Admin, or to support other funcs than user and admin someday ?
 * @todo <marco> Investigate this aliases thing before to integrate and promote it!
 */
function xarRequestGetInfo()
{
    static $requestInfo = NULL;
    static $loopHole = NULL;
    if (is_array($requestInfo)) {
        return $requestInfo;
    } elseif (is_array($loopHole)) {
    // FIXME: Security checks in functions used by decode_shorturl cause infinite loops,
    //        because they request the current module too at the moment - unnecessary ?
        xarLogMessage('Avoiding loop in xarRequestGetInfo()');
        return $loopHole;
    }

    // Get variables
    xarVarFetch('module', 'regexp:/^[a-z][a-z_0-9]*$/', $modName, NULL, XARVAR_NOT_REQUIRED);
    xarVarFetch('type', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $modType, 'user');
    xarVarFetch('func', "regexp:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/:", $funcName, 'main');

    //CGI-PHP support patch (inc subdirectory install)
    $path = xarServerGetVar('PATH_INFO');
    $scriptname=xarServerGetVar('SCRIPT_NAME');

    if (strlen(xarCore_getSystemVar('BaseModURL',true))==0) {
        $basemodurl='index.php';
    } else {
        $basemodurl=xarCore_getSystemVar('BaseModURL',true);
    }
    if ($path == '') $path = substr(xarServerGetVar('REDIRECT_URL'),strlen(xarCore_getSystemVar('BaseURI',true)));
    $basefix = str_replace($path,'',xarServerGetVar('SCRIPT_NAME')); //Fix for win-apache
    if ($GLOBALS['xarRequest_allowShortURLs'] && empty($modName) && $path != ''
    //end CGI-PHP support patch
        // IIS fix and win-apache
        && $path != xarServerGetVar('SCRIPT_NAME') && $basefix !=$basemodurl) {
    /*if ($GLOBALS['xarRequest_allowShortURLs'] && empty($modName) && ($path = xarServerGetVar('PATH_INFO')) != ''
        // IIS fix and win-apache
        && $path != xarServerGetVar('SCRIPT_NAME')) {
    */
        /*
        Note: we need to match anything that might be used as module params here too ! (without compromising security)
        preg_match_all('|/([a-z0-9_ .+-]+)|i', $path, $matches);
        
        The original regular expression prevents the use of titles, even when properly encoded, 
        as parts of a short-url path -- because it wouldn't not permit many characters that would
        in titles, such as parens, commas, or apostrophes.  Since a similiar "security" check is not
        done to normal URL params, I've changed this to a more flexable regex at the other extreme.
        
        This also happens to address Bug 2927 
        
        TODO: The security of doing this should be examined by someone more familiar with why this works
        as a security check in the first place.
        */
        //Bug 6270 - restoring prior preg match as the last change caused problems in urls with ?, at least 
        //preg_match_all('|/([^/?#]+)((?=[\?#]).*)?|i', $path, $matches);
        preg_match_all('|/([^/]+)|i', $path, $matches);

        $params = $matches[1];
        if (count($params) > 0) {
            $modName = $params[0];
            // if the second part is not admin, it's user by default
            if (isset($params[1]) && $params[1] == 'admin') $modType = 'admin';
            else $modType = 'user';
            // Check if this is an alias for some other module
            $modName = xarRequest__resolveModuleAlias($modName);
            // Call the appropriate decode_shorturl function
            if (xarModIsAvailable($modName) && xarModGetVar($modName, 'SupportShortURLs') && xarModAPILoad($modName, $modType)) {
                $loopHole = array($modName,$modType,$funcName);
            // don't throw exception on missing file or function anymore
                $res = xarModAPIFunc($modName, $modType, 'decode_shorturl', $params, 0);
                if (isset($res) && is_array($res)) {
                    list($funcName, $args) = $res;
                    if (!empty($funcName)) { // bingo
                        // Forward decoded args to xarRequestGetVar
                        if (isset($args) && is_array($args)) {
                            $args['module'] = $modName;
                            $args['type'] = $modType;
                            $args['func'] = $funcName;
                            xarRequest__setShortURLVars($args);
                        } else {
                            xarRequest__setShortURLVars(array('module' => $modName,
                            'type' => $modType,
                            'func' => $funcName));
                        }
                    }
                }
                $loopHole = NULL;
            }
            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
                // If exceptionId is MODULE_FUNCTION_NOT_EXIST there's no problem,
                // this exception means that the module does not support short urls
                // for this $modType.
                // If exceptionId is MODULE_FILE_NOT_EXIST there's no problem too,
                // this exception means that the module does not have the $modType API.

                // IMPORTANT: As this is exactly the same construct as in xarModUrl and that was
                // causing a lot of exceptions to be hidden, i commented this one out as well
                // but i haven't been able to trace exception hiding back to this line. If it behaves
                // wrong, and is still needed uncomment it (MrB)
                //xarErrorFree();

                // <mikespub> see above :)
            }
        }
    }

    if (!empty($modName)) {
        // Check if this is an alias for some other module
        $modName = xarRequest__resolveModuleAlias($modName);
        // Cache values into info static var
        $requestInfo = array($modName, $modType, $funcName);
    } else {
        // If $modName is still empty we use the default module/type/func to be loaded in that such case
        $requestInfo = $GLOBALS['xarRequest_defaultRequestInfo'];
    }

    return $requestInfo;
}

/**
 * Check to see if this is a local referral
 *
 * @access public
 * @return bool true if locally referred, false if not
 */
function xarRequestIsLocalReferer()
{
    $server = xarServerGetHost();
    $referer = xarServerGetVar('HTTP_REFERER');

    if (!empty($referer) && preg_match("!^https?://$server(:\d+|)/!", $referer)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Set Short URL Variables
 *
 * @access public
 * @global xarRequest_shortURLVariables array
 * @param array vars array
 */
function xarRequest__setShortURLVars($vars)
{
    $GLOBALS['xarRequest_shortURLVariables'] = $vars;
}

/**
 * Checks if a module name is an alias for some other module
 *
 * @access private
 * @param aliasModName name of the module
 * @return string containing the module name
 * @throws BAD_PARAM
 */
function xarRequest__resolveModuleAlias($aliasModName)
{
    $aliasesMap = xarConfigGetVar('System.ModuleAliases');
    //$aliasesMap = $GLOBALS['xarRequest_aliasesMap'];

    if (!empty($aliasesMap[$aliasModName])) {
        return $aliasesMap[$aliasModName];
    } else {
        return $aliasModName;
    }
}

// RESPONSE FUNCTIONS

/**
 * Carry out a redirect
 *
 * @access public
 * @global xarResponse_redirectCalled bool
 * @param redirectURL string the URL to redirect to
 * @param httpResponse int optional response to send with 
 *         redirect default 302, valid options 301,302,303,307
 */
function xarResponseRedirect($redirectURL, $httpResponse=NULL)
{
    // First checks if there's a pending exception, if so does not redirect browser
    if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return false;

    if (headers_sent() == true) return false;

    // MrB: We only do this for pn Legacy, consider removing it
    $GLOBALS['xarResponse_redirectCalled'] = true;

    // Remove &amp; entites to prevent redirect breakage
    $redirectURL = str_replace('&amp;', '&', $redirectURL);

    if (substr($redirectURL, 0, 4) != 'http') {
        // Removing leading slashes from redirect url
        $redirectURL = preg_replace('!^/*!', '', $redirectURL);

        // Get base URL
        $baseurl = xarServerGetBaseURL();

        $redirectURL = $baseurl.$redirectURL;
    }

    if (preg_match('/IIS/', xarServerGetVar('SERVER_SOFTWARE')) && preg_match('/CGI/', xarServerGetVar('GATEWAY_INTERFACE')) ) {
      $header = "Refresh: 0; URL=$redirectURL";
    } else {
      $header = "Location: $redirectURL";
    }// if

    if (!preg_match('/^301|302|303|307/', $httpResponse)) {
      $httpResponse = 302;
    }

     // Start all over again
    header($header, TRUE, $httpResponse);
    exit();
}

/**
 * Checks if a redirection header has already been sent.
 *
 * @author Marco Canini
 * @access public
 * @global xarResponse_redirectCalled bool
 * @return bool
 */
function xarResponseIsRedirected()
{
    return $GLOBALS['xarResponse_redirectCalled'];
}

?>

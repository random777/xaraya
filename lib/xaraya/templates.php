<?php
/**
 * BlockLayout Template Engine
 *
 * @package blocklayout
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Paul Rosania <paul@xaraya.com>
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @author Andy Varganov <andyv@xaraya.com>
 * @author Jason Judge
 **/

/**
 * Exceptions for this subsystem
 *
**/
class BLValidationException extends ValidationExceptions
{
    protected $message = 'A blocklayout tag or attribute construct was invalid, see the tag documentation for the correct syntax';
}

class BLException extends xarExceptions
{
    protected $message = 'Unknown blocklayout exception (TODO)';
}


/**
 * Initializes the BlockLayout Template Engine
 *
 * @access protected
 * @global string xarTpl_themesBaseDir
 * @global string xarTpl_defaultThemeName
 * @global string xarTpl_doctype
 * @global string xarTpl_JavaScript
 * @param  array  $args                  Elements: themesBaseDir, defaultThemeName, enableTemplateCaching
 * @throws DirectoryNotFoundException, FileNotFoundException, ConfigurationException
 * @return bool true
**/
function xarTpl_init(&$args)
{
    $GLOBALS['xarTpl_themesBaseDir']   = $args['themesBaseDirectory'];
    $GLOBALS['xarTpl_defaultThemeDir'] = $args['defaultThemeDir'];
    $GLOBALS['xarTpl_generateXMLURLs'] = $args['generateXMLURLs'];
    // set when page template root tag is compiled (dtd attribute value)
    $GLOBALS['xarTpl_doctype'] = '';

    if (!xarTplSetThemeDir($args['defaultThemeDir'])) {
        // If there is no theme, there is no page template, we dont know what to do now.
        throw new DirectoryNotFoundException(array($args['defaultThemeDir'],"xarTpl_init: Nonexistent theme directory #(1)"));
    }
    if (!xarTplSetPageTemplateName('default')) {
        // If there is no page template, we can't show anything
        throw new FileNotFoundException('default.xt',"xarTpl_init: Nonexistent #(1) page in theme directory '". xarTplGetThemeDir() ."'");
    }

    // @todo is the core define still needed now?
    sys::import('xaraya.caching.template');
    xarTemplateCache::init(sys::varpath() . XARCORE_TPL_CACHEDIR, $args['enableTemplatesCaching']);

    // This is wrong here as well, but it's better at least than in modules.php
    sys::import('xaraya.themes');
    return true;
}

/**
 * Get theme name for the theme in use.
 *
 * @access public
 * @global xarTpl_themeName string
 * @return string themename
 * @todo   the method_exists / function_exists should be in the xaraya scope, so we can deal with it's oddities
 */
function xarTplGetThemeName()
{
    if(isset($GLOBALS['xarTpl_themeName'])) return  $GLOBALS['xarTpl_themeName'];
    // If it is not set, set it return the default theme.
    // TODO: PHP 5.0/5.1 DO NOT AGREE ON method_exists / is_callable
    if (method_exists('xarModVars','Get')) {
        $defaultTheme = xarModVars::get('themes', 'default');
        if (!empty($defaultTheme)) xarTplSetThemeName($defaultTheme);
    }
    assert('isset($GLOBALS["xarTpl_themeName"]; /* Themename could not be set properly */');
    return $GLOBALS['xarTpl_themeName'];
}

/**
 * Set theme name
 *
 * @access public
 * @global string xarTpl_themesBaseDir
 * @global string xarTpl_themeName
 * @param  string $themeName Themename to set
 * @return bool
 */
function xarTplSetThemeName($themeName)
{
    assert('$themeName != "" && $themeName{0} != "/"');
    if (!file_exists($GLOBALS['xarTpl_themesBaseDir'].'/'.$themeName)) {
        return false;
    }

    xarTpl__SetThemeNameAndDir($themeName);
    return true;
}

/**
 * Set theme dir
 *
 * @access public
 * @global string xarTpl_themesBaseDir
 * @global string xarTpl_themeDir
 * @param  string themeDir
 * @return bool
 */
function xarTplSetThemeDir($themeDir)
{
    assert('$themeDir != "" && $themeDir{0} != "/"');
    if (!file_exists($GLOBALS['xarTpl_themesBaseDir'].'/'.$themeDir)) {
        return false;
    }

    xarTpl__SetThemeNameAndDir($themeDir);
    return true;
}

/**
 * Private helper function for the xarTplSetThemeName and xarTplSetThemeDir
 *
 * @access private
 * @param  string $name Name of the theme
 * @todo theme name and dir are not required to be identical
 * @return void
 */
function xarTpl__SetThemeNameAndDir($name)
{
    // dir and name are still required to be the same
    $GLOBALS['xarTpl_themeName'] = $name;
    $GLOBALS['xarTpl_themeDir']  = $GLOBALS['xarTpl_themesBaseDir'] . '/' . $name;
}

/**
 * Get theme directory
 *
 * @access public
 * @global string xarTpl_themeDir
 * @return sring  Theme directory
 */
function xarTplGetThemeDir($theme=null)
{
    if (isset($theme) && is_dir("themes/" . $theme)) return "themes/" . $theme;
    return $GLOBALS['xarTpl_themeDir'];
}

/**
 * Get page template name
 *
 * @access public
 * @global string xarTpl_pageTemplateName
 * @return string page template name
 */
function xarTplGetPageTemplateName()
{
    return $GLOBALS['xarTpl_pageTemplateName'];
}

/**
 * Set page template name
 *
 * @access public
 * @global xarTpl_pageTemplateName string
 * @param  string $templateName Name of the page template
 * @return bool
 */
function xarTplSetPageTemplateName($templateName)
{
    assert('$templateName != ""');
    if (!file_exists(xarTplGetThemeDir() . "/pages/$templateName.xt")) {
        return false;
    }
    $GLOBALS['xarTpl_pageTemplateName'] = $templateName;
    return true;
}

/**
 * Get doctype declared by page template
 *
 * @access public
 * @global string xarTpl_doctype
 * @return string doctype identifier
 */
function xarTplGetDoctype()
{
    return $GLOBALS['xarTpl_doctype'];
}

/**
 * Set doctype declared by page template
 *
 * @access public
 * @global string xarTpl_doctype
 * @param  string $doctypeName Identifier string of the doctype
 * @return bool
 */
function xarTplSetDoctype($doctypeName)
{
    assert('is_string($doctypeName); /* doctype should always be a string */');
    $GLOBALS['xarTpl_doctype'] = $doctypeName;
    return true;
}

/**
 * Set page title
 *
 * @access public
 * @global string xarTpl_pageTitle
 * @param  string $title
 * @param  string $module
 * @todo   this needs to be moved into the templating domain somehow
 * @return bool
 */
function xarTplSetPageTitle($title = NULL, $module = NULL)
{
    xarLogMessage("TPL: Setting pagetitle to $title");
    // TODO: PHP 5.0/5.1 DO NOT AGREE ON method_exists / is_callable!!!
    if (!method_exists('xarModVars','Get')){
        $GLOBALS['xarTpl_pageTitle'] = $title;
    } else {
        $order      = xarModVars::get('themes', 'SiteTitleOrder');
        $separator  = xarModVars::get('themes', 'SiteTitleSeparator');
        if (empty($module)) {
            // FIXME: the ucwords is layout stuff which doesn't belong here
            $module = ucwords(xarMod::getDisplayName());
        }
        switch(strtolower($order)) {
            case 'default':
            default:
                $GLOBALS['xarTpl_pageTitle'] = xarModVars::get('themes', 'SiteName') . $separator . $module . $separator . $title;
            break;
            case 'sp':
                $GLOBALS['xarTpl_pageTitle'] = xarModVars::get('themes', 'SiteName') . $separator . $title;
            break;
            case 'mps':
                $GLOBALS['xarTpl_pageTitle'] = $module . $separator . $title . $separator .  xarModVars::get('themes', 'SiteName');
            break;
            case 'pms':
                $GLOBALS['xarTpl_pageTitle'] = $title . $separator .  $module . $separator . xarModVars::get('themes', 'SiteName');
            break;
            case 'to':
                $GLOBALS['xarTpl_pageTitle'] = $title;
            break;
        }
    }
    return true;
}

/**
 * Get page title
 *
 * @access public
 * @return string
 */
function xarTplGetPageTitle()
{
    if(isset($GLOBALS['xarTpl_pageTitle'])) {
        return $GLOBALS['xarTpl_pageTitle'];
    }
    return '';
}


/**
 * Add JavaScript code or links to template output
 *
 * @access public
 * @global array  xarTpl_JavaScript
 * @param  string $position         Either 'head' or 'body'
 * @param  string $type             Either 'src' or 'code'
 * @param  string $data             pathname or raw JavaScript
 * @param  string $index            optional (unique key and/or ordering)
 * @return bool
 */
function xarTplAddJavaScript($position, $type, $data, $index = '')
{
    if (empty($position) || empty($type) || empty($data)) {return;}

    //Do lazy initialization of the array. There are instances of the logging system
    //where we need to use this function before the Template System was initialized
    //Maybe this can be used with a new shutdown event (not based on the
    // php's register_shutdown_function) as at that time it's already too late to be able
    // to log anything
    if (!isset($GLOBALS['xarTpl_JavaScript'])) {
        // Initialise the JavaScript array. Start with placeholders for the head and body.
        $GLOBALS['xarTpl_JavaScript'] = array('head'=>array(), 'body'=>array());
    }

    if (empty($index)) {
        $GLOBALS['xarTpl_JavaScript'][$position][] = array('type'=>$type, 'data'=>$data);
    } else {
        $GLOBALS['xarTpl_JavaScript'][$position][$index] = array('type'=>$type, 'data'=>$data);
    }

    return true;
}

/**
 * Get JavaScript code or links cached for template output
 *
 * @access public
 * @global array  xarTpl_JavaScript
 * @param  string $position
 * @param  string $index
 * @return array
 */
function xarTplGetJavaScript($position = '', $index = '')
{
    if (empty($position)) {return $GLOBALS['xarTpl_JavaScript'];}
    if (!isset($GLOBALS['xarTpl_JavaScript'][$position])) {return;}
    if (empty($index)) {return $GLOBALS['xarTpl_JavaScript'][$position];}
    if (!isset($GLOBALS['xarTpl_JavaScript'][$position][$index])) {return;}
    return $GLOBALS['xarTpl_JavaScript'][$position][$index];
}

/**
 * Turns module output into a template.
 *
 * @access public
 * @param  string $modName      the module name
 * @param  string $modType      user|admin
 * @param  string $funcName     module function to template
 * @param  array  $tplData      arguments for the template
 * @param  string $templateName string the specific template to call
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTplModule($modName, $modType, $funcName, $tplData = array(), $templateName = NULL)
{
    if (!empty($templateName)) {
        $templateName = xarVarPrepForOS($templateName);
    }

    // Basename of module template is apitype-functioname
    $tplBase        = "$modType-$funcName";

    // Get the right source filename
    $sourceFileName = xarTpl__GetSourceFileName($modName, $tplBase, $templateName);

    // Common data for BL
    $tplData['_bl_module_name'] = $modName;
    $tplData['_bl_module_type'] = $modType;
    $tplData['_bl_module_func'] = $funcName;
    $tpl = (object) null;
    $tpl->pageTitle = xarTplGetPageTitle();
    $tplData['tpl'] = $tpl;

    // TODO: make this work different, for example:
    // 1. Only create a link somewhere on the page, when clicked opens a page with the variables on that page
    // 2. Create a page in the themes module with an interface
    // 3. Use 1. to link to 2.
    // TODO: PHP 5.0/5.1 DO NOT AGREE ON method_exists / is_callable
    if (method_exists('xarModVars','Get')){
        $var_dump = xarModVars::get('themes', 'var_dump');
        if ($var_dump == true){
            if (function_exists('var_export')) {
                $pre = var_export($tplData, true);
                echo "<pre>$pre</pre>";
            } else {
                echo '<pre>',var_dump($tplData),'</pre>';
            }
        }
    }

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Renders a block content through a block template.
 *
 * @access public
 * @param  string $modName   the module name
 * @param  string $blockType the block type (xar_block_types.type)
 * @param  array  $tplData   arguments for the template
 * @param  string $tplName   the specific template to call
 * @param  string $tplBase   the base name of the template (defaults to $blockType)
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTplBlock($modName, $blockType, $tplData = array(), $tplName = NULL, $tplBase = NULL)
{
    if (!empty($tplName)) {
        $tplName = xarVarPrepForOS($tplName);
    }

    // Basename of block can be overridden
    $templateBase   = xarVarPrepForOS(empty($tplBase) ? $blockType : $tplBase);

    // Get the right source filename
    $sourceFileName = xarTpl__GetSourceFileName($modName, $templateBase, $tplName, 'blocks');

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Renders a DD element (object or property) through a template.
 *
 * @access private
 * @param  string $modName      the module name owning the object/property, with fall-back to dynamicdata
 * @param  string $ddName       the name of the object/property type, or some other name specified in BL tag or API call
 * @param  string $tplType      the template type to render
 *                              properties: ( showoutput(default)|showinput|showhidden|validation|label )
 *                              objects   : ( showdisplay(default)|showview|showform|showlist )
 * @param  array  $tplData      arguments for the template
 * @param  string $tplBase      the template type can be overridden too ( unused )
 * @return string xarTpl__executeFromFile($sourceFileName, $tplData)
 */
function xarTpl__DDElement($modName, $ddName, $tplType, $tplData, $tplBase,$elements)
{
    $tplType = xarVarPrepForOS($tplType);

    // Template type for the property can be overridden too (currently unused)
    $templateBase   = xarVarPrepForOS(empty($tplBase) ? $tplType : $tplBase);

    // Get the right source filename
    $sourceFileName = xarTpl__GetSourceFileName($modName, $templateBase, $ddName, $elements);

    // Property fall-back to default template in the module the property belongs to
    if ((empty($sourceFileName) || !file_exists($sourceFileName)) &&
        $elements == 'properties') {
        $fallbackmodule = DataPropertyMaster::getProperty(array('type' => $ddName))->tplmodule;
        if ($fallbackmodule != $modName) {
            $sourceFileName = xarTpl__GetSourceFileName($fallbackmodule, $templateBase, $ddName, $elements);
        }
    }

    // Final fall-back to default template in dynamicdata for both objects and properties
    if ((empty($sourceFileName) || !file_exists($sourceFileName)) &&
        $modName != 'dynamicdata') {
        $sourceFileName = xarTpl__GetSourceFileName('dynamicdata', $templateBase, $ddName, $elements);
    }

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}
function xarTplProperty($modName, $propertyName, $tplType = 'showoutput', $tplData = array(), $tplBase = NULL)
{
    return xarTpl__DDElement($modName,$propertyName,$tplType,$tplData,$tplBase,'properties');
}
function xarTplObject($modName, $objectName, $tplType = 'showdisplay', $tplData = array(), $tplBase = NULL)
{
    return xarTpl__DDElement($modName,$objectName,$tplType,$tplData,$tplBase,'objects');
}

/**
 * Get theme template image replacement for a module's image
 *
 * Example:
 * $my_module_image = xarTplGetImage('button1.png');
 * $other_module_image = xarTplGetImage('set1/info.png','modules');
 *
 * Correct practices:
 *
 * 1. module developers should never rely on theme's images, but instead
 * provide their own artwork inside modules/<module>/xarimages/ directory
 * and use this function to reference their images in the module's functions.
 * Such reference can then be safely passed to the module template.
 *
 * 2. theme developers should always check for the modules images
 * (at least for all core modules) and provide replacements images
 * inside the corresponding themes/<theme>/modules/<module>/images/
 * directories as necessary
 *
 * Note : your module is still responsible for taking care that "images"
 *        don't contain nasty stuff. Filter as appropriate when using
 *        this function to generate image URLs...
 *
 * @access  public
 * @param   string $modImage the module image url relative to xarimages/
 * @param   string $modName  the module to check for the image <optional>
 * @return  string $theme    image url if it exists or module image url if not, or NULL if neither found
 *
 * @todo    provide examples, improve description, add functionality
 * @todo    provide XML URL override flag
 * @todo    XML encode absolute URIs too?
*/
function xarTplGetImage($modImage, $modName = NULL)
{
    // return absolute URIs and URLs "as is"
    if (empty($modImage) || substr($modImage,0,1) == '/' || preg_match('/^https?\:\/\//',$modImage)) {
        return $modImage;
    }

    // obtain current module name if not specified
    // FIXME: make a fallback for weird requests
    if(!isset($modName)){
        list($modName) = xarRequest::getInfo();
    }

    // get module directory (could be different from module name)
    if(function_exists('xarMod_getBaseInfo')) {
        $modBaseInfo = xarMod::getBaseInfo($modName);
        if (!isset($modBaseInfo)) return; // throw back
        $modOsDir = $modBaseInfo['osdirectory'];
    } else {
        // Assume dir = modname
        $modOsDir = $modName;
    }

    // relative url to the current module's image
    $moduleImage = 'modules/'.$modOsDir.'/xarimages/'.$modImage;

    // obtain current theme directory
    $themedir = xarTplGetThemeDir();

    // relative url to the replacement image in current theme
    $themeImage = $themedir . '/modules/'.$modOsDir.'/images/'.$modImage;

    $return = NULL;

    // check if replacement image exists in the theme
    if (file_exists($themeImage)) {
        // image found, return its path in the theme
        $return = $themeImage;
    } elseif (file_exists($moduleImage)) {
        // image found, return it's path in the module
        $return = $moduleImage;
    }

    // Return as an XML URL if required.
    // This will generally have little effect, but is here for
    // completeness to support alternative types of URL.
    if (isset($return) && $GLOBALS['xarTpl_generateXMLURLs']) {
        $return = htmlspecialchars($return);
    }

    return $return;
}

/**
 * Creates pager information with no assumptions to output format.
 *
 * @since 2003/10/09
 * @access public
 * @param integer $startNum     start item
 * @param integer $total        total number of items present
 * @param integer $itemsPerPage number of links to display (default=10)
 * @param integer $blockOptions number of pages to display at once (default=10) or array of advanced options
 *
 * @todo  Move this somewhere else, preferably transparent and a widget (which might be mutually exclusive)
 */
function xarTplPagerInfo($currentItem, $total, $itemsPerPage = 10, $blockOptions = 10)
{
    // Default block options.
    if (is_numeric($blockOptions)) {
        $pageBlockSize = $blockOptions;
    }

    if (is_array($blockOptions)) {
        if (!empty($blockOptions['blocksize'])) {$blockSize = $blockOptions['blocksize'];}
        if (!empty($blockOptions['firstitem'])) {$firstItem = $blockOptions['firstitem'];}
        if (!empty($blockOptions['firstpage'])) {$firstPage = $blockOptions['firstpage'];}
        if (!empty($blockOptions['urltemplate'])) {$urltemplate = $blockOptions['urltemplate'];}
        if (!empty($blockOptions['urlitemmatch'])) {
            $urlItemMatch = $blockOptions['urlitemmatch'];
        } else {
            $urlItemMatch = '%%';
        }
        $urlItemMatchEnc = rawurlencode($urlItemMatch);
    }

    // Default values.
    if (empty($blockSize) || $blockSize < 1) {$blockSize = 10;}
    if (empty($firstItem)) {$firstItem = 1;}
    if (empty($firstPage)) {$firstPage = 1;}

    // The last item may be offset if the first item is not 1.
    $lastItem = ($total + $firstItem - 1);

    // Sanity check on arguments.
    if ($itemsPerPage < 1) {$itemsPerPage = 10;}
    if ($currentItem < $firstItem) {$currentItem = $firstItem;}
    if ($currentItem > $lastItem) {$currentItem = $lastItem;}

    // If this request was the same as the last one, then return the cached pager details.
    // TODO: is there a better way of caching for each unique request?
    $request = md5($currentItem . ':' . $lastItem . ':' . $itemsPerPage . ':' . serialize($blockOptions));
    if (xarCore::getCached('Pager.core', 'request') == $request) {
        return xarCore::getCached('Pager.core', 'details');
    }

    // Record the values in this request.
    xarCore::setCached('Pager.core', 'request', $request);

    // Max number of items in a block of pages.
    $itemsPerBlock = ($blockSize * $itemsPerPage);

    // Get the start and end items of the page block containing the current item.
    $blockFirstItem = $currentItem - (($currentItem - $firstItem) % $itemsPerBlock);
    $blockLastItem = $blockFirstItem + $itemsPerBlock - 1;
    if ($blockLastItem > $lastItem) {$blockLastItem = $lastItem;}

    // Current/Last page numbers.
    $currentPage = (int)ceil(($currentItem-$firstItem+1) / $itemsPerPage) + $firstPage - 1;
    $totalPages = (int)ceil($total / $itemsPerPage);

    // First/Current/Last block numbers
    $firstBlock = 1;
    $currentBlock = (int)ceil(($currentItem-$firstItem+1) / $itemsPerBlock);
    $totalBlocks = (int)ceil($total / $itemsPerBlock);

    // Get start and end items of the current page.
    $pageFirstItem = $currentItem - (($currentItem-$firstItem) % $itemsPerPage);
    $pageLastItem = $pageFirstItem + $itemsPerPage - 1;
    if ($pageLastItem > $lastItem) {$pageLastItem = $lastItem;}

    // Initialise data array.
    $data = array();

    $data['middleitems'] = array();
    $data['middleurls'] = array();
    $pageNum = (int)ceil(($blockFirstItem - $firstItem + 1) / $itemsPerPage) + $firstPage - 1;
    for ($i = $blockFirstItem; $i <= $blockLastItem; $i += $itemsPerPage) {
        if (!empty($urltemplate)) {
            $data['middleurls'][$pageNum] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $i, $urltemplate);
        }
        $data['middleitems'][$pageNum] = $i;
        $data['middleitemsfrom'][$pageNum] = $i;
        $data['middleitemsto'][$pageNum] = $i + $itemsPerPage - 1;
        if ($data['middleitemsto'][$pageNum] > $total) {$data['middleitemsto'][$pageNum] = $total;}
        $pageNum += 1;
    }

    $data['currentitem'] = $currentItem;
    $data['totalitems'] = $total;
    $data['lastitem'] = $lastItem;
    $data['firstitem'] = $firstItem;
    $data['itemsperpage'] = $itemsPerPage;
    $data['itemsperblock'] = $itemsPerBlock;
    $data['pagesperblock'] = $blockSize;

    $data['currentblock'] = $currentBlock;
    $data['totalblocks'] = $totalBlocks;
    $data['firstblock'] = $firstBlock;
    $data['lastblock'] = $totalBlocks;
    $data['blockfirstitem'] = $blockFirstItem;
    $data['blocklastitem'] = $blockLastItem;

    $data['currentpage'] = $currentPage;
    $data['currentpagenum'] = $currentPage;
    $data['totalpages'] = $totalPages;
    $data['pagefirstitem'] = $pageFirstItem;
    $data['pagelastitem'] = $pageLastItem;

    // These two are item numbers. The naming is historical.
    $data['firstpage'] = $firstItem;
    $data['lastpage'] = $lastItem - (($lastItem-$firstItem) % $itemsPerPage);

    if (!empty($urltemplate)) {
        // These two links are for first and last pages.
        $data['firsturl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['firstpage'], $urltemplate);
        $data['lasturl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['lastpage'], $urltemplate);
    }

    $data['firstpagenum'] = $firstPage;
    $data['lastpagenum'] = ($totalPages + $firstPage - 1);

    // Data for previous page of items.
    if ($currentPage > $firstPage) {
        $data['prevpageitems'] = $itemsPerPage;
        $data['prevpage'] = ($pageFirstItem - $itemsPerPage);
        if (!empty($urltemplate)) {
            $data['prevpageurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['prevpage'], $urltemplate);
        }
    } else {
        $data['prevpageitems'] = 0;
    }

    // Data for next page of items.
    if ($pageLastItem < $lastItem) {
        $nextPageLastItem = ($pageLastItem + $itemsPerPage);
        if ($nextPageLastItem > $lastItem) {$nextPageLastItem = $lastItem;}
        $data['nextpageitems'] = ($nextPageLastItem - $pageLastItem);
        $data['nextpage'] = ($pageLastItem + 1);
        if (!empty($urltemplate)) {
            $data['nextpageurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['nextpage'], $urltemplate);
        }
    } else {
        $data['nextpageitems'] = 0;
    }

    // Data for previous block of pages.
    if ($currentBlock > $firstBlock) {
        $data['prevblockpages'] = $blockSize;
        $data['prevblock'] = ($blockFirstItem - $itemsPerBlock);
        if (!empty($urltemplate)) {
            $data['prevblockurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['prevblock'], $urltemplate);
        }
    } else {
        $data['prevblockpages'] = 0;
    }

    // Data for next block of pages.
    if ($currentBlock < $totalBlocks) {
        $nextBlockLastItem = ($blockLastItem + $itemsPerBlock);
        if ($nextBlockLastItem > $lastItem) {$nextBlockLastItem = $lastItem;}
        $data['nextblockpages'] = ceil(($nextBlockLastItem - $blockLastItem) / $itemsPerPage);
        $data['nextblock'] = ($blockLastItem + 1);
        if (!empty($urltemplate)) {
            $data['nextblockurl'] = str_replace(array($urlItemMatch,$urlItemMatchEnc), $data['nextblock'], $urltemplate);
        }
    } else {
        $data['nextblockpages'] = 0;
    }

    // Cache all the pager details.
    xarCore::setCached('Pager.core', 'details', $data);

    return $data;
}

/**
 * Equivalent of pnHTML()'s Pager function (to get rid of pnHTML calls in modules while waiting for widgets)
 *
 * @since 1.13 - 2003/10/09
 * @access public
 * @param integer $startnum     start item
 * @param integer $total        total number of items present
 * @param string  $urltemplate  template for url, will replace '%%' with item number
 * @param integer $perpage      number of links to display (default=10)
 * @param integer $blockOptions number of pages to display at once (default=10) or array of advanced options
 * @param integer $template     alternative template name within base/user (default 'pager')
 *
 * @todo Move this somewhere else
 */
function xarTplGetPager($startNum, $total, $urltemplate, $itemsPerPage = 10, $blockOptions = array(), $template = 'default')
{
    // Quick check to ensure that we have work to do
    if ($total <= $itemsPerPage) {return '';}

    // Number of pages in a page block - support older numeric 'pages per block'.
    if (is_numeric($blockOptions)) {
        $blockOptions = array('blocksize' => $blockOptions);
    }

    // Pass the url template into the pager calculator.
    $blockOptions['urltemplate'] = $urltemplate;

    // Get the pager information.
    $data = xarTplPagerInfo($startNum, $total, $itemsPerPage, $blockOptions);

    // Nothing to do: perhaps there is an error in the parameters?
    if (empty($data)) {return '';}

    // Couple of cached values used in various pages.
    // It is unclear what these values are supposed to be used for.
    if ($data['prevblockpages'] > 0) {
        xarCore::setCached('Pager.first', 'leftarrow', $data['firsturl']);
    }

    // Links for next block of pages.
    if ($data['nextblockpages'] > 0) {
        xarCore::setCached('Pager.last', 'rightarrow', $data['lasturl']);
    }

    return trim(xarTplModule('base', 'pager', $template, $data));
}

/**
 * Execute a pre-compiled template string with the supplied template variables
 *
 * @access public
 * @param  string $templateCode pre-compiled template code (see xarTplCompileString)
 * @param  array  $tplData      template variables
 * @return string filled-in template
 * @todo   this is not MLS-aware (never was)
 * @todo   how 'special' should the 'memory' file be, namewise?
 */
function xarTplString($templateCode, &$tplData)
{
    // Pretend as if the cache is fully operational and we'll be fine
    xarTemplateCache::saveEntry('memory',$templateCode);

    // Execute the cache file
    sys::import('blocklayout.template.compiled');
    $compiled = new CompiledTemplate(xarTemplateCache::cacheFile('memory'));
    $out = $compiled->execute($tplData);
    return $out;
}

/**
 * Execute a specific template file with the supplied template variables
 *
 * @access public
 * @param  string $fileName location of the template file
 * @param  array  $tplData  template variables
 * @return string filled-in template
 */
function xarTplFile($fileName, &$tplData)
{
    return xarTpl__executeFromFile($fileName, $tplData);
}

/**
 * Compile a template string for storage and/or later use in xarTplString()
 * Note : your module should always support the possibility of re-compiling
 *        template strings e.g. after an upgrade, so you should store both
 *        the original template and the compiled version if necessary
 *
 * @access public
 * @param  string $templateSource template source
 * @return string compiled template
 * @todo    subclass xarBLCompiler, it has this method
 */
function xarTplCompileString($templateSource)
{
    sys::import('blocklayout.compiler');
    $blCompiler = xarBLCompiler::instance();
    return $blCompiler->compileString($templateSource);
}

/**
 * Renders a page template.
 *
 * @access protected
 * @param  string $mainModuleOutput       the module output
 * @param  string $pageTemplate           the page template to use (without extension .xt)
 * @return string
 *
 * @todo Needs a rewrite, i.e. finalisation of tplOrder scenario
 */
function xarTpl_renderPage($mainModuleOutput, $pageTemplate = NULL)
{
    if (empty($pageTemplate)) $pageTemplate = xarTplGetPageTemplateName();

    // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
    $pageTemplate = xarVarPrepForOS($pageTemplate);
    $sourceFileName = xarTplGetThemeDir() . "/pages/$pageTemplate.xt";

    $tpl = (object) null; // Create an object to hold the 'specials'
    $tpl->pageTitle = xarTplGetPageTitle();

    $tplData = array(
        'tpl'                      => $tpl,
        '_bl_mainModuleOutput'     => $mainModuleOutput,
    );

    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Render a block box
 *
 * @access protected
 * @param  array  $blockInfo  Information on the block
 * @param  string $templateName string
 * @return bool xarTpl__executeFromFile($sourceFileName, $blockInfo)
 *
 * @todo the search logic for the templates can perhaps use the private function?
 * @todo fallback to some internal block box template?
 */
function xarTpl_renderBlockBox($blockInfo, $templateName = NULL)
{
    // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
    $templateName = xarVarPrepForOS($templateName);
    $themeDir = xarTplGetThemeDir();

    if (!empty($templateName) && file_exists("$themeDir/blocks/$templateName.xt")) {
        $sourceFileName = "$themeDir/blocks/$templateName.xt";
    } else {
        // We must fall back to the default, as the template passed in could be the group
        // name, allowing an optional template to be utilised.
        $templateName = 'default';
        $sourceFileName = "$themeDir/blocks/default.xt";
    }
    return xarTpl__executeFromFile($sourceFileName, $blockInfo);
}

/**
 * Include a subtemplate from the theme space
 *
 * @access protected
 * @param  string $templateName Basically handler function for <xar:template type="theme".../>
 * @param  array  $tplData      template variables
 * @return string
 */
function xarTpl_includeThemeTemplate($templateName, $tplData)
{
    // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = xarTplGetThemeDir() ."/includes/$templateName.xt";
    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

/**
 * Include a subtemplate from the module space
 *
 * @access protected
 * @param  string $modName      name of the module from which to include the template
 * @param  string $templateName Basically handler function for <xar:template type="module".../>
 * @param  array  $tplData      template variables
 * @return string
 */
function xarTpl_includeModuleTemplate($modName, $templateName, $tplData)
{
    // FIXME: can we trust templatename here? and eliminate the dependency with xarVar?
    $templateName = xarVarPrepForOS($templateName);
    $sourceFileName = xarTplGetThemeDir() . "/modules/$modName/includes/$templateName.xt";
    if (!file_exists($sourceFileName)) {
        $sourceFileName = "modules/$modName/xartemplates/includes/$templateName.xd";
    }
    return xarTpl__executeFromFile($sourceFileName, $tplData);
}

// PRIVATE FUNCTIONS

/**
 * Execute template from file
 *
 * @access private
 * @param  string $sourceFileName       From which file do we want to execute?
 * @param  array  $tplData              Template variables
 * @param  string $tplType              'module' or 'page'
 * @return string generated output from the file
 * @todo  insert log warning when double entry in cachekeys occurs? (race condition)
 * @todo  make the checking whether templatecode is set more robust (related to templated exception handling)
 * @todo  subclass xarBLCompiler?
 */
function xarTpl__executeFromFile($sourceFileName, $tplData, $tplType = 'module')
{
    assert('is_array($tplData); /* Template data should always be passed in as array */');

    // Load translations for the template
    xarMLSLoadTranslations($sourceFileName);

    xarLogMessage("Using template : $sourceFileName");
    $templateCode = null;
    // Determine if we need to compile this template
    if (xarTemplateCache::isDirty($sourceFileName)) {
        // Get an instance of SourceTemplate
        sys::import('blocklayout.template.source');
        $srcTemplate = new SourceTemplate($sourceFileName);

        // Compile it
        // @todo return a CompiledTemplate object here?
        $templateCode = $srcTemplate->compile();

        // Save the entry in templatecache (if active)
        xarTemplateCache::saveEntry($sourceFileName,$templateCode);
    }

    // Execute either the compiled template, or the code determined
    // @todo get rid of the cachedFileName usage
    $cachedFileName = xarTemplateCache::cacheFile($sourceFileName);

    // Execute the compiled template from the cache file
    // @todo the tplType should be irrelevant
    sys::import('blocklayout.template.compiled');
    $compiled = new CompiledTemplate($cachedFileName,$sourceFileName,$tplType);
    $output = $compiled->execute($tplData);
    return $output;
}

/**
 * Determine the template sourcefile to use
 *
 * Based on the module, the basename for the template
 * a possible overribe and a subpart and the active
 * theme, determine the template source we should use and loads
 * the appropriate translations based on the outcome.
 *
 * @param  string $modName      Module name doing the request
 * @param  string $tplBase      The base name for the template
 * @param  string $templateName The name for the template to use if any
 * @param  string $tplSubPart   A subpart ('' or 'blocks' or 'properties')
 * @return string
 *
 * @todo do we need to load the translations here or a bit later? (here:easy, later: better abstraction)
 */
function xarTpl__getSourceFileName($modName,$tplBase, $templateName = NULL, $tplSubPart = '')
{
    if(function_exists('xarMod_getBaseInfo')) {
        if(!($modBaseInfo = xarMod::getBaseInfo($modName))) return;
        $modOsDir = $modBaseInfo['osdirectory'];
    } elseif(!empty($modName)) {
        $modOsDir = $modName;
    }

    // For modules: {tplBase} = {modType}-{funcName}
    // For blocks : {tplBase} = {blockType} or overridden value
    // For props  : {tplBase} = {propertyName} or overridden value

    // Template search order:
    // 1. {theme}/modules/{module}/{tplBase}-{templateName}.xt
    // 2. modules/{module}/xartemplates/{tplBase}-{templateName}.xd
    // 3. {theme}/modules/{module}/{tplBase}.xt
    // 4. modules/{module}/xartemplates/{tplBase}.xd
    // 5. {theme}/modules/{module}/{templateName}.xt (-syntax)
    // 6. modules/{module}/xartemplates/{templateName}.xd (-syntax)
    // 7. complain (later on)

    $tplThemesDir = xarTplGetThemeDir();
    $tplBaseDir   = "modules/$modOsDir";
    $use_internal = false;
    unset($sourceFileName);

    xarLogMessage("TPL: 1. $tplThemesDir/$tplBaseDir/$tplSubPart/$tplBase-$templateName.xt");
    xarLogMessage("TPL: 2. $tplBaseDir/xartemplates/$tplSubPart/$tplBase-$templateName.xd");
    xarLogMessage("TPL: 3. $tplThemesDir/$tplBaseDir/$tplSubPart/$tplBase.xt");
    xarLogMessage("TPL: 4. $tplBaseDir/xartemplates/$tplSubPart/$tplBase.xd");

    $canTemplateName = strtr($templateName, "-", "/");
    $canonical = ($canTemplateName == $templateName) ? false : true;

    if(!empty($templateName) &&
        file_exists($sourceFileName = "$tplThemesDir/$tplBaseDir/$tplSubPart/$tplBase-$templateName.xt")) {
        $tplBase .= "-$templateName";
    } elseif(!empty($templateName) &&
        file_exists($sourceFileName = "$tplBaseDir/xartemplates/$tplSubPart/$tplBase-$templateName.xd")) {
        $use_internal = true;
        $tplBase .= "-$templateName";
    } elseif(
        file_exists($sourceFileName = "$tplThemesDir/$tplBaseDir/$tplSubPart/$tplBase.xt")) {
        ;
    } elseif(
        file_exists($sourceFileName = "$tplBaseDir/xartemplates/$tplSubPart/$tplBase.xd")) {
        $use_internal = true;
    } elseif($canonical &&
        file_exists($sourceFileName = "$tplThemesDir/$tplBaseDir/$tplSubPart/$canTemplateName.xt")) {
    } elseif($canonical &&
        file_exists($sourceFileName = "$tplBaseDir/xartemplates/$canTemplateName.xd")) {
        $use_internal = true;
    } else {
        // CHECKME: should we do something here ? At the moment, translations still get loaded,
        //          the (invalid) $sourceFileName gets passed back to xarTpl*, and we'll get
        //          an exception when it's passed to xarTpl__executeFromFile().
        //          We probably don't want to throw an exception here, but we might return
        //          now, or have some final fall-back template in base (resp. DD for properties)
    }
    // Subpart may have been empty,
    $sourceFileName = str_replace('//','/',$sourceFileName);
    // assert('isset($sourceFileName); /* The source file for the template has no value in xarTplModule */');

    // Load the appropriate translations
    if($use_internal) {
        $domain  = XARMLS_DNTYPE_MODULE; $instance= $modName;
        $context = rtrim("modules:templates/$tplSubPart",'/');
    } else {
        $domain = XARMLS_DNTYPE_THEME; $instance = $GLOBALS['xarTpl_themeName'];
        $context = rtrim("themes:modules/$modName/$tplSubPart",'/');
    }

    return $sourceFileName;
}


/**
 * Output template
 *
 * @access private
 * @param  string $sourceFileName
 * @param  string $tplOutput
 * @return void
 *
 * @todo Rethink this function, it contains hardcoded xhtml
 */
function xarTpl_outputTemplate($sourceFileName, &$tplOutput)
{
    // flag used to determine if the header content has been found.
    static $isHeaderContent;
    if(!isset($isHeaderContent))
        $isHeaderContent = false;

    $finalTemplate ='';
    if(xarTpl_outputTemplateFilenames()) {
        $outputStartComment = true;
        if($isHeaderContent === false) {
            if($isHeaderContent = xarTpl_modifyHeaderContent($sourceFileName, $tplOutput))
                $outputStartComment = false;
        }
        // optionally show template filenames if start comment has not already
        // been added as part of a header determination.
        if($outputStartComment === true)
            $finalTemplate .= "<!-- start: " . $sourceFileName . " -->\n";
        $finalTemplate .= $tplOutput;
        $finalTemplate .= "<!-- end: " . $sourceFileName . " -->\n";
    } else {
        $finalTemplate .= $tplOutput;
    }
    return $finalTemplate;
}

/**
 * Output template filenames
 *
 * @access private
 * @global int xarTpl_showTemplateFilenames
 * @return int value of xarTpl_showTemplateFilenames (0 or 1)
 *
 * @todo Check whether the check for xarModVars::get is needed
 * @todo Rethink this function
 */
function xarTpl_outputTemplateFilenames()
{
    if (!isset($GLOBALS['xarTpl_showTemplateFilenames'])) {
        // Default to not showing it
        $GLOBALS['xarTpl_showTemplateFilenames'] = 0;
        // CHECKME: not sure if this is needed, e.g. during installation
        // TODO: PHP 5.0/5.1 DO NOT AGREE ON method_exists / is_callable
        if (method_exists('xarModVars','Get')){
            $showtemplates = xarModVars::get('themes', 'ShowTemplates');
            if (!empty($showtemplates)) {
                $GLOBALS['xarTpl_showTemplateFilenames'] = 1;
            }
        }
    }
    return $GLOBALS['xarTpl_showTemplateFilenames'];
}

/**
 * Modify header content
 *
 * Attempt to determine if $tplOutput contains header content and if
 * so append a start comment after the first matched header tag
 * found.
 *
 * @access private
 * @param  string $sourceFileName
 * @param  string $tplOutput
 * @return bool found header content
 *
 * @todo it is possible that the first regex <!DOCTYPE[^>].*]> is too
 *       greedy in more complex xml documents and others.
 * @todo The doctype of the output belongs in a template somewhere (probably the xar:blocklayout tag, as an attribute
 */
function xarTpl_modifyHeaderContent($sourceFileName, &$tplOutput)
{
    $foundHeaderContent = false;

    // $headerTagsRegexes is an array of string regexes to match tags that could
    // be sent as part of a header. Important: the order here should be inside out
    // as the first regex that matches will have a start comment appended.
    // fixes bugs: #1427, #1190, #603
    // - Comments that precede <!doctype... cause ie6 not to sniff the doctype
    //   correctly.
    // - xml parsers dont like comments that precede xml output.
    // At this time attempting to match <!doctype... and <?xml version... tags.
    // This is about the best we can do now, until we process xar documents with an xml parser and actually 'parse'
    // the document.
    $headerTagRegexes = array('<!DOCTYPE[^>].*]>',// eg. <!DOCTYPE doc [<!ATTLIST e9 attr CDATA "default">]>
                              '<!DOCTYPE[^>]*>',// eg. <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
                              '<\?xml\s+version[^>]*\?>');// eg. <?xml version="1.0"? > // remove space between qmark and gt

    foreach($headerTagRegexes as $headerTagRegex) {
        if(preg_match("/$headerTagRegex/smix", $tplOutput, $matchedHeaderTag)) {
            // FIXME: the next line assumes that we are not in a comment already, no way of knowing that,
            // keep the functionality for now, but dont change more than necessary (see bug #3559)
            // $startComment = '<!-- start(output actually commenced before header(s)): ' . $sourceFileName . ' -->';
            $startComment ='';
            // replace matched tag with an appended start comment tag in the first match
            // in the template output $tplOutput
            $tplOutput = preg_replace("/$headerTagRegex/smix", $matchedHeaderTag[0] . $startComment, $tplOutput, 1);
            // dont want start comment to be sent below as it has already been added.
            $foundHeaderContent = true;
            break;
        }
    }
    return $foundHeaderContent;
}

?>

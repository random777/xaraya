<?php
/**
 * Register all css template tags
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * register all css template tags
 *
 * @author Andy Varganov AndyV_at_Xaraya_dot_Com
 * @param none
 * @returns bool
 */
function themes_cssapi_registercsstags($args)
{
    sys::import('blocklayout.template.tags');
    // just resetting default tags here, nothing else
    
    // unregister all - just in case they got corrupted or fiddled with via gui
    try {
        xarTemplateTag::unregister('additional-styles');
        xarTemplateTag::unregister('style');
    } catch (BLException $e) {
        // Apparently they werent registered, good :-)
    }

    // use in theme to render all extra styles tags
   $tag = new xarTemplateTag( 'themes', 'additional-styles', array(), 'themes_cssapi_delivercss');
   $tag->register();

    // Register the tag which is used to include style information
    $cssTagAttributes = array(  new xarTemplateAttribute('file' , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('scope'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('method'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('module'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('type'     , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('alternate', XAR_TPL_OPTIONAL | XAR_TPL_BOOLEAN),
                                new xarTemplateAttribute('media'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('title'    , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('source'   , XAR_TPL_OPTIONAL | XAR_TPL_STRING),
                                new xarTemplateAttribute('condition', XAR_TPL_OPTIONAL | XAR_TPL_STRING));
    $tag = new xarTemplateTag('themes', 'style', $cssTagAttributes ,'themes_cssapi_registercss');
    $tag->register();
   // return
    return true;
}

?>

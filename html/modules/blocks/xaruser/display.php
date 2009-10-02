<?php
/**
 * Display a single block in the module space
 *
 * @package modules
 * @subpackage blocks
 * @return array 
 * @author Marcel van der Boom <mrb@hsdev.com>
 * @param  string $name name of the block to render
 * @param  string $bid block ID of the block to render
 * @param  string $template semicolon-separated pair of outer/inner block template (optional)
 */
function blocks_user_display($args)
{
    extract($args);
    
    if (!xarVarFetch('name', 'str:1:255', $name, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('bid', 'int:1:', $bid, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('template', 'strlist:;,:pre:trim:lower:ftoken', $template, NULL, XARVAR_NOT_REQUIRED)) {return;}

    // The remainder of the defined <xar:block /> attributes are not supported here.
    // Is there a use case for serving anonymous blocks over the wire?

    $blockinfo = array();

    // Use $name or $bid to fetch block, but we prefer $bid
    if ($bid != NULL) {
        $blockinfo = xarModAPIFunc('blocks','user','get', array('bid' => $bid));
    }
    if ($name != NULL && empty($blockinfo)) {
        $blockinfo = xarModAPIFunc('blocks','user','get', array('name' => $name));
    }

    if (empty($blockinfo)) {
        $msg = xarML('Block info could not be retrieved');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    $data = array(
        'name' => $blockinfo['name'],
        'template' => ($template != NULL ? $template : $blockinfo['template'])
    );

    return $data;
}
?>

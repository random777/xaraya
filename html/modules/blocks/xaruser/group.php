<?php
/**
 * Display a block group in the module space
 *
 * @package modules
 * @subpackage blocks
 * @return array 
 * @author Marty Vance <dracos@xaraya.com>
 * @param  string $name name of the block group to render
 * @param  string $gid group ID of the block group to render
 * @param  string $template outer block template to use (optional)
 */
function blocks_user_group($args)
{
    extract($args);
    
    if (!xarVarFetch('name', 'str:1:255', $name, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('gid', 'int:1:', $gid, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('template', 'pre:trim:lower:ftoken', $template, NULL, XARVAR_NOT_REQUIRED)) {return;}

$args = array(
    'name' => $name,
    'gid' => $gid,
    'template' => $template
);
//die(var_dump($args));

    $groupinfo = array();

    // Use $name or $gid to fetch block, but we prefer $gid
    if ($gid != NULL) {
        $groupinfo = xarModAPIFunc('blocks','user','getgroup', array('gid' => $gid));
    }
    if ($name != NULL && empty($groupinfo)) {
        $groupinfo = xarModAPIFunc('blocks','user','getgroup', array('name' => $name));
    }
//die(var_dump($groupinfo));

    if (empty($groupinfo)) {
        $msg = xarML('Block group info could not be retrieved');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    $data = array(
        'name' => $groupinfo['name'],
        'template' => ($template != NULL ? $template : $groupinfo['template'])
    );

    return $data;
}
?>

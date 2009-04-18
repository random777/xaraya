<?php
/**
 * Delete a table
 *
 */
    sys::import('modules.dynamicdata.class.objects.master');
    
    function dynamicdata_util_rename_static_table()
    {
        if (!xarSecurityCheck('AdminDynamicData')) return;

        if (!xarVarFetch('table',      'str:1',  $data['table'],    '',     XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('newtable',   'str:1',  $data['newtable'],    '',     XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('confirm',    'bool',   $data['confirm'], false,       XARVAR_NOT_REQUIRED)) return;

        $data['object'] = DataObjectMaster::getObject(array('name' => 'dynamicdata_tablefields'));

        $data['tplmodule'] = 'dynamicdata';

        if ($data['confirm']) {
            if (empty($data['newtable'])) 
                xarResponse::Redirect(xarModURL('dynamicdata','util','view_static',array('table' => $data['table'])));
            $query = 'RENAME TABLE ' . $data['table'] . ' TO ' . $data['newtable'];
            $dbconn = xarDB::getConn();
            $dbconn->Execute($query);

            // Jump to the next page
            xarResponse::Redirect(xarModURL('dynamicdata','util','view_static',array('table' => $data['newtable'])));
            return true;
        }
        return $data;
    }

?>
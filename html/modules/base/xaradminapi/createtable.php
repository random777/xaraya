<?php
    function base_adminapi_createtable($args)
    {
        extract($args);
        if (empty($module)) $module = 'base';
        if (empty($tables))
            throw new BadParameterException('Missing a tablename to create');
        if (!is_array($tables)) $tables = array($tables);
        
        foreach ($tables as $table) {
            $fullName = sys::root() . '/html/modules/' . $module . '/xardata/' . $table . '-def.xml';
            if (!file_exists($fullName))
                throw new BadParameterException($fullName, 'Could not find the file #(1) to create a table from');
            $sqlCode = xarModAPIFunc('base','admin','transform', array('file' => $fullName, 'action' => 'create'));
            $queries = explode(';',$sqlCode);
            array_pop($queries);
            $dbconn = xarDB::getConn();
            foreach ($queries as $q) $dbconn->Execute($q);
        }
        return true;
    }
?>

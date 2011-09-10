<?php

class Installer extends Object
{
    public $tableprefix = '';
    
    static private function transform($xmlFile, $xslAction='display', $dbName='mysql', $xslFile=null)
    {
        // Park this here for now
        $tableprefix = xarDB::getPrefix();
        
        if (!isset($xmlFile))
            throw new BadParameterException('No file to transform!');
        if (!isset($xslFile))
            $xslFile = sys::lib() . 'xaraya/xslt/xml2ddl-'. $dbName . '.xsl';
        if (!file_exists($xslFile)) {
            $msg = xarML('The file #(1) was not found', $xslFile);
            throw new BadParameterException($msg);
        }
        sys::import('xaraya.xslprocessor');
        $xslProc = new XarayaXSLProcessor($xslFile);
        $xslProc->setParameter('', 'action', $xslAction);
        $xslProc->setParameter('', 'tableprefix', $tableprefix);
        $xslProc->xmlFile = $xmlFile;
        return $xslProc->transform($xslProc->xmlFile);
    }
    
    static public function createTable($table, $module)
    {
        if (empty($module)) $module = 'base';
        if (empty($table))
            throw new BadParameterException('Missing a tablename to create');
        $fullName = sys::code() . 'modules/' . $module . '/schema.xml';
        if (!file_exists($fullName)) {
            $msg = xarML('Could not find the file #(1) to create a table from', $fullName);
            throw new BadParameterException($msg);
        }
        $sqlCode = self::transform($fullName, 'create');
        $queries = explode(';',$sqlCode);
        array_pop($queries);
        $dbconn = xarDB::getConn();
        foreach ($queries as $q) {
            xarLogMessage('SQL: '. $q);
            $dbconn->Execute($q);
        }
        return true;
    }
}

?>
<?php
    function base_adminapi_transform($args)
    {
        extract($args);
        if (!isset($action)) $action = 'display';
        if (!isset($dbname)) $dbname = 'mysql';
        if (!isset($file))
            throw new BadParameterException('No file to transform!');
        if (!isset($xslfile))
            $xslfile = sys::root() . '/lib/xaraya/xslt/xml2ddl-'. $dbname . '.xsl';
        if (!file_exists($xslfile))
            throw new BadParameterException($xslfile, 'The file #(1) was not found');
        sys::import('xaraya.xslprocessor');
        $xslProc = new XarayaXSLProcessor($xslfile);
        $xslProc->setParameter('', 'action', $action);
        $xslProc->xmlFile = $file;
        return $xslProc->transform($xslProc->xmlFile);
    }
?>

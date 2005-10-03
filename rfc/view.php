<?php
if (!isset($xmlfile)) {
    $xmlfile= 'rfc0000.xml';
 }
$xsltFile = 'rfc2629.xsl';
// Process and view the document
echo xslt_process(xslt_create(), $xmlfile, $xsltFile);
?>
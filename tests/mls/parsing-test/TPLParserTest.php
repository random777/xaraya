<?php
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// @copyright (C) 2002-2009 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file:
// ----------------------------------------------------------------------

include_once('../../../html/modules/translations/class/TPLParser.php');
define('TPLPARSERDEBUG','1');

$p = new TPLParser();
$p->parse('parser-test.xd');

var_dump($p);
?>
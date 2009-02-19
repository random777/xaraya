<?php
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// @copyright (C) 2002-2009 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file:
// ----------------------------------------------------------------------

include_once('../../../html/modules/translations/class/PHPParser.php');
define('PHPPARSERDEBUG','1');

$p = new PHPParser();
$p->parse('parser-test.php');

var_dump($p);
?>
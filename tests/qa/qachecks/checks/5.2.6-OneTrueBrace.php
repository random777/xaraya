<?php
/**
 * File: $Id$
 *
 * @package qachecks
 * @copyright (C) 2004 by Roger Keays and the Digital Development Foundation Inc
 * @link http://www.ninthave.net
 * @author Roger Keays <r.keays@ninthave.net>
 * 05 May 2004
 */


class OneTrueBrace extends QACheckRegexp
{
    var $id = '5.2.6';
    var $name = "Functions use 'one true brace convention'";
    var $fatal = true;
    var $filetype = 'all';
    var $enabled = true;
    var $checkcomments = true;
    var $regexps = array('/^(.*;)?\s*function.*{/',
            '/^(.*;)?\s*class.*{/');
}

/* add to the list of checks when imported */
if (isset($checks)) {
    $checks[] = new OneTrueBrace();
}
?>

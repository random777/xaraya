<?php
// {{{ Header
/*
 * -File        $Id: StripTask.php,v 1.3 2003/02/28 20:45:41 openface Exp $
 * -License     LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright   2001, Thyrell
 * -Author      Anderas Aderhold, a.aderhold@tyhrell.com
 * -Author 		Albert Lash, alby@thirteen.net
 */
// }}}
// {{{ imports

import('phing.Task');

// }}}
// {{{ Strip Task

//
// 'comments'      strip comments
// 'debug'         strip "$Debug->" calls
// 'crlf'          fix CRLF
// 'all'           do all actions above
//
// - checks if filetype is text
// - accpets also params for filename or pattern
// - do the stripping action on file
/**
 * THIS CLASS IS OLD AND BROKEN
 *
 * @package  phing.tasks.binarycloud.misc
 */
class StripTask extends Task {

    var $__taskname = 'strip';

    function StripTask() {
        parent::Task();
    }
    // {{{ method Init()

    /**
     * Initialize Task
     */
    function Init() {
        $Logger =& System::GetLogger();
        $Logger->Log(PH_LOG_DEBUG, "StripTask::Init() called");
    }

    // }}}
    function Main() {
        $Logger =& System::GetLogger();

        // Get the source filename
        $source = parent::GetParam('src');

        // Log an error if there is no source, but ignore
        if (is_null($source)) {
            $Logger->Log(PH_LOG_WARNING, 'No source file set, ignoring');
        }

        //Get the target
        $target = parent::GetParam('to');

        // Define $target as source if its not defined
        if (is_null($target)) {
            $target = $source;
        }

        // Get the mode and the custom delimiters
        $mode = parent::GetParam('mode');
        $ldim = parent::GetParam('ldim');
        $rdim = parent::GetParam('rdim');

        // Open the file
        $contents = parent::Readfile($source);

        // Explode the mode string, set default if none provided
        $arrModes = explode(",", $mode);
        if (is_empty($arrModes)) {
            $arrModes = array("crlf");
        }

        // Check for modes and call the right functions
        $cnt = $count($arrModes);
        for ($i = 0; $i < $cnt; $i++) {

            if ($arrModes[$i] == 'comments') {
                // Call comment stripper
                $contents = $this->_CommentStripper($contents);
            }
            elseif ($arrModes[$i] == 'debug') {

                // Call debug stripper
                $contents = $this->_DebugStripper($contents);

            }
            elseif ($arrModes[$i] == 'crlf') {

                // Call crlf stripper
                $contents = $this->CrlfStripper($contents);

            }
            elseif ($arrModes[$i] == 'all') {

                // Call all strippers
                $contents = $this->_CommentStripper($contents);
                $contents = $this->_DebugStripper($contents);
                $contents = $this->_CommentStripper($contents, $ldim, $rdim);
            }
        }


        // Check to see if left and right delimeters are specified
        if(!is_null($ldmin) && !is_null($rdim)) {
            $dimBool = true;
        } else {
            $dimBool = false;
            $Logger->Log(PH_LOG_WARNING, "Either no or incomplete delimeters specified. Not stripping.");
        }

        // Line feeds
        function _CrlfStripper($_contents) {
            // Replace Windows and Mac line feeds with Unix line feeds
            preg_replace('/\r\n|\r/', '\n', $_contents);

            // Replace wierd BBEdit life feeds
            preg_replace('/(\^M)/', '', $_contents);

            return $_contents;
        }

        // Comments
        function _CommentStripper($_contents, $_ldim, $_rdim) {
            // Define common comments
            $search = array ("/(#).*(\n)/",
                             "/(\/\*).*([^\/\*]+).*(\*\/)/",
                             "/(\/\*\*).*([^\/\*]+).*(\*\/)/",
                             "/(\/\/).*(\n)/",
                             "/(\^M)/");
            // Remove common comments
            $text = preg_replace($search, '', $_contents);

            // If custom comments included, remove
            if ($dimBool=="true") {
                preg_replace('/($_ldim).*([^\/\*].*($_rdim)/', '', $_contents);
            }

            return $contents;

        }

        //Debugs
        function _DebugStripper($_contents) {
            preg_replace('/($Debug->Log/).*([^\/\*]+).*(/);)/', '', $_contents);
            return $_contents;
        }

        parent::Writefile($target, $contents);

        $Logger->Log(PH_LOG_EVENT, "Stripped file $target");
        return true;
    }
}

// }}}
/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

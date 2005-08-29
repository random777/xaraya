<?php
// {{{ Header
/*
 * -File		$Id: HtmlTidyTask.php,v 1.8 2003/02/28 20:45:41 openface Exp $
 * -License		LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright	2001, Eye Integrated Communications
 * -Author		jason hines, jason@greenhell.com
 * -Author      Manuel Holtgrewe, grin@gmx.net
 */
// }}}

import('phing.FileTask');

// {{{ class HtmlTidyTask

/**
 * THIS CLASS IS OLD AND BROKEN
 *
 * @package  phing.tasks.ext
 */

class HtmlTidyTask extends FileTask {

    // {{{ properties

    var $__taskname = 'htmltidy';
    var $src = "";
    var $to = "";
    var $todir ="";

    // }}}
    // {{{ method HtmlTidyTask()

    /**
     * Constructor
     *
     * @author jason hines, jason@greenhell.com
     * @access public
     */
    function HtmlTidyTask() {
        parent::FileTask();
    }

    // }}}
    // {{{ method Init()

    /**
     * Initialize Task
     */
    function Init() {
        $Logger =& System::GetLogger();
        $Logger->Log(PH_LOG_DEBUG, "HtmlTidyTask::Init() called");
    }

    // }}}
    // {{{ method Main()

    /**
     * Entry point for this class
     *
     * @author jason hines, jason@greenhell.com
     * @access public
     */
    function Main() {
        $Logger =& System::GetLogger();

        $file = $this->src;

        $strTarget = $this->to;

        $Logger->Log(PH_LOG_EVENT, "Using tidy on {$file}");
        $cmd = "tidy -q -c -f /dev/null -wrap 78 -m {$file}";

        @system($cmd,$result);
        if (!$result) {
            $Logger->Log(PH_LOG_ERROR,"Failed HtmlTidy on file {$strTarget}");
            return false;
        } else {
            $Logger->Log(PH_LOG_EVENT,"Successfully HtmlTidy on file{$strTarget}");
            return true;
        }
    }

    // }}}
    // {{{ Setters and Getters ====================================================================
    // {{{ method SetSrc($_src)

    function SetSrc($_src) {
        $this->src = $_src;
        return true;
    }

    // }}}
    // {{{ method GetSrc()

    function GetSrc() {
        return $this->src;
    }

    // }}}
    // {{{ method SetTo()

    function SetTo($_to) {
        $this->to = $_to;
        return true;
    }

    // }}}
    // {{{ method GetTo()

    function GetTo() {
        return $this->to;
    }

    // }}}
    // {{{ method SetTodir($_todir)

    function SetTodir($_todir) {
        $this->todir = $_todir;
        return true;
    }

    // }}}
    // {{{ method GetTodir()

    function GetTodir() {
        return $this->todir;
    }

    // }}}
    // }}} ========================================================================================

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

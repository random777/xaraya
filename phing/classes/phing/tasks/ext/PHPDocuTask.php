<?php
// {{{ Header
/*
 * -File        $Id: PHPDocuTask.php,v 1.4 2003/07/02 19:13:04 openface Exp $
 * -License     LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright   2003, Eye Integrated Communications
 * -Author      Jason Hines, jason@greenhell.com
 */
// }}}
// {{{ imports

import('phing.Task');

// }}}
// {{{ PhpdocuTask

/**
 * Generate phpdoc documentation
 *
 * PHPDocumentor wrapper phing task
 *
 * @author  jason hines, jason@greenhell.com
 * @package phing.tasks.ext
 */

class PHPDocuTask extends Task {

    // {{{ properties

    var $source = NULL;
    var $target = NULL;
    var $clean = FALSE;
    var $output = "HTML:default:default";
    var $title = "PHPDocumentor Export";
    var $package = "default";

    // }}}
    // {{{ method PHPDocuTask()

    /**
     * Contructor.
     *
     * @access	public
     */

    function PHPDocuTask() {}

    // }}}
    // {{{ method Main()

    /**
     * Entry point for this Task.
     *
     * @access	public
     * @author  Jason Hines, jason@greenhell.com
     */

    function Main() {

        $phpdocu_dir = getenv("BCHOME") . "/ext/phpdocu";

        if ($this->source === null || $this->target === null) {
            throw (new BuildException("Both the source and target directory path must be given."));
            return;
        }


        // pass in some command line options
        unset($_SERVER['argv']);
        $_SERVER['argv'] = array(
                               "--target",$this->target,
                               "--directory",$this->source,
                               "--output",$this->output,
                               "--quiet","on",
                               "--title",$this->title,
                               "--defaultpackagename",$this->package,
                               "--pear","on",
                               "--parseprivate","on"
                           );

        // clean target dir if requested
        if ($this->clean == TRUE) {
            $project =& $this->GetProject();

            $this->log("Cleaning target directory first");
            $del_task =& $project->createTask("delete");
            $del_task->setDir($this->target);
            $del_task->Main();
        }

        // main PHPDoc Include File
        include_once($phpdocu_dir."/phpDocumentor/phpdoc.inc");

        return true;
    }

    // }}}
    // {{{ method SetSource($_src)

    function SetSource($_source) {
        $this->source = (string) $_source;
        return true;
    }

    // }}}
    // {{{ method GetSource()

    function GetSource() {
        return $this->source;
    }

    // }}}
    // {{{ method SetTarget()

    function SetTarget($_target) {
        $this->target = (string) $_target;
        return true;
    }

    // }}}
    // {{{ method GetTarget()

    function GetTarget() {
        return $this->target;
    }

    // }}}
    // {{{ method SetPackage($_src)

    function SetPackage($_package) {
        $this->package = (string) $_package;
        return true;
    }

    // }}}
    // {{{ method SetClean()

    function SetClean($_clean) {
        $this->clean = (boolean) $_clean;
        return true;
    }

    // }}}
    // {{{ method SetTitle()

    function SetTitle($_title) {
        $this->title = (string) $_title;
        return true;
    }

    // }}}
    // {{{ method SetOutput()

    function SetOutput($_output) {
        $this->output = (string) $_output;
        return true;
    }

    // }}}
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

<?php
// {{{ Header
/*
 * -File        $Id: PHPDocTask.php,v 1.9 2003/04/29 15:36:13 openface Exp $
 * -License     LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright   2001, Thyrell
 * -Author      Anderas Aderhold, a.aderhold@tyhrell.com
 * -Author 		  Albert Lash, alby@thirteen.net
 * -Author      Manuel Holtgrewe, grin@gmx.net
 * -Author      Jason Hines, jason@greenhell.com
 */
// }}}
// {{{ imports

import('phing.Task');

// }}}
// {{{ PhpdocTask

/**
 * Generate phpdoc documentation
 *
 * @author  andreas aderhold, andi@binarycloud.com
 * @author  manuel holtgrewe, grin@gmx.net
 * @package phing.tasks.ext
 */

class PHPDocTask extends Task {

    // {{{ properties

    var $srcdir = NULL;
    var $todir = NULL;
    var $clean = FALSE;
    var $template = 'default';
    var $title = 'PHPDoc Export';

    // }}}
    // {{{ method PHPDocTask()

    /**
     * Contructor.
     *
     * @access	public
     */

    function PHPDocTask() {}

    // }}}
    // {{{ method Main()

    /**
     * Entry point for this Task.
     *
     * @access	public
     * @author	Andreas Aderhold, andi@binarycloud.com
     * @author  Jason Hines, jason@greenhell.com
     */

    function Main() {
        // Directory with include files
        // TODO: use PropertyTask / environment
        $phpdoc_include_dir = getenv("BCHOME") . "/ext/phpdoc/";
        define("PHPDOC_INCLUDE_DIR", $phpdoc_include_dir);

        // constant required by PHPDoc
        define("PHPDOC_TEMPLATE_DIR", PHPDOC_INCLUDE_DIR);

        // main PHPDoc Include File
        include_once($phpdoc_include_dir . "/prepend.php");

        // for safe measure - PHPDoc has many notices
        error_reporting (E_ALL ^ E_NOTICE);
        ini_set('max_execution_time', 0);

        // clean the destination directory if requested
        if ($this->clean === TRUE && @is_dir($this->todir)) {
          $this->log("Removing $this->todir");
          // TODO: Delete dir with Phing filesystem class
        }

        // override argument handling
        global $argc;
        $argc=0;

        // new PHPDoc instance
        $doc = new Phpdoc;

        // silence the PHPDoc output
        $doc->flag_output = false;

        // Sets the name of your application.
        // The name of the application gets used in many default templates.
        $doc->setApplication($this->title);

        // directory where your source files reside:
        $doc->setSourceDirectory($this->srcdir);
        $this->log("Source directory: ".$this->srcdir);

        // save the generated docs here:
        $doc->setTarget($this->todir);
        $this->log("Target directory: ".$this->todir);

        // use these templates:
        $template_dir = PHPDOC_TEMPLATE_DIR . "renderer/html/";
        $doc->setTemplateDirectory($template_dir . $this->template);

        // source files have one of these suffixes:
        $doc->setSourceFileSuffix( array ("php", "inc") );

        $this->log("Generating PHPDoc API ...");

        // parse and generate the xml files
        $doc->parse();

        // turn xml in to html using templates
        $doc->render();

        // copy static files
        $files = array('empty.html', 'index.html', 'phpdoc.css', 'phpdoc.dtd');
        foreach ($files as $file) {
          // TODO: this should use Phing filesystem class
          copy($template_dir.$this->template."/static/".$file, $this->todir."/".$file);
        }

        return true;
    }

    // }}}
    // {{{ method SetSrc($_src)

    function SetSrcdir($_srcdir) {
        $this->srcdir = (string) $_srcdir;
        return true;
    }

    // }}}
    // {{{ method GetSrcdir()

    function GetSrcdir() {
        return $this->srcdir;
    }

    // }}}
    // {{{ method SetTodir()

    function SetTodir($_todir) {
        $this->todir = (string) $_todir;
        return true;
    }

    // }}}
    // {{{ method GetTodir()

    function GetTodir() {
        return $this->todir;
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

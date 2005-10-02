<?php
// {{{ Header
/*
 * -File        $Id: ZencodeTask.php,v 1.8 2003/04/09 15:58:11 thyrell Exp $
 * -License     LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright   2001, Thyrell
 * -Author      Anderas Aderhold, a.aderhold@tyhrell.com
 * -Author      Manuel Holtgrewe, grin@gmx.net
 */
// }}}
// {{{ imports

import('phing.FileTask');

// }}}
// {{{ EncodeTask

/**
 * THIS CLASS IS OLD AND BROKEN
 *
 *
 * @author  andreas aderhold, andi@binarycloud.com
 * @author  manuel holtgrewe, grin@gmx.net
 * @package phing.tasks.ext
 */

class ZEncodeTask extends FileTask {

    // {{{ properties

    /* name of the task, required here!*/
    var $__taskname = 'encode';

    // }}}
    // {{{ method ZEncodeTask()

    /**
     * Contructor. Dont touch
     *
     * @access	public
     * @author	Andreas Aderhold, andi@binarycloud.com
     */

    function EncodeTask() {
        parent::FileTask();
    }

    // }}}
    // {{{ method Init()

    /**
     * Initialize Task
     */
    function Init() {
        $Logger =& System::GetLogger();
        $Logger->Log(PH_LOG_DEBUG, "ZencodeTask::Init() called");
    }

    // }}}
    // {{{ method Main()

    /**
     * Entry point for this Task,
     *
     * @access	public
     * @author	Andreas Aderhold, andi@binarycloud.com
     */

    function Main() {
        $Logger =& System::GetLogger();

        $Logger->Log(PH_LOG_EVENT, "Encoding Files not yet implemented...");
        return true;

        /*
         * found this snippet somewhere, maybe useful (anyway,thats bad code)
         * just use to see how encoder works

         if ($dir && ereg("/$",$dir)) $dir=substr($dir,0,strlen($dir)-1);
         if ($dir) {setcookie("dirc",$dir,time()+3600*24*365);$dirc=$dir;};
         <form>Please encode every file in dir:<input type=text name=dir value="<?echo $dirc;?>">
         if ($dir) {
         if (!ereg("~/",$dir) && !ereg("~.:",$dir)) $dir=dirname(getenv("PATH_TRANSLATED"))."/".$dir;
         echo "<br>".$dir."<br><br>";
         chdir($dir);
         $attdir=opendir($dir);
         while ($att=readdir($attdir))
         if (strlen($att)>3 && ereg("\.php$|\.php3$|\.phps$|\.phtml$",$att))
         {
         echo("<br><br><b>".$dir."/".$att."</b><br>");
         $fp=fopen($dir."/".$att,"r");
         $beg=substr(fgets($fp,5),0,4);
         fclose($fp);
         if ($beg=="Zend")
         echo "Already encoded.";
         else system ("zendenc --rename-source src ".$dir."/".$att." ".$dir."/".$att);
         };
         };
         *
         *
         */
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

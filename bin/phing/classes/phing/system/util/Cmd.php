<?php
// {{{ Header
/*
 * -File	   $Id: Cmd.php,v 1.5 2003/04/09 15:58:11 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Andrzej Nowodworski, a.nowodworski@learn.pl
 * -Author	   Anderas Aderhold, andi@binarycloud.com
 */
// }}}
// {{{ Cmd

/**
 *  The Cmd class provides a convinient interface to command line parameter
 *  handling and shell IO including stanard IO modes as well as abstrction
 *  to the ncurses/dialog library for ease of use
 *
 *  @author   Andrzej Nowodworski, a.nowodworski@learn.pl
 *  @author   Andreas Aderhold, andi@binarycloud.com
 *  @version  $Revision: 1.5 $
 *  @package  phing.system.util
 */

class Cmd {

    // {{{ properties

    var $phpArgv  = null;         // ref to plain php array
    var $phpArgc  = null;         // ref to related php array

    // OLD
    var $argv = array();		  // the command line options, will go away
    // /OLD

    var $definitions = array();	  // user specified params and data
    var $given       = array();   // array, holds list of given parameters id=>value
    var $optional    = array();   // idx array, holds all non paramters

    var $undefined = null;        // the value of undefined
    // }}}
    // {{{


    /**
     * Description.
     *
     * @author  Andreas Aderhold, andi@binarycloud.com
     */

    function Cmd(&$_argv, &$_argc) {
        $this->phpArgc =& $_argc;
        $this->phpArgv =& $_argv;
        $this->_LoadArgv();
    }

    /**
     * Description.
     *
     * @author Andrzej Nowodworski, a.nowodworski@learn.pl
     */

    function _LoadArgv() {
        // FIXME
        // bring the argv array into a more usable format, maybe using
        $IsValue = false;
        for ($i = 0; $i < $this->phpArgc; $i++) {
            if($this->phpArgv[$i]{0} != '-') {
                // unnamed parameter in here like
                // phing help
                // array (
                //	  'help' => true
                // );
                if ($IsValue) {
                    $key = substr($this->phpArgv[$i-1], 1);
                    $this->argv += array (
                                       $key => $this->phpArgc[$i]
                                   );
                    $IsValue = false;
                } else {
                    $this->argv += array (
                                       $this->phpArgv[$i] => true
                                   );
                }
            }
            else {
                if($this->phpArgv[$i]{1} != '-') {
                    // named parameter in here
                    // phing -name value
                    // the next value in $argv array should be value
                    // this one is name of the key
                    $IsValue = true;
                }
                else {
                    // named parameter in here
                    // phing --name=value
                    $tmp = explode("=", $this->phpArgv[$i]);
                    $key = substr($tmp['0'], 2);
                    $this->argv += array (
                                       $key => $tmp['1']
                                   );
                }
            }
        }
        return(true);
    }

    // returns reference to the cleaned up argv array
    function &GetArgv() {
        return($this->argv);
    }

    function AddParameter($_id, $_name, $_posixname, $_help) {

        $ary = array(
                   'name' => $_name,
                   'posixname' => $_posixname,
                   'help' => $_help,
               );
        $this->definitions[$_id] = $ary;

    }

    function GetParameter($_id) {
        if (isset($this->given[$_id])) {
            return($this->given[$_id]);
        } else {
            return $this->undefined;
        }
    }

    function GetHelpScreen() {
        // assembles help screen from this->defintions
    }

    function GetHelp($_id) {
        $strFormat = "%s, %s\t\t%s";

        if (isset($this->definitions[$_id])) {
            $strHelp = sprintf(
                           $strFormat,
                           $this->definitions[$_id]['name'],
                           $this->definitions[$_id]['posixname'],
                           $this->definitions[$_id]['help']
                       );

            return($strHelp);

        } else {
            return $this->undefined;
        }
    }

    /*
    function _LoadArgv()
    {

    	modified argv code here

    	assembles two lists:
    	$this->given[id]=value

    	and

    	$this->optional[] = value


    }
    */
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

<?php

// decrepated, flagged for removal


// Header {{{
/*******************************************************************************
 ** cvs:
 ** $Id: Timer.php,v 1.6 2003/02/01 19:55:58 openface Exp $
 **
 ** -License     GPL     (http://www.gnu.org/copyleft/gpl.html)
 **      // This document is (c)2001 The Turing Studio, Inc.
 **      // This program is distributed in the hope that it will be useful,
 **      // but WITHOUT ANY WARRANTY; without even the implied warranty of
 **      // MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 **      // GNU General Public License for more details.
 **      //
 **      // You are permitted to use and distribute this document under the terms
 **      // of the GPL. You may distribute and/or incorporate this document as
 **      // part of a commercial product, so long as you:
 **      // - Agree to explicitly and clearly credit both The Turing Studio, Inc.
 **      // and the stated author of this document.
 **      // - Obtain prior written permission from The Turing Studio, Inc.
 **      // If you have any questions regarding this document, or the system of
 **      // which it is a part, direct them to turing at info@turingstudio.com.
 **      // have fun!
 **
 ** -Author	Charles Killian, Charles@AlcatrazDesignGroup.com
 *******************************************************************************/

// }}}
// {{{ Timer

/**
 * This class can be used to obtain the execution time of all of the scripts
 * that are executed in the process of building a page.
 *
 * Example:
 * To be done before any scripts execute:
 *
 * $Timer = new Timer;
 * $Timer->Start_Timer();
 *
 * To be done after all scripts have executed:
 *
 * $timer->Stop_Timer();
 * $timer->Get_Elapsed_Time(int number_of_places);
 *
 * @author	Charles Killian
 * @param	$stime  time at start of script execution
 * @param	$etime  time at end of script execution
 *  @package   phing.lib
 */

// }}}

class Timer {

    // {{{ properties

    var $stime;
    var $etime;

    // }}}
    // {{{ Start_Timer

    /**
     * This function sets the class variable $stime to the current time in
     * microseconds.
     *
     * @author	Charles Killian
     * @access	public
     */

    function StartTimer() {
        $this->stime = $this->_GetMicrotime();
    }

    // }}}
    // {{{ End_Timer

    /**
     * This function sets the class variable $etime to the current time in
     * microseconds.
     *
     * @author	Charles Killian
     * @access	public
     */

    function StopTimer() {
        $this->etime = $this->_GetMicrotime();
    }

    // }}}
    // {{{ Get_Elapsed_Time

    /**
     * This function returns the elapsed time in seconds.
     *
     * Call start_time() at the beginning of script execution and end_time() at
     * the end of script execution.  Then, call elapsed_time() to obtain the
     * difference between start_time() and end_time().
     *
     * @author	Charles Killian
     * @param	$places  decimal place precision of elapsed time (default is 5)
     * @access	public
     */

    function GetElapsedTime($places=5) {
        $etime = $this->etime - $this->stime;
        $format = "%0.".$places."f";
        return (sprintf ($format, $etime));
    }

    // }}}
    // {{{ _get_microtime

    /**
     * This function returns the current time in microseconds.
     *
     * @author	Everett Michaud, Zend.com
     * @return	current time in microseconds
     * @access	private
     */

    function _GetMicrotime() {
        $tmp=split(" ",microtime());
        return ($tmp[0]+$tmp[1]);
    }

    // }}}

}
/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

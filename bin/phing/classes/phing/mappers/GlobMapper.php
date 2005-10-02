<?php
// {{{ Header
/*
 * -File       $Id: GlobMapper.php,v 1.2 2003/04/09 15:58:10 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, 2002 Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}
// {{{ imports

import('phing.mappers.FileNameMapper');

// }}}
// {{{ Classname

/**
 * description here
 *
 * @author   Andreas Aderhold, andi@binarycloud.com
 * @version  $Revision: 1.2 $
 *  @package   phing.mappers
 */

class GlobMapper extends FileNameMapper {

    /**
     * Part of &quot;from&quot; pattern before the *.
     */
    var $fromPrefix = null;

    /**
     * Part of &quot;from&quot; pattern after the *.
     */
    var $fromPostfix = null;

    /**
     * Length of the prefix (&quot;from&quot; pattern).
     */
    var $prefixLength;

    /**
     * Length of the postfix (&quot;from&quot; pattern).
     */
    var $postfixLength;

    /**
     * Part of &quot;to&quot; pattern before the *.
     */
    var $toPrefix = null;

    /**
     * Part of &quot;to&quot; pattern after the *.
     */
    var $toPostfix = null;


	function GlobMapper() {}


	function main($_sourceFileName)
	{
		if (($this->fromPrefix === null)
			|| !strStartsWith($this->fromPrefix, $_sourceFileName)
            || !strEndsWith($this->fromPostfix, $_sourceFileName)) {
            return null;
        }
		$varpart = (string) $this->_extractVariablePart($_sourceFileName);
		$substitution = $this->toPrefix.$varpart.$this->toPostfix;
		return (array) (string) $substitution;
	}



   function setFrom($from)
   {
        $index = strLastIndexOf('*', $from);

        if ($index === -1) {
            $this->fromPrefix = $from;
            $this->fromPostfix = "";
        } else {
			$this->fromPrefix  = substr($from, 0, $index);
            $this->fromPostfix = substr($from, $index+1);
		}
        $this->prefixLength  = strlen($this->fromPrefix);
        $this->postfixLength = strlen($this->fromPostfix);
    }

    /**
     * Sets the &quot;to&quot; pattern. Required.
     */
    function setTo($to)
	{
        $index = strLastIndexOf('*', $to);
        if ($index == -1) {
            $this->toPrefix = $to;
            $this->toPostfix = "";
        } else {
            $this->toPrefix  = substr($to, 0, $index);
            $this->toPostfix = substr($to, $index+1);
        }
    }

    function _extractVariablePart($_name)
	{
		// ergh, i really hate php's string functions .... all but natural
		$start = ($this->prefixLength === 0) ? 0 : $this->prefixLength;
		$end   = ($this->postfixLength === 0) ? strlen($_name) : strlen($_name) - $this->postfixLength;
		$len   = $end-$start;
		return substr($_name, $start, $len);
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

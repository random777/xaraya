<?php
// {{{ Header
/*
 * -File		$Id: Trie.php,v 1.4 2003/04/09 15:58:10 thyrell Exp $
 * -License		LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright	2002, Turing Studios
 * -Author		Charlie Killian, charlie@tizac.com
 */
// }}}
// {{{ Trie

/**
 * This Class implements a Trie data structure using arrays.
 *
 * @author	Charlie Killian, charlie@tizac.com
 * @package   phing.util
 */
class Trie {

    // {{{ Properties

    var $mTrie = array(); // Initialize so extra key [] is not created.

    // }}}
    // {{{ Trie()

    /**
     * Constructor. Actually does nothing.
     *
     * @access	public
     * @return	Trie Object
     * @author	Charlie Killian, charlie@tizac.com
     */

    function Trie() {
        return;
    }

    // }}}
    // {{{ GetTrie()

    /**
     * Return the whole Trie as an array.
     *
     * @access	public
     * @return	Array
     * @author	Charlie Killian, charlie@tizac.com
     */

    function GetTrie() {
        return $this->mTrie;
    }

    // }}}
    // {{{ SetNode()

    /**
     * Creates the path to the node if it doesn't exist and sets the value
     * of the node.
     *
     * @param	nodeArray	Array. An array where each value corresponds to the
     *                      next node in the Trie.
     *                      Example: array("path", "to", "node");
     *
     * @param   value       Mixed. The node is set to this value.
     *
     * @access	public
     * @return	TRUE on success. Err object on failure.
     * @author	Charlie Killian, charlie@tizac.com
     */

    function SetNode($nodeArray, $value=TRUE) {
        /* NOTE: There is a problem with array_merge_recursive(). It doesn't
         *   overwrite values. To get around the problem use
         *   $this->_ArrayMergeClobber(). However, array_merge_recursive() is
         *   about 300% faster so use it until someone complains.
         *
         *	$trie =& $this->_ArrayMergeClobber($this->mTrie,
         *        $this->_ChangeArrayValuesToKeys($nodeArray, $value));
         */
        // Use array_merge_recursive instead of _ChangeArrayValuesToKeys.
        // See NOTE above.
        $trie =& array_merge_recursive($this->mTrie,
                                       $this->_ChangeArrayValuesToKeys($nodeArray, $value));

        if (Err::CheckError($trie)) { // error.

            $msg = "Trie::SetNode() FAILED. Can't set node. ".
                   $trie->GetMessage();

            $Logger =& System::GetLogger();
            $Logger->Log(PH_LOG_DEBUG, $msg);

            throw (new RuntimeException($msg));

        } else // set as trie
        {
            $this->mTrie =& $trie;
        }
    }

    // }}}
    // {{{ GetNode()

    /**
     * Returns the value of a leaf.
     *
     * @param	nodeArray	Array. An array where each value corresponds to the
     *                      next node in the Trie.
     *                      Example: array("path", "to", "node");
     *
     * @access	public
     * @return	TRUE on success. Err object on failure.
     * @author	Charlie Killian, charlie@tizac.com
     */

    function GetNode($nodeArray) {
        if ($this->IsNode($nodeArray)) {
            $len_to_node = count($nodeArray) - 1;

            $mTrie =& $this->mTrie;

            // Move to node.
            for ($i = 0; $i < $len_to_node ; ++$i) {
                $mTrie =& $mTrie[$nodeArray[$i]];
            }

            return($mTrie[$nodeArray[$i]]);

        } else // Node doesn't exist.
        {
            // return error.
            $msg = "Trie::GetNode() FAILED. Node doesn't exist.";

            $Logger =& System::GetLogger();
            $Logger->Log(PH_LOG_DEBUG, $msg);

            throw (new RuntimeException($msg));
        }
    }

    // }}}

    // {{{ AddPathElement()

    /**
        * Make an array out of a normal path and set the node
        *
        * Example. /path/to/node/value.php becomes
        * array("path", "to", "node", "value.php")
        *
        * This class is used to convert paths to the datastructure used by  the
        * Trie class.
        *
        * @param	path	String. Path to create a one diminsional array out of.
        *
        * @access	public
        * @return	Array.
        * @author	Charlie Killian, charlie@tizac.com
     * @author  Andreas Aderhold, andi@binarycloud.com
        */

    function AddPathElement($_path) {

        // clean up path
        $_path = trim($_path);
        $_path = str_replace('\\', DIRECTORY_SEPARATOR, $_path);
        $_path = str_replace('/', DIRECTORY_SEPARATOR, $_path);

        // tokenize the pattern
        $pathParts = array();
        $tok = strtok($_path, DIRECTORY_SEPARATOR);
        while ($tok !== FALSE) {
            $pathParts[] = $tok;
            $tok = strtok(DIRECTORY_SEPARATOR);
        }

        // "/" was first character.
        if (strStartsWith(DIRECTORY_SEPARATOR, $_path)) {
            // Pop off [0]
            //array_shift($path_parts);

            // Add / to array so we don't loose path info.
            array_unshift($pathPars, DIRECTORY_SEPARATOR);
        }

        $this->SetNode($pathParts, true);
    }

    // }}}

    function isInstanceOf(&$object, $classname = 'Trie') {
        if (is_object($object) && (get_class($object) === strtolower($classname))) {
            return true;
        }
        return false;
    }
    // {{{

    // --FIXME--
    // Merges the current trie stored in $this into the trie
    // given by the reference parameter
    // Charlie: please check if you have the time

    function mergeInto(&$mrTrie) {
        if (!Trie::isInstanceOf($mrTrie)) {
            $mrTrie = new Trie();
        }

        $trie = $this->_ArrayMergeClobber($this->mTrie, $mrTrie->mTrie);
        $mrTrie->Set($trie);
        return true;
    }


    function Set($trieDataArray) {
        $this->Clear();
        $this->mTrie = (array) $trieDataArray;
    }


    function CopyInto(&$mrTrie) {
        $mrTrie = $this;
        return true;
    }

    // }}}
    // {{{ IsNode()

    /**
     * Decide if array of values is in mTrie array.
     *
     * @param	nodeArray	Array. An array where each value corresponds to the
     *                      next node in the Trie.
     *
     * @access	public
     * @return	BOOLEAN
     * @author	Markus Krummenacker, kr@pangene.com
     * @author	Charlie Killian, charlie@tizac.com
     */

    function IsNode($nodeArray) {

        $path_len = count($nodeArray);
        $mTrie = $this->mTrie; // Don't modify $this->mTrie.

        for ($i = 0; $i < $path_len; ++$i) {

            if (array_key_exists($nodeArray[$i], &$mTrie)) { // Check for key.

                $mTrie =& $mTrie[$nodeArray[$i]];

            } else { // Not found.

                return FALSE;

            }
        }

        // we only reach this if a full match occurred
        return TRUE;
    }

    // }}}
    // {{{ RemoveNode()

    /**
     * RemoveNode a node and all of its children. This function traverses a
     * reference to $this->mTrie unsetting the last node (array).
     *
     * @param	nodeArray	Array. An array where each value corresponds to the
     *                      next node in the Trie.
     *
     * @access	public
     * @return	TRUE.
     * @author	Charlie Killian, charlie@tizac.com
     */

    function RemoveNode($nodeArray) {
        if ($this->IsNode($nodeArray)) {
            $len_to_node = count($nodeArray) - 1;

            $mTrie =& $this->mTrie;

            // Move to node.
            for ($i = 0; $i < $len_to_node ; ++$i) {
                $mTrie =& $mTrie[$nodeArray[$i]];
            }

            unset($mTrie[$nodeArray[$i]]);
        }
        return TRUE;
    }

    // }}}
    // {{{ Clear()

    /**
     * Clear all nodes from $this->mTrie.
     *
     * @access	public
     * @return	TRUE.
     * @author	Charlie Killian, charlie@tizac.com
     */

    function Clear() {
        $this->mTrie = array();
        return TRUE;
    }

    // }}}
    // {{{ _ArrayMergeClobber()

    /**
     * From the php manual notes on array_merge_recursive:
     *
     * There seemed to be no built in function that would merge two arrays
     * recursively and clobber any existing key/value pairs.  Array_Merge() is
     * not recursive, and array_merge_recursive seemed to give unsatisfactory
     * results... it would append duplicate key/values.
     *
     * @param   a1	 Array.
     * @param   a2   Array.
     *
     * @access	public
     * @return	Multidimensional keyed array. Error object on failure.
     * @author	kc@hireability.com php manual notes on array_merge_recursive
     * @author	Charlie Killian, charlie@tizac.com
     */

    function _ArrayMergeClobber($a1,$a2) {
        if(!is_array($a1) || !is_array($a2)) {
            // return error.
            $msg = "Trie::_ArrayMergeClobber() FAILED. Passed parameter is
                   not an array.";

            $Logger =& System::GetLogger();
            $Logger->Log(PH_LOG_DEBUG, $msg);

            throw (new RuntimeException($msg));
        }

        while (list($key, $val) = each($a2)) {
            if (is_array($val) && is_array($a1[$key])) {
                $a1[$key] = $this->_ArrayMergeClobber($a1[$key], $val);
            } else {
                $a1[$key] = $val;
            }
        }
        return $a1;
    }

    // }}}
    // {{{ _ChangeArrayValuesToKeys()

    /**
     * Take an array and change all of the values to a multidimensional keyed
     * array.
     * Example: array("path", "to", "node") becomes
    			array("path"=>array("to"=>array("node"=>TRUE)))
     *
     * @param	array	    Array. A single dimensional array of values.
     * @param   nodeValue   Mixed. The value the last node is set to.
     *
     * @access	public
     * @return	Multidimensional keyed array. Error object on failure.
     * @author	Andrew White, andyw@pixeltrix.co.uk
     * @author	Charlie Killian, charlie@tizac.com
     */

    function _ChangeArrayValuesToKeys($array, $nodeValue) {
        if(!is_array($array)) {
            // return error.
            $msg = "Trie::_ChangeArrayValuesToKeys() FAILED. Passed parameter is
                   not an array.";

            $Logger =& System::GetLogger();
            $Logger->Log(PH_LOG_DEBUG, $msg);

            throw (new RuntimeException($msg));
        }

        $keys = $nodeValue;
        for ($i = count($array) - 1; $i >= 0; --$i) {
            $keys = array($array[$i] => $keys);
        }

        return $keys;
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

<?php
// {{{ Header
/*
 * -File     $Id: EventObject.php,v 1.7 2003/04/09 15:58:11 thyrell Exp $
 * -License LGPL (http://www.gnu.org/copyleft/lesser.html)   
 * -Copyright  2001, Thyrell
 * -Author   Anderas Aderhold, andi@binarycloud.com
 */
// }}}

/**
 *  @package   phing.system.lang
 */
class EventObject {

    /** The object on which the Event initially occurred. */
    var $source;

    /** Constructs a prototypical Event. */

    function EventObject(&$source) {
        if ($source === null) {
            throw (new RuntimeException("Null source"), __FILE__, __LINE__);
            return;
        }
        $this->source =& $source;
    }

    /** The object on which the Event initially occurred. */
    function &getSource() {
        return $this->source;
    }

    /** Returns a String representation of this EventObject.*/
    function toString() {
        if (method_exists($this->source, "toString")) {
            return get_class($this)."[source=".$this->source->toString()."]";
        } else {
            return get_class($this)."[source=".get_class($this->source)."]";
        }
    }
}
?>

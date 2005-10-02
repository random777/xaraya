<?php
// {{{ Header
/*
 * -File       $Id: ConditionTask.php,v 1.6 2003/04/09 15:58:12 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell  
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import("phing.BuildException");
import("phing.tasks.system.condition.ConditionBase");

/**
 *  <condition> task as a generalization of <available>
 *
 *  <p>This task supports boolean logic as well as pluggable conditions
 *  to decide, whether a property should be set.</p>
 *
 *  <p>This task does not extend Task to take advantage of
 *  ConditionBase.</p>
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.6 $ $Date: 2003/04/09 15:58:12 $
 *  @access    public
 *  @package   phing.tasks.system
 */
class ConditionTask extends ConditionBase {

    var $_property;
    var $_value = "true";

    /**
     * The name of the property to set. Required.
     */
    function setProperty($p) {
        $this->_property = (string) $p;
    }

    /**
     * The value for the property to set. Defaults to "true".
     */
    function setValue($v) {
        $this->_value = (string) $v;
    }

    /**
     * See whether our nested condition holds and set the property.
     */
    function main() {

        if ($this->countConditions() > 1) {
            throw (new BuildException("You must not nest more than one condition into <condition>"), __FILE__, __LINE__);
            return;
        }
        if ($this->countConditions() < 1) {
            throw (new BuildException("You must nest a condition into <condition>"), __FILE__, __LINE__);
            return;
        }
        $cs =& $this->getConditions();
        $c  =& $cs->nextElement();
        if ($c->evaluate()) {
            $p =& $this->getProject();
            $p->setProperty($this->_property, $this->_value);
        }
    }
}
/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

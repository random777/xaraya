<?php
/*
 * $Id: NoBannerLogger.php,v 1.9 2003/05/16 21:36:17 openface Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://binarycloud.com/phing/>.
 */

import("phing.DefaultLogger");

/**
 *  Extends DefaultLogger to strip out empty targets.  This logger is most
 *  commonly used and also enforced by the default phing invokation scripts
 *  in bin/.
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.9 $ $Date: 2003/05/16 21:36:17 $
 *  @access    public
 *  @package   phing
 */

class NoBannerLogger extends DefaultLogger {

    var $_targetName = null;

    function targetStarted(&$event) {
        $target =& $event->getTarget();
        $this->_targetName = $target->getName();
    }

    function targetFinished(&$event) {
        $this->_targetName = null;
    }

    function messageLogged(&$event) {
        if ($event->getPriority() > $this->msgOutputLevel ||
                null === $event->getMessage() ||
                         trim($event->getMessage() === "")) {
            return;
        }

        if ($this->_targetName !== null) {
            System::println("\nTarget: ".$this->_targetName);
            $this->_targetName = null;
        }

        parent::messageLogged($event);
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

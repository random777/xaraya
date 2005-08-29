<?php
/*
 * $Id: RootFilter.php,v 1.5 2003/04/09 15:58:10 thyrell Exp $
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

import("phing.parser.ExpatParseException");
import("phing.parser.AbstractFilter");
import("phing.parser.ProjectFilter");

/**
 * Root filter class for a phing buildfile.
 *
 * The root filter is called by the parser first. This is where the phing
 * specific parsing starts. Rootfilter decides what to do next.
 *
 * @author	  Andreas Aderhold <andi@binarycloud.com>
 * @copyright © 2001,2002 THYRELL. All rights reserved
 * @version   $Revision: 1.5 $ $Date: 2003/04/09 15:58:10 $
 * @access    public
 * @package   phing.parser
 */

class RootFilter extends AbstractFilter {

    /**
     * The phing project configurator object
     */
    var $configurator;

    /**
     * Constructs a new RootFilter
     *
     * The root filter is required so the parser knows what to do. It's
     * called by the ExpatParser that is instatiated in ProjectConfigurator.
     *
     * It recieves the expat parse object ref and a reference to the
     * configurator
     *
     * @param  object  the ExpatParser object
     * @param  object  the ProjectConfigurator object
     * @access public
     */
    function RootFilter(&$parser, &$configurator) {
        $this->configurator =& $configurator;
        parent::AbstractFilter($parser, $this);
    }

    /**
     * Kick off a custom action for a start element tag.
     *
     * The root element of our buildfile is the &gt;project&lt; element. The
     * root filter handles this element if it occurs creates a filter object
     * that handles the root element and calls init.
     *
     * ^^^^^^^^^ That doesn't make much sense.... ?? ^^^^^^^^^^^^^^^^^^^^^^^
     *
     * @param  string  the xml tagname
     * @param  array   the attributes of the tag
     * @throws ExpatParseException if the first element within our build file
     *         is not the &gt;project&lt; element
     * @access public
     */
    function startElement($tag, $attrs) {
        if ($tag === "project") {
            $ph =& new ProjectFilter($this->parser, $this, $this->configurator);
            $ph->init($tag, $attrs);
        } else {
            throw (new ExpatParseException("Config file is not of expected XML type", $this->parser->getLocation()));
            return;
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

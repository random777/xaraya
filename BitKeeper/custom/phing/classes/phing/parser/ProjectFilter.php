<?php
/*
 * $Id: ProjectFilter.php,v 1.5 2003/04/09 15:58:10 thyrell Exp $
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

import("phing.parser.*");

/**
 * Handler class for the <project> XML element This class handles all elements
 * under the <project> element.
 *
 * @author	  Andreas Aderhold <andi@binarycloud.com>
 * @copyright © 2001,2002 THYRELL. All rights reserved
 * @version   $Revision: 1.5 $ $Date: 2003/04/09 15:58:10 $
 * @access    public
 * @package   phing.parser
 */

class ProjectFilter extends AbstractFilter {

    /**
     * The phing project configurator object
     *
     * @var object the ProjectConfigurator
     */
    var $configurator;

    /**
     * Constructs a new ProjectFilter
     *
     * @param  object  the ExpatParser object
     * @param  object  the parent handler that invoked this handler
     * @param  object  the ProjectConfigurator object
     * @access public
     */
    function ProjectFilter(&$parser, &$parentHandler, &$configurator) {
        $this->configurator =& $configurator;
        parent::AbstractFilter($parser, $parentHandler);
    }

    /**
     * Executes initialization actions required to setup the project. Usually
     * this method handles the attributes of a tag.
     *
     * @param  string  the tag that comes in
     * @param  array   attributes the tag carries
     * @param  object  the ProjectConfigurator object
     * @throws ExpatParseException if attributes are incomplete or invalid
     * @access public
     */
    function init($tag, $attrs) {
        $def = null;
        $name = null;
        $id	= null;
        $baseDir = null;

        // some shorthands
        $project =& $this->configurator->project;
        $buildFileParent =& $this->configurator->buildFileParent;

        foreach ($attrs as $key => $value) {
            if ($key === "default") {
                $def = $value;
            } else if ($key === "name") {
                $name = $value;
            } else if ($key === "id") {
                $id = $value;
            } else if ($key === "basedir") {
                $baseDir = $value;
            } else {
                throw (new ExpatParseException("Unexpected attribute '$key'"));
                return;
            }
        }
        if ($def === null) {
            throw (new ExpatParseException("The default attribute of project is required"));
            return;
        }
        $project->setDefaultTarget($def);

        if ($name !== null) {
            $project->setName($name);
            $project->addReference($name, $project);
        }

        if ($id !== null) {
            $project->addReference($id, $project);
        }

        if ($project->getProperty("project.basedir") !== null) {
            $project->setBasedir($project->getProperty("project.basedir"));
        } else {
            if ($baseDir === null) {
                $project->setBasedir($buildFileParent->getAbsolutePath());
            } else {
                // check whether the user has specified an absolute path
                $f = new File($baseDir);
                if ($f->isAbsolute()) {
                    $project->setBasedir($baseDir);
                } else {
                    $project->setBaseDir($project->resolveFile($baseDir, $buildFileParent));
                }
            }
        }
    }

    /**
     * Handles start elements within the <project> tag by creating and
     * calling the required handlers for the detected element.
     *
     * @param  string  the tag that comes in
     * @param  array   attributes the tag carries
     * @throws ExpatParseException if a unxepected element occurs
     * @access public
     */
    function startElement($name, $attrs) {
        $project  =& $this->configurator->project;
        $typedefs = $project->getDataTypeDefinitions();

        if ($name === "taskdef") {
            $null = null;
            $tf =& new TaskFilter($this->parser, $this, $this->configurator, $null, $null);
            $tf->init($name, $attrs);
        } else if ($name === "property") {
            $null = null;
            $tf =& new TaskFilter($this->parser, $this, $this->configurator, $null, $null);
            $tf->init($name, $attrs);
        } else if ($name === "target") {
            $tf =& new TargetFilter($this->parser, $this, $this->configurator);
            $tf->init($name, $attrs);
        } else if (isset($typedefs[$name])) {
            $null = null;
            $tyf =& new DataTypeFilter($this->parser, $this, $this->configurator, $null);
            $tyf->init($name, $attrs);
        } else {
            throw (new ExpatParseException("Unexpected element '$name'", $this->parser->getLocation()));
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

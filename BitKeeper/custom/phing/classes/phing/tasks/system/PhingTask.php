<?php
// {{{ Header
/*
 * -File       $Id: PhingTask.php,v 1.38 2003/07/09 14:46:51 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell  
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

// {{{ imports
import("phing.BuildException");
import("phing.BuildListener");
import("phing.DefaultLogger");

import("phing.parser.*");
import("phing.Project");
import("phing.Task");

import("phing.util.FileUtils");
import("phing.system.io.File");
import("phing.system.io.IOException");

import("phing.types.Reference");
// }}}


/**
 *  @package phing.tasks.system
 */
class PhingTask extends Task {
    // {{{ properties
    // *Everything within here is protected*

    /* Properties that are set by specifying attributes in the build
     * XML file. 
     */

    /** the basedir where is executed the build file */
    var $dir = null;
    /** build.xml (can be absolute) in this case dir will be ignored */
    var $phingFile = null;
    /** the target to call if any */
    var $newProjectTarget = null;
    /** should we inherit properties from the parent ? */
    var $inheritAll = true;
    /** should we inherit references from the parent ? */
    var $inheritRefs = false;

    /* Properties that are set by nested elements within the <phing>
     * tag.
     */

    /** the properties to pass to the new project */
    var $properties = array();
    /** the references to pass to the new project */
    var $references = array();
    /** The filesets that contain the files PhingTask is to be run on. */
    var $filesets = array();

    /* Other attributes for internal use only.
     */

    /** the temporary project created to run the build file */
    var $newProject;
    // }}} 

    // {{{ method main()
    /**
     * Main entry point for the task.
     *
     * @access public
     */
    function main() {
        $this->log("Calling Buildfile '" . $this->phingFile . "' with target '" . $this->newProjectTarget . "'", PROJECT_MSG_DEBUG);

        // Call Phing on the file set with the attribute "phingfile"
        if ($this->phingFile !== null or $this->dir !== null) {
            $this->processFile();
        }

        // if no filesets are given stop here; else process filesets
        if (count($this->filesets) === 0 ) 
            return true;

        // preserve old settings
        $old_phingFile = $this->phingFile;
        $old_dir = $this->dir;
        $old_target = $this->newProjectTarget;

        // set no specific target for files in filesets
        $this->newProjectTarget = NULL;

        /* If one or more filesets are provide, use the files included
         * with them as well to execute Phing.
         * TODO: This may be buggy...
         */
        // set shortcut
        $project =& $this->getProject();

        $count = count($this->filesets); 
        for ($i = 0; $i < $count; ++$i) {
            $fs =& $this->filesets[$i];
            $ds =& $fs->getDirectoryScanner($project);

            $fromDir  = $fs->getDir($project);
            $srcFiles = $ds->getIncludedFiles();

            $count2 = count($srcFiles);
            for ($i = 0; $i < $count2; $i++) {
                $f =& new File($ds->getbasedir(), $srcFiles[$i]);
                $f =& $f->getAbsoluteFile();

                $this->phingFile = $f->getAbsolutePath();
                $this->dir = $f->getParentFile();

                // start Phing
                $this->processFile();
            }
        }

        // side effect free programming ;-)
        $this->newProjectTarget = $old_target;
        $this->phingFile = $old_phingFile;
        $this->dir = $old_dir;
    }
    // }}}
    // {{{ method processFile()
    /**
     * Processes one phing file. To process more, PhingTask::main() sets
     * the attributes of the objects and executes processFile() several
     * times.
     *
     * @access      protected
     */
    function processFile() {
        $savedDir = $this->dir;
        // init Project
        $this->configureProject();

        // directory settings
        if ($this->dir === null and $this->inheritAll === true) {
            $p = &$this->getProject();
            $this->dir = $p->getBaseDir();
        }

        if ($this->dir !== null) {
            $this->newProject->setBaseDir($this->dir);
            if ( $savedDir !== null ) {	// Has been set explicitly
                $this->newProject->setProperty("project.basedir", $this->dir->getAbsolutePath());
            }
        } else {
            $p = &$this->getProject();
            $this->dir = $p->getBaseDir();
        }

        // ??
        //$this->_overrideProperties();

        // Set the build file name and get its absolute path
        if ($this->phingFile === null)
            $this->phingFile = "build.xml";

        $fu = FileUtils::newFileUtils();
        $file = $fu->resolveFile($this->dir, $this->phingFile);
        $this->phingFile = $file->getAbsolutePath();

        $this->newProject->setProperty("phing.file", $this->phingFile);
        $this->log("Calling ".( ($this->newProjectTarget !== null) ? "'".$this->newProjectTarget."'" : ("default target") )." in buildfile ".$this->phingFile, PROJECT_MSG_VERBOSE);

        ProjectConfigurator::configureProject($this->newProject, new File($this->phingFile));

        if ($this->newProjectTarget === NULL) {
            $this->newProjectTarget = $this->newProject->getDefaultTarget();
        }

        //$this->_addReferences();

        // Are we trying to all the target in which we are defined ?
        $p = &$this->getProject();
        $ot = &$this->getOwningTarget();
        if ( ( $this->newProject->getBaseDir() === $p->getBaseDir() ) &&
                ( $this->newProject->getProperty("phing.file") === $p->getProperty("phing.file") ) &&
                ( $ot !== null ) && ( $ot->getName() === $this->newProjectTarget ) ) {

            throw(new BuildException("PhingTask calling its own parent target"));
        }

        $this->newProject->executeTarget($this->newProjectTarget);

        // done, maybe do some cleanup
    }
    // }}}
    // {{{ method configureProject()
    /**
     * Configure the Project, i.e. make intance, attach build listeners
     * (copy from father project), add Task and Datatype definitions,
     * copy properties and references from old project if these options
     * are set via the attributes of the XML tag.
     *
     * Developer note:
     * This function replaces the old methods "init", "_reinit" and 
     * "_initializeProject".
     *
     * @access      protected
     */
    function configureProject() {
        // Create new project
        $this->newProject =& new Project();

        /* Attach the build listeners of the father project to the new
         * Project.
         */
        $listeners =& $this->project->getBuildListeners();
        $count = count($listeners);
        for ($i = 0; $i < $count; ++$i) {
            $this->newProject->addBuildListener($listeners[$i]);
        }

        /* Copy things from old project. Datatypes and Tasks are always
         * copied, properties and references only if specified so/not
         * specified otherwise in the XML definition.
         */
        // Add Datatype definitions
        $defs =& $this->project->getDataTypeDefinitions();
        foreach ($defs as $name => $class) {
            $this->newProject->addDataTypeDefinition($name, $class);
        }
        // Add Task definitions
        $defs =& $this->project->getTaskDefinitions();
        foreach ($defs as $name => $class) {
            $this->newProject->addTaskDefinition($name, $class);
        }

        // Copy Properties of the old project if this is desired
        if ($this->inheritAll === true) {
            $properties =& $this->project->getProperties();
            foreach ($properties as $name => $value) {
                $this->newProject->setProperty($name, $value);
            }
        } else 
            $this->newProject->setSystemProperties();

        // Copy References of the old project if this is desired
        if ($this->inheritRefs === true) {
            $refs =& $this->project->getReferences();

            // fast foreach with references
            $keys = array_keys($refs);
            foreach ($keys as $key) {
                $obj =& $refs[$key];
                $this->newProject->addReference($key, $obj);
            }
        }

        /* Add the references and properties directly given to PhingTask
         * in the XML.
         */
        // Add Properties
        $count = count($this->properties);
        for ($i = 0; $i < $count; $i++) {
            $p =& $this->properties[$i];

            $propTask =& $this->newProject->createTask("property");
            $propTask->setName($p->name);
            if ($p->value !== null) {
                $propTask->setValue($p->value);
            }
            if ($p->file !== null) {
                $propTask->setFile($p->file);
            }
            if ($p->refid !== null) {
                $propTask->setRefid($p->refid);
            }
            if ($p->override !== null) {
                $propTask->setOverride($p->override);
            }
            $propTask->main();
        }

        // Add References
        $keys = array_keys($this->references);
        foreach ($keys as $key) {
            $obj =& $this->references;
            $this->newProject->addReference($key, $object);
        }
    }
    // }}}

    // {{{ private functions
    /**
     * Add the references explicitly defined as nested elements to the
     * new project.  Also copy over all references that don't override
     * existing references in the new project if inheritrefs has been
     * requested.
     */
    //function _addReferences() /* throws BuildException */ {
        // Copy "explicitly defined" ?? What does this mean ?
        // I guess Andi is talking about the ones in the <project> task
        // the build.xml file the phing task is pointed to
/*
        // Copy References from current project if they do not override
        // a reference in the freshly created Project.
        if ($this->_inheritRefs === true) {
            $oldProject =& $this->GetProject();
            $oldRefs =& $oldProject->GetReferences();
            $newRefs =& $this->_newProject->GetReferences();

            foreach ($oldRefs as $key => $element) {
                // Copying/cloning is done in PhingTask::_copyReference()
                if (!isset($newRefs[$key]))
                    $this->_CopyReference($key, $key);
            }
        }
    }*/

    /**
     * Try to clone and reconfigure the object referenced by oldkey in
     * the parent project and add it to the new project with the key
     * newkey.
     *
     * <p>If we cannot clone it, copy the referenced object itself and
     * keep our fingers crossed.</p> -- okay, let's pray...
     */
    /*
    function _copyReference($oldKey, $newKey) {
        /* At the moment, this method only copies the references and does not
         * clone them.
         * IMHO trying to clone would only make sense if we could *completely*
         * reproduce the Graph/Tree structure of each reference. Referenced
         * Object could contain References on other objects and so create a
         * circular structure. */
/*        $oldProject =& $this->GetProject();
        $oldRef =& $oldProject->getReferences();
        $newRef =& $this->_newProject->getReferences();

        // just copying, no "deep" cloning
        $newRef[$newKey] = $oldRef[$oldKey];
    }*/

    /**
     * Copying properties from parent to sub-project
     */
    /*
     * Override the properties in the new project with the one
     * explicitly defined as nested elements here.
    */
/*    function _overrideProperties() {
        $count = count($this->_properties);
        for($i = 0 ; $i<$count ; $i++) {
            $p = &$this->_properties[$i];
            $p->setProject($this->_newProject);
            $p->main();
        }

        //
        // These things should be done in phing.Project ...
        // $this->getProject()->copyInheritedProperties($this->_newProject)
        //
        /* $p = &$this->getProject();

        foreach($p->properties as $key => $value) {
        	if ( $this->_newProject->getProperty($key) === null )
        		continue;

        	$this->_newProject->setProperty($key, $value);
        } */
//    }
/*
    function _addProperties() {

        /**
         * Just check if we should copy properties from 
         * "parent" project */
/*        if($this->_inheritAll === true) {

            $oldProject =& $this->GetProject();
            $oldProperties =& $oldProject->getProperties();

            /**
             * I'm not quite sure whatever to use 
             * $this->_properties
             * or
             * $this->_newProject->SetProperty($key, $value)
             * to copy properties from current project to subproject
             * if (Andi) you can verify it for me **/
/*            foreach($oldProperties as $key => $value) {
                if ( $key === "phing.file" || $key === "project.basedir" ) {
                    // basedir and phing.file get special treatment in main()
                    continue;
                }

                // Don't reset properties, avoid the warning message
                if ( $this->_newProject->getProperty($key) === null ) {
                    $this->_newProject->setProperty($key, $value);
                }
            }
        }
    }*/
    // }}} 
    // {{{ Accessors
    /**
     * If true, pass all properties to the new phing project.
     * Defaults to true.
     *
     * @access      public
     */
    function setInheritAll($value) {
        $this->inheritAll = (boolean) $value;
    }

    /**
     * If true, pass all references to the new phing project.
     * Defaults to false.
     *
     * @access      public
     */
    function setInheritRefs($value) {
        $this->inheritRefs = (boolean)$value;
    }

    /**
     * The directory to use as a base directory for the new phing project.
     * Defaults to the current project's basedir, unless inheritall
     * has been set to false, in which case it doesn't have a default
     * value. This will override the basedir setting of the called project.
     *
     * @access      public
     */
    function setDir($d) {
        if ( is_string($d) )
            $this->dir = new File($d);
        else
            $this->dir = $d;
    }

    /**
     * The build file to use.
     * Defaults to "build.xml". This file is expected to be a filename relative
     * to the dir attribute given.
     *
     * @access      public
     */
    function setPhingfile($s) {
        // it is a string and not a file to handle relative/absolute
        // otherwise a relative file will be resolved based on the current
        // basedir.
        $this->phingFile = $s;
    }

   /**
    * Alias function for setPhingfile
    *
    * @access       public
    */
    function setBuildfile($s) {
        $this->setPhingFile($s);
    }

    /**
     * The target of the new Phing project to execute.
     * Defaults to the new project's default target.
     *
     * @access      public
     */
    function setTarget($s) {
        $this->newProjectTarget = (string) $s;
    }
    // }}}
    // {{{ Creators for nested elements
    /**
     * Support for filesets; This method returns a reference to an instance
     * of a Fileset object.
     *
     * @access      public
     */
    function &createFileset() {
        $num = array_push($this->filesets, new Fileset());
        return $this->filesets[$num-1];
    }

    /**
     * Property to pass to the new project.
     * The property is passed as a 'user property'
     *
     * @access      public
     */
    function &createProperty() {
        $num = array_push($this->properties, new Property());
        return $this->properties[$num-1];
    }

    /**
     * Reference element identifying a data type to carry
     * over to the new project.
     *
     * @access      public
     */
    function createReference() {
        $num = array_push($this->references, new Reference());
        return $this->references[$num-1];
    }
    // }}}
}

// {{{ class Property
/**
 * Dummy class to store Properties
 */
class Property {
    // {{{ property
    var $name       = null;
    var $value      = null;
    var $file       = null;
    var $refid      = null;
    var $override   = false;
    // }}}
    // {{{ Accessors
    /**
     * @access      public
     */
    function setName($name) {
        $this->name = (string) $name;
    }

    /**
     * @access      public
     */
    function setValue($value) {
        $this->value = (string) $value;
    }

    /**
     * @access      public
     */
    function setFile($file) {
        $this->file = (string) $file;
    }

    /**
     * @access      public
     */
    function setRefid($refid) {
        $this->refid = (string) $refid;
    }

    /**
     * @access      public
     */
    function setOverride($override) {
        $this->override = (boolean) $override;
    }
    // }}}
}

// }}}

?>

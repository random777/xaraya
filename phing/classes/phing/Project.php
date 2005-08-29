<?php
/*
 * $Id: Project.php,v 1.14 2003/05/08 11:10:40 purestorm Exp $
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

define('PROJECT_MSG_DEBUG', 4);
define('PROJECT_MSG_VERBOSE', 3);
define('PROJECT_MSG_INFO', 2);
define('PROJECT_MSG_WARN', 1);
define('PROJECT_MSG_ERR', 0);

import('phing.util.FileUtils');
import("phing.TaskAdapter");

/**
 *  The Phing project class. Represents a completely configured Phing project.
 *  The class defines the project and all tasks/targets. It also contains
 *  methods to start a build as well as some properties and FileSystem
 *  abstraction.
 *
 *  <strong>SMART-UP INLINE DOCS</strong>
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.14 $ $Date: 2003/05/08 11:10:40 $
 *  @access    public
 *  @package   phing
 */

class Project {

    var $targets         = array(); // contains the targets
    var $globalFilterset = array(); // global filterset (future use)
    var $globalFilters   = array(); // all globals filters (future use)

    var $properties  = array();	    // properties of this project
    var $taskdefs    = array();		// taskdefinitions for this project
    var $typedefs    = array();     // typedefinitions for this project
    var $references  = array();		// holds ref names and a reference to the
    // referred object

    /* -- properties that come in via xml attributes -- */
    var $basedir       = null;	    // basedir (File object)
    var $defaultTarget = 'all';     // the default target name
    var $name          = null;      // project name (required)
    var $description   = null;      // projects description

    var $fileUtils     = null;      // a FileUtils object
    var $listeners     = array();   // event listeneers

    /**
     *  Constructor, sets up everything
     *
     *  @author  Andreas Aderhold, andi@binarycloud.com
     */
    function Project() {
        $this->fileUtils = FileUtils::newFileUtils();
        return;
    }

    /** inits the project, called from main app */
    function init() {
        // set builtin properties
        $this->setSystemProperties();

        // load default tasks
        $taskdefs = getResourcePath("phing/tasks/defaults.properties");
        // remove in ze2
        if ($taskdefs === null) {
            throw (new BuildException("Can't load default task list"), __FILE__, __LINE__);
            return;
        }


        { // try to load taskdefs
            $props = new Properties();
            $in    = new File((string)$taskdefs);

            if ($in === null) {
                throw( new BuildException("Can't load default task list"), __FILE__, __LINE__ );
                return;
            }
            $props->load($in);

            $enum = $props->propertyNames();
            foreach($enum as $key) {
                $value = $props->getProperty($key);
                $this->addTaskDefinition($key, $value);
            }
        }
        if (catch ("IOException", $ioe)) {
            throw (new BuildException("Can't load default task list"), __FILE__, __LINE__);
            return;
        }

        // load default tasks
        $typedefs = getResourcePath("phing/types/defaults.properties");
        // remove in ZE2
        if ($typedefs === null) {
            throw (new BuildException("Can't load default datatype list"), __FILE__, __LINE__);
            return;
        }

        { // try to load taskdefs
            $props = new Properties();
            $in    = new File((string)$typedefs);
            if ($in === null) {
                throw( new BuildException("Can't load default datatype list"), __FILE__, __LINE__ );
                return;
            }
            $props->load($in);

            $enum = $props->propertyNames();
            foreach($enum as $key) {
                $value = $props->getProperty($key);
                $this->addDataTypeDefinition($key, $value);
            }
        }
        if (catch ("IOException", $ioe)) {
            throw (new BuildException("Can't load default datatype list"), __FILE__, __LINE__);
            return;
        }
    }

    /** returns the global filterset (future use) */
    function &getGlobalFilterSet() {
        return $this->globalFilterSet;
    }

    /** return a named property */
    function getProperty($_name) {
        if (array_key_exists($_name, $this->properties)) {
            return($this->properties[$_name]);
        } else {
            return System::getProperty($_name);
        }
    }

    /** Return reference to all properties */
    function &getProperties() {
        return $this->properties;
    }

    /** set a property, overriding */
    function setProperty($_name, $_value) {
        $this->properties[(string)$_name] = (string) $_value;
    }

    function setDefaultTarget($targetName) {
        $this->defaultTarget = (string) trim($targetName);
    }

    function getDefaultTarget() {
        return (string) $this->defaultTarget;
    }

    /**
     * Sets the name of the current project
     *
     * @param    string   name of project
     * @return   void
     * @access   public
     * @author   Andreas Aderhold, andi@binarycloud.com
     */

    function setName($name) {
        $this->name = (string) trim($name);
        $this->setProperty("phing.project.name", $this->name);
    }

    /**
     * Returns the name of this project
     *
     * @returns  string  projectname
     * @access   public
     * @author   Andreas Aderhold, andi@binarycloud.com
     */
    function getName() {
        return (string) $this->name;
    }

    /** Set the projects description */
    function setDescription($description) {
        $this->description = (string) trim($description);
    }

    /** return the description, null otherwise */
    function getDescription() {
        return $this->description;
    }

    /** Set basedir object from xml*/
    function setBasedir($dir) {
        if (isInstanceOf($dir, "File")) {
            $dir = $dir->getAbsolutePath();
        }

        $dir = $this->fileUtils->normalize($dir);

        $dir = new File((string) $dir);
        if (!$dir->exists()) {
            throw (new BuildException("Basedir ".$dir->getAbsolutePath()." does not exist"));
            return;
        }
        if (!$dir->isDirectory()) {
            throw (new BuildException("Basedir ".$dir->getAbsolutePath()." is not a directory"));
            return;
        }
        $this->basedir = $dir;
        $this->setProperty("project.basedir", $this->basedir->getPath());
        $this->log("Project base dir set to: " . $this->basedir->getPath(), PROJECT_MSG_VERBOSE);
    }

    /**
     * Returns the basedir of this project
     *
     * @returns  File  Basedir File object
     * @access   public
     * @throws   BuildException
     * @author   Andreas Aderhold, andi@binarycloud.com
     */

    function &getBasedir() {
        if ($this->basedir === null) {
            { // try to set it
                $this->setBasedir(".");
            }
            if (catch ("BuildException", $exc)) {
                throw (new BuildException("Can not set default basedir. ".$exc->getMessage()));
                return;
            }
        }
        return $this->basedir;
    }

    // FIXME -> Properties class
    function setSystemProperties() {
        //$this->setProperty('phing.version', PH_VERSION);
        //$this->setProperty('phing.phpversion', PH_HOST_PHP_VERSION);
        //$this->setProperty('phing.home', PH_HOME);
        //$this->setProperty('phing.startdir', PH_START_DIR);

        // and now the env vars
        foreach($_SERVER as $name => $value) {
            // skip arrays
            if (is_array($value)) {
                continue;
            }
            $this->setProperty("env.$name", $value);
        }
        return true;
    }

    function addTaskDefinition($name, $class) {
        $name  = (string) $name;
        $class = (string) $class;
        if (!isset($this->taskdefs[$name])) {
            import($class);
            $this->taskdefs[$name] = $class;
            $this->log("  +Task definiton: $name ($class)", PROJECT_MSG_DEBUG);
        } else {
            $this->log("Task $name ($class) already registerd, skipping", PROJECT_MSG_VERBOSE);
        }
    }

    function &getTaskDefinitions() {
        return $this->taskdefs;
    }

    function addDataTypeDefinition($typeName, $typeClass) {
        $typeName  = (string) $typeName;
        $typeClass = (string) $typeClass;

        if (!isset($this->taskdefs[$typeName])) {
            import($typeClass);
            $this->typedefs[$typeName] = $typeClass;
            $this->log("  +User datatype: $typeName ($typeClass)", PROJECT_MSG_DEBUG);
        } else {
            $this->log("Type $name ($class) already registerd, skipping", PROJECT_MSG_VERBOSE);
        }
    }

    function &getDataTypeDefinitions() {
        return $this->typedefs;
    }

    /** add a new target to the project */
    function addTarget($targetName, &$target) {
        if (isset($this->targets[$targetName])) {
            throw (new BuildException("Duplicate target: $targetName"));
            return;
        }
        $this->addOrReplaceTarget($targetName, $target);
    }

    function addOrReplaceTarget($targetName, &$target) {
        $this->log("  +Target: $targetName", PROJECT_MSG_DEBUG);
        $target->setProject($this);
        $this->targets[$targetName] =& $target;
    }

    function &getTargets() {
        return $this->targets;
    }

    /**
     * Create a new task instance and return reference to it. This method is
     * sorta factory like. A _local_ instance is created and a reference returned to
     * that instance. Usually PHP destroys local variables when the function call
     * ends. But not if you return a reference to that variable.
     * This is kinda error prone, because if no reference exists to the variable
     * it is destroyed just like leaving the local scope with primitive vars. There's no
     * central place where the instance is stored as in other OOP like languages.
     *
     * We might update this and not use the "new" operator to generate the object
     * but using a static function call like "MyTask::newMyTask" that returns a reference
     * to the object stored in a static variable of the the class. We might also wait
     * for ZE2 if the method here (reference counting) does not lead to serious trouble.
     *
     * Just be sure to always use reference assign syntax with objects ( =& ).
     *
     * @param    string   Task name
     * @returns  object   A task object
     * @throws   BuildException
     *           RuntimeException
     */
    function &createTask($taskType) {
        { //try
            $cls = "";
            foreach ($this->taskdefs as $name => $class) {
                if (strtolower($name) === strtolower($taskType)) {
                    $lastdot = strrpos($class, ".");
                    $cls = substr($class, $lastdot + 1);
                    break;
                }
            }

            if (!class_exists(strtolower($cls))) {
                return null;
            }

            $o = new $cls();

            if (is_a($o, "Task")) {
                $task =& $o;
            } else {
                // not a real task, try adapter
                $taskA = new TaskAdapter();
                $taskA->setProxy($o);
                $task =& $taskA;
            }
            $task->setProject($this);
            $task->setTaskType($taskType);
            // set default value, can be changed by the user
            $task->setTaskName($taskType);
            $this->log ("  +Task: $taskType", PROJECT_MSG_DEBUG);
        }
        if (catch ('Exception', $t)) {
            throw (new BuildException("Could not create task of type: $taskType due to ".$t->getMessage()));
            return;
        }
        // everything fine return reference
        return $task;
    }

    /**
     * Create a task instance and return reference to it
     * See createTask() for explanation how this works
     *
     * @param    string   Type name
     * @returns  object   A datatype object
     * @throws   BuildException
     *           RuntimeException
     */

    function &createDataType($typeName) {
        { //try
            $cls = (string) $typeName;
            if (!class_exists(strtolower($cls))) {
                return null;
            }
            $type = new $cls(crc32(time()));
            $this->log("  +Type: $typeName", PROJECT_MSG_DEBUG);
            if (!is_a($type, 'DataType')) {
                throw (new RuntimeException("Failed to create type $typeName"),__FILE__,__LINE__);
            }
            if (is_a($type, 'ProjectComponent')) {
                $type->setProject($this);
            }
        }
        if (catch ('Exception', $t)) {
            throw (new BuildException("Could not create type: $typeName due to ".$t->getMessage()));
            return;
        }
        // everything fine return reference
        return $type;
    }

    /**
     * Executes a list of targets
     *
     * @param    array  List of target names to execute
     * @returns  void
     * @throws   BuildException
     */

    function executeTargets(&$targetNames) {
        for ($i = 0; $i < count($targetNames); ++$i) {
            $this->executeTarget((string) $targetNames[$i]);
        }
    }

    /**
     * Executes a target
     *
     * @param    string  Name of Target to execute
     * @returns  void
     * @throws   BuildException
     */
    function executeTarget($targetName = null) {
        // complain about executing void
        if ($targetName === null) {
            throw (new BuildException("No target specified"));
            return;
        }

        // invoke topological sort of the target tree and run all targets
        // until targetName occurs.
        $sortedTargets = $this->_topoSort($targetName, $this->targets);
        if (catch("BuildException", $ex)) {
            throw($ex);
            return;
        }

        $curIndex = (int) 0;
        $curTarget = null;
        do {
            $curTarget =& $sortedTargets[$curIndex++];
            $curTarget->performTasks();
            if(catch('BuildException', $exc)) {
                $this->log("Execution of target \"".$curTarget->getName()."\" failed for the following reason: ".$exc->getMessage(), PROJECT_MSG_ERR);
            }
        } while ($curTarget->getName() !== $targetName);
    }


    function resolveFile($fileName, $rootDir = null) {
        if ($rootDir === null) {
            return (object) $this->fileUtils->resolveFile($this->basedir, (string) $fileName);
        } else {
            return (object) $this->fileUtils->resolveFile($rootDir, (string) $fileName);
        }
    }

    /**
     * Translate a path into its native (platform specific) format.
     *
     * This method uses the PathTokenizer class to separate the input path
     * into its components. This handles DOS style paths in a relatively
     * sensible way. The file separators are then converted to their platform
     * specific versions. Static method
     *
     * @param  string The path to be converted
     * @return string The native version of to_process or an empty string if to_process
     *                is null or empty
     */
    function translatePath($str) {
        if ( $str === null || strlen($str) === 0 ) {
            return (string) "";
        }
        $path = "";
        $tokenizer = new PathTokenizer($str);
        while ($tokenizer->hasMoreTokens()) {
            $pathComponent = $tokenizer->nextToken();
            $pathComponent = str_replace('/', FileSystem::getSeparator());
            $pathComponent = str_replace('\\', FileSystem::getSeparator());
            if (strlen($path) !== 0) {
                $path .= FileSystem::getPathSeparator();
            }
            $path .= $pathComponent;
        }
        return (string) $path;
    }

    /**
     * returns the boolean equivalent of a string, which is considered true
     * if either "on", "true", or "yes" is found, ignoring case.
     */
    function toBoolean($s) {
        return (strtolower($s) === "on" || strtolower($s) === "true" || strtolower($s) === "yes");
    }

    /** tests if a string is a representative of a boolean */
    function isBoolean(&$s) {
        // nasty type check
        if (!is_string($s) || $s === null || strcmp($s, "") == 0) {
            return false;
        }

        $test = strtolower($s);
        if ($test === "on" || $test === "true" || $test === "yes"
                                        || $test === "off" || $test === "false" || $test === "no") {
            return true;
        }
    }

    /**
     * Topologically sort a set of Targets.
     * @param  $root is the (String) name of the root Target. The sort is
     *         created in such a way that the sequence of Targets until the root
     *         target is the minimum possible such sequence.
     * @param  $targets is a array representing a "name to Target" mapping
     * @return An array of Strings with the names of the targets in
     *         sorted order.
     */

    function _topoSort($root, &$targets) {

        $root     = (string) $root;
        $ret      = array();
        $state    = array();
        $visiting = array();

        // We first run a DFS based sort using the root as the starting node.
        // This creates the minimum sequence of Targets to the root node.
        // We then do a sort on any remaining unVISITED targets.
        // This is unnecessary for doing our build, but it catches
        // circular dependencies or missing Targets on the entire
        // dependency tree, not just on the Targets that depend on the
        // build Target.

        $this->_tsort($root, $targets, $state, $visiting, $ret);

        $retHuman = "";
        for ($i=0; $i<count($ret); $retHuman .= $ret[$i]->toString()." ", ++$i)
            ;
        $this->log("Build sequence for target '$root' is: $retHuman", PROJECT_MSG_VERBOSE);

        $keys = array_keys($targets);
        while($keys) {
            $curTargetName = (string) array_shift($keys);
            if (!isset($state[$curTargetName])) {
                $st = null;
            } else {
                $st = (string) $state[$curTargetName];
            }

            if ($st === null) {
                $this->_tsort($curTargetName, $targets, $state, $visiting, $ret);
            }
            elseif ($st === "VISITING") {
                throw (new RuntimeException("Unexpected node in visiting state: $curTargetName"), __FILE__, __LINE__);
                return;
            }
        }

        $retHuman = "";
        for ($i=0; $i<count($ret); $retHuman .= $ret[$i]->toString()." ", ++$i)
            ;
        $this->log("Complete build sequence is: $retHuman", PROJECT_MSG_VERBOSE);

        return $ret;
    }

    // one step in a recursive DFS traversal of the target dependency tree.
    // - The array "state" contains the state (VISITED or VISITING or null)
    //   of all the target names.
    // - The stack "visiting" contains a stack of target names that are
    //   currently on the DFS stack. (NB: the target names in "visiting" are
    //    exactly the target names in "state" that are in the VISITING state.)
    // 1. Set the current target to the VISITING state, and push it onto
    //    the "visiting" stack.
    // 2. Throw a BuildException if any child of the current node is
    //    in the VISITING state (implies there is a cycle.) It uses the
    //    "visiting" Stack to construct the cycle.
    // 3. If any children have not been VISITED, tsort() the child.
    // 4. Add the current target to the Vector "ret" after the children
    //    have been visited. Move the current target to the VISITED state.
    //    "ret" now contains the sorted sequence of Targets upto the current
    //    Target.

    function _tsort($root, &$targets, &$state, &$visiting, &$ret) {
        $state[$root] = "VISITING";
        $visiting[]  = $root;

        if (!isset($targets[$root]) || !isInstanceOf($targets[$root], "Target")) {
            $target = null;
        } else {
            $target =& $targets[$root];
        }

        // make sure we exist
        if ($target === null) {
            $sb = "Target '$root' does not exist in this project.";
            array_pop($visiting);
            if (!empty($visiting)) {
                $parent = (string) $visiting[count($visiting)-1];
                $sb .= "It is used from target '$parent'.";
            }
            throw (new BuildException($sb));
            return;
        }

        //var_dump($target);
        $deps = $target->getDependencies();

        while($deps) {
            $cur = (string) array_shift($deps);
            if (!isset($state[$cur])) {
                $m = null;
            } else {
                $m = (string) $state[$cur];
            }
            if ($m === null) {
                // not been visited
                $this->_tsort($cur, $targets, $state, $visiting, $ret);
            } else if ($m == "VISITING") {
                // currently visiting this node, so have a cycle
                throw ($this->_makeCircularException($cur, $visiting));
                return;
            }
        }

        $p = (string) array_pop($visiting);
        if ($root !== $p) {
            throw (new RuntimeException("Unexpected internal error: expected to pop $root but got $p"), __FILE__, __LINE__);
            return;
        }

        $state[$root] = "VISITED";
        $ret[] =& $target;
    }

    function _makeCircularException($end, $stk) {
        $sb = "Circular dependency: $end";
        do {
            $sb .= " <- ".(string) array_pop($stk);
        } while($c != $end);
        return new BuildException($sb);
    }

    /**
     * Adds a reference to an object. This method is called when the parser
     * detects a id="foo" attribute. It passes the id as $name and a reference
     * to the object assigned to this id as $value
     */

    function addReference($name, &$object) {
        if (isset($this->references[$name])) {
            $this->log("Overriding previous definition of reference to $name", PROJECT_MSG_WARN);
        }
        $this->log("Adding reference: $name -> ".get_class($object), PROJECT_MSG_DEBUG);
        $this->references[$name] =& $object;
    }

    /**
     * Returns a reference to references array ;)
     *
     */
    function &getReferences() {
        return $this->references;
    }

    /**
     * Abstracting and simplifyling Logger calls for project messages
     */
    function log($msg, $level = PROJECT_MSG_INFO) {
        $this->logObject($this, $msg, $level);
    }


    function logObject(&$obj, $msg, $level) {
        $this->fireMessageLogged($obj, $msg, $level);
    }

    function addBuildListener(&$listener) {
        if (!is_a($listener, "BuildListener")) {
            throw (new RuntimeException("Parameter of unexpected type (need BuildListener object)"),__FILE__,__LINE__);
            return;
        }
        $this->listeners[] =& $listener;
    }

    function removeBuildListener(&$listener) {
        if (!is_a($listender, "BuildListener")) {
            throw (new RuntimeException("Parameter of unexpected type (need BuildListener object)"),__FILE__,__LINE__);
            return;
        }
        $newarray = array();
        for ($i=0; $i<count($this->listeners); ++$i) {
            if (!compareReferences($listener, $this->listeners[$i])) {
                $newarray[] =& $this->listeners[$i];
            }
        }
        $this->listeners = $newarray;
    }

    function &getBuildListeners() {
        return $this->listeners;
    }

    function fireBuildStarted() {
        $event =& new BuildEvent($this);
        for ($i = 0; $i < count($this->listeners); ++$i) {
            $listener =& $this->listeners[$i];
            $listener->buildStarted($event);
        }
    }

    function fireBuildFinished(&$exception) {
        $event =& new BuildEvent($this);
        $event->setException($exception);
        for ($i = 0; $i < count($this->listeners); ++$i) {
            $listener =& $this->listeners[$i];
            $listener->buildFinished($event);
        }
    }

    function fireTargetStarted(&$target) {
        $event =& new BuildEvent($target);
        for ($i = 0; $i < count($this->listeners); ++$i) {
            $listener =& $this->listeners[$i];
            $listener->targetStarted($event);
        }
    }

    function fireTargetFinished(&$target, &$exception) {
        $event =& new BuildEvent($target);
        $event->setException($exception);
        for ($i = 0; $i < count($this->listeners); ++$i) {
            $listener =& $this->listeners[$i];
            $listener->targetFinished($event);
        }
    }

    function fireTaskStarted(&$task) {
        $event =& new BuildEvent($task);
        for ($i = 0; $i < count($this->listeners); ++$i) {
            $listener =& $this->listeners[$i];
            $listener->taskStarted($event);
        }
    }

    function fireTaskFinished(&$task, &$exception) {
        $event =& new BuildEvent($task);
        $event->setException($exception);
        for ($i = 0; $i < count($this->listeners); ++$i) {
            $listener =& $this->listeners[$i];
            $listener->taskFinished($event);
        }
    }

    function fireMessageLoggedEvent(&$event, $message, $priority) {
        $event->setMessage($message, $priority);
        for ($i = 0; $i < count($this->listeners); ++$i) {
            $listener =& $this->listeners[$i];
            $listener->messageLogged($event);
        }
    }

    function fireMessageLogged(&$object, $message, $priority) {
        $event =& new BuildEvent($object);
        $this->fireMessageLoggedEvent($event, $message, $priority);
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

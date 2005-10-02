<?php
/*
 * $Id: Main.php,v 1.30 2003/06/02 17:43:05 openface Exp $
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

import("phing.system.lang.System");
import("phing.*");
import("phing.parser.*");
import("phing.system.util.Properties");
import("phing.system.io.File");
import("phing.system.io.FileReader");

/**
 * Command line entry point into Phing. This class is entered via the
 * main() function call and reads the command line arguments. It then
 * assembles and executes a Phing project.
 *
 * If you integrating Phing into some other tool, this is not the class
 * to use as an entry point. Please see the source code of this
 * class to see how it manipulates the Phing project classes.
 *
 *  <strong>SMART-UP INLINE DOCS</strong>
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.30 $ $Date: 2003/06/02 17:43:05 $
 *  @access    public
 *  @package   phing
 */

//define("PHING_VERSION", "Phing version 1.0, Released 2002-??-??");

class Main {

    /** The default build file name */
    var $DEFAULT_BUILD_FILENAME = "build.xml";

    /** Our current message output status. Follows Project.MSG_XXX */
    var $msgOutputLevel = PROJECT_MSG_INFO;

    /** File that we are using for configuration */
    var $buildFile = null;

    /** The build targets */
    var $targets = array();

    /** Set of properties that can be used by tasks */
    var $definedProps;

    /** Names of classes to add as listeners to project */
    var $listeners = array();

    var $loggerClassname = null;

    /** Indicates if this phing should be run */
    var $readyToRun = false;

    /** Indicates we should only parse and display the project help information */
    var $projectHelp = false;

    /**
     * Prints the message of the Throwable if it's not null.
     */
    function printMessage(&$t) {
        $message = $t->getMessage();
        if ($message !== null) {
            System::println($message);
        }
    }

    /** Entry point allowing for more options from other front ends. Static */
    function start(&$args, $additionalUserProperties = null) {

        $m = null;
        $m = new Main();
        $m->execute($args);

        if (catch("Exception", $exc)) {
            $m->printMessage($exc);
            System::halt(-1); // Parameter error
        }

        if ($additionalUserProperties !== null) {
            $keys = $m->additionalUserProperties->keys();
            while(count($keys)) {
                $key = array_shift($keys);
                $property = $m->additionalUserProperties->getProperty($key);
                $m->definedProps->setProperty($key, $property);
            }
        }

        $m->runBuild();
        if (catch ("BuildException", $bex)) {
            $m->printMessage($bex);
            System::halt(1); // Errors occured
        }
        else if (catch("Exception", $exc)) {
            $m->printMessage($exc);
            System::halt(1); // Errors occured
        }
        else {
            // everything fine, shutdown
            System::halt(0); // no errors, everything is cake
        }
    }

    /**
     * Command line entry point. This method kicks off the building
     * of a project object and executes a build using either a given
     * target or the default target.
     *
     * @param args Command line args.
     */
    function fire(&$args) {
        Main::start($args, null);
    }


    function execute(&$args) {
        $this->definedProps = new Properties();

        $this->searchForThis = null;

        // cycle through given args
        for ($i = 0; $i < count($args); ++$i) {

            $arg = (string) $args[$i];

            if ($arg == "-help" || $arg == "-h") {
                $this->printUsage();
                return;
            } else if ($arg == "-version" || $arg == "-v") {
                $this->printVersion();
                return;
            } else if ($arg == "-quiet" || $arg == "-q") {
                $this->msgOutputLevel = PROJECT_MSG_WARN;
            } else if ($arg == "-verbose") {
                $this->printVersion();
                $this->msgOutputLevel = PROJECT_MSG_VERBOSE;
            } else if ($arg == "-debug") {
                $this->printVersion();
                $this->msgOutputLevel = PROJECT_MSG_DEBUG;
            } else if ($arg == "-logfile") {
                {// try to set logfile
                    if (!isset($args[$i+1])) {
                        throw(new ArrayIndexOutOfBoundsException($i+1, __FILE__, __LINE__));
                    } else {
                        $logFile = new File($args[$i+1]);
                        $i++;
                        // FIXME
                    }
                }
                if (catch ("IOException", $ioe)) {
                    $msg = "Cannot write on the specified log file. Make sure the path exists and you have write permissions.";
                    System::println($msg);
                    return;
                }
                if (catch ("ArrayIndexOutOfBoundsException", $aox)) {
                    $msg = "You must specify a log file when using the -log argument";
                    System::println($msg);
                    return;
                }
            } else if ($arg == "-buildfile" || $arg == "-file" || $arg == "-f") {
                {
                    if (!isset($args[$i+1])) {
                        throw(new ArrayIndexOutOfBoundsException($i+1));
                    } else {
                        $this->buildFile = new File($args[$i+1]);
                        $i++;
                    }
                }
                if (catch ("ArrayIndexOutOfBoundsException", $aox)) {
                    $msg = "You must specify a buildfile when using the -buildfile argument";
                    System::println($msg);
                    return;
                }
            } else if ($arg == "-listener") {
                $this->listeners[] = $args[$i++];
            } else if (strStartsWith("-D", $arg)) {
                $name = substring($arg, 2, strlen($arg)-1);
                $value = null;
                $posEq = strIndexOf("=", $name);
                if ($posEq > 0) {
                    $value = substring($name, $posEq+1);
                    $name  = substring($name, 0, $posEq-1);
                } else if ($i < count($args)-1) {
                    $value = $args[++$i];
                }
                $this->definedProps->setProperty($name, $value);
            } else if ($arg == "-logger") {
                if ($this->loggerClassname !== null) {
                    System::println("Only one logger class may be specified.");
                    return;
                }
                { // try setting logger
                    if (!isset($args[$i+1])) {
                        throw(new ArrayIndexOutOfBoundsException($i+1, __FILE__, __LINE__));
                    } else {
                        $this->loggerClassname = $args[++$i];
                    }
                }
                if (catch ("ArrayIndexOutOfBoundsException", $aix)) {
                    System::println("You must specify a classname when using the -logger argument");
                    return;
                }
            } else if ($arg == "-projecthelp" || $arg == "-targets" || $arg == "-list" || $arg == "-l") {
                // set the flag to display the targets and quit
                $this->projectHelp = true;
            } else if ($arg == "-find") {
                // eat up next arg if present, default to build.xml
                if ($i < count($args)-1) {
                    $this->searchForThis = $args[++$i];
                } else {
                    $this->searchForThis = $this->DEFAULT_BUILD_FILENAME;
                }
            } else if (substr($arg,0,1) == "-") {
                // we don't have any more args
                System::println("Unknown argument: $arg");
                $this->printUsage();
                return;
            } else {
                // if it's no other arg, it may be the target
                array_push($this->targets, $arg);
            }
        }

        // if buildFile was not specified on the command line,
        if ($this->buildFile === null) {
            // but -find then search for it
            if ($this->searchForThis !== null) {
                $this->buildFile = $this->_findBuildFile(System::getProperty("user.dir"), $this->searchForThis);
            } else {
                $this->buildFile = new File($this->DEFAULT_BUILD_FILENAME);
            }
        }
        // make sure buildfile exists
        if (!$this->buildFile->exists()) {
            System::println("Buildfile: ".$this->buildFile->toString()." does not exist!");
            throw (new BuildException("Build failed"));
            return;
        }

        // make sure it's not a directory
        if ($this->buildFile->isDirectory()) {
            System::println("What? Buildfile: ".$this->buildFile->toString()." is a dir!");

            // debug temp
            //print_r($this->buildFile);
            //exit;

            throw (new BuildException("Build failed"));
            return;
        }

        $this->readyToRun = true;
    }

    /**
     * Helper to get the parent file for a given file.
     *
     * @param file   File
     * @return	   Parent file or null if none
     */
    function _getParentFile(&$file) {
        $filename = $file->getAbsolutePath();
        $file	 = new File($filename);
        $filename = $file->getParent();

        if ($filename !== null && $this->msgOutputLevel >= PROJECT_MSG_VERBOSE) {
            System::println("Searching in $filename");
        }

        return ($filename === null) ? null : new File($filename);
    }

    /**
     * Search parent directories for the build file.
     *
     * Takes the given target as a suffix to append to each
     * parent directory in search of a build file.  Once the
     * root of the file-system has been reached an exception
     * is thrown.
     *
     * @param suffix	Suffix filename to look for in parents.
     * @return		  A handle to the build file
     *
     * @exception BuildException	Failed to locate a build file
     */
    function _findBuildFile($start, $suffix) {
        if ($this->msgOutputLevel >= PROJECT_MSG_INFO) {
            System::println("Searching for $suffix ...");
        }
        $startf = new File($start);
        $parent = new File($startf->getAbsolutePath());
        $file   = new File($parent, $suffix);

        // check if the target file exists in the current directory
        while (!$file->exists()) {
            // change to parent directory
            $parent = $this->_getParentFile($parent);

            // if parent is null, then we are at the root of the fs,
            // complain that we can't find the build file.
            if ($parent === null) {
                throw (new BuildException("Could not locate a build file!"));
                return;
            }
            // refresh our file handle
            $file = new File($parent, $suffix);
        }
        return $file;
    }

    /** Executes the build. */
    function runBuild() {

        if (!$this->readyToRun) {
            return;
        }

        if ($this->msgOutputLevel >= PROJECT_MSG_INFO) {
            System::println("Buildfile: ".$this->buildFile->toString());
        }

        $project =& new Project();

        $error = null;

        $this->_addBuildListeners($project);


        // { try to run project, this sorta emulates try/catch
        $project->fireBuildStarted();

        $project->init();
        if (catch("RuntimeException", $exc)) {
            throw ($exc, __FILE__, __LINE__);
            $project->fireBuildFinished($exc);
            return;
        }

        $project->setProperty("phing.version", $this->getPhingVersion());

        $e = $this->definedProps->keys();
        while (count($e)) {
            $arg   = (string) array_shift($e);
            $value = (string) $this->definedProps->getProperty($arg);
            $project->setProperty($arg, $value);
        }
        unset($e);

        $project->setProperty("phing.file", $this->buildFile->getAbsolutePath());

        // first use the Configurator to create the project object
        // from the given build file.

        // FIXME error handling
        ProjectConfigurator::configureProject($project, $this->buildFile);
        if (catch("RuntimeException", $exc)) {
            throw ($exc, __FILE__, __LINE__);
            $project->fireBuildFinished($exc);
            return;
        }

        // make sure that we have a target to execute
        if (count($this->targets) === 0) {
            $this->targets[] = $project->getDefaultTarget();
        }

        // execute targets if help param was not given
        if (!$this->projectHelp) {

            $project->executeTargets($this->targets);

            if (catch("RuntimeException", $exc)) {
                throw ($exc);
                $project->fireBuildFinished($exc);
                return;
            }
        }
        // if help is requested print it
        if ($this->projectHelp) {
            $this->printDescription($project);
            $this->printTargets($project);
        }
        //}
        if (catch("RuntimeException", $exc)) {
            throw ($exc);
        }
        $project->fireBuildFinished($exc);
    }


    function _addBuildListeners(&$project) {
        // Add the default listener
        $project->addBuildListener($this->_createLogger());
    }

    /** Creates the default build logger for sending build events to the log. */
    function &_createLogger() {
        if ($this->loggerClassname !== null) {
            import($this->loggerClassname);
            // get class name part
            $lastDot = strLastIndexOf(".", $this->loggerClassname);
            $classname = substring($this->loggerClassname, $lastDot+1);
            $logger = new $classname;
        } else {
            import("phing.DefaultLogger");
            $logger = new DefaultLogger();
        }
        $logger->setMessageOutputLevel($this->msgOutputLevel);
        return $logger;
    }

    /**  Prints the usage of how to use this class */
    function printUsage() {
        $lSep = System::getProperty("line.separator");
        $msg = "";
        $msg .= "phing [options] [target [target2 [target3] ...]]" . $lSep;
        $msg .= "Options: " . $lSep;
        $msg .= "  -h -help               print this message" . $lSep;
        $msg .= "  -l -list               list available targets in this project" . $lSep;
        $msg .= "  -v -version            print the version information and exit" . $lSep;
        $msg .= "  -q -quiet              be extra quiet" . $lSep;
        $msg .= "  -verbose               be extra verbose" . $lSep;
        $msg .= "  -debug                 print debugging information" . $lSep;
        $msg .= "  -logfile <file>        use given file for log" . $lSep;
        $msg .= "  -logger <classname>    the class which is to perform logging" . $lSep;
        $msg .= "  -f -buildfile <file>   use given buildfile" . $lSep;
        $msg .= "  -D<property>=<value>   use value for given property" . $lSep;
        $msg .= "  -find <file>           search for buildfile towards the root of the" . $lSep;
        $msg .= "                         filesystem and use it" . $lSep;
        //$msg .= "  -recursive <file>      search for buildfile downwards and use it" . $lSep;
        $msg .= $lSep;
        $msg .= "Report bugs to <dev@binarycloud.tigris.org>";
        System::println($msg);
    }

    function printVersion() {
        System::println(Main::getPhingVersion());
    }

    function getPhingVersion() {
        $versionPath = getResourcePath("phing/VERSION.TXT");
        { // try to read file
            $reader = new FileReader(new File($versionPath));
            $reader->readInto($buffer);
            $buffer = trim($buffer);
            //$buffer = "PHING version 1.0, Released 2002-??-??";
            $phingVersion = $buffer;
        }
        if (catch("IOException", $iox)) {
            System::println("Can't read version information file");
            throw (new BuildException("Build failed"));
            return;
        }
        return $phingVersion;
    }

    /**  Print the project description, if any */
    function printDescription(&$project) {
        if ($project->getDescription() !== null) {
            System::println($project->getDescription());
        }
    }

    /** Print out a list of all targets in the current buildfile */
    function printTargets(&$project) {
        // find the target with the longest name
        $maxLength  = 0;
        $targets    =& $project->getTargets();
        $targetNames = array_keys($targets);
        $targetName  = null;
        $targetDescription = null;
        $currentTarget = null;

        // split the targets in top-level and sub-targets depending
        // on the presence of a description
        $topNames        = array();
        $topDescriptions = array();
        $subNames        = array();

        for ($i=0; $i<count($targetNames); ++$i) {
            $currentTarget =& $targets[$targetNames[$i]];
            $targetName = $currentTarget->getName();
            $targetDescription = $currentTarget->getDescription();
            // maintain a sorted list of targets
            if ($targetDescription === null) {
                $pos = (int) $this->_findTargetPosition($subNames, $targetName);
                $subNames[$pos] = $targetName;
            } else {
                $pos = (int) $this->_findTargetPosition($topNames, $targetName);
                $topNames[$pos] = $targetName;
                $topDescriptions[$pos] = $targetDescription;
                if (strlen($targetName) > $maxLength) {
                    $maxLength = strlen($targetName);
                }
            }
        }

        $defaultTarget = $project->getDefaultTarget();

        if ($defaultTarget !== null && $defaultTarget !== "") {
            $defaultName = array();
            $defaultDesc = array();
            $defaultName[] = $defaultTarget;

            $indexOfDefDesc = array_search($defaultTarget, $topNames, true);
            if ($indexOfDefDesc !== false && $indexOfDefDesc >= 0) {
                $defaultDesc = array();
                $defaultDesc[] = $topDescriptions[$indexOfDefDesc];
            }

            $this->_printTargets($defaultName, $defaultDesc, "Default target:", $maxLength);

        }
        $this->_printTargets($topNames, $topDescriptions, "Main targets:", $maxLength);
        $this->_printTargets($subNames, null, "Subtargets:", 0);
    }

    /**  Search for the insert position to keep names a sorted list of Strings */
    function _findTargetPosition(&$names, &$name) {

        $res = count($names);
        for ($i=0; $i < count($names) && $res == count($names); ++$i) {
            // this is strange, it should be < 0 but only < -1 works
            // as desired. It swallows one target if we use < 0
            if (strcmp($name, $names[$i]) < -1) {
                $res = $i;
            }
        }
        return $res;
    }

    /** Output a formatted list of target names with an optional description */
    function _printTargets($names, $descriptions, $heading, $maxlen) {
        $lSep = System::getProperty("line.separator");
        $spaces = '  ';
        while (strlen($spaces) < $maxlen) {
            $spaces .= $spaces;
        }
        $msg = "";
        $msg .= $heading . $lSep;
        $msg .= str_repeat("-",79) . $lSep;

        $total = count($names);
        for($i=0; $i<$total;$i++) {
            $msg .= " ";
            $msg .= $names[$i];
            if ($descriptions !== null) {
                $msg .= substring($spaces, 0, $maxlen-strlen($names[$i]) +2);
                $msg .= $descriptions[$i];
            }
            $msg .= $lSep;
        }
        if ($total>0) {
          System::println($msg);
        } 
   }
}
?>

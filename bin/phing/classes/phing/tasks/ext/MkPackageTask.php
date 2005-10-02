<?php
// {{{ Header
/*
 * -File       $Id: MkPackageTask.php,v 1.2 2003/03/06 19:37:50 openface Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2003, Eye Integrated Communications
 * -Author     jason hines <jason@greenhell.com>
 */
// }}}

/**
 * PackageGenerator - A task capable of generating a PEAR compliant
 * package.xml package file.
 *
 * Defaults can be overridden in the build.xml with:
 * <property name="release_version" value="2.0" />
 *
 * @author jason hines, <jason@greenhell.com>
 * @access public
 * @todo   documentation
 */
class MkPackageTask extends Task {

    var $basedir              = ".";
    var $package_name         = "Package Name";
    var $package_summary      = "This is a short summary of this package.";
    var $package_description  = "This is a long description of this package.";
    var $maintainer_user      = "Username";
    var $maintainer_name      = "Your Name";
    var $maintainer_email     = "you@domain.com";
    var $maintainer_role      = "developer";
    var $release_version      = "X.X";
    var $release_date         = "yyyy-mm-dd";
    var $release_state        = "devel";
    var $license              = "GPL License";

    var $pkgfile = "package.xml";
    var $excludeDirs  = array("CVS");
    var $excludeFiles = array("package.xml");
    var $_properties  = array();

    /**
     * Main entry point for this class.
     *
     * @access public
     */
    function Main() {
        // evaluate properties and override defaults
        foreach ($this->_properties as $prop) {
            $name = $prop->getName();
            if (isset($this->$name)) {
                $this->$name = $prop->getValue();
                $this->log("Assigning property '{$name}' with value '".$prop->getValue()."'");
            } else {
                $this->log("Property '{$name}' not supported -- ignoring");
            }
        }

        // set default base directory
        if ($this->basedir==".") {
          $this->basedir = getenv("PWD");
        }

        // write to package
        if (!$fp = fopen($this->basedir."/".$this->pkgfile,"w")) {
            $message = "Cannot write to package file {$this->basedir}/{$this->pkgfile}\n";
            throw (new BuildException($message), __FILE__, __LINE__);
        }

        $data = <<< EOD
<?xml version="1.0" encoding="ISO-8859-1" ?>
<!DOCTYPE package SYSTEM "../package.dtd">
<package version="1.0">
  <name>{$this->package_name}</name>
  <summary>{$this->package_summary}</summary>
  <description>{$this->package_description}</description>
  <license>{$this->license}</license> 
  <maintainers>
    <maintainer>
      <user>{$this->maintainer_user}</user>
      <name>{$this->maintainer_name}</name>
      <email>{$this->maintainer_email}</email>
      <role>{$this->maintainer_role}</role>
    </maintainer>
  </maintainers>
  <release>
    <version>{$this->release_version}</version>
    <date>{$this->release_date}</date>
    <state>{$this->release_state}</state>
  </release>
  <filelist>

EOD;
        $dirlist=$this->_recurseDir($this->basedir);
        $dirlist[] = $this->basedir;
        foreach ($dirlist as $key=>$val) {
            $dir = str_replace($this->basedir,basename($this->basedir),$val);
            // TODO: nested dirs aren't handled correctly here
            $data .= "    <dir name=\"".$dir."\">\n";
            $filelist=$this->_listFiles($val);
            foreach ($filelist as $fkey=>$file) {
                $ext = substr($file, strrpos($file,".") + 1);
                $data .= "      <file role=\"{$ext}\">".$file."</file>\n";
            }
            $data .= "    </dir>\n";
        }
        $data .= <<< EOD
  </filelist>
</package>
EOD;
        fwrite($fp, $data);
        fclose($fp);

        $this->log("Successfully wrote package file '{$this->pkgfile}'.\n");

    }


    /**
     * Builds a stack of dir names recursively
     *
     * @access private
     */
    function _recurseDir($basedir, $A=array()) {
        $ThisDir=array();
        chdir($basedir);
        $current=getcwd();
        $handle=opendir(".");
        while ($file = readdir($handle)) {
            if (($file!='..') & ($file!='.')) {
                if (is_dir($file) && !in_array($file,$this->excludeDirs)) {
                    array_push($ThisDir,$current.'/'.$file);
                }
            }
        }
        closedir($handle);
        foreach ($ThisDir as $key=>$var) {
            array_push($A, $var);
            $A=$this->_recurseDir($var, $A);
        }
        chdir($basedir);
        return (array) $A;
    }

    /**
     * Returns array of files in given dir name
     *
     * @access private
     */
    function _listFiles($dir) {
        if ($handle = opendir($dir)) {
            $A = array();
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".."
                        && is_file($dir."/".$file)
                        && !in_array($file,$this->excludeFiles)) {
                    $A[]=$file;
                }
            }
            closedir($handle);
            return $A;
        }
    }

    /**
     * Sets the base dir name
     *
     * @access public
     */
    function setBasedir($dir) {
        $this->basedir = $dir;
    }

    /**
     * Task helper method used for creating properties
     *
     * @access public
     */
    function &createProperty() {
        $prop =& new PropertyTask();
        $this->_properties[] =& $prop;
        return $prop;
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

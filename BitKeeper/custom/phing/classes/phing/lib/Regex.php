<?php
// {{{ Header
/*
 * -File	   $Id: Regex.php,v 1.4 2003/02/01 19:55:58 openface Exp $
 * -License	LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Plateau Innovation
 * -Author	 Albert Lash, alash@plateauinnovation.com
 */
// }}}
// {{{ imports

import('phing.system.lang.functions');
import('phing.TrieTransformer');

// }}}
// {{{ Regex

/**
 * Regex.php accepts file lists or singular files
 * and filters them according to a pattern
 *
 * @author   Albert Lash, alash@plateauinnovation.com
 * @version  $Revision: 1.4 $
 * @package   phing.util
 */

class Regex {

    // {{{ properties

    var $property;
    var $include;
    var $exclude;
    var $filelist;
    var $group;
    var $path;

    // }}}
    // {{{ method AddIncludes(mixed _pattern)

    /**
     * AddIncludes; Add a single pattern (string) or a group of patterns (array)
     * that are used to find matches
     * Returns true or error
     *
     * @author  Alby Lash, alash@plateauinnovation.com
     * @access  public
     */

    function AddIncludes($include) {

        // Syntax check
        if (is_array($include)) {
            $i = 0;
            // add elements to this->include
            while(each($include)) {
                // action if it is an array
                $this->include[] = $include[$i];
                $i++;
            }
        }
        elseif (is_string($include)) {
            // add string to this->include
            $this->include[] = $include;
        }
        else {
            $msg = "Regex::AddIncludes() FAILED. Include must be a string or an array." ;
            $Logger =& System::GetLogger();
            $Logger->Log(PH_LOG_DEBUG, $msg);
            throw (new RuntimeException($msg));
        }
    }

    // }}}
    // {{{ method AddExcludes(mixed _pattern)

    /**
     * AddExcludes; Add a single pattern (string) or a group of patterns (array)
     * that are used to find matches (files/dirs that should be excluded
     * from match)
     * Returns true or error
     *
     * @author  Alby Lash, alash@plateauinnovation.com
     * @access  public
     */

    function AddExcludes($exclude) {
        // Syntax check
        if (is_array($exclude)) {
            $i = 0;
            // add elements to this->exclude
            while(each($exclude)) {
                // action if it is an array
                $this->exclude[] = $exclude[$i];
                $i++;
            }
        }
        elseif (is_string($exclude)) {
            // add string to this->exclude
            $this->exclude[] = $exclude;
        }
        else {
            $msg = "Regex::AddExcludes() FAILED. Exclude must be string or an array." ;
            $Logger =& System::GetLogger();
            $Logger->Log(PH_LOG_DEBUG, $msg);
            throw (new RuntimeException($msg));
        }
    }

    // }}}
    // {{{ method GetIncludes()

    /**
     * Returns the current include pattern lists
     *
     * @author  Alby Lash, alash@plateauinnovation.com
     * @access  public
     */

    function &GetIncludes() {
        return($this->include);
    }

    // }}}
    // {{{ method GetExcludes()

    /**
     * Returns the current exclude pattern lists
     *
     * @author  Alby Lash, alash@plateauinnovation.com
     * @access  public
     */

    function &GetExcludes() {
        return($this->exclude);
    }

    // }}}
    // {{{ method GetMatches(mixed _dir)

    /**
     * Get list of files that match the patterns in the
     * according directory (string) or group of directories (array)
     *
     * @author  Alby Lash, alash@plateauinnovation.com
     * @access  public
     */

    function GetMatches($filelist) {
        // Filter the filelist and return the list that complies with the includes
        // and exclude patterns

        //Need $this->include_patterns, $this->exclude_patterns, $filelist

    }

    // }}}
    // {{{ method Filter(mixed _dir)

    /**
     * Get list of files that match the patterns in the
     * according directory (string) or group of directories (array)
     *
     * @author  Alby Lash, alash@plateauinnovation.com
     * @access  public
     */

    function &Filter($filelist) {

        // Check to see if filelist is a trie array
        // This is the section to do first!!

        if(is_trie($filelist)) {

            // trie_matcher
            // Probably easier to convert to path to ensure the matching
            // is done correctly and consistently.

            $filelist = TrieTransformer::ConvertTrieToPaths($filelist);

        }

        // Match filelist against include patterns, create filtered list
        // with matches.
        if (!empty($this->include)) {
            $filelist = $this->matchincludes($this->include, $filelist);
        }

        // Match filteredlist against exclude patterns, remove matches
        // from filteredlist, create excludedlist with those removed
        if (!empty($this->exclude)) {
            $filelist = $this->matchexcludes($this->exclude, $filelist);
        }

        return($filelist);
    }


    // }}}
    // {{{ method GetExcludedMatches(mixed _dir)

    /**
     * The negation of GetMatches
     *
     * @author  Alby Lash, alash@plateauinnovation.com
     * @access  public
     */

    function GetExcludedMatches() {
        // Filter Returns the filenames in the filelist that are excluded
        //Need $this->include_patterns, $this->exclude_patterns, $filelist
        return $excludedmatches;
    }

    // }}}



    ///////////////////////////////////////////////////////////
    //////////////////////////////
    /// PRIVATE FUNCTIONS
    //////////////////////////////
    ///////////////////////////////////////////////////////////

    // {{{ matchincludes()

    function matchincludes($includepattern, $filelist) {
        // See if arrays are a direct match or if filelist is empty
        if(($includepattern == $filelist) OR (empty($filelist))) {
            // All files match or there are no files to filter.
            return($filelist);
        }

        // Loop through the filelist.
        foreach($filelist as $value) {

            reset($includepattern);

            // Loop through the include patterns.
            foreach($includepattern as $patvalue) {

                $pattern = $patvalue;
                $file = $value;

                // Where every file gets matched
                if(preg_match('/\*\*$/', $pattern)) {
                    //Check for duplicates
                    if($this->dupcheck($file, $filteredlist)) {
                        $filteredlist[] = $file;
                    }
                } else {
                    if ($this->matchpattern($pattern, $file)) {
                        //Check for duplicates
                        if($this->dupcheck($file, $filteredlist)) {
                            $filteredlist[]=$file;
                        }
                    }
                }
            }
        }

        return($filteredlist);
    }

    // }}}
    // {{{ matchexcludes()

    function matchexcludes($excludepattern, $filteredlist) {

        // See if arrays are a direct match or the filter list is empty
        if($excludepattern == $filteredlist OR empty($filteredlist)) {
            // All files match and are filtered or nothing to filter.
            return(null);
        }

        //Evaluate each piece one at a time
        $i = 0;
        foreach($filteredlist as $file) {

            reset($excludepattern);

            foreach($excludepattern as $pattern) {

                // Where every file gets matched
                if($pattern=="**"|$pattern=="/**") {
                    unset($filteredlist[$i]);
                }

                //Match the patterns
                if($pattern != "**") {

                    if($this->matchpattern($pattern, $file)) {
                        unset($filteredlist[$i]);
                        print("\n\n\n Deleting Pattern: $pattern, unsetting $file \n\n\n");

                        //Check for duplicates
                        if($this->dupcheck($file, $this->excludelist)) {
                            $this->excludelist[]=$file;
                        }
                        break;
                    }
                }
            }
            $i++;
        }

        return($filteredlist);
    }

    // }}}
    // {{{ matchpattern()

    function matchpattern($pattern, $file) {
        $Logger =& System::GetLogger();

        // DEBUG
        $Logger->Log(PH_LOG_EVENT, "Regex::matchpattern() Pattern $pattern, File $file");

        // See if it is a direct match
        if($pattern == $file) {
            $Logger->Log(PH_LOG_EVENT, "Regex::matchpattern() direct match. $pattern==$file");
            return(true);
        }

        // See if it is a double whammy
        if($pattern=="**") {
            $Logger =& System::GetLogger();
            $Logger->Log(PH_LOG_DEBUG, "Regex::matchpattern() double whammy. $pattern=='**'");
            return(true);
        }

        // See if it has a double whammy
        if(preg_match('/(\*\*)/', $pattern)) {

            if($this->multimatcher($pattern, $file)) {

                return(true);

            } else {

                return(false);

            }

        }

        // See if it has a wildcard
        if($this->wildcheck($pattern)) {
            // Split into strings
            echo "*$pattern *\n";
            return($this->splitintostringsandmatch($pattern, $file));
        }

        //See if the pattern ends with a folder identifier
        if(preg_match('/(\/$)/', $pattern)) {
            //$Logger->Log(PH_LOG_DEBUG, "Regex::matchpattern() ends with / NO match. $pattern, $file");
            return(false);
        }

        // No match.
        return(false);
    }

    // }}}
    // {{{ splitintostringsandmatch()

    function splitintostringsandmatch($pattern, $file) {
        // Split into strings delimited by the /
        $patternstrings = preg_split("/(\/)/", $pattern, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
        $filestrings = preg_split("/(\/)/", $file, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

        $p = count($patternstrings);
        $f = count($filestrings);

        // Set the loop limit to the comparison entity with the greater number
        // of elements
        if ($f > $p) {
            $l_ = $f ;
        } else if ($f < $p) {
            $l_ = $p;
        } else if ($f==$p) {
            $l_ = $p;
        }

        for($i=0; $i<$l_; $i++)
        {
            $patternstring=$patternstrings[$i];
            $filestring=$filestrings[$i];

            // If the path identifier is a double wildcard
            if($patternstring=="**" && ($i==($p-1))) {
                return TRUE;
            }

            // Look for basic mismatch
            if(!$this->matchsection($patternstring, $filestring) && $patternstring!="*") {

                return FALSE;
            }

            // Look for basic match on the same level
            if($this->matchsection($patternstring, $filestring) && ($i==($l_-1))) {
                return TRUE;
            }

            // Look for basic match on the same level
            if($this->matchsection($patternstring, $filestring) && ($f==$p)) {

                //return TRUE;
            }

            // If the final pattern identifier is a folder
            if($patternstring=="/" && ($i==($p-1))) {
                $h=$i-1;
                // Match previous folder
                $_patternstring=$patternstrings[$h];
                $_filestring=$filestrings[$h];

                if(matchsection($_patternstring,$_filestring)) {

                    return TRUE;
                }

            }

            // Check for single wildcard
            if ($patternstring =="*" && $f == $p) {

                return TRUE;
            }

        }

        return FALSE;

    }
    // }}}
    // {{{ matchsection()
    Function matchsection($patternstring, $filestring)
    {
        // See if it is a direct match
        if($filestring==$patternstring) {
            return TRUE;
        }

        // See if it has a wildcard
        if (!$this->wildcheck($patternstring)) {
            return FALSE;
        }

        // See if the wildcard nonmatches the $filestring

        if ((!$this->matchstring($patternstring, $filestring))) {
            return FALSE;
        }

        // See if the wildcard matches the $filestring
        if (($this->matchstring($patternstring, $filestring))) {
            return TRUE;
        }

        return TRUE;

    }
    // }}}
    // {{{ matchstring()
    Function matchstring($patternstring, $filestring) {
        if($patternstring=="*") {
            return TRUE;
        }

        $patternpieces = preg_split('/(\*)|(\.)/', $patternstring, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
        $patterndelimiters = implode(")|(", $patternpieces);

        $patterndelimiters = preg_replace("/\*/", "\*", $patterndelimiters);
        $patterndelimiters = preg_replace("/\?/", "\?", $patterndelimiters);
        $patterndelimiters = preg_replace("/\./", "\.", $patterndelimiters);
        $patterndelimiters = preg_replace("/\#/", "\#", $patterndelimiters);
        //$patterndelimiters = preg_replace("/\~/", "\~", $patterndelimiters);
        $patterndelimiters = preg_replace("/\//", "\/", $patterndelimiters);
        $patterndelimiters = "/(" . $patterndelimiters . ")/";

        //echo "$patterndelimiters \n";

        $limit = count($patternpieces);
        $filepieces = preg_split($patterndelimiters, $filestring, -1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);

        //print_r($filepieces);

        $numberofpatternpieces = count($patternpieces);

        for($i=0; $i<$numberofpatternpieces; $i++) {
            $patternpiece=$patternpieces[$i];
            $filepiece=$filepieces[$i];

            if($patternpiece!=$filepiece && $patternpiece!="*" && (!preg_match('/\?/', $patternstring))) {

                return FALSE;
            }

            if($patternpiece!=$filepiece && $patternpiece!="*" && (!preg_match('/\?/', $patternstring))) {

                return FALSE;
            }

            //echo "$patternstring \n";
            if(preg_match('/\?/', $patternpiece)) {
                //echo "$filestring, $patternstring \n";

                $char1 = preg_split('//', $filepiece, -1, PREG_SPLIT_NO_EMPTY);
                $char2 = preg_split('//', $patternpiece, -1, PREG_SPLIT_NO_EMPTY);

                if(count($char1)!=count($char2)) {

                    return FALSE;
                }

                if(count($char1)>count($char2)) {
                    $i = count($char2);
                } else {
                    $i = count($char1);
                }

                for($j=0; $j<=$i; $j++) {
                    if($char1[$j]!=$char2[$j] && $char2[$j]!="?") {
                        return FALSE;
                    }
                    return TRUE;
                }
            } else {}
        }
        return TRUE;
    }
    // }}}
    // {{{ multimatcher()
    Function multimatcher($pattern, $file) {
        //echo "$pattern \n";
        $pre_path = Regex::get_pre_path($pattern);
        $new_pattern = Regex::strip_pre_path($pre_path, $pattern);
        //echo "$new_pattern \n";
        $new_file = Regex::strip_pre_path($pre_path, $file);

        //echo "$new_pattern \n$new_file \n\n";

        if(Regex::stripdoublewhammy($new_pattern, $new_file)) {
            return TRUE;
        }

        if(Regex::walkthrough($new_pattern, $new_file)) {
            return TRUE;
        }

    }
    // }}}
    // {{{ get_pre_path()
    Function get_pre_path($pattern) {
        $p_pre_path = preg_split('/\*\*/', $pattern, -1, PREG_SPLIT_NO_EMPTY);
        $pprp = $p_pre_path[0];
        $rp_pre_path = preg_replace('/\//', '\/', $pprp);
        //echo "$rp_pre_path \n";
        return $rp_pre_path;

    }
    // }}}
    // {{{ strip_pre_path()
    Function strip_pre_path($pre_path, $old_path) {

        if(!preg_match("/$rp_pre_path/", $old_path)) {
            return FALSE;
        }

        $new_path = preg_replace("/$pre_path/", "", $old_path);
        return $new_path;

    }
    // }}}
    // {{{ walkthrough()
    Function walkthrough($pattern, $file) {
        $pattern = preg_replace("/^\/\*\*\//", "/", $new_pattern);

        if($this->splitintostringsandmatch($pattern, $file)) {

            return TRUE;
        }


    }
    // }}}
    // {{{ stripdoublewhammy($pattern, $file)
    Function stripdoublewhammy($pattern, $file) {

        $pattern = preg_replace("/^\*\*\//", "", $pattern);

        //echo "$pattern, $file \n";
        if(Regex::splitintostringsandmatch($pattern, $file)) {

            return TRUE;
        }

    }
    // }}}
    // {{{ walkthrough()
    Function walkthrough($pattern, $file) {

        $pattern = preg_replace("/^\*\*\//", "", $pattern);
        //echo "$pattern, $file \n";
        if(preg_match("/(.+?\/)/", $pattern, $patmatch)) {
            $pattern_step = "$patmatch[0]";
            if(!$newer_file = Regex::matchnextdir($pattern_step, $file)) {
                return FALSE;
            }
            $pattern_step = preg_replace("/\//", "\/", $pattern_step);
            $rpattern_step = "/.*" . $pattern_step . "/";
            $newer_pattern = preg_replace($rpattern_step, "", $pattern);

            if(Regex::stripdoublewhammy($newer_pattern, $newer_file)) {
                return TRUE;
            }

            if(Regex::multimatcher($newer_pattern, $newer_file)) {
                return TRUE;
            }

        }
        elseif (preg_match("/(.*$)/", $pattern, $patmatch)) {
            $pattern_step = "$patmatch[0]";

            $newer_file = preg_replace("/(.*\/)/", "", $file);
            //echo "$pattern_step, $newer_file \n";
            if($newer_file==$pattern_step) {
                return TRUE;
            }

            if(Regex::matchstring($pattern_step, $newer_file)) {
                //echo "$pattern_step, $newer_file";
                return TRUE;
            }

            return FALSE;

        }

    }
    // }}}
    // {{{ matchnextdir()
    Function matchnextdir($pattern_step, $file) {

        //echo "$pattern_step, $file \n";
        $pattern_step = preg_replace("/\//", "\/", $pattern_step);
        $rpattern_step = "/.*" . $pattern_step . "/";

        if(!preg_match($rpattern_step, $file)) {
            return FALSE;
        }


        $newer_file = preg_replace($rpattern_step, "", $file);

        //echo "$pattern_step, $newer_file \n";

        return $newer_file;

    }





    // }}}
    // {{{ wildcheck()
    Function wildcheck($patternstring) {

        //Preg_match for a wildcard
        if(preg_match('/\*|\?/', $patternstring)) {
            return TRUE;
        }
        return FALSE;
    }
    // }}}
    // {{{ dupcheck()
    Function dupcheck($file, $filteredlist) {
        $q=count($filteredlist);

        for($r=0; $r<$q; $r++) {
            $curfile=$filteredlist[$r];
            if($curfile==$file) {
                return FALSE;
            }
        }
        return TRUE;

    }
    // }}}
    //////////////////////////

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

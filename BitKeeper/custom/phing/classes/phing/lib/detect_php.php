<?php
/* @package   phing.lib */

define ('PHP_TYPE_UNKNOWN', 		0);
define ('PHP_TYPE_MODULE', 			1);
define ('PHP_TYPE_EXECUTABLE',		2);
define ('PHP_TYPE_APACHE_STATIC',	3);

############################################
############################################
# return Apache's Confroot
function guess_apache_root () {
    $APACHE_ROOT = '';
# install-from-src redhat slackware suse

    $locations = array (0=>'/usr/local/apache');
    if (OS == 'WIN') {
        $locations = preg_replace ('/\//', '\\/', $locations);
#$tailext = preg_replace ('/\\\\/', '\\\\\\\\',$tailext);

    }




    $APACHE_ROOT=$locations[0];
    foreach ($locations as $location) {
        if (is_dir_silent ($location)) {
            $APACHE_ROOT = $location;
            break;
        }
    }
    while (1) {
        $rc = inputbox ("Specify the location of your Apache root directory", $APACHE_ROOT);
        if ($rc==false) {
            on_abort();
        }
        $APACHE_ROOT=$rc;
        if (! is_dir_silent ($APACHE_ROOT)) {
            msgbox("Directory $APACHE_ROOT does not exist.");
        } else {
            break;
        }
    }
    return ($APACHE_ROOT);
} # end of guess_apache_root()


# return PHP version
function guess_php_version() {
    $PHP_VERSION='';
    $php_module='';
    $confirm_msg = "";

    if (yesnobox("\nAre you using the Apache web server?")) {
        $APACHE_ROOT = guess_apache_root ();

        while (!file_exists("$APACHE_ROOT/conf/httpd.conf")) {
            msgbox("httpd.conf was not found in $APACHE_ROOT. Please Retry.");
            $APACHE_ROOT = guess_apache_root ();
        }

        $php_type = get_php_path($APACHE_ROOT, $php_path);

        switch ($php_type) {
        case PHP_TYPE_MODULE:
                $PHP_VERSION =find_php_ver_in($php_path);
            break;

        case PHP_TYPE_EXECUTABLE:
            $php_path = dirname($php_path);
            $str = `$php_path/php -v`;
            if(preg_match("/(\d\.\d\.\d)/",$str,$match)) {
                $PHP_VERSION = $match[1];
            }
            break;

        case PHP_TYPE_UNKNOWN:
#no php in httpd.conf file
					#$APACHE_BINROOT=str_replace("/conf","/bin", $APACHE_CONFROOT);

            $PHP_VERSION = find_php_ver_in("$APACHE_ROOT/bin/httpd");
            $php_type = PHP_TYPE_APACHE_STATIC;
        }

        if (empty($PHP_VERSION))
            $confirm_msg =
                "Failed to determine your version of Apache PHP !\n".
                "Setup will attempt to determine your PHP version from an executable.\n";

    }

# if still haven't found php version try php executable
    if (empty ($PHP_VERSION)) {
        $php_location = "/usr/local/bin";
        $php_location = inputbox($confirm_msg."Please enter the location of your php file", $php_location);

        while(!is_file_silent ("$php_location/php")) {
            $php_location = inputbox(
                                "PHP was not found in $php_location.\n".
                                "Please enter location of your php file", $php_location);
        }
        $str = `$php_location/php -v`;

        if(preg_match("/(\d\.\d\.\d)/",$str,$match)) {
            $PHP_VERSION = $match[1];
        }

    }
    return ($PHP_VERSION);
}

# find PHP version in the  file
# args: 1 - file where lookes for "PHP/x.x.x"
function find_php_ver_in($file) {
    $PHP_VERSION='';
    if (!is_file_silent($file))
        return ('');

    if (!($fd=fopen($file, "r")))
        on_error ('failed on openning $file');

    $str = '';
    while (! feof ($fd)) {
        $str .= fread ($fd, 4096);
    }
    fclose($fd);

    if(preg_match("/PHP\/(\d\.\d\.\d)/",$str,$match)) {
        $PHP_VERSION = $match[1];
    }
    return $PHP_VERSION ;
}

# return mod_php file name
# args: 1 - where to search "LoadModule php4_module ..." or Action <php-exe-path>
# return:	($type) what type of php was found and
#			($php_path) path to php module or executable
function get_php_path ($apache_root, &$php_path) {
    $type = PHP_TYPE_UNKNOWN;
    $fd = fopen ($apache_root.SLASH."conf".SLASH."httpd.conf", 'r');
#open fail

    if(!$fd) {
        return $type;
    }
    $str = "";
    while (! feof ($fd)) {
        $str = fgets ($fd, 4096);

        if(preg_match('|^\s*LoadModule php4_module\s+(.*)\s*$|',$str,$match)) {
            $php_path = str_replace("\"", "", $match[1]);
            $type = PHP_TYPE_MODULE;
        } else if (preg_match ('/^\s*Action\s+application\/x-httpd-php\s+(.*)\s*/', $str, $match)) {
            $php_path = str_replace("\"", "", $match[1]);
            $type = PHP_TYPE_EXECUTABLE;
        }
    }
    if($type !=PHP_TYPE_UNKNOWN) {
        if(!ereg('^[/\\]',$php_path))
            $php_path = $apache_root.SLASH.$php_path;
    }
    fclose($fd);
    return $type;
}

function select_php_ver($PHP_VER) {
    if (empty($PHP_VER) or (!yesnobox ("Install has detected PHP version $PHP_VER\nDoes this look correct?"))) {
        $PHP_VER = menubox (
                       "Install has failed to detect your version of PHP.\n".
                       "In order to install the correct versions of the Zend Modules\n".
                       "you must specify which PHP version you are using.",
                       array(
                           1=>'PHP 4.0.5/6',
                           2=>'PHP 4.1.x',
                           3=>'PHP 4.2.0',
                           4=>'PHP 4.2.1'));

        if($PHP_VER == false)
            on_abort();

        switch ($PHP_VER) {
        case 1:
            $PHP_VER="4.0.6";
            break;
        case 2:
            $PHP_VER="4.1.2";
            break;
        case 3:
            $PHP_VER="4.2.0";
            break;
        case 4:
            $PHP_VER="4.2.1";
            break;
        default:
            return ('');
        }
    }

    return $PHP_VER;
}
?>

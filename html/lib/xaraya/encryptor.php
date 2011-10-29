<?php
/**
 * Encryptor utility class
 *
 * @package core
 * @subpackage encryption
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marc Lutolf <mfl@netspan.ch>
**/
    class xarEncryptor extends Object
    {
        private static $instance  = null;
        public $initvector;
        public $algorithm;
        public $key;

        private function __construct()
        {
            include_once(sys::lib()."xaraya/encryption.php");            
            $this->algorithm = mcrypt_module_open($encryption['cipher'], '', $encryption['mode'], '');
//            $this->initvector = mcrypt_create_iv(mcrypt_enc_get_iv_size($this->algorithm), MCRYPT_RAND);
            $this->initvector = $encryption['initvector'];
            $keysize = mcrypt_enc_get_key_size($this->algorithm);
            $this->key = substr($encryption['key'], 0, $keysize);
        }

        public static function &instance()
        {
            if(self::$instance == null) self::$instance = new xarEncryptor();
            return self::$instance;
        }

        public function decrypt($value)
        {
            mcrypt_generic_init($this->algorithm, $this->key, $this->initvector);
            $value = mdecrypt_generic($this->algorithm, base64_decode($value));
            mcrypt_generic_deinit($this->algorithm);
            return trim($value);
        }

        public function encrypt($value=null)
        {
            mcrypt_generic_init($this->algorithm, $this->key, $this->initvector);
            $value = base64_encode(mcrypt_generic($this->algorithm, $value));
            mcrypt_generic_deinit($this->algorithm);
            return trim($value);
        }
    }

?>
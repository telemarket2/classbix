<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */


/**
 * @see Zend_Cache_Backend_Interface
 */
require_once 'Zend/Cache/Backend/Interface.php';

/**
 * @see Zend_Cache_Backend
 */
require_once 'Zend/Cache/Backend.php';


/**
 * @package    Zend_Cache
 * @subpackage Zend_Cache_Backend
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Cache_Backend_Xcache extends Zend_Cache_Backend implements Zend_Cache_Backend_Interface
{

    /**
     * Available options
     *
     * =====> (string) user :
     * xcache.admin.user (necessary for the clean() method)
     *
     * =====> (string) password :
     * xcache.admin.pass (clear, not MD5) (necessary for the clean() method)
     *
     * @var array available options
     */
    protected $_options = array(
        'user' => null,
        'password' => null,
    	'precalculate_time' =>120 // bu sure oncesinden cache yenilemek icin. 
    );
    
    /**
     * Constructor
     *
     * @param  array $options associative array of options
     * @throws Zend_Cache_Exception
     * @return void
     */
    public function __construct(array $options = array())
    {
        if (!extension_loaded('xcache')) {
            Zend_Cache::throwException('The xcache extension must be loaded for using this backend !');
        }
        parent::__construct($options);
    }

    /**
     * Test if a cache is available for the given id and (if yes) return it (false else)
     *
     * WARNING $doNotTestCacheValidity=true is unsupported by the Xcache backend
     *
     * @param  string  $id                     cache id
     * @param  boolean $doNotTestCacheValidity if set to true, the cache validity won't be tested
     * @return string cached datas (or false)
     */
    public function load($id, $doNotTestCacheValidity = false)
    {
    	if ($doNotTestCacheValidity) {
            $this->_log("Zend_Cache_Backend_Xcache::load() : \$doNotTestCacheValidity=true is unsupported by the Xcache backend");
        }
    	       	
		if (@xcache_isset($id)) 
		{
			$tmp = @xcache_get($id);
	        if (is_array($tmp)) 
	        {
	        	if( $tmp[1] + $tmp[2] - $this->_options['precalculate_time'] < time() )
	        	{
	        		// resave old result for short period and return false to repopulate
	        		// if it is repopulated it will be saved for long normal
	        		$this->save($tmp[0],$id,array(),$this->_options['precalculate_time']);
	        	}
	        	else
	        	{
		            return $tmp[0];
	        	}
	        }
	        else
	        {
	        	// save 0 for concurent requests
	        	$this->save(0,$id,array(),$this->_options['precalculate_time']);
	        }
		}
		else
		{
			// save 0 for concurent requests
			$this->save(0,$id,array(),$this->_options['precalculate_time']);
		}

		return false;
    }
    
    
  

    /**
     * Test if a cache is available or not (for the given id)
     *
     * @param  string $id cache id
     * @return mixed false (a cache is not available) or "last modified" timestamp (int) of the available cache record
     */
    public function test($id)
    {
        if (xcache_isset($id)) {
            $tmp = xcache_get($id);
            if (is_array($tmp)) {
                return $tmp[1];
            }
        }
        return false;
    }

    /**
     * Save some string datas into a cache record
     *
     * Note : $data is always "string" (serialization is done by the
     * core not by the backend)
     *
     * @param string $data datas to cache
     * @param string $id cache id
     * @param array $tags array of strings, the cache record will be tagged by each string entry
     * @param int $specificLifetime if != false, set a specific lifetime for this cache record (null => infinite lifetime)
     * @return boolean true if no problem
     */
    public function save($data, $id, $tags = array(), $specificLifetime = false)
    {
		// cache de false saklamak icin 0 kullan. yoksa falseler sanki cachelenmemis gibi davraniyor ve DByi sorguluyor.
		if($data===false){$data=0;}
	
        $lifetime = $this->getLifetime($specificLifetime);

        // save 
        $result = @xcache_set($id, array($data, time(), $lifetime), $lifetime);
		if (count($tags) > 0) 
		{
            $this->_log("Zend_Cache_Backend_Xcache::save() : tags are unsupported by the Xcache backend");
        }
        
        return $result;
    }

    /**
     * Remove a cache record
     *
     * @param  string $id cache id
     * @return boolean true if no problem
     */
    public function remove($id)
    {
        return xcache_unset($id);
    }

    /**
     * Clean some cache records
     *
     * Available modes are :
     * 'all' (default)  => remove all cache entries ($tags is not used)
     * 'old'            => remove too old cache entries ($tags is not used)
     * 'matchingTag'    => remove cache entries matching all given tags
     *                     ($tags can be an array of strings or a single string)
     * 'notMatchingTag' => remove cache entries not matching one of the given tags
     *                     ($tags can be an array of strings or a single string)
     *
     * @param  string $mode clean mode
     * @param  array  $tags array of tags
     * @return boolean true if no problem
     */
    public function clean($mode = Zend_Cache::CLEANING_MODE_ALL, $tags = array())
    {
        if ($mode==Zend_Cache::CLEANING_MODE_ALL) {
            // Necessary because xcache_clear_cache() need basic authentification
            $backup = array();
            if (isset($_SERVER['PHP_AUTH_USER'])) {
                $backup['PHP_AUTH_USER'] = $_SERVER['PHP_AUTH_USER'];
            }
            if (isset($_SERVER['PHP_AUTH_PW'])) {
                $backup['PHP_AUTH_PW'] = $_SERVER['PHP_AUTH_PW'];
            }
            if ($this->_options['user']) {
                $_SERVER['PHP_AUTH_USER'] = $this->_options['user'];
            }
            if ($this->_options['password']) {
                $_SERVER['PHP_AUTH_PW'] = $this->_options['password'];
            }
            xcache_clear_cache(XC_TYPE_VAR, 0);
            if (isset($backup['PHP_AUTH_USER'])) {
                $_SERVER['PHP_AUTH_USER'] = $backup['PHP_AUTH_USER'];
                $_SERVER['PHP_AUTH_PW'] = $backup['PHP_AUTH_PW'];
            }
            return true;
        }
        if ($mode==Zend_Cache::CLEANING_MODE_OLD) {
            $this->_log("Zend_Cache_Backend_Xcache::clean() : CLEANING_MODE_OLD is unsupported by the Xcache backend");
        }
        if ($mode==Zend_Cache::CLEANING_MODE_MATCHING_TAG) {
            $this->_log("Zend_Cache_Backend_Xcache::clean() : tags are unsupported by the Xcache backend");
        }
        if ($mode==Zend_Cache::CLEANING_MODE_NOT_MATCHING_TAG) {
            $this->_log("Zend_Cache_Backend_Xcache::clean() : tags are unsupported by the Xcache backend");
        }
    }

    /**
     * Return true if the automatic cleaning is available for the backend
     *
     * @return boolean
     */
    public function isAutomaticCleaningAvailable()
    {
        return false;
    }

}

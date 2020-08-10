<?php

class SimpleCache
{

	// 3 hour garbage clearing period
	private static $expire = 10800;
	private static $loaded = array();
	public static $drive = 'config'; // file|config
	private static $request_time = 0;

	public static function get($key)
	{
		Benchmark::cp();
		$data = false;
		$src = '';

		if (isset(self::$loaded[$key]))
		{
			$data = self::$loaded[$key];
			$src = ':mem';
		}
		else
		{

			switch (self::$drive)
			{
				case 'file':
					$files = glob(DIR_CACHE . self::formattedKey($key) . '*');

					if ($files)
					{
						$cache = file_get_contents($files[0]);
						$data = unserialize($cache);
						foreach ($files as $file)
						{
							$time = substr(strrchr($file, '.'), 1);
							if ($time < self::time())
							{
								if (file_exists($file))
								{
									unlink($file);
								}
							}
						}
					}
					break;
				case 'config':
				default:
					if ($key === 'config')
					{
						// prevent infinite loop
						//self::$loaded[$key] = $data;
						return false;
					}

					$cache = Config::option(self::formattedKey($key));
					if ($cache)
					{
						$data_pack = @unserialize($cache);
						if ($data_pack)
						{
							// we have valid unserialized $data_pack 							
							$data = $data_pack->data;
							$time = $data_pack->time;
							if ($time < self::time())
							{
								// delete old cache
								Config::optionDelete(self::formattedKey($key));
							}
						}
						else
						{
							Benchmark::cp('ERROR:SimpleCache::get(' . $key . '):strlen-' . strlen($cache) . ': unserialize not valid data from db. probably didnt fit into cinfig:value field');
						}
					}
			}


			self::$loaded[$key] = $data;
		}
		Benchmark::cp('SimpleCache::get(' . $key . ')' . $src);
		return $data;
	}

	public static function set($key, $value, $ttl = null)
	{
		Benchmark::cp();
		if (is_null($ttl) || $ttl < 1)
		{
			$ttl = self::$expire;
		}
		switch (self::$drive)
		{
			case 'file':
				self::delete($key);
				self::$loaded[$key] = $value;

				if (defined('DIR_CACHE'))
				{
// restrict direct access to cache dir for security reasons 
					FileDir::checkMakeFile(DIR_CACHE . '.htaccess', 'deny from all');

// define cache file path and name 
					$file = DIR_CACHE . self::formattedKey($key) . (self::time() + $ttl);
					FileDir::checkMakeFile($file, self::_serialize($value));
				}
				break;
			case 'config':
			default:
				if ($key !== 'config')
				{
					self::$loaded[$key] = $value;
					$data_pack = new stdClass();
					$data_pack->time = (self::time() + $ttl);
					$data_pack->data = $value;

					Config::optionSet(self::formattedKey($key), self::_serialize($data_pack), false);

					unset($data_pack);
				}
		}
		Benchmark::cp('SimpleCache::set(' . $key . ')');
	}

	private static function _serialize($data)
	{
		// clean object 
		$data = Record::cleanObject($data);

		return serialize($data);
	}

	public static function delete($key)
	{
		Benchmark::cp();

		$count_deleted_from_memory = 0;

		if (isset(self::$loaded[$key]))
		{
			unset(self::$loaded[$key]);
			$count_deleted_from_memory++;
		}

		// delete all matching keys $key + % in DB so delete them as well 	
		$arr_unset = array();
		foreach (self::$loaded as $k => $v)
		{
			if (strpos($k, $key) === 0)
			{
				// found $key + % in $k so add it to array and delete after loop ends
				$arr_unset[$k] = true;
			}
		}
		foreach ($arr_unset as $unset_key => $v)
		{
			unset(self::$loaded[$unset_key]);
		}

		$count_deleted_from_memory += count($arr_unset);
		if ($count_deleted_from_memory)
		{
			Benchmark::cp('SimpleCache::delete(' . $key . '):deleted from memory:' . $count_deleted_from_memory);
		}


		// delete from drive: DB or file 
		switch (self::$drive)
		{
			case 'file':
				// deleting by key ad means deleting 97% of cache and it is slow. so delete all cache instead
				if ($key === 'ad')
				{
					Benchmark::cp('SimpleCache::delete(' . $key . ')');
					return self::clearAll();
				}

				// get list of files
				$files = glob(DIR_CACHE . self::formattedKey($key) . '*');

				if ($files)
				{
					foreach ($files as $file)
					{
						FileDir::rmdirr($file);
						//Benchmark::cp('FileDir::rmdirr(' . $file . ')');
					}
				}
				break;
			case 'config':
			default:
				if ($key !== 'config')
				{
					Config::optionDeleteByKey(self::formattedKey($key));
				}
		}

		Benchmark::cp('SimpleCache::delete(' . $key . ')');
	}

	/**
	 * delete data cache
	 * 
	 * @param boolean $forece
	 * @return boolean
	 */
	public static function clearAll($forece = true)
	{
		// clear data cache from garbage collection every hour to keep folder small and deletes fast.
		// one day will be too big for avarage user with more than 40 category and location site.
		// wait 1 hour beteen passive calls 

		$wait = 3600;
		if ($forece || Config::option('last_SimpleCache_clearAll') < REQUEST_TIME - $wait)
		{
			// save current call time 
			Config::optionSet('last_SimpleCache_clearAll', REQUEST_TIME);

			// delete both file and config cache to clear system.
			if (defined('DIR_CACHE'))
			{
				// file cahce used, delete folder
				$return = FileDir::rmdirr(DIR_CACHE);
			}
			// db cache used delete db
			Config::optionDeleteByKey('cache.');
			$return = true;

			self::$loaded = array();
			Benchmark::cp('SimpleCache::clearAll');
		}

		return $return;
	}

	public static function uniqueKey()
	{
		$args = func_get_args();
		return md5(serialize($args));
	}

	private static function formattedKey($key)
	{
		return 'cache.' . preg_replace('/[^A-Z0-9\._-]/i', '', $key) . '.';
	}

	private static function time()
	{
		if (!self::$request_time)
		{
			self::$request_time = time();
		}
		return self::$request_time;
	}

}

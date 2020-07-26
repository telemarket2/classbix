<?php

/**
 * ClassiBase Classifieds Script
 *
 * ClassiBase Classifieds Script by Vepa Halliyev is licensed under a Creative Commons Attribution-Share Alike 3.0 License.
 *
 * @package		ClassiBase Classifieds Script
 * @author		Vepa Halliyev
 * @copyright	Copyright (c) 2009, Vepa Halliyev, veppa.com.
 * @license		http://classibase.com
 * @link		http://classibase.com
 * @since		Version 1.0
 * @filesource
 */

/**
 * class AdSearch
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class AdSearch extends Record
{

	const TABLE_NAME = 'ad_search';

	private static $cols = array(
		'id'		 => 1,
		'idhash'	 => 1,
		'paramhash'	 => 1,
		'term'		 => 1,
		'params'	 => 1,
		'results'	 => 1,
		'meta'		 => 1,
		'cnt'		 => 1,
		'added_at'	 => 1,
		'updated_at' => 1
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	public static function search($params)
	{
		// check for query term 'q' if set then perform basic search and store it 
	}
}

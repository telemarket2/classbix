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
 * class Category
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class CategoryDescription extends Record
{

	const TABLE_NAME = 'category_description';

	private static $cols = array(
		'category_id'	 => 1,
		'language_id'	 => 1,
		'name'			 => 1,
		'description'	 => 1,
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	function beforeInsert()
	{
		// trim name 
		$this->name = TextTransform::removeSpacesNewlines($this->name);
		if (!strlen($this->name))
		{
			$this->name = __('no name');
		}

		if (!strlen($this->description))
		{
			$this->description = '';
		}
		return true;
	}

}

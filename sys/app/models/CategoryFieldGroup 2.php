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
 * class CategoryFieldGroup
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class CategoryFieldGroup extends Record
{

	const TABLE_NAME = 'category_field_group';

	private static $opened_group = null;
	private static $opened_group_last_close = null;
	private static $cols = array(
		'id'	 => 1,
		'space'	 => 1// used to make updating work properly
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	function beforeDelete()
	{
		// delete descriptions
		CategoryFieldGroupDescription::deleteWhere('CategoryFieldGroupDescription', 'cfg_id=?', array($this->id));

		// remove category relation
		CategoryFieldRelation::update('CategoryFieldRelation', array('group_id' => 0), "group_id=?", array($this->id));

		return true;
	}

	function afterInsert()
	{
		$return = $this->updateDescription();

		return $return;
	}

	function afterUpdate()
	{
		$return = $this->updateDescription();

		return $return;
	}

	function afterDelete()
	{
		return true;
	}

	public static function appendRelatedCategories($catfieldgroup)
	{
		// get relation 
		$catfieldgroup->CategoryFieldRelation = CategoryFieldRelation::findAllFrom('CategoryFieldRelation', "group_id=? GROUP BY location_id,category_id", array($catfieldgroup->id));

		// append categories 
		Category::appendCategory($catfieldgroup->CategoryFieldRelation);

		// append locations
		Location::appendLocation($catfieldgroup->CategoryFieldRelation);
	}

	/**
	 * used for formatting custom fields in group. call it before outputing every field value. at the en of loop call htmlGroupClose
	 * 
	 * @param object $group
	 * @param string $html_open with replaceble values {id},{name}
	 * @param string $html_close
	 * @return string 
	 */
	public static function htmlGroupOpen($group, $html_open = '<div class="post_custom_group"><h4>{name}</h4>', $html_close = '</div>')
	{
		$return = '';
		if (self::$opened_group != $group)
		{
			if (!is_null(self::$opened_group))
			{
				$return .= $html_close;
			}
			if (!is_null($group))
			{
				$return .= str_replace(array('{name}', '{id}'), array(CategoryFieldGroup::getName($group), $group->id), $html_open);
				self::$opened_group_last_close = $html_close;
			}
			else
			{
				self::$opened_group_last_close = '';
			}

			self::$opened_group = $group;
		}

		return $return;
	}

	/**
	 * call it after using htmlGroupOpen to reset values and echo last closing 
	 * @return type 
	 */
	public static function htmlGroupClose()
	{
		// get last close tags
		$html_close = self::$opened_group_last_close;

		// reset croup vals
		self::$opened_group = self::$opened_group_last_close = null;

		return $html_close;
	}

	function updateDescription()
	{
		// save description if set
		if ($this->CategoryFieldGroupDescription && $this->id)
		{
			foreach ($this->CategoryFieldGroupDescription as $cd)
			{
				$cd->cfg_id = $this->id;
				// delete description first
				CategoryFieldGroupDescription::deleteWhere('CategoryFieldGroupDescription', 'cfg_id=? AND language_id=?', array($cd->cfg_id, $cd->language_id));
				$cd->save('new_id');
			}
		}

		return true;
	}

	function setFromData($data)
	{
		parent::setFromData($data);

		if ($this->cfg_description)
		{
			foreach ($this->cfg_description as $lng => $cd)
			{
				// add new description 
				$cd['cfg_id'] = $this->id;
				$cd['language_id'] = $lng;
				$this->CategoryFieldGroupDescription[$lng] = new CategoryFieldGroupDescription($cd);
			}
		}

		unset($this->cfg_description);
	}

	public static function getName($cgf)
	{
		return $cgf->CategoryFieldGroupDescription->name;
	}

	public static function getNameByLng($cgf, $lng = '')
	{
		if (!strlen($lng))
		{
			$lng = Language::getDefault();
		}

		return $cgf->CategoryFieldGroupDescription[$lng]->name;
	}

	public static function appendName($cfg)
	{
		CategoryFieldGroupDescription::appendObject($cfg, 'id', 'CategoryFieldGroupDescription', 'cfg_id', '', MAIN_DB, '*', false, false, "language_id=" . self::quote(I18n::getLocale()) . " AND ");
	}

	public static function appendWithDescription($records, $field = 'group_id')
	{
		$records = Record::checkMakeArray($records);

		$continue = false;
		foreach ($records as $r)
		{
			if ($r->group_id > 0)
			{
				$continue = true;
				break;
			}
		}

		if (!$continue)
		{
			return false;
		}

		// append adfields
		CategoryFieldGroup::appendObject($records, $field, 'CategoryFieldGroup');

		// appedn names
		$cfg = array();
		foreach ($records as $r)
		{
			if ($r->CategoryFieldGroup)
			{
				$cfg[] = $r->CategoryFieldGroup;
			}
		}

		// append adfield description
		CategoryFieldGroup::appendName($cfg);
	}

}

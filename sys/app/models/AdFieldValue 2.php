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
 * class AdFieldValue stores multiple values for adfields with type dropdown, checkbox, radio. 
 * Value names are stored in AdFieldValueDescription with language id
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class AdFieldValue extends Record
{

	const TABLE_NAME = 'ad_field_value';

	private static $arr_afv_by_name = array();
	private static $cols = array(
		'id' => 1,
		'af_id' => 1,
		'pos' => 1,
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}
	
	public function __destruct()
	{
		unset($this->AdFieldValueDescription);
	}

	function beforeDelete()
	{
		// delete related descriptions
		AdFieldValueDescription::deleteWhere('AdFieldValueDescription', 'afv_id=?', array($this->id));
		return true;
	}

	function afterInsert()
	{
		return $this->updateDescription();
	}

	function afterUpdate()
	{
		return $this->updateDescription();
	}

	public static function getName($afv, $field = 'name')
	{
		return $afv->AdFieldValueDescription->{$field};
	}

	public static function getNameByLng($afv, $lng = '', $field = 'name')
	{
		if(!strlen($lng))
		{
			$lng = Language::getDefault();
		}

		return $afv->AdFieldValueDescription[$lng]->{$field};
	}

	public static function deleteByAfId($af_id, $clear_values = false)
	{
		if($af_id)
		{
			// get all values 
			$afv = AdFieldValue::findAllFrom('AdFieldValue', 'af_id=?', array($af_id));
			foreach($afv as $_afv)
			{
				$_afv->delete();
			}

			if($clear_values)
			{
				// FIXME : update stored value ids with actual values
				// because after this they will have integer values representing related value ids only which is meaningless. 
				// --------    or   --------
				// clear all associated field values
				// never delete any value. because it deletes even if field name updated or translated
				// AdFieldRelation::Update('AdFieldRelation', array('val' => ''), "field_id=?", array($af_id));
			}
		}


		return true;
	}

	public static function checkMakeByAfValue($af_id, $name, $lng = null)
	{
		$name = trim($name);
		if(strlen($name) < 1)
		{
			// skip empty values 
			return false;
		}

		$languages = Language::getLanguages();

		if(!isset(self::$arr_afv_by_name[$af_id][$name]))
		{
			$afv = AdFieldValue::findByName($name, $af_id, $lng);

			if(!$afv)
			{
				// add AdFieldValue 
				$afv = new AdFieldValue();
				$afv->af_id = $af_id;

				foreach($languages as $lng_obj)
				{
					$cd = new AdFieldValueDescription();
					$cd->language_id = $lng_obj->id;
					$cd->name = $name;

					$afv->AdFieldValueDescription[$lng_obj->id] = $cd;
				}
				$afv->save();
			}
			self::$arr_afv_by_name[$af_id][$name] = $afv;
		}
		$afv = self::$arr_afv_by_name[$af_id][$name];


		return $afv;
	}

	/**
	 * Find if given value defined for given AdField
	 * 
	 * @param string $name
	 * @param int $af_id AdField id
	 * @param string $lng
	 * @return AdFieldValue | boolean flase
	 */
	public static function findByName($name, $af_id, $lng = null)
	{
		if(!$lng)
		{
			$lng = Language::getDefault();
		}

		$where = "afv.af_id=? AND afvd.name=? AND afvd.language_id=?";
		$vals = array($af_id, $name, $lng);

		$sql = "SELECT afv.* 
			FROM " . AdFieldValue::tableNameFromClassName('AdFieldValue') . " afv 
			LEFT JOIN " . AdFieldValueDescription::tableNameFromClassName('AdFieldValueDescription') . " afvd ON(afv.id=afvd.afv_id)
				WHERE " . $where;

		$afvs = Category::query($sql, $vals);
		if($afvs)
		{
			return $afvs[0];
		}

		return false;
	}

	function updateDescription()
	{
		// save description if set
		if($this->AdFieldValueDescription)
		{
			foreach($this->AdFieldValueDescription as $afvd)
			{
				if(!strlen($afvd->name))
				{
					// all languages should have some value 
					// delete this value and return false
					AdFieldValueDescription::deleteWhere('AdFieldValueDescription', 'afv_id=?', array($afvd->afv_id));
					return false;
				}
				$afvd->afv_id = $this->id;
				// delete description first
				AdFieldValueDescription::deleteWhere('AdFieldValueDescription', 'afv_id=? AND language_id=?', array($afvd->afv_id, $afvd->language_id));
				$afvd->save('new_id');
			}

			// value saved for all langueges
			return true;
		}

		return false;
	}

}


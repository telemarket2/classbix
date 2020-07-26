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
 * class AdFieldRelation
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class AdFieldRelation extends Record
{

	const TABLE_NAME = 'ad_field_relation';

	private static $cols = array(
		'ad_id'		 => 1,
		'field_id'	 => 1,
		'val'		 => 1,
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	/**
	 * Save given custom field value, delete if saving empty string
	 * 
	 * @param type $ad_id
	 * @param type $field_id
	 * @param type $val
	 * @return type 
	 */
	public static function saveVal($ad_id, $field_id, $val = '')
	{
		if (!strlen($val))
		{
			// delete value and do not set anything
			return self::deleteWhere('AdFieldRelation', 'ad_id=? AND field_id=?', array($ad_id, $field_id));
		}
		else
		{

			$sql = "INSERT INTO " . self::tableNameFromClassName('AdFieldRelation') . "
			(ad_id,field_id,val) VALUES (?,?,?) ON DUPLICATE KEY UPDATE val=?";

			return self::query($sql, array($ad_id, $field_id, $val, $val));
		}
	}

	/**
	 * convert given value to object for making it ready to save later after saving ad.
	 * 
	 * @param AdField $AdField
	 * @param Ad $ad
	 * @param string $val
	 * @param bool $by_value if true will convert value to AdFieldValue->id, used when importing data from other sites
	 */
	public static function prepareVal($AdField, $ad, $val = null, $by_value = false, $convert = true)
	{
		switch ($AdField->type)
		{
			case AdField::TYPE_CHECKBOX:
				// validate  checkbox
				if ($val)
				{
					$valid_id = array();
					foreach ($val as $afv_id)
					{
						// convert value to id
						if ($by_value)
						{
							$afv_id = self::prepareVal2AfvId($AdField->AdFieldValue, $afv_id);
						}

						if (isset($AdField->AdFieldValue[$afv_id]))
						{
							$valid_id[$afv_id] = $afv_id;
						}
					}
					if ($valid_id)
					{
						$val = ';' . implode(';', $valid_id) . ';';
					}
				}
				break;
			case AdField::TYPE_RADIO:
			case AdField::TYPE_DROPDOWN:
				// convert value to id
				if ($by_value)
				{
					$val = self::prepareVal2AfvId($AdField->AdFieldValue, $val);
				}
				// validate redio,  dropdown
				// radio and dropdown should have at least one value that exists in definition
				if (!isset($AdField->AdFieldValue[$val]))
				{
					$val = '';
				}
				break;
			case AdField::TYPE_NUMBER:
			case AdField::TYPE_PRICE:
				// convert value to number
				if (strlen($val))
				{
					if ($convert)
					{
						// remove space and comma seperators, leave only number 
						if ($AdField->type == AdField::TYPE_PRICE)
						{
							$val = AdField::stringToFloat($val);
						}
						else
						{
							$val = preg_replace("/[^-0-9\.]/", "", $val);
							$val = intval($val);
						}

						if ($val == 0)
						{
							$val = '';
						}
					}
					else
					{
						$val = Input::getInstance()->xss_clean(trim(strip_tags($val)));
					}
				}
				break;
			case AdField::TYPE_VIDEO_URL:
			case AdField::TYPE_TEXT:
			case AdField::TYPE_ADDRESS:
			case AdField::TYPE_URL:
			case AdField::TYPE_EMAIL:
			default:
				$val = Input::getInstance()->xss_clean(trim(strip_tags($val)));
		}


		$afr = new AdFieldRelation();
		$afr->val = $val;
		$afr->ad_id = $ad->id;
		$afr->field_id = $AdField->id;
		$afr->_field_type = $AdField->type;

		$ad->AdFieldRelation[$AdField->id] = $afr;
	}

	/**
	 * Convert pure value to AdFieldValue->id
	 * 
	 * @param array $arr_afv AdFieldValue
	 * @param string $value
	 * @return null|int
	 */
	private static function prepareVal2AfvId($arr_afv, $value)
	{
		foreach ($arr_afv as $afv)
		{
			if ($afv->AdFieldValueDescription->name == $value)
			{
				return $afv->id;
			}
		}

		return null;
	}

	public static function defineValidationRules($catfields, & $rules, & $fields)
	{
		// add custom field validation
		if ($catfields)
		{
			foreach ($catfields as $cf)
			{
				$af_id = $cf->adfield_id;
				$name = 'cf[' . $af_id . ']';
				$rules[$name] = 'trim|strip_tags|xss_clean';
				$fields[$name] = AdField::getName($cf->AdField);

				switch ($cf->AdField->type)
				{
					case AdField::TYPE_CHECKBOX:
						// validate  checkbox
						if ($_POST['cf'][$af_id])
						{
							foreach ($_POST['cf'][$af_id] as $afv_id)
							{
								if (!isset($cf->AdField->AdFieldValue[$afv_id]))
								{
									// posted not existing value then return error
									Validation::getInstance()->set_error(__('{name} is not defined.', array('{name}' => $fields[$name])), $name);
								}
							}
						}
						break;
					case AdField::TYPE_RADIO:
					case AdField::TYPE_DROPDOWN:
						// validate redio,  dropdown
						// radio and dropdown should have at least one value that exists in definition
						$afv_id = $_POST['cf'][$af_id];
						if (!isset($cf->AdField->AdFieldValue[$afv_id]))
						{
							// posted not existing value then return error
							Validation::getInstance()->set_error(__('{name} is not defined.', array('{name}' => $fields[$name])), $name);
						}
						break;
					case AdField::TYPE_NUMBER:
						$val = $_POST['cf'][$af_id];
						if (strlen(trim($val)))
						{
							$rules[$name] .= '|numeric';
						}
						break;
					case AdField::TYPE_PRICE:
						// convert price to float 
						$_POST['cf'][$af_id] = AdField::stringToFloat($_POST['cf'][$af_id]);

						$val = $_POST['cf'][$af_id];
						if (strlen(trim($val)))
						{
							$rules[$name] .= '|numeric';
						}
						break;
					case AdField::TYPE_URL:
						$val = $_POST['cf'][$af_id];
						if (strlen(trim($val)))
						{
							$rules[$name] .= '|valid_url';
						}
						break;
					case AdField::TYPE_VIDEO_URL:
						$val = $_POST['cf'][$af_id];
						if (strlen(trim($val)))
						{
							$rules[$name] .= '|callback_VideoUrl::validation_isValid';
						}
						break;
					case AdField::TYPE_EMAIL:
						$val = $_POST['cf'][$af_id];
						if (strlen(trim($val)))
						{
							$rules[$name] .= '|strtolower|valid_email';
						}
						break;
				}
			}
		}
	}

	/**
	 * this will append all fields to ad with field type
	 * It is used to get field by type, for example price regardless if it is listed or searchable
	 * It will check if AdFieldRelation appende to first Ad then will not append it 
	 * It will check if AdField appende to first Ad->AdFieldRelation then will not append it 
	 * 
	 * @param array $ads Ad
	 * @param array $type string
	 */
	public static function appendAllFields($ads = array())
	{
		$ads = Record::checkMakeArray($ads);
		$_ads = array();

		foreach ($ads as $ad)
		{
			// seelect ads that have not appended
			if (!is_array($ad->AdFieldRelation))
			{
				$_ads[$ad->id] = $ad;
			}
		}

		if ($_ads)
		{
			// append all AdFieldRelation values 
			AdFieldRelation::appendObject($_ads, 'id', 'AdFieldRelation', 'ad_id', '', MAIN_DB, '*', false, 'field_id');

			// now loop and add empty array AdFieldRelation to those ads which dont have any 
			// this is done to not appending it next time when requested
			foreach ($_ads as $ad)
			{
				if (!is_array($ad->AdFieldRelation))
				{
					$ad->AdFieldRelation = array();
				}
			}
		}
	}

	/**
	 * Get first price custom field from ad
	 * @param Ad $ad
	 * @param bool $format
	 * @return string
	 */
	public static function getPrice($ad, $format = true)
	{
		return self::getByType($ad, 'price', $format);
	}

	/**
	 * Get first custom field of ad that matches given AdField->type  
	 * 
	 * @param Ad $ad
	 * @param string $type
	 * @param bool $format
	 * @param bool $first return first value as string or all values as array
	 * @return string
	 */
	public static function getByType($ad, $type, $format = true, $first = true)
	{
		return self::getBy($ad, 'type', $type, $format, $first);
	}

	/**
	 * Get first custom field of ad that matches given AdField->id 
	 * 
	 * @param Ad $ad
	 * @param string $type
	 * @param bool $format
	 * @param bool $first return first value as string or all values as array
	 * @return string
	 */
	public static function getByAdFieldId($ad, $af_id, $format = true, $first = true)
	{
		return self::getBy($ad, 'id', $af_id, $format, $first);
	}

	/**
	 * Get first custom field of ad that matches given field and field value  
	 * 
	 * @param Ad $ad
	 * @param string $field one of fields of AdField : id,type
	 * @param string $field_val
	 * @param bool $format
	 * @param bool $first return first value as string or all values as array
	 * @return string
	 */
	public static function getBy($ad, $field, $field_val, $format = true, $first = true)
	{
		if ($first)
		{
			$return = '';
		}
		else
		{
			$return = array();
		}

		// append custom field in case it is not appended already 
		AdFieldRelation::appendAllFields($ad);

		if (isset($ad->AdFieldRelation))
		{
			foreach ($ad->AdFieldRelation as $afr)
			{
				// get adfield 
				$adField = AdField::getAdFieldFromTree($afr->field_id);

				if ($adField->{$field} == $field_val)
				{
					$return_one = $afr->val;
					if ($format)
					{
						$return_one = Ad::formatCustomValue($adField, $return_one);
					}
					if ($first)
					{
						return $return_one;
					}
					else
					{
						$return[] = $return_one;
					}
				}
			}
		}

		return $return;
	}

	/**
	 * Clean unlinked ad|af from afr table
	 * 
	 * @param string $table ad|af
	 */
	static public function cleanUnlinked($table = 'ad')
	{
		// wait 30 days because it is very slow query. for 70k ads it takes 3s. and usually nothing to delete.
		$wait = 3600 * 24 * 30;
		// remove unlinked data
		switch ($table)
		{
			case 'ad':
				if (Config::option('last_afr_cleanUnlinked_ad') < REQUEST_TIME - $wait + 15)
				{
					// save current call time 
					Config::optionSet('last_afr_cleanUnlinked_ad', REQUEST_TIME);

					// delete AdFieldRelation for non existing Ad
					$sql = "DELETE afr 
						FROM " . AdFieldRelation::tableNameFromClassName('AdFieldRelation') . " afr
						LEFT JOIN " . Ad::tableNameFromClassName('Ad') . " ad ON(ad.id=afr.ad_id)
						WHERE ad.id is NULL";
					Record::query($sql);
				}
				break;
			case 'af':
				if (Config::option('last_afr_cleanUnlinked_af') < REQUEST_TIME - $wait + 88)
				{
					// save current call time 
					Config::optionSet('last_afr_cleanUnlinked_af', REQUEST_TIME);

					// delete AdFieldRelation for non existing AdField
					$sql = "DELETE afr 
						FROM " . AdFieldRelation::tableNameFromClassName('AdFieldRelation') . " afr
						LEFT JOIN " . AdField::tableNameFromClassName('AdField') . " af ON(af.id=afr.field_id)
						WHERE af.id is NULL";
					Record::query($sql);
				}
				break;
		}
	}

}

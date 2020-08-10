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
 * class AdField
 * These are fields that can be attached to all ads by default or by category and location
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class AdField extends Record
{

	const TABLE_NAME = 'ad_field';

	/* define types here to find all related usages */
	const TYPE_NUMBER = 'number';
	const TYPE_PRICE = 'price';
	const TYPE_TEXT = 'text';
	const TYPE_ADDRESS = 'address';
	const TYPE_URL = 'url';
	const TYPE_EMAIL = 'email';
	const TYPE_CHECKBOX = 'checkbox';
	const TYPE_RADIO = 'radio';
	const TYPE_DROPDOWN = 'dropdown';
	const TYPE_VIDEO_URL = 'video_url';

	private static $arr_af_by_name = array();
	private static $arr_tree;
	private static $cols = array(
		'id'		 => 1,
		'type'		 => 1, // text, dropdown, checkbox, radiobutton, number, price, 
		'added_at'	 => 1,
		'added_by'	 => 1,
	);

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	public function __destruct()
	{
		unset($this->AdFieldDescription);
		unset($this->AdFieldValue);
	}

	function beforeInsert()
	{
		$this->added_at = REQUEST_TIME;
		$this->added_by = AuthUser::$user->id;

		return true;
	}

	function beforeDelete()
	{
		// delete category relation
		CategoryFieldRelation::deleteWhere('CategoryFieldRelation', "adfield_id=?", array($this->id));

		// delete ad relation
		AdFieldRelation::deleteWhere('AdFieldRelation', "field_id=?", array($this->id));

		// delete descriptions
		AdFieldDescription::deleteWhere('AdFieldDescription', 'af_id=?', array($this->id));

		// delete connected values 
		AdFieldValue::deleteByAfId($this->id);

		return true;
	}

	function afterInsert()
	{
		$return = $this->updateValueAndDescription();
		if ($return)
		{
			self::_clearCache();
		}
		return $return;
	}

	function afterUpdate()
	{
		$return = $this->updateValueAndDescription();
		if ($return)
		{
			self::_clearCache();
		}
		return $return;
	}

	function afterDelete()
	{
		self::_clearCache();
		return true;
	}

	public static function appendRelatedCategories($adfield)
	{
		// get relation 
		$adfield->CategoryFieldRelation = CategoryFieldRelation::findAllFrom('CategoryFieldRelation', "adfield_id=? ORDER BY location_id,category_id", array($adfield->id));

		// append categories 
		Category::appendCategory($adfield->CategoryFieldRelation);

		// append locations
		Location::appendLocation($adfield->CategoryFieldRelation);
	}

	public static function countRelatedAds($adfield_id)
	{
		return AdFieldRelation::countFrom('AdFieldRelation', "field_id=?", array($adfield_id));
	}

	public static function getTypes()
	{
		return array(
			self::TYPE_NUMBER	 => __('Number'),
			self::TYPE_PRICE	 => __('Price'),
			self::TYPE_TEXT		 => __('Text'),
			self::TYPE_VIDEO_URL => __('Video url'),
			self::TYPE_ADDRESS	 => __('Address'),
			self::TYPE_URL		 => __('URL'),
			self::TYPE_EMAIL	 => __('Email'),
			self::TYPE_CHECKBOX	 => __('Checkbox'),
			self::TYPE_RADIO	 => __('Radio'),
			self::TYPE_DROPDOWN	 => __('Dropdown')
		);
	}

	public static function selectBox($selected_id = 0, $name = 'type', $is_edit = true)
	{
		$types = self::getTypes();

		if ($is_edit)
		{
			// if editing then only some types can be changed to similar types
			// this is because dropdown, checkbox, radio stores AdFieldValue->id in AdFieldRelation->val
			$types_ = array();
			switch ($selected_id)
			{
				case self::TYPE_NUMBER:
				case self::TYPE_PRICE:
				case self::TYPE_TEXT:
				case self::TYPE_ADDRESS:
				case self::TYPE_URL:
				case self::TYPE_EMAIL:
				case self::TYPE_VIDEO_URL:
					$types_[self::TYPE_NUMBER] = $types[self::TYPE_NUMBER];
					$types_[self::TYPE_PRICE] = $types[self::TYPE_PRICE];
					$types_[self::TYPE_TEXT] = $types[self::TYPE_TEXT];
					$types_[self::TYPE_ADDRESS] = $types[self::TYPE_ADDRESS];
					$types_[self::TYPE_URL] = $types[self::TYPE_URL];
					$types_[self::TYPE_EMAIL] = $types[self::TYPE_EMAIL];
					$types_[self::TYPE_VIDEO_URL] = $types[self::TYPE_VIDEO_URL];
					break;
				case self::TYPE_RADIO:
				case self::TYPE_DROPDOWN:
					$types_[self::TYPE_RADIO] = $types[self::TYPE_RADIO];
					$types_[self::TYPE_DROPDOWN] = $types[self::TYPE_DROPDOWN];
					$types_[self::TYPE_CHECKBOX] = $types[self::TYPE_CHECKBOX];
					break;
			}
		}
		else
		{
			$types_ = $types;
		}

		if ($types_)
		{
			foreach ($types_ as $k => $v)
			{
				$options .= '<option value="' . $k . '">' . $k . ' - ' . View::escape($v) . '</option>';
			}

			// define selected type
			$options = str_replace('value="' . $selected_id . '"', 'value="' . $selected_id . '" selected="selected"', $options);


			return '<select name="' . $name . '" id="' . $name . '">' . $options . '</select>';
		}
		else
		{
			return $selected_id . ' - ' . View::escape($types[$selected_id])
					. '<input type="hidden" name="' . $name . '" id="' . $name . '" value="' . View::escape($selected_id) . '" />';
		}
	}

	public static function getType($adfield)
	{
		$types = self::getTypes();
		if (isset($types[$adfield->type]))
		{
			return $types[$adfield->type];
		}
		else
		{
			return $adfield->type;
		}
	}

	/**
	 * Generate form field for given CategoryCustomField
	 * 
	 * @param CategoryFieldRelation $cf
	 * @param string $val
	 * @param bool|string $is_search if for search form values [old type bool: (false),true] [new type str:input,search,hidden,hidden_search] in new style used only hidden for editing item. don't use anywhere else. always use js version in widget, new item forms.
	 * @param string $prepend_to_id
	 * @return string 
	 */
	public static function htmlField($cf, $val = '', $is_search = 'input', $prepend_to_id = '')
	{
		// convert is_search to new type string 
		if ($is_search === true)
		{
			$is_search = 'search';
		}
		elseif ($is_search === false)
		{
			$is_search = 'input';
		}

		// use old types to support old themes
		if (Theme::versionSupport(Theme::VERSION_SUPPORT_HTML5INPUT))
		{
			// new types 
			$arr_types = array(
				'number' => 'number',
				'url'	 => 'url',
				'email'	 => 'email'
			);
		}
		else
		{
			// old types 
			$arr_types = array(
				'number' => 'text',
				'url'	 => 'text',
				'email'	 => 'text'
			);
		}
		// define common values 
		$name = 'cf[' . $cf->adfield_id . ']';
		$id = $prepend_to_id . $name;
		$af_id = $cf->adfield_id;
		if ($is_search === 'search' || $is_search === 'hidden_search')
		{
			$val = ($_GET['cf'][$af_id]);
		}

		$return = '';
		switch ($cf->AdField->type)
		{
			case self::TYPE_NUMBER:
				$type = $arr_types['number'];
				// add to field for number range
				switch ($is_search)
				{
					case 'search':
						$val_from = View::escape(trim($_GET['cf'][$af_id]['from']));
						$val_to = View::escape(trim($_GET['cf'][$af_id]['to']));

						// do not use [from] for firt element id to make it selectable by label
						$return = '<span class="inline_group">'
								. '<input type="' . $type . '" name="' . $name . '[from]" id="' . $id . '" value="' . $val_from . '" class="from" /> '
								. __('to')
								. ' <input type="' . $type . '" name="' . $name . '[to]" id="' . $id . '[to]" value="' . $val_to . '" class="to" aria-label="' . View::escape(__('to')) . '" />'
								. '</span>';
						// append unit if set 
						$return .= View::escape(AdField::getName($cf->AdField, 'val'));
						break;
					case 'hidden_search':
						$val_from = View::escape(trim($_GET['cf'][$af_id]['from']));
						if (strlen($val_from))
						{
							$return = '<input type="hidden" name="' . $name . '[from]" value="' . $val_from . '"/>';
						}

						$val_to = View::escape(trim($_GET['cf'][$af_id]['to']));
						if (strlen($val_to))
						{
							$return = '<input type="hidden" name="' . $name . '[to]" value="' . $val_to . '"/>';
						}
						break;
					case 'hidden':
						if (strlen($val))
						{
							$return = '<input type="hidden" name="' . $name . '" value="' . View::escape($val) . '"/>';
						}
						break;
					case 'input':
					default:
						$return = '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" value="' . View::escape($val) . '"/>';
						// append unit if set 
						$return .= View::escape(AdField::getName($cf->AdField, 'val'));
						break;
				}
				break;
			case self::TYPE_PRICE:
				// add to field for price range
				$type = $arr_types['number'];
				switch ($is_search)
				{
					case 'search':
						$val_from = AdField::stringToFloat($_GET['cf'][$af_id]['from']);
						$val_to = AdField::stringToFloat($_GET['cf'][$af_id]['to']);
						if ($val_from == 0)
						{
							$val_from = '';
						}
						if ($val_to == 0)
						{
							$val_to = '';
						}

						// do not use [from] for firt element id to make it selectable by label
						$return = '<span class="inline_group">'
								. '<input type="' . $type . '" name="' . $name . '[from]" id="' . $id . '" value="' . $val_from . '" class="from" /> '
								. __('to')
								. ' <input type="' . $type . '" name="' . $name . '[to]" id="' . $id . '[to]" value="' . $val_to . '"  class="to" aria-label="' . View::escape(__('to')) . '" />'
								. '</span>';
						// add currency
						$return .= View::escape(Config::option('currency'));
						break;
					case 'hidden_search':
						$val_from = AdField::stringToFloat($_GET['cf'][$af_id]['from']);
						$val_to = AdField::stringToFloat($_GET['cf'][$af_id]['to']);
						if ($val_from == 0)
						{
							$val_from = '';
						}
						if ($val_to == 0)
						{
							$val_to = '';
						}

						if (strlen($val_from))
						{
							$return = '<input type="hidden" name="' . $name . '[from]" value="' . $val_from . '" /> ';
						}
						if (strlen($val_to))
						{
							$return = '<input type="hidden" name="' . $name . '[to]" value="' . $val_to . '" /> ';
						}
						break;
					case 'hidden':
						$val = AdField::stringToFloat($val);
						if ($val == 0)
						{
							$val = '';
						}
						if (Strlen($val))
						{
							$return = '<input type="hidden" name="' . $name . '" value="' . $val . '" />';
						}
						break;
					case 'input':
					default:
						$val = AdField::stringToFloat($val);
						if ($val == 0)
						{
							$val = '';
						}
						$return = '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" value="' . $val . '" />';
						// add currency
						$return .= View::escape(Config::option('currency'));
						break;
				}
				break;
			case self::TYPE_CHECKBOX:
				switch ($is_search)
				{
					case 'search':
					case 'hidden_search':
						$saved_vals = $val;
						break;
					case 'hidden':
					case 'input':
					default:
						// parse saved values 
						$saved_vals = self::parseVals($val, true);
						break;
				}

				switch ($is_search)
				{
					case 'hidden':
					case 'hidden_search':
						// show only checked checkboxes as hidden 
						foreach ($cf->AdField->AdFieldValue as $k => $v)
						{
							$_name = $name . '[' . $k . ']';

							// if saved value then show
							if ($saved_vals[$k])
							{
								$return .= '<input type="hidden" name="' . $_name . '" value="' . View::escape($k) . '" /> ';
							}
						}
						break;
					case 'search':
					case 'input':
					default:
						// show checkboxes with labels
						// parse availeble values
						foreach ($cf->AdField->AdFieldValue as $k => $v)
						{
							$_name = $name . '[' . $k . ']';
							$id = $prepend_to_id . $_name;

							// if saved value then check
							if ($saved_vals[$k])
							{
								$checked = 'checked="checked"';
							}
							else
							{
								$checked = '';
							}

							$return .= '<label for="' . $id . '"> 
							<input type="checkbox" name="' . $_name . '" id="' . $id . '" 
								value="' . View::escape($k) . '" ' . $checked . ' /> ' .
									View::escape(AdFieldValue::getName($v)) . '</label> ';
						}
						if ($return)
						{
							$return = '<span class="adfield_checkbox">' . $return . '</span>';
						}
						break;
				}



				break;
			case self::TYPE_RADIO:
				// parse availeble values
				$first_radio = 'checked="checked"';
				$val = trim($val);


				if ($is_search === 'search')
				{
					// add all field for search form 
					$_name = $name . '[all]';
					$id = $prepend_to_id . $_name;
					$checked = $first_radio;
					$return .= '<label for="' . $id . '"> '
							. '<input type="radio" name="' . $name . '" id="' . $id . '" value="" ' . $checked . ' /> '
							. __('All') . '</label> ';
					$first_radio = '';
				}

				switch ($is_search)
				{
					case 'hidden_search':
					case 'hidden':
						// show single hidden input 
						if (strlen($val))
						{
							$return = '<input type="hidden" name="' . $name . '"  value="' . $val . '" />';
						}
						break;
					case 'search':
					case 'input':
					default:
						// show all radio optons
						foreach ($cf->AdField->AdFieldValue as $k => $v)
						{
							$_name = $name . '[' . $k . ']';
							$id = $prepend_to_id . $_name;

							// if saved value then check
							if (strcmp($k, $val) == 0)
							{
								$checked = 'checked="checked"';
							}
							else
							{
								$checked = $first_radio;
							}
							$return .= '<label for="' . $id . '"> '
									. '<input type="radio" name="' . $name . '" id="' . $id . '" value="' . View::escape($k) . '" ' . $checked . ' /> '
									. View::escape(AdFieldValue::getName($v)) . '</label> ';
							$first_radio = '';
						}

						if ($return)
						{
							$return = '<span class="adfield_radio">' . $return . '</span>';
						}
						break;
				}
				break;
			case self::TYPE_DROPDOWN:
				$val = trim($val);

				if ($is_search === 'search')
				{
					// add all field for search form 
					if ($val == '')
					{
						$checked = 'selected="selected"';
					}
					else
					{
						$checked = '';
					}
					// $return .= '<option value="" ' . $checked . '>' . __('All') . '</option>';
					// display custom field name to minimise space used by  search form
					$return .= '<option value="" ' . $checked . '>' . View::escape(AdField::getName($cf->AdField)) . '</option>';
				}


				switch ($is_search)
				{
					case 'hidden_search':
					case 'hidden':
						// show hidden input 
						if (strlen($val))
						{
							$return = '<input type="hidden" name="' . $name . '" value="' . $val . '" />';
						}
						break;
					case 'search':
					case 'input':
					default:
						// parse availeble values
						foreach ($cf->AdField->AdFieldValue as $k => $v)
						{

							// if saved value then check
							if (strcmp($k, $val) == 0)
							{
								$checked = 'selected="selected"';
							}
							else
							{
								$checked = '';
							}
							$return .= '<option value="' . View::escape($k) . '" ' . $checked . '>' . View::escape(AdFieldValue::getName($v)) . '</option>';
						}

						if ($return)
						{
							$return = '<select name="' . $name . '" id="' . $id . '">' . $return . '</select>';
						}
						break;
				}
				break;
			case self::TYPE_VIDEO_URL:
				$type = $arr_types['url'];
				switch ($is_search)
				{
					case 'hidden_search':
					case 'hidden':
						if (strlen($val))
						{
							$return = '<input type="hidden" name="' . $name . '" value="' . View::escape($val) . '" />';
						}
						break;
					case 'search':
					case 'input':
					default:
						// Video url
						$return = '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" value="' . View::escape($val) . '" />';
						$return .= '<em>' . self::fieldHelpText(self::TYPE_VIDEO_URL) . '</em>';
						break;
				}
				break;
			case self::TYPE_EMAIL:
				$type = $arr_types['email'];
				switch ($is_search)
				{
					case 'hidden_search':
					case 'hidden':
						$type = 'hidden';
						if (strlen($val))
						{
							$return = '<input type="' . $type . '" name="' . $name . '" value="' . View::escape($val) . '" />';
						}
						break;
					default:
						$return = '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" value="' . View::escape($val) . '" />';
				}
				break;
			case self::TYPE_URL:
				$type = $arr_types['url'];
				switch ($is_search)
				{
					case 'hidden_search':
					case 'hidden':
						$type = 'hidden';
						if (strlen($val))
						{
							$return = '<input type="' . $type . '" name="' . $name . '" value="' . View::escape($val) . '" />';
						}
						break;
					default:
						$return = '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" value="' . View::escape($val) . '" />';
				}
				break;
			case self::TYPE_TEXT:
			case self::TYPE_ADDRESS:
			default:
				// regular text field
				$type = 'text';
				switch ($is_search)
				{
					case 'hidden_search':
					case 'hidden':
						$type = 'hidden';
						if (strlen($val))
						{
							$return = '<input type="' . $type . '" name="' . $name . '" value="' . View::escape($val) . '" />';
						}
						break;
					default:
						$return = '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" value="' . View::escape($val) . '" />';
				}
				break;
		}
		return $return;
	}

	public static function fieldHelpText($type)
	{
		$arr_help = array(
			self::TYPE_VIDEO_URL => __('Video url from {name}', array('{name}' => 'Youtube'))
		);

		return $arr_help[$type];
	}

	/**
	 * parse ;val1;val2;val3; string to array. used for checkbox custom fields
	 * 
	 * @param string|array $vals
	 * @param bool $same_key make associative array, useful when checking 
	 * @return array
	 */
	public static function parseVals($vals, $same_key = true)
	{
		$return = array();

		if (!is_array($vals))
		{
			// parse by ; and convert to array
			$vals = str_replace("\n", ";", $vals);
			$vals = explode(";", $vals);
		}

		foreach ($vals as $v)
		{
			$v = trim($v);

			if (strlen($v) > 0)
			{
				if ($same_key)
				{
					$return[$v] = $v;
				}
				else
				{
					$return[] = $v;
				}
			}
		}

		return $return;
	}

	function _validate_val($adfield)
	{
		//echo '[_validate_val(' . $str . ', ' . $type . ')]';
		// check if values correspond to type
		switch ($adfield->type)
		{
			case 'radio':
			case 'checkbox':
			case 'dropdown':

				$value_count = 0;
				if ($adfield->AdFieldValue)
				{
					foreach ($adfield->AdFieldValue as $afv)
					{

						$name_count = 0;
						if ($afv->AdFieldValueDescription)
						{
							foreach ($afv->AdFieldValueDescription as $lng => $afvd)
							{
								if (strlen(trim($afvd->name)))
								{
									$name_count++;
								}
							}
						}

						if ($name_count > 0 && count($afv->AdFieldValueDescription) != $name_count)
						{
							// some value fields are empty give warning
							$validation = Validation::getInstance();
							$validation->set_error(__('Every value whould have correspongng translation.'));

							return false;
						}

						if ($name_count > 0)
						{
							$value_count++;
						}
					}
				}
				if (!$value_count)
				{
					$validation = Validation::getInstance();
					$validation->set_error(__('This field type should have at least one value.'), 'val');
					//exit('false');
					return false;
				}
				break;
			default:
				$adfield->val = '';
		}

		//exit('true');
		return true;
	}

	/**
	 * update adfield descriptio, adfield value, and adfieldvalue description
	 *  
	 * @return boolean 
	 */
	function updateValueAndDescription()
	{
		$remove_val = false;
		$remove_multi_value = false;

		// filter unused values 
		switch ($this->type)
		{
			case self::TYPE_NUMBER:
				// keep val
				$remove_multi_value = true;
				break;
			case self::TYPE_CHECKBOX:
			case self::TYPE_DROPDOWN:
			case self::TYPE_RADIO:
				// keep multi values
				$remove_val = true;
				break;
			default:
				// remove both
				$remove_val = true;
				$remove_multi_value = true;
		}

		if ($remove_val)
		{
			if ($this->AdFieldDescription)
			{
				foreach ($this->AdFieldDescription as $cd)
				{
					$cd->val = '';
				}
			}
		}

		if ($remove_multi_value)
		{
			unset($this->AdFieldValue);

			// delete connected values 
			AdFieldValue::deleteByAfId($this->id);
		}


		$this->updateDescription();
		$this->updateValueDescription();

		return true;
	}

	function updateDescription()
	{
		// save description if set
		if ($this->AdFieldDescription)
		{
			foreach ($this->AdFieldDescription as $afd)
			{
				$afd->af_id = $this->id;
				// delete description first
				AdFieldDescription::deleteWhere('AdFieldDescription', 'af_id=? AND language_id=?', array($afd->af_id, $afd->language_id));
				$afd->save('new_id');
			}
		}

		return true;
	}

	function updateValueDescription()
	{
		$set_ids = array();

		// save description if set
		if ($this->AdFieldValue)
		{
			foreach ($this->AdFieldValue as $afv)
			{
				$afv->af_id = $this->id;

				// if has id then update record
				if ($afv->save('id'))
				{
					// has description then mark as saved
					$set_ids[$afv->id] = $afv->id;
				}
			}
		}

		// delete unset values 
		$all_afv = AdFieldValue::findAllFrom('AdFieldValue', 'af_id=?', array($this->id));
		foreach ($all_afv as $_afv)
		{
			if (!isset($set_ids[$_afv->id]))
			{
				$_afv->delete();
			}
		}

		return true;
	}

	function setFromData($data)
	{
		parent::setFromData($data);

		if ($this->af_description)
		{
			foreach ($this->af_description as $lng => $cd)
			{
				// add new description 
				$cd['af_id'] = $this->id;
				$cd['language_id'] = $lng;
				$this->AdFieldDescription[$lng] = new AdFieldDescription($cd);
			}
		}

		unset($this->af_description);

		// reset values 
		unset($this->AdFieldValue);

		if ($this->afv)
		{
			$pos = 0;
			foreach ($this->afv['afv_id'] as $k => $afv_id)
			{
				// add new description 
				$afv['af_id'] = $this->id;
				$afv['id'] = $afv_id;
				$afv['pos'] = $pos;

				$_afv = new AdFieldValue($afv);

				$name_count = 0;
				foreach ($this->afv['afvd'] as $lng => $arr)
				{
					// set descriptions
					$afvd['afv_id'] = $afv_id;
					$afvd['language_id'] = $lng;
					$afvd['name'] = trim($arr['name'][$k]);

					$_afv->AdFieldValueDescription[$lng] = new AdFieldValueDescription($afvd);

					if (strlen($afvd['name']))
					{
						$name_count++;
					}
				}


				if ($name_count > 0)
				{
					// this is valid value field 
					$this->AdFieldValue[] = $_afv;

					$pos++;
				}
			}
		}

		unset($this->afv);
	}

	/**
	 * Get requested field in current language
	 * 
	 * @param AdField $af
	 * @param string $field
	 * @return string
	 */
	public static function getName($af, $field = 'name')
	{
		return $af->AdFieldDescription->{$field};
	}

	public static function getNameByLng($af, $lng = '', $field = 'name')
	{
		if (!strlen($lng))
		{
			$lng = Language::getDefault();
		}

		return $af->AdFieldDescription[$lng]->{$field};
	}

	/**
	 * append adfield name in current language
	 * 
	 * @param AdField $af array
	 */
	public static function appendAll($af)
	{
		AdFieldDescription::appendObject($af, 'id', 'AdFieldDescription', 'af_id', '', MAIN_DB, '*', false, false, "language_id=" . self::quote(I18n::getLocale()) . " AND ");
	}

	/**
	 * append adfield name
	 * append adfield value name 
	 * 
	 * @param AdField $af array
	 */
	public static function appendNameValue($af)
	{
		self::appendAll($af);
		self::appendValues($af);
	}

	/**
	 * Append AdField, AdFieldDescription, AdFieldValue, AdFieldValueDescription in current locale 
	 * 
	 * @param array $records
	 * @param string $field to use as adfield id
	 */
	public static function appendAdField($records, $field = 'adfield_id')
	{
		$records = Record::checkMakeArray($records);

		// append location from tree
		foreach ($records as $r)
		{
			$r->AdField = AdField::getAdFieldFromTree($r->{$field});
		}
	}

	public static function getAdFieldFromTree($id)
	{
		if (!isset(self::$arr_tree))
		{
			// get all values from db 
			$cache_key = 'adfield.' . I18n::getLocale();

			$adfields = SimpleCache::get($cache_key);
			if ($adfields === false)
			{
				// geal all category names and parent id for tree view
				$adfields = AdField::findAllFrom('AdField');

				AdField::appendNameValue($adfields);

				$_adfields = array();
				foreach ($adfields as $af)
				{
					$_adfields[$af->id] = $af;
				}
				$adfields = $_adfields;
				unset($_adfields);

				SimpleCache::set($cache_key, $adfields, 86400); //24 hours
			}

			self::$arr_tree = $adfields;

			unset($adfields);
		}

		return self::$arr_tree[$id];
	}

	public static function appendValues($records)
	{
		$records = Record::checkMakeArray($records);

		// append values ordered by position 
		// get ids from records
		$ids = array();
		foreach ($records as $r)
		{
			$ids[$r->id] = $r->id;
		}

		if (!$ids)
		{
			return false;
		}

		$objects = AdFieldValue::findAllFrom('AdFieldValue', 'af_id IN (' . implode(',', $ids) . ') ORDER BY pos');

		if (!$objects)
		{
			return false;
		}

		// append names 
		AdFieldValueDescription::appendObject($objects, 'id', 'AdFieldValueDescription', 'afv_id', '', MAIN_DB, '*', false, false, "language_id=" . self::quote(I18n::getLocale()) . " AND ");

		$objects_arr = array();
		foreach ($objects as $o)
		{
			$objects_arr[$o->af_id][$o->id] = $o;
		}


		// assign retrieved objects 
		foreach ($records as $r)
		{
			$r->AdFieldValue = $objects_arr[$r->id];
		}
	}

	/**
	 * format value as plain string. shorten if longer than 30 chars. used only in admin.
	 * 
	 * @param AdField $af
	 * @return string 
	 */
	public static function formatPredefinedValue($af)
	{
		switch ($af->type)
		{
			case self::TYPE_NUMBER:
				return self::getName($af, 'val');
				break;
			case self::TYPE_CHECKBOX:
			case self::TYPE_RADIO:
			case self::TYPE_DROPDOWN:
				$return = array();
				if ($af->AdFieldValue)
				{
					foreach ($af->AdFieldValue as $afv)
					{
						$return[] = AdFieldValue::getName($afv);
					}
				}
				$return_str = implode(', ', $return);

				return Inflector::utf8Substr($return_str, 0, 30) . (strlen($return_str) > 30 ? ' ...' : '');
				break;
			default:
				return '';
		}
	}

	public static function checkMakeByName($name, $type, $values = null, $lng = null)
	{
		$name = trim($name);
		if (strlen($name) < 1)
		{
			// skip empty values 
			return false;
		}

		$languages = Language::getLanguages();

		if (!isset(self::$arr_af_by_name[$type][$name]))
		{
			$af = AdField::findByName($name, $type, $lng);

			if (!$af)
			{
				// add AdField 
				$af = new AdField();
				$af->type = $type;

				foreach ($languages as $lng_obj)
				{
					$afd = new AdFieldDescription();
					$afd->language_id = $lng_obj->id;
					$afd->name = $name;

					$af->AdFieldDescription[$lng_obj->id] = $afd;
				}
				$af->save();
			}
			self::$arr_af_by_name[$type][$name] = $af;
		}
		$af = self::$arr_af_by_name[$type][$name];


		// make sure that drop downs values are there
		if ($af && $values && ($type == 'checkbox' || $type == 'radio' || $type == 'dropdown'))
		{
			foreach ($values as $value)
			{
				// TODO check make value 
				AdFieldValue::checkMakeByAfValue($af->id, $value);
			}
		}

		return $af;
	}

	public static function findByName($name, $type, $lng = null)
	{
		if (!$lng)
		{
			$lng = Language::getDefault();
		}

		$where = "af.type=? AND afd.name=? AND afd.language_id=?";
		$vals = array($type, $name, $lng);

		$sql = "SELECT af.* 
			FROM " . AdField::tableNameFromClassName('AdField') . " af 
			LEFT JOIN " . AdFieldDescription::tableNameFromClassName('AdFieldDescription') . " afd ON(af.id=afd.af_id)
				WHERE " . $where;

		$adfields = AdField::query($sql, $vals);
		if ($adfields)
		{
			return $adfields[0];
		}

		return false;
	}

	/**
	 * return itemscope and other values if AdField type price
	 * 
	 * @param string $val
	 * @param AdField $adField
	 * @return string
	 */
	public static function schemaItemScope($val, $adField)
	{
		// depricated in version 2.0.5
		/*if ($adField->type == 'price' && strlen($val))
		{
			return Schema::scope(Schema::SC_OFFER);
		}*/
		return '';
	}

	/**
	 * return itemprop and other values if AdField type price
	 * 
	 * @param string $val
	 * @param AdField $adField
	 * @return string
	 */
	public static function schemaItemProp($str_val, $adField, $val = null)
	{
		// depricated 
		/*if ($adField->type == 'price' && strlen($str_val))
		{
			// it is price then format value to proper float without thousand seperator
			// content="1000.00"

			return Schema::prop(Schema::PR_PRICE) . ' content="' . View::escape(floatval($val)) . '"';
		}*/
		return '';
	}

	/**
	 * Convert any price string to float using defined currency formatting by admin 
	 * use Ad::formatCurrency($number) to reverse convert to price 
	 * 
	 * @param string $val
	 * @return float
	 */
	public static function stringToFloat($val)
	{
		$currency_decimal_point = Config::option('currency_decimal_point');
		$currency_thousands_seperator = Config::option('currency_thousands_seperator');

		// if formatted differently then remove comma and dots and convert to regular float with dot
		$val = str_replace(array($currency_decimal_point, $currency_thousands_seperator), array('.', ''), $val);

		// remove all chars leave numbers, - and .
		$val = preg_replace("/[^-0-9\.]/", "", $val);

		// return float rounded to 4 digits after decimal point 
		return round(floatval($val), 4);
	}

	/**
	 * convert custom field value to SEO friendly slug, keyword
	 * used for building links for radio, dropdown, checkbox bustom fields
	 * this value is not used as variable in url 
	 * 
	 * @param AdField $af
	 * @param string|array $val
	 * @return string
	 */
	public static function slug($af, $val)
	{
		$keyword = '';

		switch ($af->type)
		{
			case AdField::TYPE_DROPDOWN:
			case AdField::TYPE_RADIO:
				// insert maximum 2 titles in url 
				$keyword = Ad::formatCustomValue($af, $val, array('escape' => false));
				break;
			case AdField::TYPE_CHECKBOX:
				// insert maximum 2 titles in url 
				$keyword = Ad::formatCustomValue($af, $val, array('display_all_checkboxes' => false, 'checkbox_pattern' => '', 'escape' => false));
				break;
		}

		// remove &amp from url 
		$keyword = str_replace('&amp', '&', $keyword);


		if (strlen($keyword) > 2 && !Permalink::isNumber($keyword))
		{
			$keyword = StringUtf8::makePermalink($keyword, null, '');
		}

		if (strlen($keyword) <= 2)
		{
			$keyword = '';
		}

		return $keyword;
	}

	/**
	 * Delete cache and update json version
	 */
	private static function _clearCache()
	{
		// delete category cache
		SimpleCache::delete('adfield');
		// update json version to request updated location data
		Config::optionSet('json_version', REQUEST_TIME);
	}

}

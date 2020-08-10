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
 * class Config
 *
 * @author Vepa Halliyev <veppa.com>
 * @since  0.1
 */
class Import
{

	public function __construct($data = false, $locale_db = false)
	{
		$this->setColumns(self::$cols);
		parent::__construct($data, $locale_db);
	}

	public static function importFromUrlXml($url, $page = 1)
	{
		/*
		 * read from rss url 
		 * create category
		 * create location 
		 * read contact email and phone
		 * read date added and calculate expirry according to current settings of site
		 * read if ad verified, enabled
		 * create custom fields
		 * 		read field type and add new field value if required
		 * 
		 * user passwords will be reset. 
		 * 
		 */
		if ($page < 1)
		{
			$page = 1;
		}

		$xml_file = Curl::get($url . '?page=' . $page);

		if (!strlen($xml_file))
		{
			// file not loaded
			exit('error: cannot load xml file');
		}

		$xml = @simplexml_load_string($xml_file);
		if (!$xml)
		{
			// file not loaded
			exit('error: xml file is not properly formatted');
		}
		if (!$xml->channel->item)
		{
			// nothing to import 
			exit('error: finished importing or nothing to import');
		}


		// empty value defaults to hide email, will display form if no phone define when saving ad
		$map_showemail = array(
			'hide'	 => 0,
			'show'	 => 1,
			'form'	 => 2,
			''		 => 0
		);

		$duplicates = 0;
		$invalid_email = 0;
		$num_added = 0;
		$num_images = 0;

		foreach ($xml->channel->item as $item)
		{
			//print_r($item);
			$new_ad = new Ad();
			$new_ad->title = self::strval($item->title);
			$new_ad->description = self::strval($item->description);
			$new_ad->email = self::strval($item->email);
			$new_ad->showemail = $map_showemail[self::strval($item->showemail)];
			$new_ad->phone = self::strval($item->phone);
			$new_ad->ip = self::strval($item->ip);
			$new_ad->added_at = strtotime(self::strval($item->pubDate));
			$new_ad->enabled = intval($item->enabled);
			if (intval($item->verified) > 0)
			{
				// make verified, if not set then will generate verification code for this ad when saving
				$new_ad->verified = 1;
			}

			// check email validity 
			if (!Validation::getInstance()->valid_email($new_ad->email))
			{
				// skip this because invalid email
				$invalid_email++;
				continue;
			}

			// check if this ad is unique 
			$existing_ad = Ad::findOneFrom('Ad', 'description=?', array($new_ad->description));
			if ($existing_ad)
			{
				// skip this because it already exists
				$duplicates++;
				continue;
			}

			// create user 
			$user = User::checkMakeByEmail($new_ad->email, true, $new_ad->verified, $new_ad->ip);
			$new_ad->added_by = $user->id;

			// create category 
			$arr_category = self::xml2strArr($item->category->val);
			if ($arr_category)
			{
				$category = Category::checkMakeByName($arr_category);
				if ($category)
				{
					$new_ad->category_id = $category->id;
				}
			}

			// create location 		
			$arr_location = self::xml2strArr($item->location->val);
			if ($arr_location)
			{
				$location = Location::checkMakeByName($arr_location);
				if ($location)
				{
					$new_ad->location_id = $location->id;
				}
			}


			// create custom fields
			if ($item->customfield)
			{
				foreach ($item->customfield as $cf)
				{
					// create custom field 
					$cfv = self::xml2strArr($cf->defined->val);
					$adfield = AdField::checkMakeByName(self::strval($cf->name), self::strval($cf->type), $cfv);
					if ($adfield)
					{
						// custom field found or created then check if it is related to this category and location
						$cfr = CategoryFieldRelation::checkMake($new_ad->location_id, $new_ad->category_id, $adfield->id, self::strval($cf->is_search), self::strval($cf->is_list));

						if ($cfr)
						{
							// custom field related to this category and location 
							$cfr->AdField = $adfield;
							AdField::appendValues($adfield);

							// TODO: if checkbox then $cf->value should be array not string
							// prepare AdFieldRelation
							/** multivalue
							 * <value>
							 * 		<val>val_1</val>
							 * 		<val>val_2</val>
							 * 		<val>val_3</val>
							 * </value>
							 * 
							 * or single value
							 * <value>value_1</value>
							 */
							if (count($cf->value->val))
							{
								// it has multiple values, this can be checkbox
								$stored_value = self::xml2strArr($cf->value->val);
							}
							else
							{
								$stored_value = self::strval($cf->value);
							}
							AdFieldRelation::prepareVal($adfield, $new_ad, $stored_value, true);
						}
					}
				}
			}

			// save ad 
			if ($new_ad->save())
			{
				$new_ad->saveCustomFieldsPrepared();

				// append images
				if ($item->image)
				{
					foreach ($item->image as $img_url)
					{
						$img_url = self::strval($img_url);
						echo 'upload_image:' . $img_url;
						if (Adpics::uploadToAdByUrl($new_ad->id, $img_url))
						{
							$num_images++;
						}
					}
				}

				$num_added++;
			}
		}

		// add and upload images
		//var_dump($xml);

		$return = array();
		if ($num_added)
		{
			$return[] = 'added:' . $num_added;
		}
		if ($num_images)
		{
			$return[] = 'images:' . $num_images;
		}
		if ($duplicates)
		{
			$return[] = 'duplicates:' . $duplicates;
		}
		if ($invalid_email)
		{
			$return[] = 'invalid_email:' . $invalid_email;
		}
		
		
		// check if duplicates are equal to total items then check page-1 xml if same then finish 
		if($num_added==0 && $duplicates>0 && $page>1)
		{
			// prevent infinite loop
			$xml_file_prev = Curl::get($url . '?page=' . ($page-1));
			if(strcmp($xml_file,$xml_file_prev)==0){
				exit('DONE: finished importing');
			}
		}

		exit('ok{SEP}' . implode(', ', $return));

		exit;
	}

	/**
	 * trim and convert to string
	 * 
	 * @param SimpleXMLElement $obj
	 * @return string
	 */
	public static function strval($obj)
	{
		return trim(html_entity_decode(strval($obj)));
	}

	/**
	 * convert xml array to string array
	 * 
	 * @param SimpleXMLElement $xmlObj array
	 * @return array
	 */
	public static function xml2strArr($xmlObj)
	{
		$return = array();
		if (isset($xmlObj))
		{
			foreach ($xmlObj as $str)
			{
				$str = self::strval($str);
				$return[] = $str;
			}
		}
		return $return;
	}

}

// end Import class

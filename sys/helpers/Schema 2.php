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
class Schema
{
	/* define schema values */

	const SC_PRODUCT = ' itemscope itemtype="http://schema.org/Product"';
	const SC_OFFER = ' itemscope itemtype="http://schema.org/Offer"';
	const SC_ORGANIZATION = ' itemscope itemtype="http://schema.org/Organization"';
	const SC_ITEMLIST = ' itemscope itemtype="http://schema.org/ItemList"';


	/* define schema itemprop values  */
	const PR_NAME = ' itemprop="name"';
	const PR_URL = ' itemprop="url"';
	/* ex Schema::prop(Schema::PR_RELEASE_DATE, $ad->use_schema, Ad::formatDate($ad, 'c')) */
	const PR_RELEASE_DATE = ' itemprop="releaseDate"';
	const PR_SKU = ' itemprop="sku"';
	const PR_PRICE = ' itemprop="price"';
	const PR_PRICE_CURRENCY = ' itemprop="priceCurrency"';
	const PR_DESCRIPTION = ' itemprop="description"';
	const PR_TELEPHONE = ' itemprop="telephone"';
	const PR_EMAIL = ' itemprop="email"';
	const PR_IMAGE = ' itemprop="image"';
	const PR_OFFERS = ' itemprop="offers"';
	const PR_BRAND = ' itemprop="brand"';
	const PR_ITEMLIST_ELEMENT = ' itemprop="itemListElement"';
	const PR_ITEMLIST_ORDER = ' itemprop="itemListOrder"';
	const PR_AV_INSTOCK = '<link itemprop="availability" href="http://schema.org/InStock">';
	const PR_AV_OUTOFSTOCK = '<link itemprop="availability" href="http://schema.org/OutOfStock">';

	/* itemListOrder values  */
	const ORDER_DESC = 'Descending';
	const ORDER_ASC = 'Ascending';
	const ORDER_UNORD = 'Unordered';

	/* breadcrumb new 24.01.2020 */
	const SC_BREADCRUMBLIST = ' itemscope itemtype="https://schema.org/BreadcrumbList"';
	const SC_LISTITEM = ' itemscope itemtype="https://schema.org/ListItem"';
	const PR_ITEMLISTELEMENT = ' itemprop="itemListElement"';
	const PR_ITEM = ' itemprop="item"';
	const PR_POSITION = ' itemprop="position"';

	/**
	 * Generate itemprop property if $use_schema true
	 * 
	 * @param string $type
	 * @param bool $use_schema
	 * @param string $hidden_content if set then will generate separate meta tag
	 * @return string
	 */
	public static function prop($type, $use_schema = true, $hidden_content = null)
	{
		if ($use_schema)
		{
			// constant itself formatted value 
			if (strlen($hidden_content))
			{
				// hidden meta tag
				return '<meta' . self::prop($type, $use_schema) . ' content="' . View::escape($hidden_content) . '">';
			}
			elseif (is_null($hidden_content))
			{
				// tag property itemprop="..."
				return $type;
			}
		}

		return '';
	}

	/**
	 * Generate itemscope and itemtype properties if $use_schema is true
	 * 
	 * @param string $type
	 * @param boolean $use_schema
	 * @param boolean $autoprop add related property if defined
	 * @return string
	 */
	public static function scope($type, $use_schema = true, $autoprop = true)
	{
		$return = '';
		if ($use_schema)
		{
			if ($autoprop)
			{
				// add itemprop to hold current itemscope
				switch ($type)
				{
					case Schema::SC_ORGANIZATION:
						// itemprop="brand" itemscope itemtype="http://schema.org/Organization"
						$return .= Schema::prop(Schema::PR_BRAND, $use_schema);
						break;
					case Schema::SC_OFFER:
						// itemprop="offers" itemscope itemtype="http://schema.org/Offer"
						$return .= Schema::prop(Schema::PR_OFFERS, $use_schema);
						break;
					case Schema::SC_LISTITEM:
						// itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"
						$return .= Schema::prop(Schema::PR_ITEMLISTELEMENT, $use_schema);
						break;
				}
			}
			$return .= $type;
		}

		return $return;
	}

}

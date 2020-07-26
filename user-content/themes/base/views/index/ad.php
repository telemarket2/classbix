<?php

$_hide_contact_details = (!AuthUser::hasPermission(User::PERMISSION_USER, $ad->added_by, false) && $ad->listed == 0);
$_user_name = User::getNameFromUserOrEmail($ad->User, $ad->email);
echo '<div class="clearfix mt1' . ($ad->featured ? ' featured' : '') . '">';

// show gallery slider 
echo Ad::formatGallerySlider($ad);

// title
echo '<h1>'
 . View::escape(Ad::getTitle($ad, null, $_hide_contact_details))
 . ($ad->featured ? ' <span class="label_text green small">' . __('Featured') . '</span>' : '')
 . '</h1>';



/**
 * custom fields 
 */
$arr_format_custom_fields = array(
	'html_group_open'		 => '',
	'html_group_close'		 => '',
	'html_checkbox'			 => '<div class="col col-12 sm-col-6 p1" {schema_item_scope}>'
	. '<div class="clearfix">'
	. '<span class="cf_label col col-6">{name}: </span>'
	. '<span{schema_item_prop} class="cf_value col col-6">{value}</span>'
	. '</div>'
	. '</div> ',
	'html_checkbox_wrap'	 => '',
	'html_name_value'		 => '<div class="col col-12 sm-col-6 p1" {schema_item_scope}>'
	. '<div class="clearfix">'
	. '<span class="cf_label col col-6">{name}: </span>'
	. '<span{schema_item_prop} class="cf_value col col-6">{value}</span>{schema_item_extra}'
	. '</div>'
	. '</div> ',
	/* 'html_name_value_wrap'	 => '<div class="post_custom_fields clearfix" ' . Schema::prop(Schema::PR_DESCRIPTION, $ad->use_schema) . '>{html}</div>' */
	'html_name_value_wrap'	 => '',
	'custom_value_options'	 => array('checkbox_seperator' => ', ')
);
Ad::formatCustomFields($ad, $arr_format_custom_fields);


// format additional info 
$arr_item_info = array();
// item main
$loc = Location::getFullNameById($ad->location_id, '', ', ');
$cat = Category::getFullNameById($ad->category_id, '', ', ');
if ($cat)
{
	$arr_item_info['main']['category'] = array(
		'{name}'	 => __('Category'),
		'{value}'	 => '<a href="' . Location::urlById($ad->location_id, $ad->category_id) . '">' . $cat . '</a>'
	);
}
if ($loc)
{
	$arr_item_info['main']['location'] = array(
		'{name}'	 => __('Location'),
		'{value}'	 => '<a href="' . Location::urlById($ad->location_id) . '">' . $loc . '</a>'
	);
}


// item contact 
$_contact_wrap = '';
$redirect_to_login = isset($_GET['login']) ? true : false;
if (Config::option('view_contact_registered_only') && !AuthUser::isLoggedIn($redirect_to_login))
{
	// display login link
	$arr_item_info['contact']['posted_by'] = array(
		'{name}'	 => __('Contact'),
		'{value}'	 => '<a href="' . Ad::url($ad, null, '?login=1') . '" class="button">' . __('Log in to view contact details') . '</a>'
	);
}
elseif ($_hide_contact_details)
{
	// not listed item viewed by not owner. 
	// hide contact details 
	$arr_item_info['contact']['posted_by'] = array(
		'{name}'	 => __('Contact'),
		'{value}'	 => __('Contact details hidden for not running items')
	);
}
else
{
	// show all contact info 
	// other ads
	if ($ad->User->countAds > 1)
	{
		// display other ads by user
		$arr_item_info['contact']['posted_by'] = array(
			'{name}'	 => __('Contact'),
			'{value}'	 => '<a href="' . User::url($ad->User) . '" class="view_by_user">' . __('{name} ({num} ads)', array(
				'{name}' => View::escape($_user_name),
				'{num}'	 => $ad->User->countAds,
			)) . '</a>'
		);
	}
	else
	{
		// no more ads just show name
		$arr_item_info['contact']['posted_by'] = array(
			'{name}'	 => __('Contact'),
			'{value}'	 => '<span>' . View::escape($_user_name) . '</span>'
		);
	}

	// phone
	if ($ad->phone)
	{
		$arr_item_info['contact']['phone'] = array(
			'{name}'	 => __('Phone'),
			'{value}'	 => '<span class="telephone">' . View::escape($ad->phone) . '</span>'
		);
	}

	// email
	switch ($ad->showemail)
	{
		case Ad::SHOWEMAIL_YES:
			$arr_item_info['contact']['email'] = array(
				'{name}'	 => __('Email'),
				'{value}'	 => '<a href="mailto:' . View::escape($ad->email) . '">' . View::escape($ad->email) . '</a>'
			);

			break;
		case Ad::SHOWEMAIL_FORM:
			$arr_item_info['contact']['email'] = array(
				'{name}'	 => __('Email'),
				'{value}'	 => '<a href="#contact_form" class="button show_cotact_form" data-toggle="cb_modal" data-target="#contact_form">' . __('Contact by email') . '</a>'
				. (new View('index/_contact_form', $this->vars))
			);
			break;
		case Ad::SHOWEMAIL_NO:
		default:
	}

	if ($ad->use_schema)
	{
		$_contact_wrap = '<span>{content}</span>';
	}
}


// item_meta
$arr_item_info['meta']['posted_on'] = array(
	'{name}'	 => __('Posted on'),
	'{value}'	 =>  Ad::formatDate($ad)
);

$arr_item_info['meta']['hits'] = array(
	'{name}'	 => __('Views'),
	'{value}'	 => '<span class="js_stat" data-itemid="' . $ad->id . '">' . number_format($ad->hits + 1) . '</span>'
);

// format item info 
$_item_hr = '<div class="col col-12 sm-col-6 md-col-4 sm-hide md-hide lg-hide p1"><hr class="m0" /></div>';
$_item_info_patttern = '<div class="col col-12 sm-col-6 p1">'
		. '<div class="clearfix">'
		. '<span class="cf_label col col-6">{name}: </span>'
		. '<span class="cf_value col col-6">{value}</span>'
		. '</div>'
		. '</div>' . "\n";
$_item_info_str_arr = array();
foreach ($arr_item_info as $k => $arr_item_info_)
{
	// add seperator 
	$_item_info_str = '';
	foreach ($arr_item_info_ as $key => $_item_info)
	{
		$_item_info_str .= str_replace(array_keys($_item_info), array_values($_item_info), $_item_info_patttern);
	}

	if ($k === 'contact' && $_contact_wrap)
	{
		// wrap contact if needed
		$_item_info_str = str_replace('{content}', $_item_info_str, $_contact_wrap);
	}

	$_item_info_str_arr[] = $_item_info_str;
}

echo '<div class="post_custom_fields clearfix mxn1">';
// display regular custom fields
echo $ad->html_custom_fields_all;
if (strlen($ad->html_custom_fields_all) > 1)
{
	// divide with hr 
	echo $_item_hr;
}

// display additional info 
echo implode($_item_hr, $_item_info_str_arr);
echo '</div>';

/**
 *  description 
 */
echo '<p class="description">'
 . Ad::formatDescription($ad, $_hide_contact_details)
 . '</p>';



/**
 * ad action links and info
 */
$arr_meta_info = array();
$action_link_class = 'button small narrow outline';
$arr_meta_info['item_id'] = '<a href="' . Ad::url($ad) . '" class="' . $action_link_class . '">'
		. __('#')
		. '<span>' . $ad->id . '</span></a>';
if (!AuthUser::isLoggedIn(false) || AuthUser::hasPermission(User::PERMISSION_USER, $ad->added_by, false))
{
	// show edit link if not logged in or if admin or if viewing your own ad
	$arr_meta_info['edit'] = '<a href="' . Ad::urlEdit($ad) . '" rel="nofollow" class="' . $action_link_class . '"><i class="fa fa-edit" aria-hidden="true"></i> ' . __('Edit') . '</a>';
}


// display promote link if possible 
if (Ad::isPaymentAvailable($ad))
{
	$arr_meta_info['promote'] = '<a href="' . Ad::urlPromote($ad) . '" class="promote ' . $action_link_class . '"><i class="fa fa-diamond" aria-hidden="true"></i> ' . __('Promote this ad') . '</a>';
}

// display QR code 
$arr_meta_info['qr'] = '<a href="' . Ad::urlQR($ad) . '" class="qr_code ' . $action_link_class . '"><i class="fa fa-qrcode" aria-hidden="true"></i> ' . __('QR code') . '</a>';

// display reporting 
$arr_meta_info['report'] = '<a href="#report" class="report ' . $action_link_class . '" rel="' . $ad->id . '" 
	msg-confirm="' . __('Do you really want to report this ad as inappropriate?') . '"
	msg-input="' . __('Reporting reason') . '"
		><i class="fa fa-warning" aria-hidden="true"></i> '
		. __('Report as inappropriate') . '</a>';

echo '<p class="meta_footer"><span class="meta_item">' . implode('</span>  <span class="meta_item">', $arr_meta_info) . '</span></p>';



// display dealer info if dealer
if (User::canDisplayDealerInfoOnAdPage($ad->User) && ($ad->User->logo || $ad->User->info))
{
	if ($ad->User->logo)
	{
		$_img_placeholder_src = Adpics::imgPlaceholder();
		$_img_thumb = User::logo($ad->User, null, 'lazy');
		$_dealer_logo = '<img src="' . $_img_placeholder_src . '"'
				. ($_img_thumb ? ' class="lazy user_logo" data-src="' . $_img_thumb . '"' : ' class="user_logo"')
				. ' alt="' . View::escape($ad->User->name) . '" />';
	}
	else
	{
		$_dealer_logo = '<i class="fa fa-user fa-2x" aria-hidden="true"></i>';
	}

	$_dealer_meta_arr = array();
	$_dealer_meta_arr[] = __('on site for {time}', array('{time}' => Config::timeRelative($ad->User->added_at, 1, false)));
	if ($ad->User->countAds > 1)
	{
		$_dealer_meta_arr[] = __('{num} items', array('{num}' => View::escape($ad->User->countAds)));
	}


	$_dealer_info = '<div class="dealer_info">'
			. '<a href="' . User::url($ad->User) . '" class="dealer_link clearfix">'
			. '<span class="block left dealer_logo">' . $_dealer_logo . '</span> '
			. '<span class="dealer_content">'
			. '<b class="dealer_title">' . View::escape($_user_name) . '</b>'
			. ($ad->User->info ? '<span class="dealer_description">' . TextTransform::excerpt($ad->User->info, 100) . '</span>' : '')
			. '<span class="dealer_meta">' . implode(' • ', $_dealer_meta_arr) . '</span>'
			. '</span>'
			. '</a>'
			. '</div>';

	echo $_dealer_info;
}



if (!Config::option('hide_othercontactok'))
{
	if ($ad->othercontactok)
	{
		echo '<p class="othercontactok">' . __('It is ok to contact this poster with commercial interests.') . '</p>';
	}
	else
	{
		echo '<p class="othercontactok_not">' . __('It is <b>NOT</b> ok to contact this poster with other commercial interests.') . '</p>';
	}
}


// floating call button 
echo '<a class="button green phonecall display-none lg-hide" '
 . 'data-phonecall=".post_custom_fields .telephone" '
 . 'data-min="5" data-max="15">'
 . '<i class="fa fa-phone" aria-hidden="true"></i> '
 . __('Call seller')
 . '</a>';


echo '</div>';
// product END

echo '<hr>';



// previous next ads 
if ($ad->prev_next->prev || $ad->prev_next->next)
{
	echo '<p class="item_prev_next clearfix">';

	if ($ad->prev_next->prev)
	{

		echo '<a href="' . Ad::url($ad->prev_next->prev) . '" class="item_prev button link narrow left" title="' . View::escape(Ad::getTitle($ad->prev_next->prev)) . '"><i class="fa fa-arrow-left" aria-hidden="true"></i> ' . __('Previous') . '</a>';
	}
	if ($ad->prev_next->next)
	{
		echo '<a href="' . Ad::url($ad->prev_next->next) . '" class="item_next button link narrow right" title="' . View::escape(Ad::getTitle($ad->prev_next->next)) . '">' . __('Next') . ' <i class="fa fa-arrow-right" aria-hidden="true"></i></a>';
	}
	echo '</p>';
}

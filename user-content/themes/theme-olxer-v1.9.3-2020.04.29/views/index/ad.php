<?php

$_user_name = User::getNameFromUserOrEmail($ad->User, $ad->email);
echo '<div class="' . ($ad->featured ? 'featured' : '') . '">';

echo '<div class="c1">';
echo '<div class="item_details">';

// title
$_location_name = Location::getNameById($ad->location_id);
echo '<h1>' . View::escape(Ad::getTitle($ad))
 . ($_location_name ? '<span class="small_text"> - ' . $_location_name . '</span>' : '')
 . ($ad->featured ? ' <span class="label_text green small">' . __('Featured') . '</span>' : '') . '</h1>';


/**
 *  gallery
 */
echo Ad::formatGallery($ad, '460x300');

/**
 * custom fields 
 */
$arr_format_custom_fields = array(
	'html_group_open' => '<li class="post_custom_group"><h4>{name}</h4></li>',
	'html_group_close' => '',
	'html_checkbox' => '<li{schema_item_scope}><span class="label">{name}</span><span{schema_item_prop} class="type_{type}">{value}</span></li>',
	'html_checkbox_wrap' => '<ul class="post_custom_fields_big">{html}</ul>',
	'html_name_value' => '<li{schema_item_scope}><span class="label">{name}</span><span{schema_item_prop} class="type_{type} {long_text}">{value}</span>{schema_item_extra}</li>',
	'html_name_value_wrap' => '<ul class="post_custom_fields">{html}</ul>',
	'skip_by_type' => array(AdField::TYPE_ADDRESS)
);
Ad::formatCustomFields($ad, $arr_format_custom_fields);

// display regular custom fields
echo $ad->html_custom_fields;

echo '<div class="clear"></div>';

/**
 *  description 
 */
echo '<p class="description">' . Ad::formatDescription($ad) . '</p>';

/**
 * display bigger custom fields like checkboxes
 */
echo $ad->html_custom_fields_checkbox;

/**
 * ad info
 */
$arr_meta_info = array();
$arr_meta_info['posted_on'] = '<b>' . __('Posted on') . ' :</b> '
		. Ad::formatDate($ad);
$arr_meta_info['item_id'] = __('#')
		. '<a href="' . Ad::url($ad) . '">'
		. '<span>' . $ad->id . '</span></a>';
$arr_meta_info['hits'] = '<span class="js_stat" data-itemid="' . $ad->id . '">' . __('{num} views', array('{num}' => number_format($ad->hits + 1))) . '</span>';
if (!AuthUser::isLoggedIn(false) || AuthUser::hasPermission(User::PERMISSION_USER, $ad->added_by, false))
{
	// show edit link if not logged in or if admin or if viewing your own ad
	$arr_meta_info['edit'] = '<a href="' . Ad::urlEdit($ad) . '" rel="nofollow">' . __('Edit') . '</a>';
}
echo '<p class = "meta"><span class="meta_item">' . implode('</span>, <span class="meta_item">', $arr_meta_info) . '</span></p>';





if(!Config::option('hide_othercontactok'))
{
	if($ad->othercontactok)
	{
		echo '<p class="othercontactok">' . __('It is ok to contact this poster with commercial interests.') . '</p>';
	}
	else
	{
		echo '<p class="othercontactok_not">' . __('It is <b>NOT</b> ok to contact this poster with other commercial interests.') . '</p>';
	}
}

/**
 * Action links 
 */
$action_links = array();
// display promote link if possible 
if(Ad::isPaymentAvailable($ad))
{
	$action_links[] = '<a href="' . Ad::urlPromote($ad) . '" class="button promote">' . __('Promote this ad') . '</a>';
}

// display QR code 
$action_links[] = '<a href="' . Ad::urlQR($ad) . '" class="qr_code button">' . __('QR code') . '</a>';
// display reporting 
$action_links[] = '<a href="#report" class="button report" rel="' . $ad->id . '" 
					msg-confirm="' . __('Do you really want to report this ad as inappropriate?') . '"
					msg-input="' . __('Reporting reason') . '"
						>'
		. __('Report as inappropriate') . '</a>';
echo '<p class="action_links">' . implode(' ', $action_links) . '</p>';


echo '</div>';
// .item_details END
// display prev / next button at the bottom as well
echo Ad::formatPrevNext($ad, array(
	'wrap' => '<p class="item_prev_next_simple">{BUTTON_PREV} {BUTTON_NEXT}</p>',
	'button_prev' => '<a href="{URL_PREV}" class="button button_prev" title="{TITLE_PREV}">&larr; ' . __('Previous') . '</a>',
	'button_next' => '<a href="{URL_NEXT}" class="button button_next" title="{TITLE_NEXT}">' . __('Next') . ' &rarr;</a>'
));


echo '</div>';
// .c1 END



/**
 *  post details & contact 
 */
$contact_details = '<div class="post_details">';

// other ads
if($ad->User->countAds > 1)
{
	// display other ads by user
	$_other_ads_by_user .= '<p><a href="' . User::url($ad->User) . '" class="view_by_user">' . __('View all ads by {name} ({num})', array(
				'{name}' => View::escape($ad->User->name),
				'{num}' => $ad->User->countAds,
			)) . '</a></p>';
	$_user_name_link = '<a href="' . User::url($ad->User) . '">' . View::escape($_user_name) . '</a>';
}
else
{
	$_user_name_link = View::escape($_user_name);
}
$contact_details .= '<div class="contact">
						<h3><span>' . $_user_name_link . '</span></h3>';
$contact_details .= '<p class="hide_screen">' . Ad::permalinkShort($ad) . '</p>';

// display full location 
$_full_location = Location::getFullNameById($ad->location_id, '', ', ', true);
if($_full_location)
{
	$contact_details .= '<p>' . $_full_location . '</p>';
}

$_custom_field_address = Map::getAddress($ad, false);
if($_custom_field_address)
{
	$contact_details .= '<p>' . $_custom_field_address . '</p>';
}

// other items
$contact_details .= $_other_ads_by_user;



// get contact details
$_contact_details = '';
if($ad->phone)
{
	$_contact_details .= '<p><b>' . __('Phone') . ' :</b> <span>' . View::escape($ad->phone) . '</span></p>';
}
// email
switch($ad->showemail)
{
	case Ad::SHOWEMAIL_YES:
		$_contact_details .= '<p><b>' . __('Email') . ' :</b> <a href="mailto:' . View::escape($ad->email) . '">' . View::escape($ad->email) . '</a></p>';
		break;
	case Ad::SHOWEMAIL_FORM:
		$_contact_details .= '<p><a href="#contact_form" class="button big primary show_cotact_form" data-toggle="cb_modal" data-target="#contact_form">' . __('Reply to this ad') . '</a></p>';
		$_contact_details .= '<div class="hide">' . (new View('index/_contact_form', $this->vars)) . '</div>';
		break;
	case Ad::SHOWEMAIL_NO:
	default:
}

$redirect_to_login = isset($_GET['login']) ? true : false;
if(Config::option('view_contact_registered_only') && !AuthUser::isLoggedIn($redirect_to_login))
{
	// display login link
	$_contact_details = '<p><a href="' . Ad::url($ad, null, '?login=1') . '" class="button">' . __('Log in to view contact details') . '</a></p>';
}
$contact_details .= $_contact_details;

$contact_details .= '</div>';
// .contact END
// map placeholder
$contact_details .= '<div id="itemMap"></div>';

// display dealer info if dealer
if(User::canDisplayDealerInfoOnAdPage($ad->User))
{
	$_dealer_info = '';
	if($ad->User->logo)
	{
		$_dealer_logo = '<a href="' . User::url($ad->User) . '"><img src="' . User::logo($ad->User) . '" class="user_logo" alt="' . View::escape($_user_name) . '" /></a> ';
	}
	else
	{
		$_dealer_logo = '';
	}

	if($_dealer_logo || $ad->User->info)
	{
		$_dealer_info .= '<p>' . $_dealer_logo . TextTransform::excerpt($ad->User->info, 100) . '</p>';
	}
	if($ad->User->web)
	{
		$_dealer_info .= '<p>' . TextTransform::str2Link($ad->User->web) . '</p>';
	}

	if($_dealer_info)
	{
		$contact_details .= '<div class="dealer_box">
				<h3><a href="' . User::url($ad->User) . '">' . View::escape($_user_name) . '</a></h3>'
				. '<div class="contact">' . $_dealer_info . '</div>
				</div>';
	}
}

$contact_details .= '</div>';
// .post_details END
echo '<div class="c2">' . $contact_details . '</div>';


echo '<div class="clear"></div>';
echo '</div';
// .featured END
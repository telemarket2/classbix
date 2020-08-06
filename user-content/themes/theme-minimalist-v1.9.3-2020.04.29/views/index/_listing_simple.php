<?php

//simple list 
$return = '<ul class="list_style_simple">';
foreach($ads as $ad)
{
	$_img_thumb = Adpics::imgThumb($ad->Adpics, '', $ad->User);
	if($_img_thumb)
	{
		$thumb = '<a href="' . Ad::url($ad) . '" class="thumb"><img src="'
				. $_img_thumb . '" alt="'
				. View::escape(Ad::getTitle($ad)) . '" /></a>';
	}
	else
	{
		$thumb = '';
	}

	// contact 
	$contact = '';
	if($ad->phone)
	{
		$contact = '<p class="contact_phone"><b>' . __('Phone') . ' :</b> ' . View::escape($ad->phone) . '</p>';
	}
	switch($ad->showemail)
	{
		case Ad::SHOWEMAIL_YES:
			$contact.= '<p class="contact_email"><b>' . __('Contact') . ' :</b> <a href="mailto:' . View::escape($ad->email) . '">' . View::escape($ad->email) . '</a></p>';
			break;
		case Ad::SHOWEMAIL_FORM:
		case Ad::SHOWEMAIL_NO:
		default:
	}

	if(Config::option('view_contact_registered_only') && !AuthUser::isLoggedIn(false))
	{
		// display login link
		$contact = '<p><a href="' . Ad::url($ad, null, '?login=1') . '">' . __('Log in to view contact details') . '</a></p>';
	}

	// price
	$price = AdFieldRelation::getPrice($ad);
	if($price)
	{
		$price = ' <span class="price">' . $price . '</span>';
	}

	$return .= '<li>';
	$return .= '<h2><a href="' . Ad::url($ad) . '">' . View::escape(Ad::getTitle($ad)) . '</a></h2>';
	$return .= '<p>' . $thumb . View::escape(Ad::snippet($ad, 50)) . ' ' . $price . '</p>' . $contact;
	$return .= '</li>';
}
$return .= '</ul>';

echo $return;
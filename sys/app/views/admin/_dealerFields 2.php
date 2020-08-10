<?php

$logo_dimention = '(' . Config::option('dealer_logo_width') . 'x' . Config::option('dealer_logo_height') . ' pixel.)';
echo '<div class="dealer' . $class_hidden . '">'
 . '<h2>' . __('Dealer details') . '</h2>'
 . '<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="web">' . __('Website') . ' </label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="web" type="url" id="web" maxlength="100"  value="' . View::escape($user->web) . '" class="input input-long"  />
			' . $this->validation()->web_error . '
			<input name="dealer" type="hidden" id="dealer" value="1"  />
		</div>
	</div>'
 . '<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="logo">' . __('Logo') . '</label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="logo" type="file" id="logo" size="30" class="input" />
			' . $this->validation()->logo_error . '
			<p>' . $logo_dimention . '</p>
			' . ($user->logo ? '<img src="' . User::logo($user) . '" class="dealer_logo" /> 
			<a href="#" class="remove_dealer_logo">' . __('Remove') . '</a>' : '') . '
		</div>
	</div>'
 . '<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="info">' . __('Info') . '</label></div>
		<div class="col col-12 sm-col-10 px1">
			<textarea name="info" id="info">' . View::escape($user->info) . '</textarea>
			' . $this->validation()->info_error . '
			<p><em>' . __('(address, contacts, work hours, etc. max 1000 characters)') . '</em></p>
		</div>
	</div>'
 . '</div>';

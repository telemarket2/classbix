<h1 class="mt0"><?php echo __('Post ad') ?></h1>
<?php
if (!AuthUser::isLoggedIn(false))
{
	echo '<p>' . __('If you have an account please <a href="{url}">log in</a>.', array('{url}' => Language::get_url('post/login/?login=1'))) . '</p>';
}
?>
<form action="" method="post" enctype="multipart/form-data">
	<?php
// image upload fields
	echo Adpics::renderUploadFields(array(
		'ad'		 => $ad,
		'pattern'	 => '<div class="clearfix form-row">
								<div class="col col-12 sm-col-2 px1 form-label">{TITLE}</div>
								<div class="col col-12 sm-col-10 px1">{FIELDS}{DESCRIPTION}</div>
							</div>'
	));
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="title"><?php echo __('Title') ?></label></div>
		<div class="col col-12 sm-col-10 px1"><input name="title" type="text" id="title" size="20" maxlength="100" class="input input-long" value="<?php echo View::escape($ad->title) ?>" /></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="description"><?php echo __('Description') . ' ' . Config::markerRequired(); ?></label></div>
		<div class="col col-12 sm-col-10 px1"><textarea name="description" rows="10" id="description" required><?php echo View::escape($ad->description) ?></textarea>
			<?php echo Validation::getInstance()->description_error; ?></div>
	</div>
	<?php
	if (Location::hasValidPostingLocations())
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="location_id"><?php echo __('Location') ?></label></div>
			<div class="col col-12 sm-col-10 px1"><?php
				echo '<input name="location_id" 
					value="' . $ad->location_id . '" 
					data-src="' . Config::urlJson() . '"
					data-key="location"
					data-selectalt="1"
					data-rootname="' . View::escape(__('Select location')) . '"
					data-currentname="' . View::escape(Location::getNameById($ad->location_id)) . '"
					class="display-none"							
					required
					>';

				echo Validation::getInstance()->location_id_error;
				?></div>
		</div>

		<?php
	}// location 

	if (Category::hasValidPostingCategories())
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="category_id"><?php echo __('Category') ?></label></div>
			<div class="col col-12 sm-col-10 px1"><?php
				echo '<input name="category_id" 
					value="' . View::escape($ad->category_id) . '" 
					data-src="' . Config::urlJson() . '"
					data-key="category"
					data-selectalt="1"
					data-rootname="' . View::escape(__('Select category')) . '"
					data-currentname="' . View::escape(Category::getNameById($ad->category_id)) . '"
					class="display-none"
					required
					>';

				echo Validation::getInstance()->category_id_error;
				?></div>
		</div>

		<?php
	}// categories 
// add custom fields as hidden 
// then use template and js to show properly styled dynamic custom fields 
	$hidden_cf = '';
	if ($catfields)
	{
		foreach ($catfields as $cf)
		{
			// store error messages to display if needed
			$name = 'cf[' . $cf->adfield_id . ']';
			$error = Validation::getInstance()->{$name . '_error'};
			if ($error)
			{
				$hidden_cf .= '<span id="' . $name . '_error" class="hidden_error">' . $error . '</span>';
			}

			// custom field ad hidden value in case js not loaded but form submitted with updates 
			$hidden_cf .= AdField::htmlField($cf, $ad->AdFieldRelation[$cf->AdField->id]->val, 'hidden');

			// remove from posted values and append left post values at the end as hidden for use in update id needed
			unset($_POST['cf'][$cf->adfield_id]);
		}
	}
	?>
	<hr> 
	<!-- DYNAMIC CONTENT -->
	<div id="wrap_cf">
<?php echo '<div class="diplay-none">' . $hidden_cf . '</div>'; ?>
	</div>
	<script>
		// define dynamic template as var 
		// load custom fields json
		// then call rendering function stored in js 
		addLoadEvent(function ()
		{
			cb.cf.init({
				datasrc: '<?php echo View::escape(Config::urlJson()); ?>',
				template: '<div class="clearfix form-row">'
						+ '<div class="col col-12 sm-col-2 px1 form-label">${label}</div>'
						+ '<div class="col col-12 sm-col-10 px1">${input} ${help}</div>'
						+ '</div>',
				target: '#wrap_cf',
				loc: 'input[name="location_id"]',
				cat: 'input[name="category_id"]',
				templatepayment: {
					p: {
						title: '<?php echo View::escape(__('Posting price')); ?>',
						input: '{price}'
					},
					f: {
						title: '<?php echo View::escape(__('Featured listing')); ?>',
						input: '<label class="input-checkbox">'
								+ '<input type="checkbox" name="price_featured_requested" id="price_featured_requested" value="1" />'
								+ '<span class="checkmark"></span>'
								+ '<?php
echo View::escape(__('Enable featured listing ({num} days listing {price})', array(
	'{num}'		 => intval(Config::option('featured_days')),
	'{price}'	 => '{price}'
)));
?>'
								+ '</label>'
					}
				}
			});
		});

	</script>
	<!-- DYNAMIC CONTENT ENDs -->	
	<hr>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="phone"><?php echo __('Phone') . ' ' . Config::markerRequired(Config::option('required_phone')); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$phone_regex = Config::option('phone_regex');
			$phone_regex_attr = $phone_regex ? ' pattern="' . View::escape($phone_regex) . '"' : '';
			$_ml_phone_hint = Config::option('_ml_phone_hint');
			$_ml_phone_hint_attr = $_ml_phone_hint ? ' title="' . View::escape($_ml_phone_hint) . '"' : '';
			$_ml_phone_hint_info = $_ml_phone_hint ? '<em>' . View::escape($_ml_phone_hint) . '</em>' : '';
			?>
			<input class="input" name="phone" id="phone" type="tel" size="20" 
				   value="<?php echo View::escape($ad->phone) ?>" required <?php echo $phone_regex_attr . $_ml_phone_hint_attr ?>>
				   <?php echo Validation::getInstance()->phone_error; ?>
<?php echo $_ml_phone_hint_info ?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="email"><?php echo __('Email') . ' ' . Config::markerRequired(); ?></label></div>
		<div class="col col-12 sm-col-10 px1"><input class="input" name="email" id="email" type="email" size="20" value="<?php echo View::escape($ad->email) ?>" required>
			<?php echo Validation::getInstance()->email_error; ?>
			<!-- contact options -->
			<?php
			if (count($contact_options) > 1)
			{
				// more than one option available then display them
				foreach ($contact_options as $k => $v)
				{
					echo '<p><label class="input-radio">'
					. '<input name="showemail" id="' . $k . '" type="radio" 
									value="' . $v['value'] . '" ' . ($ad->showemail == $v['value'] ? 'checked="checked"' : '') . ' />'
					. '<span class="checkmark"></span> '
					. View::escape($v['label']) . '</label>'
					. ($v['message'] ? ' <em class="msg-error-line">' . View::escape($v['message']) . '</em>' : '')
					. '</p>';
				}
			}
			else
			{
				// only one option available then no need to display input form
				// display only info message 
				foreach ($contact_options as $k => $v)
				{
					echo '<em>' . View::escape($v['message_selected']) . '</em> '
					. ($v['message'] ? '<em class="msg-error-line">' . View::escape($v['message']) . '</em>' : '');
				}
			}
			?></div>
	</div>


	<?php
// display captcha
	$captcha_pattern = '<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="{name}">{label} {marker}</label></div>
		<div class="col col-12 sm-col-10 px1">{input}</div>
	</div>';
	echo $captcha_render = Captcha::render($captcha_pattern);


// other contact 
	if (!Config::option('hide_othercontactok'))
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"></div>
			<div class="col col-12 sm-col-10 px1">
				<label class="input-checkbox">
					<input type="checkbox" name="othercontactok" id="othercontactok" value="1" <?php echo ($ad->othercontactok ? 'checked="checked"' : '') ?> >
					<span class="checkmark"></span>
	<?php echo __('People with other commercial requests can contact me.') ?></label>
			</div>
		</div>
		<?php
	}

// hide agree
	if (!Config::option('hide_agree'))
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"></div>
			<div class="col col-12 sm-col-10 px1">
				<label class="input-checkbox">
					<input type="checkbox" name="agree" id="agree" value="1" required <?php echo ($_POST['agree'] ? 'checked="checked"' : ''); ?>> 
					<span class="checkmark"></span>
	<?php echo __('I have read and comply with {terms} for posting this ad.', array('{terms}' => Page::pageLink('page_id_terms'))) ?>
				</label>
			</div>
		</div>
		<?php
	}
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"> </div>
		<div class="col col-12 sm-col-10 px1">
<?php echo Config::nounceInput(); ?>
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit') ?>" class="button" />	
			<a href="<?php echo Ad::urlReturn() ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>
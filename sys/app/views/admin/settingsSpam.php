<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Spam') ?></h1>
<form action="" method="post" id="settings_form">
	<h2 id="grp_image"><?php echo __('Spam filter'); ?></h2>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="abuse_minimum"><?php echo __('Abuse minimum'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="abuse_minimum" type="text" id="abuse_minimum" value="<?php echo View::escape(Config::option('abuse_minimum')); ?>" class="input input-short" />
			<em><?php echo __('Ads reached this abuse minimum will be unlisted automaticly') ?></em>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="bad_word_filter"><?php echo __('Bad word filter'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<textarea name="bad_word_filter" id="bad_word_filter"><?php echo View::escape(Config::option('bad_word_filter')); ?></textarea>
			<p><em><?php echo __('When a post contains any of these words, it will be replaced with Bad Word Replacement. One word per line.') ?></em></p>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="bad_word_replacement"><?php echo __('Bad word replacement'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="bad_word_replacement" type="text" id="bad_word_replacement" class="input input-long" value="<?php echo View::escape(Config::option('bad_word_replacement')); ?>" />
			<em><?php echo __('Bad words will be replaced with this word.') ?></em>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="bad_word_block"><?php echo __('Bad word block'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<textarea name="bad_word_block" id="bad_word_block"><?php echo View::escape(Config::option('bad_word_block')); ?></textarea>
			<p><em><?php echo __('When a post contains any of these words, it will be held in the moderation queue. One word per line.') ?></em></p>
		</div>
	</div>

	<!-- contact form spam blocking -->
	<?php
	if (IpBlock::contactLimitIsEnabled())
	{
		$_contactLimit_enabled = '<small class="label_text green">' . __('Enabled') . '</small>';
		$_contactLimit_description = '';
	}
	else
	{
		$_contactLimit_enabled = '<span class="label_text red">' . __('Disabled') . '</span>';
		$_contactLimit_description = '<p><em>' . __('To enable, all values should be bigger than zero') . '</em></p>';
	}
	?>
	<h2 id="grp_ipblock_contact_limit"><?php echo __('Contact form spam') . ' ' . $_contactLimit_enabled; ?></h2>
	<?php echo $_contactLimit_description; ?>
	<p>
		<?php
		echo __('Contact form usage reached "{str}" in "{str2}" will be blocked for "{str3}".', array(
			'{str}'	 => __('Contact form usage count'),
			'{str2}' => __('Contact form usage period'),
			'{str3}' => __('Contact form ban period')
		)) . ' <a href="' . Language::get_url('admin/logs/' . IpBlock::TYPE_CONTACT . '/') . '" class="button">' . __('View log') . '</a>';
		?>
	</p>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ipblock_contact_limit_count"><?php echo __('Contact form usage count'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ipblock_contact_limit_count" type="number" id="ipblock_contact_limit_count" value="<?php echo View::escape(Config::option('ipblock_contact_limit_count')); ?>" class="input input-short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ipblock_contact_limit_period"><?php echo __('Contact form usage period'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ipblock_contact_limit_period" type="number" id="ipblock_contact_limit_period" value="<?php echo View::escape(Config::option('ipblock_contact_limit_period')); ?>" class="input input-short" />
			<em><?php echo __('in minutes') ?></em>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ipblock_contact_ban_period"><?php echo __('Contact form ban period'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ipblock_contact_ban_period" type="number" id="ipblock_contact_ban_period" value="<?php echo View::escape(Config::option('ipblock_contact_ban_period')); ?>" class="input input-short" />
			<em><?php echo __('in minutes') ?></em>
		</div>
	</div>

	<!-- brute force login attempt blocking -->
	<?php
	if (IpBlock::loginAttemptIsEnabled())
	{
		$_loginAttemps_enabled = '<small class="label_text green">' . __('Enabled') . '</small>';
		$_loginAttemps_description = '';
	}
	else
	{
		$_loginAttemps_enabled = '<span class="label_text red">' . __('Disabled') . '</span>';
		$_loginAttemps_description = '<p><em>' . __('To enable, all values should be bigger than zero') . '</em></p>';
	}
	?>
	<h2 id="grp_ipblock_login_attempt"><?php echo __('Invalid login attempts') . ' ' . $_loginAttemps_enabled; ?></h2>
	<?php echo $_loginAttemps_description; ?>
	<p>
		<?php
		echo __('Login attempts reached "{str}" in "{str2}" will be blocked for "{str3}".', array(
			'{str}'	 => __('Allowed invalid login attempts count'),
			'{str2}' => __('Allowed invalid login attempts period'),
			'{str3}' => __('Login ban period')
		)) . ' <a href="' . Language::get_url('admin/logs/' . IpBlock::TYPE_LOGIN . '/') . '" class="button">' . __('View log') . '</a>';
		?>
	</p>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ipblock_login_attempt_count"><?php echo __('Allowed invalid login attempts count'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ipblock_login_attempt_count" type="number" id="ipblock_login_attempt_count" value="<?php echo View::escape(Config::option('ipblock_login_attempt_count')); ?>" class="input input-short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ipblock_login_attempt_period"><?php echo __('Allowed invalid login attempts period'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ipblock_login_attempt_period" type="number" id="ipblock_login_attempt_period" value="<?php echo View::escape(Config::option('ipblock_login_attempt_period')); ?>" class="input input-short" />
			<em><?php echo __('in minutes') ?></em>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ipblock_login_ban_period"><?php echo __('Login ban period'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ipblock_login_ban_period" type="number" id="ipblock_login_ban_period" value="<?php echo View::escape(Config::option('ipblock_login_ban_period')); ?>" class="input input-short" />
			<em><?php echo __('in minutes') ?></em>
		</div>
	</div>



	<!-- CAPTCHA -->
	<?php
	// check if current option is old recaptcha 
	$is_recaptcha_v1 = Captcha::isOldRecaptcha();
	?>
	<h2 id="grp_captcha"><?php echo __('Captcha'); ?></h2>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="use_captcha"><?php echo __('Use captcha'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<select name="use_captcha" id="use_captcha">
				<?php
				$use_captcha = Config::option('use_captcha');


				// check if current type available on this server 
				if (!Captcha::isAvailable($use_captcha))
				{
					// not available then fall back to simple capthca 
					$use_captcha = Captcha::TYPE_SIMPLE;
				}

				$arr_use_captcha[$use_captcha] = ' selected="selected"';
				?>
				<option value="<?php echo Captcha::TYPE_NONE; ?>" <?php echo $arr_use_captcha[Captcha::TYPE_NONE]; ?>><?php echo __('none') ?></option>
				<option value="<?php echo Captcha::TYPE_SIMPLE; ?>" <?php echo $arr_use_captcha[Captcha::TYPE_SIMPLE]; ?>><?php echo __('Simple') ?></option>
				<?php
				if ($is_recaptcha_v1)
				{
					// selected recaptcha v1 so show it 
					echo '<option value="' . Captcha::TYPE_RECAPTCHA_1 . '" ' . $arr_use_captcha[Captcha::TYPE_RECAPTCHA_1] . '>' . __('Recaptcha v1') . '</option>';
				}

				// check if server supports 
				if (Captcha::isAvailable(Captcha::TYPE_RECAPTCHA_2))
				{
					echo '<option value="' . Captcha::TYPE_RECAPTCHA_2 . '" ' . $arr_use_captcha[Captcha::TYPE_RECAPTCHA_2] . '>' . __('Recaptcha v2') . '</option>';
				}
				if (Captcha::isAvailable(Captcha::TYPE_RECAPTCHA_INVISIBLE))
				{
					echo '<option value="' . Captcha::TYPE_RECAPTCHA_INVISIBLE . '" ' . $arr_use_captcha[Captcha::TYPE_RECAPTCHA_INVISIBLE] . '>' . __('Recaptcha invisible') . '</option>';
				}
				?>
			</select>
		</div>
	</div>
    <div class="clearfix form-row use_captcha">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input name="logged_user_disable_captcha" type="checkbox" id="logged_user_disable_captcha" value="1" 
					   <?php echo (Config::option('logged_user_disable_captcha') ? 'checked="checked"' : ''); ?> />
				<span class="checkmark"></span>
				<?php echo __('Disable captcha for logged users'); ?>
			</label>
		</div>
	</div>
	<div class="use_captcha_recaptcha">
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="recaptcha_public_key"><?php echo __('Recaptcha site key'); ?></label></div>
			<div class="col col-12 sm-col-10 px1">
				<input name="recaptcha_public_key" type="text" id="recaptcha_public_key" value="<?php echo View::escape(Config::option('recaptcha_public_key')); ?>" />
				<em><a href="https://www.google.com/recaptcha/" target="_blank"><?php echo __('Get recaptcha api keys') ?></a></em>
			</div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="recaptcha_private_key"><?php echo __('Recaptcha secret key'); ?></label></div>
			<div class="col col-12 sm-col-10 px1">
				<input name="recaptcha_private_key" type="text" id="recaptcha_private_key" value="<?php echo View::escape(Config::option('recaptcha_private_key')); ?>" />
			</div>
		</div>
		<?php
		if ($is_recaptcha_v1 && Config::option('recaptcha_ajax'))
		{
			// already using old recaptcha so display related setting
			?>
			<div class="clearfix form-row">
				<div class="col col-12 sm-col-2 px1 form-label"></div>
				<div class="col col-12 sm-col-10 px1">
					<label class="input-checkbox">
						<input name="recaptcha_ajax" type="checkbox" id="recaptcha_ajax" value="1" 
							   <?php echo (Config::option('recaptcha_ajax') ? 'checked="checked"' : ''); ?> />
						<span class="checkmark"></span>
						<?php echo __('Load recaptcha using ajax'); ?>
					</label>
				</div>
			</div>
			<?php
		}
		?>
	</div>
	<!-- submit -->
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<a href="<?php echo Language::get_url('admin/') ?>" class="button link"><?php echo __('Cancel'); ?></a>
		</div>
	</div>
</form>
<script>
	addLoadEvent(function ()
	{
		$('[name="use_captcha"]').change(show_hide_recaptcha_keys);
		show_hide_recaptcha_keys();
	});
	function show_hide_recaptcha_keys()
	{
		switch ($('[name="use_captcha"]').val())
		{
			case 'recaptcha':
			case 'recaptcha2':
			case 'recaptcha_invisible':
				$('.use_captcha_recaptcha,.use_captcha').slideDown('slow');
				break;
			case 'simple':
				$('.use_captcha').slideDown('slow');
				$('.use_captcha_recaptcha').hide();
				break;
			default:
				$('.use_captcha_recaptcha,.use_captcha').hide();
		}
	}
</script>
<?php echo $this->validation()->messages(); ?>
<form name="login" method="post" action="" class="form_large" <?php echo ($dealer ? 'enctype="multipart/form-data"' : '') ?>>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label">
			<label for="email"><?php echo __('Email') ?>: </label>
		</div>
		<div class="col col-12 sm-col-8 px1">
			<input name="email" type="email" id="email" size="30" maxlength="100"  value="<?php echo View::escape($user->email) ?>" autocorrect="off" autocapitalize="off" spellcheck="false" class="input input-long" required="required" />
			<?php echo $this->validation()->email_error; ?>
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label">
			<label for="password"><?php echo __('Password') ?>:</label>
		</div>
		<div class="col col-12 sm-col-8 px1">
			<input name="password" type="password" id="password" size="30" value="" maxlength="32" autocorrect="off" autocapitalize="off" spellcheck="false" class="input input-long" required="required" />
			<?php echo $this->validation()->password_error; ?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label">
			<label for="password_repeat"><?php echo __('Repeat password') ?>: </label>
		</div>
		<div class="col col-12 sm-col-8 px1">
			<input name="password_repeat" type="password" id="password_repeat" size="30" maxlength="32" autocorrect="off" autocapitalize="off" spellcheck="false" value="" class="input input-long" required="required" />
			<?php echo $this->validation()->password_repeat_error; ?>
		</div>
	</div>
	<?php
	// dealer options
	if ($dealer)
	{
		$logo_dimention = '(' . Config::option('dealer_logo_width') . 'x' . Config::option('dealer_logo_height') . ' pixel.)';

		echo '<div class="clearfix form-row">
				<div class="col col-12 sm-col-4 px1 form-label">
					<label for="web">' . __('Website') . ': </label>
				</div>
				<div class="col col-12 sm-col-8 px1">
					<input name="web" type="url" id="web" size="30" maxlength="100" value="' . View::escape($user->web) . '"  autocorrect="off" autocapitalize="off" spellcheck="false" class="input input-long" />
					' . $this->validation()->web_error . '
					<input name="dealer" type="hidden" id="dealer" value="1"  />
				</div>
			</div>
			<div class="clearfix form-row">
				<div class="col col-12 sm-col-4 px1 form-label">
					<label for="logo">' . __('Logo') . ': </label>
				</div>
				<div class="col col-12 sm-col-8 px1">
					<input name="logo" type="file" id="logo" size="30" class="input" />
					' . $this->validation()->logo_error . '
					<em>' . $logo_dimention . '</em>
				</div>
			</div>
			<div class="clearfix form-row">
				<div class="col col-12 sm-col-4 px1 form-label">
					<label for="info">' . __('Info') . ': </label>
				</div>
				<div class="col col-12 sm-col-8 px1">
					<textarea name="info" id="info">' . View::escape($user->info) . '</textarea>
					' . $this->validation()->info_error . '
					<em>' . __('(address, contacts, work hours, etc. max 1000 characters)') . '</em>
				</div>
			</div>
			';
	}

	echo Captcha::render('<div class="clearfix form-row">
			<div class="col col-12 sm-col-4 px1 form-label">
				<label for="{name}">{label}: </label>
			</div>
			<div class="col col-12 sm-col-8 px1">
				{input}
			</div>
		</div>');
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label"></div>
		<div class="col col-12 sm-col-8 px1">
			<label class="input-checkbox">
				<input name="read" type="checkbox" id="read" value="1" required="required">
				<span class="checkmark"></span>
				<?php
				echo __('I have read and agree to the {terms}', array(
					'{terms}' => Page::pageLink('page_id_terms')
				))
				?>
			</label>
			<?php echo $this->validation()->read_error; ?>	
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label"></div>
		<div class="col col-12 sm-col-8 px1">
			<input name="submit"  type="submit" value="<?php echo __('Create my account') ?>"  class="button" />
			<a href="<?php echo Language::get_url() ?>" class="button link js_back_if_same"><?php echo __('Cancel') ?></a>
			<input type="hidden" name="rd" value="<?php echo View::escape($rd) ?>" />
			<?php echo Config::nounceInput(); ?>	
		</div>
	</div>
</form>
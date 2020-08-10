<?php echo $this->validation()->messages(); ?>
<form action="<?php echo Language::get_url('login/login/'); ?>" method="post" class="form_large">


	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label"><label for="login-username"><?php echo __('Email'); ?>:</label></div>
		<div class="col col-12 sm-col-8 px1">
			<input id="login-username" type="email" name="login[username]" value="<?php echo $this->escape($username) ?>" size="30" autocorrect="off" autocapitalize="off" spellcheck="false" class="input input-long" required="required" />
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label"><label for="login-password"><?php echo __('Password'); ?>:</label></div>
		<div class="col col-12 sm-col-8 px1">
			<input id="login-password" type="password" name="login[password]" value="" size="30" autocorrect="off" autocapitalize="off" spellcheck="false" class="input input-long" required="required" />
		</div>
	</div>
	<?php
	/*
	  ?><p class="center">
	  <input id="login-remember-me" type="checkbox" name="login[remember]" value="checked" />
	  <label class="checkbox" for="login-remember-me"><?php echo __('Remember my username on this computer'); ?></label>
	  </p>
	  <?php
	 */
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label"></div>
		<div class="col col-12 sm-col-8 px1">
			<input type="submit" name="submit" accesskey="s" value="<?php echo __('Login'); ?>" class="button" />
			<a href="<?php echo Language::get_url(); ?>" class="button link js_back_if_same"><?php echo __('Cancel'); ?></a>
		</div>
	</div>


	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label"></div>
		<div class="col col-12 sm-col-8 px1">

			<?php
			// register as dealer permission
			// register as user permission 
			if (Config::option('account_user') && Config::option('account_user_can_register'))
			{
				// display register as user link
				echo '<a href="' . Language::get_url('login/register/'), '" class="button link">' . __('Register') . '</a> ';
			}
			if (Config::option('account_dealer') && Config::option('account_dealer_can_register'))
			{
				// display register as user link
				echo '<a href="' . Language::get_url('login/register/dealer/'), '" class="button link">' . __('Register as dealer') . '</a> ';
			}
			?>
			<a href="<?php echo Language::get_url('login/forgot/'); ?>" class="button link"><?php echo __('Forgot password'); ?></a> 
		</div>
	</div>
</form>
<script type="text/javascript" language="javascript" charset="utf-8">
	// <![CDATA[
	// focus to input field  
	var loginUsername = document.getElementById('login-username');
	if (loginUsername.value == '')
	{
		loginUsername.focus();
	}
	else
	{
		document.getElementById('login-password').focus();
	}
	// ]]>
</script>

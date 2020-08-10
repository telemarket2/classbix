<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Account') ?></h1>
<form action="" method="post" id="settings_form">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="account_dealer"><?php echo __('Dealer Account'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$account_dealer[Config::option('account_dealer')] = 'checked="checked"';
			?>
			<label class="input-radio">
				<input name="account_dealer" type="radio" id="account_dealer_1" value="1" <?php echo $account_dealer[1] ?> /> 
				<span class="checkmark"></span>
				<?php echo __('Enabled'); ?>
			</label>
			<label class="input-radio">
				<input name="account_dealer" type="radio" id="account_dealer_0" value="0" <?php echo $account_dealer[0] ?> /> 
				<span class="checkmark"></span>
				<?php echo __('Disabled'); ?>
			</label>

			<div class="account_dealer_1">
				<?php
				// use array and loop for setting checkboxes 
				$arr_checks = array(
					'account_dealer_can_register'				 => __('Any visitor can register as dealer account'),
					'account_dealer_auto_approve_registration'	 => __('Auto approve all visitors registering as dealer'),
					'account_dealer_move_from_user'				 => __('User account can be upgraded to dealer account'),
					'account_dealer_move_from_user_auto_approve' => __('Auto approve dealer account upgraded from user account'),
					'account_dealer_display_info_ad_page'		 => __('Display dealer info on ad page'),
				);

				foreach ($arr_checks as $k => $v)
				{
					echo '<div class="my1">
							<label class="input-checkbox">
								<input type="checkbox" name="' . $k . '" id="' . $k . '" value="1" ' . (Config::option($k) ? 'checked="checked"' : '') . ' />
								<span class="checkmark"></span>
								' . $v . '
							</label>
						</div>';
				}
				?>
			</div>
		</div>
	</div>
	<div class="clearfix form-row account_dealer_1">
		<div class="col col-12 sm-col-2 px1 form-label">
			<label for="account_dealer_display_logo_listing"><?php echo __('Display dealer logo on listing page'); ?></label>
		</div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$account_dealer_display_logo_listing = Config::option('account_dealer_display_logo_listing');
			$input_element = '<select name="account_dealer_display_logo_listing" id="account_dealer_display_logo_listing">
							<option value="">' . __('Never') . '</option>
							<option value="no_ad_image">' . __('For ads without image') . '</option>
							<option value="always">' . __('Always') . '</option>
						</select>';
			echo str_replace('value="' . $account_dealer_display_logo_listing . '"', 'value="' . $account_dealer_display_logo_listing . '" selected="selected"', $input_element);
			?>
		</div>
	</div>
	<hr>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="account_user"><?php echo __('User Account'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$account_user[Config::option('account_user')] = 'checked="checked"';
			?>
			<label class="input-radio">
				<input name="account_user" type="radio" id="account_user_1" value="1" <?php echo $account_user[1] ?> /> 
				<span class="checkmark"></span>
				<?php echo __('Enabled'); ?>
			</label>
			<label class="input-radio">
				<input name="account_user" type="radio" id="account_user_0" value="0" <?php echo $account_user[0] ?> /> 
				<span class="checkmark"></span>
				<?php echo __('Disabled'); ?>
			</label>

			<div class="account_user_1">
				<?php
				$arr_checks = array(
					'account_user_can_register'				 => __('Any visitor can register as user account'),
					'account_user_auto_approve_registration' => __('Auto approve all visitors registering as user')
				);
				foreach ($arr_checks as $k => $v)
				{
					echo '<div class="my1">
							<label class="input-checkbox">
								<input type="checkbox" name="' . $k . '" id="' . $k . '" value="1" ' . (Config::option($k) ? 'checked="checked"' : '') . ' />
								<span class="checkmark"></span>
								' . $v . '
							</label>
						</div>';
				}
				?>				
			</div>
		</div>
	</div>
	<hr>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$arr_checks = array(
				'ad_posting_without_registration' => __('Ad posting without registration')
			);
			foreach ($arr_checks as $k => $v)
			{
				echo '<div class="my1">
						<label class="input-checkbox">
							<input type="checkbox" name="' . $k . '" id="' . $k . '" value="1" ' . (Config::option($k) ? 'checked="checked"' : '') . ' />
							<span class="checkmark"></span>
							' . $v . '
						</label>
					</div>';
			}
			?>
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="dealer_logo_height"><?php echo __('Dealer logo height'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="dealer_logo_height" type="text" id="dealer_logo_height" value="<?php echo View::escape(Config::option('dealer_logo_height')); ?>" class="short" />
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="dealer_logo_width"><?php echo __('Dealer logo width'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="dealer_logo_width" type="text" id="dealer_logo_width" value="<?php echo View::escape(Config::option('dealer_logo_width')); ?>"  class="short" />
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="default_user_permission"><?php echo __('Default user permission'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$default_user_permission = Config::option('default_user_permission');
			$input_element = '<select name="default_user_permission" id="default_user_permission">
						<option value="' . User::PERMISSION_USER . '">' . User::getLevel(User::PERMISSION_USER) . '</option>
						<option value="' . User::PERMISSION_DEALER . '">' . User::getLevel(User::PERMISSION_DEALER) . '</option>
						</select>';
			echo str_replace('value="' . $default_user_permission . '"', 'value="' . $default_user_permission . '" selected="selected"', $input_element);
			?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<a href="<?php echo Language::get_url('admin/') ?>" class="button link"><?php echo __('Cancel'); ?></a>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1"></div>
	</div>
</form>
<script>
	addLoadEvent(function ()
	{
		$('input[name="account_user"],input[name="account_dealer"]').change(show_hide_extra);
		$('input[name="account_user"],input[name="account_dealer"]').change();

	});
	function show_hide_extra()
	{
		var $me = $(this);
		var name = $me.attr('name');
		if ($('#' + name + "_1").prop('checked'))
		{
			$('.' + name + "_1").slideDown();
		}
		else
		{
			$('.' + name + "_1").slideUp();
		}
	}


</script>
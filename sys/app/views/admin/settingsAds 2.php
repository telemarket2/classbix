<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Ads') ?></h1>
<form action="" method="post" id="settings_form">
	<!-- General -->
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ads_featured_per_page"><?php echo __('Ads featured per page'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ads_featured_per_page" type="text" id="ads_featured_per_page" value="<?php echo intval(Config::option('ads_featured_per_page')); ?>" class="short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ads_listed_per_page"><?php echo __('Ads listed per page'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ads_listed_per_page" type="text" id="ads_listed_per_page" value="<?php echo intval(Config::option('ads_listed_per_page')); ?>" class="short" />

			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="ads_separate" id="ads_separate" value="1"
						   <?php echo (Config::option('ads_separate') ? 'checked="checked"' : ''); ?> />
					<span class="checkmark"></span>
					<?php echo __('Separate consecutive ads by same user'); ?>
				</label>
			</p>

		</div>
	</div>

    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ads_verification_days"><?php echo __('Ads verification days'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ads_verification_days" type="text" id="ads_verification_days" value="<?php echo intval(Config::option('ads_verification_days')); ?>" class="short" />
			<p><em><?php echo __('Ads and user accounts with not verified email addresses older than this period will be deleted from system') ?></em></p>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="expire_days"><?php echo __('Expire days'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="expire_days" type="text" id="expire_days" value="<?php echo intval(Config::option('expire_days')); ?>" class="short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="delete_after_days"><?php echo __('Delete ad after days'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="delete_after_days" type="text" id="delete_after_days" value="<?php echo intval(Config::option('delete_after_days')); ?>" class="short" />
			<em><?php echo __('Auto delete ads after number days past from expiring to keep database clean. Set -1 if you do not want to delete expired ads.') ?></em>
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="move_to_trash_after_days"><?php echo __('Move to trash after days'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$auto_move_to_trash_after = trim(Config::option('auto_move_to_trash_after'));
			$auto_move_to_trash_after = @unserialize($auto_move_to_trash_after);
			$auto_move_to_trash_after_days = trim($auto_move_to_trash_after['days']);
			if ($auto_move_to_trash_after_days != '')
			{
				$auto_move_to_trash_after_days = intval($auto_move_to_trash_after_days);
			}
			if (!isset($auto_move_to_trash_after['status']))
			{
				$auto_move_to_trash_after['status'] = array();
			}
			?>
			<input name="auto_move_to_trash_after[days]" type="text" id="auto_move_to_trash_after_days" value="<?php echo $auto_move_to_trash_after_days; ?>" class="short" />
			<em><?php echo __('Auto move to trash ads with following status') ?></em>

			<?php
			// statuses that can be moved to trash automaticly 
			$arr_move_to_trash = array(
				Ad::STATUS_BANNED,
				Ad::STATUS_DUPLICATE,
				Ad::STATUS_INCOMPLETE,
				Ad::STATUS_COMPLETED,
				Ad::STATUS_PENDING_APPROVAL,
				Ad::STATUS_PAUSED
			);

			echo '<div>';
			foreach ($arr_move_to_trash as $status)
			{
				echo '<label class="input-checkbox">
						<input type="checkbox" name="auto_move_to_trash_after[status][]" value="' . $status . '" '
				. (in_array($status, $auto_move_to_trash_after['status']) ? 'checked="checked"' : '') . ' >
						<span class="checkmark"></span>
						' . Ad::statusName($status) . '
					</label> ';
			}
			echo '</div>';
			?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="auto_delete_from_trash_days"><?php echo __('Delete from trash days'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="auto_delete_from_trash_days" type="text" id="auto_delete_from_trash_days" value="<?php
			$auto_delete_from_trash_days = trim(Config::option('auto_delete_from_trash_days'));
			if (strlen($auto_delete_from_trash_days))
			{
				// not empty string then convert to integer 
				$auto_delete_from_trash_days = intval($auto_delete_from_trash_days);
			}
			echo $auto_delete_from_trash_days;
			?>" class="short" />
			<em><?php echo __('Completely delete items in trash after this days.') ?></em>
		</div>
	</div>

    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="default_location"><?php echo __('Default location'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php echo Location::selectBox(Config::option('default_location'), 'default_location', Location::STATUS_ENABLED, true, __('All locations'), 0, true); ?>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Sticky location'); ?></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input type="checkbox" name="location_cookie" id="location_cookie" value="1"
					   <?php echo (Config::option('location_cookie') ? 'checked="checked"' : ''); ?> />
				<span class="checkmark"></span>
				<?php echo __('Store location selected by site visitor in cookie'); ?>
			</label>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label">
			<label><?php echo __('Other') ?></label>
		</div>
		<div class="col col-12 sm-col-10 px1">
			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="url_to_link" id="url_to_link" value="1"
						   <?php echo (Config::option('url_to_link') ? 'checked="checked"' : ''); ?> />
					<span class="checkmark"></span>
					<?php echo __('Convert text urls to clickable links'); ?>
				</label>
			</p>
			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="hide_phone_title" id="hide_phone_title" value="1"
						   <?php echo (Config::option('hide_phone_title') ? 'checked="checked"' : ''); ?> />
					<span class="checkmark"></span>
					<?php echo __('Hide phone number in title and excerpt'); ?>
				</label>
			</p>
			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="view_contact_registered_only" id="view_contact_registered_only" value="1"
						   <?php echo (Config::option('view_contact_registered_only') ? 'checked="checked"' : ''); ?> />
					<span class="checkmark"></span>
					<?php echo __('Only registered users can view contact details'); ?>
				</label>
			</p>
			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="disable_ad_counting" id="disable_ad_counting" value="1"
						   <?php echo (Config::option('disable_ad_counting') ? 'checked="checked"' : ''); ?> />
					<span class="checkmark"></span>
					<?php echo __('Disable ad counting'); ?>

					<span class="block"><em><?php echo __('Disabled automaticly if you reach Locations X Categories > 50000') ?></em></span>
				</label>
			</p>			
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="disable_extending_ads"><?php echo __('Disable extending paid ads'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$disable_extending_ads = Config::option('disable_extending_ads');
			$input_element = '<select name="disable_extending_ads" id="disable_extending_ads">
						<option value="0">' . __('Allow users to extend all ads') . '</option>
						<option value="1">' . __('Disable users to extend paid ads') . '</option>
						<option value="2">' . __('Disable users to extend all ads') . '</option>
						</select>';
			echo str_replace('value="' . $disable_extending_ads . '"', 'value="' . $disable_extending_ads . '" selected="selected"', $input_element);
			?>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="extend_ad_days"><?php echo __('Extend ad days'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="extend_ad_days" type="text" id="extend_ad_days" value="<?php echo View::escape(implode(',', Config::getExtendAdDays())); ?>" />
			<em><?php echo __('Comma seperated days for example 10,30,100,365'); ?></em>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="renew_ad_days"><?php echo __('Renew ads'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="renew_ad" id="renew_ad" value="1" 
						   <?php echo (Config::option('renew_ad') ? 'checked="checked"' : ''); ?> />
					<span class="checkmark"></span>
					<?php echo __('Allow users to renew ads'); ?>
				</label>
			</p>
			<p>
				<input name="renew_ad_days" type="number" id="renew_ad_days" class="input input-short" value="<?php echo View::escape(Config::option('renew_ad_days')); ?>" />
				<em><?php echo __('Number of days after ad published or renewed.'); ?></em>
			</p>
		</div>
	</div>




	<!-- MODERATION -->
	<h2 id="grp_moderation"><?php echo __('Ad moderation'); ?></h2>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ads_auto_approve"><?php echo __('Ads auto approve'); ?>:</label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$ads_auto_approve = Config::option('ads_auto_approve');
			$input_element = '<select name="ads_auto_approve" id="ads_auto_approve">
						<option value="0">' . __('None - approve ads manually') . '</option>
						<option value="1">' . __('Auto approve all ads by user and dealer') . '</option>
						<option value="2">' . __('Auto approve ads by user') . '</option>
						<option value="3">' . __('Auto approve ads by dealer') . '</option>
						<option value="4">' . __('Auto approve ads posted by previously approved users') . '</option>
						</select>';
			echo str_replace('value="' . $ads_auto_approve . '"', 'value="' . $ads_auto_approve . '" selected="selected"', $input_element);
			?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$arr_checks = array(
				'notify_admin_pending_approval' => __('Notify admin about actions pending approval'),
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
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ad_moderation_similarity_min"><?php echo __('Minimum similarity to hold for moderation'); ?>:</label></div>
		<div class="col col-12 sm-col-10 px1">
			<span class="input-group input-short">
				<input type="number" name="ad_moderation_similarity_min" id="ad_moderation_similarity_min" value="<?php echo View::escape(Config::option('ad_moderation_similarity_min')); ?>" class="input">
				<span class="button addon">%</span>
			</span>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ad_moderation_limit_posting"><?php echo __('Limit posting if number of items pending moderation reached'); ?>:</label></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="number" name="ad_moderation_limit_posting" id="ad_moderation_limit_posting" value="<?php echo View::escape(Config::option('ad_moderation_limit_posting')); ?>" class="input input-short">
		</div>
	</div>


	<!-- Throttle ad posting -->
	<?php
	if (IpBlock::postLimitIsEnabled())
	{
		$_throttlePosting_enabled = '<small class="label_text green">' . __('Enabled') . '</small>';
		$_throttlePosting_description = '';
	}
	else
	{
		$_throttlePosting_enabled = '<span class="label_text red">' . __('Disabled') . '</span>';
		$_throttlePosting_description = '<p><em>' . __('To enable, all values should be bigger than zero') . '</em></p>';
	}
	?>
	<h2 id="grp_ipblock_post_limit"><?php echo __('Throttle posting') . ' ' . $_throttlePosting_enabled; ?></h2>
	<?php echo $_throttlePosting_description; ?>
	<p>
		<?php
		echo __('"{str}" reached in "{str2}" will be limited for "{str3}". Throttling is not applied to admins and moderators.', array(
			'{str}'	 => __('Post count'),
			'{str2}' => __('Post period'),
			'{str3}' => __('Post limit period')
		)) . ' <a href="' . Language::get_url('admin/logs/' . IpBlock::TYPE_POST . '/') . '" class="button">' . __('View log') . '</a>';
		?>
	</p>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ipblock_post_limit_count"><?php echo __('Post count'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ipblock_post_limit_count" type="number" id="ipblock_post_limit_count" value="<?php echo View::escape(Config::option('ipblock_post_limit_count')); ?>" class="input input-short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ipblock_post_limit_period"><?php echo __('Post period'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ipblock_post_limit_period" type="number" id="ipblock_post_limit_period" value="<?php echo View::escape(Config::option('ipblock_post_limit_period')); ?>" class="input input-short" />
			<em><?php echo __('in minutes') ?></em>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ipblock_post_ban_period"><?php echo __('Post limit period'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ipblock_post_ban_period" type="number" id="ipblock_post_ban_period" value="<?php echo View::escape(Config::option('ipblock_post_ban_period')); ?>" class="input input-short" />
			<em><?php echo __('in minutes') ?></em>
		</div>
	</div>







	<!-- IMAGE -->
	<h2 id="grp_image"><?php echo __('Image'); ?></h2>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ad_image_num"><?php echo __('Image uploads per ad'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ad_image_num" type="number" id="ad_image_num" value="<?php echo View::escape(Config::option('ad_image_num')); ?>" class="input input-short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ad_image_width"><?php echo __('Ad image width'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ad_image_width" type="number" id="ad_image_width" value="<?php echo View::escape(Config::option('ad_image_width')); ?>" class="input input-short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ad_image_height"><?php echo __('Ad image height'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ad_image_height" type="number" id="ad_image_height" value="<?php echo View::escape(Config::option('ad_image_height')); ?>" class="input input-short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ad_thumbnail_width"><?php echo __('Ad thumbnail width'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ad_thumbnail_width" type="number" id="ad_thumbnail_width" value="<?php echo View::escape(Config::option('ad_thumbnail_width')); ?>" class="input input-short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ad_thumbnail_height"><?php echo __('Ad thumbnail height'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ad_thumbnail_height" type="number" id="ad_thumbnail_height" value="<?php echo View::escape(Config::option('ad_thumbnail_height')); ?>" class="input input-short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ad_image_max_width"><?php echo __('Ad image max width'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ad_image_max_width" type="number" id="ad_image_max_width" value="<?php echo View::escape(Adpics::getImagesMaxWidth()); ?>" class="input input-short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ad_image_max_height"><?php echo __('Ad image max height'); ?>:</label></div>
		<div class="col col-12 sm-col-10 px1"><input name="ad_image_max_height" type="number" id="ad_image_max_height" value="<?php echo View::escape(Adpics::getImagesMaxHeight()); ?>" class="input input-short" /></div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="ad_image_max_filesize"><?php echo __('Ad image max filesize (Kb)'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="ad_image_max_filesize" type="number" id="ad_image_max_filesize" value="<?php echo View::escape(Config::option('ad_image_max_filesize')); ?>" class="input input-short" />
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="image_watermark_text"><?php echo __('Image watermark text'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="image_watermark_text" type="text" id="image_watermark_text" value="<?php echo View::escape(Config::option('image_watermark_text')); ?>" />
			<p><em><?php echo __('Suggested values: {@VIEWADLINKSHORT}, {@SITENAME}. Leave empty if you do not want to add watermark text.'); ?></em></p>
		</div>
	</div>

	<!-- MAP -->
	<h2 id="grp_image"><?php echo __('Map'); ?></h2>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label">
			<label><?php echo __('Display map') ?></label>
		</div>
		<div class="col col-12 sm-col-10 px1">
			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="map_enabled" id="map_enabled" value="1"
						   <?php echo (Map::isEnabled() ? 'checked="checked"' : ''); ?> />
					<span class="checkmark"></span>
					<?php echo __('Displays map for ads with address custom field.'); ?>
				</label>
			</p>
			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="map_append_to_description" id="map_append_to_description" value="1"
						   <?php echo (Map::isAppendToDescription() ? 'checked="checked"' : ''); ?> />
					<span class="checkmark"></span>				
					<?php echo __('Display map for old themes'); ?>
					<em><?php echo __('Select this if your theme has no map support.'); ?></em>
				</label>
			</p>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="map_zoom_level"><?php echo __('Zoom level'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="map_zoom_level" type="text" id="map_zoom_level" value="<?php echo View::escape(Map::zoomLevel()); ?>" class="input input-short" />
			<em><?php echo __('0 - whole world map, 14 - street level map'); ?></em>
		</div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="map_google_api_key"><?php echo __('Google maps API key'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="map_google_api_key" type="text" id="map_google_api_key" value="<?php echo View::escape(Config::option('map_google_api_key')); ?>" />
			<em><?php echo __('See <a href="{url}" target="_blank">usage limits</a> without API key.', array('{url}' => 'https://developers.google.com/maps/documentation/javascript/usage#usage_limits')); ?></em>
		</div>
	</div>

	<!-- POST -->
	<h2 id="grp_image"><?php echo __('Ad posting'); ?></h2>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label">
			<label><?php echo __('Hide fields') ?></label>
		</div>
		<div class="col col-12 sm-col-10 px1">
			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="hide_othercontactok" id="hide_othercontactok" value="1"
						   <?php echo (Config::option('hide_othercontactok') ? 'checked="checked"' : ''); ?> />
					<span class="checkmark"></span>
					<?php echo __('Hide "{name}" field', array('{name}' => __('People with other commercial requests can contact me.'))); ?>:
				</label>
			</p>
			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="hide_agree" id="hide_agree" value="1"
						   <?php echo (Config::option('hide_agree') ? 'checked="checked"' : ''); ?> />
					<span class="checkmark"></span>
					<?php echo __('Hide "{name}" field', array('{name}' => __('Agree to site terms and conditions'))); ?>
				</label>
			</p>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label">
			<label><?php echo __('Enabled contact options'); ?></label>
		</div>
		<div class="col col-12 sm-col-10 px1">
			<p>
				<label class="input-checkbox">
					<input name="showemail_0" id="showemail_0" type="checkbox" value="1" <?php echo (Config::option('showemail_0') ? 'checked="checked"' : '') ?> /> 
					<span class="checkmark"></span>
					<?php echo __('Do not show my email address, contact by phone only.') ?>
				</label>
			</p>
			<p>
				<label class="input-checkbox">
					<input name="showemail_2" id="showemail_2" type="checkbox" value="1" <?php echo (Config::option('showemail_2') ? 'checked="checked"' : '') ?> /> 
					<span class="checkmark"></span>
					<?php echo __('Do not show my email address but allow to send me email using contact form.') ?>
				</label>
			</p>
			<p>
				<label class="input-checkbox">
					<input name="showemail_1" id="showemail_1" type="checkbox" value="1" <?php echo (Config::option('showemail_1') ? 'checked="checked"' : '') ?> />
					<span class="checkmark"></span>
					<?php echo __('Show my email address to everyone.') ?>
				</label>
			</p>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="default_contact_option"><?php echo __('Default contact option'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$default_contact_option[Config::option('default_contact_option')] = 'selected="selected"';
			?>
			<select name="default_contact_option" id="default_contact_option" class="input input-long">
				<option value="showemail_0" <?php echo $default_contact_option['showemail_0'] ?>><?php echo __('Do not show my email address, contact by phone only.') ?></option>
				<option value="showemail_2" <?php echo $default_contact_option['showemail_2'] ?>><?php echo __('Do not show my email address but allow to send me email using contact form.') ?></option>
				<option value="showemail_1" <?php echo $default_contact_option['showemail_1'] ?>><?php echo __('Show my email address to everyone.') ?></option>
			</select>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label">
			<label><?php echo __('Required fields'); ?></label>
		</div>
		<div class="col col-12 sm-col-10 px1">
			<p>
				<label class="input-checkbox">
					<input name="required_image" id="required_image" type="checkbox" value="1" <?php echo (Config::option('required_image') ? 'checked="checked"' : '') ?> /> 
					<span class="checkmark"></span>
					<?php echo __('Image') ?>
				</label>
			</p>
			<p>
				<label class="input-checkbox">
					<input name="required_phone" id="required_phone" type="checkbox" value="1" <?php echo (Config::option('required_phone') ? 'checked="checked"' : '') ?> /> 
					<span class="checkmark"></span>
					<?php echo __('Phone') ?>
				</label>
			</p>
		</div>
	</div>

	<!-- PHONE  -->
	<h2 id="grp_phone"><?php echo __('Phone'); ?></h2>
	<?php
	$language = Language::getLanguages();

// render multilingual inputs 
	$tab_key = 'phone_';
	$tabs_pattern = '<div class="clearfix form-row"><div class="col col-12 px1 tabs">{tabs}</div></div>';
	echo Language::tabs($language, $tab_key, $tabs_pattern);
	foreach ($language as $lng)
	{
		// phone_hint
		$lng_label = Language::tabsLabelLngInfo($language, $lng);
		$key_lng = '_ml_phone_hint[' . $lng->id . ']';
		$input_element = '<input name="' . $key_lng . '" type="text" id="' . $key_lng . '" 
				value="' . View::escape(Config::option('_ml_phone_hint', $lng->id)) . '" class="input input-long" />';
		echo '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
				<div class="col col-12 sm-col-2 px1 form-label"><label for="' . $key_lng . '">' . __('Phone hint') . $lng_label . '</label></div>
				<div class="col col-12 sm-col-10 px1">'
		. $input_element
		. '<em>' . __('Displayed as help text on phone input fields to users. Can have examples of valid phone numbers.') . '</em>'
		. '</div>
			</div>
			';
	}
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="phone_regex"><?php echo __('Phone regex'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="phone_regex" type="text" id="phone_regex" 
				   value="<?php echo View::escape(Config::option('phone_regex')) ?>" class="input input-long" />
			<em><?php echo __('Used for validating phone input'); ?></em>
		</div>
	</div>


	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<a href="<?php echo Language::get_url('admin/') ?>" class="button link"><?php echo __('Cancel'); ?></a>
		</div>
	</div>
</form>
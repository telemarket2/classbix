<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Settings') ?></h1>
<form action="" method="post" id="settings_form" enctype="multipart/form-data">

	<?php
	$language = Language::getLanguages();

	// render multilingual inputs 
	$tab_key = 'site_title_';
	$tabs_pattern = '<div class="clearfix form-row"><div class="col col-12 px1 tabs">{tabs}</div></div>';
	echo Language::tabs($language, $tab_key, $tabs_pattern);
	foreach ($language as $lng)
	{
		// site title
		$lng_label = Language::tabsLabelLngInfo($language, $lng);
		$key_lng = 'site_title[' . $lng->id . ']';
		$input_element = '<input name="' . $key_lng . '" type="text" id="' . $key_lng . '" 
				value="' . View::escape(Config::option('site_title', $lng->id)) . '" class="input input-long" />';
		echo '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
				<div class="col col-12 sm-col-2 px1 form-label"><label for="' . $key_lng . '">' . __('Site title') . $lng_label . '</label></div>
				<div class="col col-12 sm-col-10 px1">' . $input_element . '</div>
			</div>';

		// site description 
		$key_lng = 'site_description[' . $lng->id . ']';
		$input_element = '<input name="' . $key_lng . '" type="text" id="' . $key_lng . '" 
				value="' . View::escape(Config::option('site_description', $lng->id)) . '" class="input input-long" />';
		echo '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
				<div class="col col-12 sm-col-2 px1 form-label"><label for="' . $key_lng . '">' . __('Site description') . $lng_label . '</label></div>
				<div class="col col-12 sm-col-10 px1">' . $input_element . '</div>
			</div>';

		// post button
		$key_lng = 'site_button_title[' . $lng->id . ']';
		I18n::saveLocale();
		I18n::setLocale($lng->id);
		$input_element = '<input name="' . $key_lng . '" type="text" id="' . $key_lng . '" 
				value="' . View::escape(Config::optionElseDefault('site_button_title', __('Post ad'), $lng->id)) . '" class="input input-long"/>';
		I18n::restoreLocale();
		echo '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
				<div class="col col-12 sm-col-2 px1 form-label"><label for="' . $key_lng . '">' . __('Post button title') . $lng_label . '</label></div>
				<div class="col col-12 sm-col-10 px1">' . $input_element . '</div>
			</div>';
	}
	?>

	<hr>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="upload_favicon"><?php echo __('Favicon'); ?>:</label></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="file" name="upload_favicon" id="upload_favicon" class="input"  />
			<em><?php echo __('{size} pixel image. Will be used as favicon, app icon for iphone and android.', array('{size}' => '512x512')); ?></em>
			<?php
			$favicon = Config::faviconUrl();
			if ($favicon)
			{
				echo '<p><img src="' . $favicon . '" /></p>';
			}
			?>
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="listing_permalinks"><?php echo __('Listing permalinks'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<select name="listing_permalinks" id="listing_permalinks">
				<?php
				$arr_listing_permalinks[Config::option('listing_permalinks')] = ' selected="selected"';
				?>
				<option value="loc_cat" <?php echo $arr_listing_permalinks['loc_cat']; ?>><?php echo __('Location') . '/' . __('Category') ?></option>
				<option value="cat_loc" <?php echo $arr_listing_permalinks['cat_loc']; ?>><?php echo __('Category') . '/' . __('Location') ?></option>
			</select>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="website_protocol"><?php echo __('Protocol'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<select name="website_protocol" id="website_protocol">
				<?php
				$arr_website_protocol[Config::option('website_protocol')] = ' selected="selected"';
				?>
				<option value="" <?php echo $arr_website_protocol['']; ?>><?php echo __('no change') ?></option>
				<option value="http" <?php echo $arr_website_protocol['http']; ?>>HTTP</option>
				<option value="https" <?php echo $arr_website_protocol['https']; ?>>HTTPS</option>
			</select>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="date_format"><?php echo __('Date format'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<p class="mt0">
				<input name="date_format" type="text" id="date_format" value="<?php echo View::escape(Config::optionElseDefault('date_format', 'd/m/Y')); ?>" />
				<em><?php
					echo __('Date format: {str}. Now: {str2}', array(
						'{str}'	 => 'd/m/Y, m/d/Y, Y-m-d, F j Y',
						'{str2}' => Config::date()
					));
					?></em>
			</p>
			<p>
				<input name="date_time_format" type="text" id="date_time_format" value="<?php echo View::escape(Config::optionElseDefault('date_time_format', 'd/m/Y H:i:s')); ?>" />
				<em><?php
					echo __('Date with time format: {str}. Now: {str2}', array(
						'{str}'	 => 'd/m/Y H:i:s, D M j G:i:s T Y',
						'{str2}' => Config::dateTime()
					));
					?></em>
			</p>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input type="checkbox" name="display_classibase_news" id="display_classibase_news" value="1"
					   <?php echo (Config::option('display_classibase_news') ? 'checked="checked"' : ''); ?> />
				<span class="checkmark"></span>
				<?php echo __('Display classibase news on admin dashboard'); ?>
			</label>
		</div>
	</div>



	<!-- Pages -->
	<h2 id="grp_pages"><?php echo __('Pages'); ?></h2>
	<p><em><?php echo __('Set following page destinations. Pages can be managed on <a href="{url}">here</a>.', array('{url}' => Language::get_url('admin/pages/'))); ?></em></p>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="page_id_terms"><?php echo __('Terms and conditions'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php echo Page::selectBox(Config::option('page_id_terms'), 'page_id_terms', Page::STATUS_ALL, true, __('None')); ?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="page_id_payment"><?php echo __('Paid options'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php echo Page::selectBox(Config::option('page_id_payment'), 'page_id_payment', Page::STATUS_ALL, true, __('None')); ?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="page_id_contactus"><?php echo __('Contact us'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php echo Page::selectBox(Config::option('page_id_contactus'), 'page_id_contactus', Page::STATUS_ALL, true, __('None')); ?>
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
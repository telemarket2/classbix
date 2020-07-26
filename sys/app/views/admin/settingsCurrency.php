<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Currency') ?></h1>
<form action="" method="post" id="settings_form">
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="currency"><?php echo __('Currency'); ?></label></div>
		<div class="col col-12 sm-col-10 px1"><input name="currency" type="text" id="currency" value="<?php echo View::escape(Config::option('currency')); ?>" class="short" /></div>
	</div>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="currency_iso_4721"><?php echo __('ISO 4217'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="currency_iso_4721" type="text" id="currency_iso_4721" value="<?php echo View::escape(Config::option('currency_iso_4721')); ?>" class="short" />
			<em><?php echo __('The currency (in 3-letter <a href="{url}" target="blank">ISO 4217</a> format), used for schema.org microdata.', array('{url}' => 'http://en.wikipedia.org/wiki/ISO_4217')); ?></em>
		</div>
	</div>
	<?php
	$language = Language::getLanguages();

	// render multilingual inputs 
	$tab_key = 'currency_';
	$tabs_pattern = '<div class="clearfix form-row"><div class="col col-12 px1 tabs">{tabs}</div></div>';
	echo Language::tabs($language, $tab_key, $tabs_pattern);

	$_arr_fields = array(
		'currency_format'				 => __('Currency format'),
		'currency_decimal_num'			 => __('Number of decimals'),
		'currency_decimal_point'		 => __('Decimal point'),
		'currency_thousands_seperator'	 => __('Thousands seperator')
	);

	$_arr_fields_info = array(
		'currency_format' => __('Suggested formats: {name}', array('{name}' => '{NUMBER}{CURRENCY}, {CURRENCY}{NUMBER}'))
	);

	foreach ($language as $lng)
	{
		foreach ($_arr_fields as $key => $val)
		{
			// currency_format
			$lng_label = Language::tabsLabelLngInfo($language, $lng);
			$key_lng = $key . '[' . $lng->id . ']';
			$input_element = '<input name="' . $key_lng . '" type="text" id="' . $key_lng . '" 
					value="' . View::escape(Config::option($key, $lng->id)) . '" />';
			$_info = isset($_arr_fields_info[$key]) ? ' <em>' . $_arr_fields_info[$key] . '</em>' : '';
			echo '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
					<div class="col col-12 sm-col-2 px1 form-label"><label for="' . $key_lng . '">' . $val . $lng_label . '</label></div>
					<div class="col col-12 sm-col-10 px1">' . $input_element . $_info . '</div>
				</div>';
		}
	}
	?>
    <div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<a href="<?php echo Language::get_url('admin/') ?>" class="button link"><?php echo __('Cancel'); ?></a>
		</div>
	</div>
</form>
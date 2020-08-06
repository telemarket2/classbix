<?php
// save first
fcp_save();

// load css first,  apply stored custom pattern and display it 
$meta->css[] = '<style id="custom_style_pattern">' . fcp_apply_styles(fcp_custom_pattern(), fcp_get_data($selected_category)) . '</style>';

if(DEMO || Theme::previewTheme())
{
	// load control panel last 
	$meta->javascript[] = fcp_panel();
}

function fcp_custom_pattern()
{
	return array(
		'fcp_colors_main' => '.header .c1{background-color:__VAL__;}',
		'fcp_colors_secondary' => '.button.primary{background-color: __VAL__;color:#fff !important;}',
		'fcp_colors_contact' => '.post_details{background-color: __VAL__;}.grid tr:hover,.grid tr:hover td{background-color: __VAL__ !important;}',
		'fcp_colors_contact_text' => '.post_details{color:__VAL__;}',
		'fcp_colors_link' => 'a{color:__VAL__;}',
		'fcp_colors_background' => 'body{background-color:__VAL__;}',
		'fcp_image_background' => 'body{background-image:__VAL__;}',
		'fcp_font_body_family' => 'body{font-family:__VAL__;}',
		'fcp_font_title_family' => 'h1,h2,h3,h4,h5{font-family:__VAL__;}'
	);
}

function fcp_save()
{
	$post = array();
	parse_str($_POST['data'], $post);

	// perform save action 
	if(isset($_POST['fcp_save']))
	{
		if(!Config::nounceCheck(true))
		{
			// no nounce then possible xss
			exit('Error:' . strip_tags(Validation::getInstance()->messages_dump()));
			return false;
		}

		if(DEMO)
		{
			// no nounce then possible xss
			//Validation::getInstance()->set_error(__('Some actions restricted in demo mode.'));
			exit(__('Some actions restricted in demo mode.'));
			return false;
		}

		if(!AuthUser::hasPermission(User::PERMISSION_ADMIN))
		{
			//Validation::getInstance()->set_error(__('No permission to perform this action.'));
			exit(__('No permission to perform this action.'));
			return false;
		}

		$theme = Theme::getTheme();


		$fcp_category_id = intval($post['fcp_category_id']);
		$data = array();


		unset($post['fcp_predefined_scheme']);
		unset($post['fcp_save']);
		unset($post['fcp_category_id']);

		$data['fcp'][$fcp_category_id] = $post;
		$theme->optionSaveAllFromData($data);

		exit(__('Theme colors saved'));
	}
}

function fcp_arr2js($arr = array())
{
	foreach($arr as $k => $v)
	{
		if(is_array($v))
		{
			$return[] = '"' . View::escape($k) . '":' . fcp_arr2js($v);
		}
		else
		{
			// replace new line and tabs and add ad one line string 
			$return[] = '"' . View::escape($k) . '":"' . View::escape(fcp_remove_spaces($v)) . '"';
		}
	}

	return '{' . implode(',', $return) . '}';
}

function fcp_remove_spaces($str)
{
	return preg_replace('/[\n\t\r]+\s+/', ' ', $str);
}

/* functions */

function fcp_get_data($selected_category = null)
{
	$theme = Theme::getTheme();

	$fcp_data_defaults = array(
		'fcp_colors_main' => '#0076BE',
		'fcp_colors_secondary' => '#E07314',
		'fcp_colors_contact' => '#E0F4FF',
		'fcp_colors_contact_text' => '#333',
		'fcp_colors_link' => '#0066DD',
		'fcp_colors_background' => '#fff',
		'fcp_image_background' => 'none'
	);

	// display saved options
	$theme->optionLoadAll();
	$fcp_data = $theme->themeConfig->options['fcp'];


	//var_dump($theme);
	$category_id = intval($selected_category->id);
	if(isset($fcp_data[$category_id]))
	{
		$fcp_data = $fcp_data[$category_id];
	}
	else
	{
		$fcp_data = $fcp_data[0];
	}


	if(!is_array($fcp_data))
	{
		$fcp_data = array();
	}

	foreach($fcp_data_defaults as $k => $v)
	{
		if(!isset($fcp_data[$k]))
		{
			$fcp_data[$k] = $v;
		}
	}
	return $fcp_data;
}

function fcp_apply_styles($custom_style_pattern, $data = array())
{
	$return = '';
	foreach($data as $key => $val)
	{
		if(strlen($val) && isset($custom_style_pattern[$key]))
		{
			if(strpos($key, 'fcp_image') !== false)
			{
				// if value none then display none, else display image with url
				if($val != 'none')
				{
					$val = 'url("' . Config::urlAssets() . $val . '")';
				}
			}
			// . ' !important'
			$return .= str_replace('__VAL__', $val, fcp_remove_spaces($custom_style_pattern[$key]));
		}
	}

	// clear left overs
	return $return;
}

function fcp_panel()
{

	$theme = Theme::getTheme();

	$label_default = View::escape(__('Theme Default'));
	$label_built_in = View::escape(__('Built-in Fonts'));

	$font_options = <<<EOD
		<option value="">{$label_default}</option>
		<optgroup type="built" label="{$label_built_in}">
			<option value="Arial">Arial</option>
			<option value="'Arial Narrow','Liberation Sans Narrow'">Arial Narrow</option>
			<option value="Tahoma">Tahoma</option>
			<option value="Verdana">Verdana</option>
			<option value="'Trebuchet MS'">Trebuchet MS</option>
			<option value="'Lucida Sans Unicode','Lucida Grande'">Lucida Sans Unicode</option>
			<option value="Georgia">Georgia</option>
			<option value="'Times New Roman'">Times New Roman</option>
		</optgroup>
EOD;
	/* google fonts not added yet
	  <optgroup type="google" label="Google Fonts">
	  <option value="Abel">Abel</option>
	  <option value="Abril Fatface">Abril Fatface</option>
	  <option value="Aclonica">Aclonica</option>
	  <option value="Wire One">Wire One</option>
	  <option value="Yanone Kaffeesatz">Yanone Kaffeesatz</option>
	  <option value="Yellowtail">Yellowtail</option>
	  <option value="Yeseva One">Yeseva One</option>
	  <option value="Yesteryear">Yesteryear</option>
	  <option value="Zeyada">Zeyada</option>
	  </optgroup>
	 * 
	 * <input type="hidden" value="google" name="font[body_type]">
	  <input type="hidden" value="700italic,700,400italic,400" name="font[body_variant]">
	  <input type="hidden" value="latin" name="font[body_subsets]">
	 * 
	 * <input type="hidden" value="google" name="font[intro_type]">
	  <input type="hidden" value="400" name="font[intro_variant]">
	  <input type="hidden" value="latin" name="font[intro_subsets]">
	 */

	$label_default = View::escape(__('Default'));

	$texture_options = <<<EOD
		<optgroup label="{$label_default}">
			<option value="none">none</option>
			<option value="images/bg-diagonal-bold-light.png">Diogonal</option>
			<option value="images/bg-dots-light.png">Dots</option>
			<option value="images/bg-grunge-dark.png">Grunge</option>
			<option value="images/bg-noise-light.png">Noise</option>
			<option value="images/bg-rough.png">Rough</option>
			<option value="images/bg-squares-regular.png">Squares</option>
			<option value="images/bg-squares-light.png">Squares2</option>
			<option value="images/bg-wood-light.png">Wood</option>
		</optgroup>
EOD;


	// custom backgrounds
	$existing_images = $theme->option('_background');
	//print_r($existing_images);
	// http://localhost/classibase/user-content/themes/fafotka/public/
	// http://localhost/classibase/user-content/uploads/theme/fafotka/img-bg-pat.png
	if($existing_images)
	{
		foreach($existing_images as $existing_img)
		{
			// $theme->uploadUrl($existing_img)
			$existing_img_options.='<option value="../../../uploads/theme/' . View::escape($theme->id) . '/' . View::escape($existing_img) . '">' . View::escape($existing_img) . '</option>';
		}
		$texture_options .= '<optgroup label="' . __('Custom') . '">' . $existing_img_options . '</optgroup>';
	}

	/* <optgroup label="Custom">
	  <option value="048aae17-ac70-8d64-6bd1-bb81e5619d54">background_0</option>
	  </optgroup>
	 */


	ob_start();
	?>
	<div id="fcp" class="fcp_mini">
		<a href="#" id="fcp_close">&laquo;</a>
		<span id="fcp_title">Control Panel</span>
		<div id="fcp_wrapper">
			<form method="post" action="" name="fcp" style="">			
				<div class="ui-accordion">
					<h3 class="ui-accordion-header ui-active"><i class="arrow_down"></i> <?php echo __('Theme colors') ?></h3>
					<div class="ui-accordion-content" id="fcp_theme_colors">					
						<div class="fcp_row clearfix">
							<label><strong>Color scheme:</strong></label>
							<span class="clear"></span>
							<div id="prefedined_themes">
								<select name="fcp_predefined_scheme" id="fcp_predefined_scheme">
									<option value="custom">custom</option>
									<option value="default">Default</option>
									<option value="blue">Blue</option>
									<option value="magenta">Magenta</option>
									<option value="green">Green</option>
									<option value="orange">Orange</option>
									<option value="treehouse">Tree house</option>
									<option value="wood">Wood</option>
								</select>
							</div>
						</div>
						<div class="fcp_row clearfix">
							<label for="fcp_colors_main">Header color:</label>
							<input type="text" class="color" id="fcp_colors_main" name="fcp_colors_main" value="" />						
						</div>
						<div class="fcp_row clearfix">
							<label for="fcp_colors_secondary">Button color:</label>
							<input type="text" class="color" id="fcp_colors_secondary" name="fcp_colors_secondary" value="" />
						</div>
						<div class="fcp_row clearfix">
							<label for="fcp_colors_contact">Contact box:</label>
							<input type="text" class="color" id="fcp_colors_contact" name="fcp_colors_contact" value="" />
						</div>
						<div class="fcp_row clearfix">
							<label for="fcp_colors_contact_text">Contact text:</label>
							<input type="text" class="color" id="fcp_colors_contact_text" name="fcp_colors_contact_text" value="" />
						</div>
						<div class="fcp_row clearfix">
							<label for="fcp_colors_link">Link:</label>
							<input type="text" class="color" id="fcp_colors_link" name="fcp_colors_link" value="" />
						</div>												
						<div class="fcp_row clearfix">
							<label for="fcp_colors_background">Body background:</label>
							<input type="text" class="color" id="fcp_colors_background" name="fcp_colors_background" value="" />
						</div>
						<div class="fcp_row clearfix">
							<label for="fcp_image_background">Texture:</label>
							<select name="fcp_image_background" style="width: 90px; margin-right: 0px;" id="fcp_image_background">
								<?php echo $texture_options; ?>
							</select>
						</div>
						<div class="fcp_row clearfix">
							<label><strong>Body font</strong></label>
							<select name="fcp_font_body_family"><?php echo $font_options; ?></select>
						</div>
						<div class="fcp_row clearfix">
							<label><strong>Title font</strong></label>
							<select name="fcp_font_title_family"><?php echo $font_options; ?></select>
						</div>
					</div>
				</div>

				<div class="s_submit">
					<input class="button fcp_save" type="submit" value="<?php echo __('Save') ?>" name="fcp_save" />
					<?php echo Config::nounceInput(); ?>
				</div>

			</form>
		</div>
	</div>
	<link rel="stylesheet" href="<?php echo Config::urlAssets() ?>css/fcp.css" type="text/css" />
	<link rel="stylesheet" href="<?php echo URL_ASSETS ?>css/spectrum.css" type="text/css" />
	<script>
		var custom_style_pattern = <?php echo fcp_arr2js(fcp_custom_pattern()); ?>;
		var fcp_data = <?php echo fcp_arr2js(fcp_get_data()); ?>;
		var THEME_ASSETS = "<?php echo Config::urlAssets(); ?>";
		var theme_id = "<?php echo $theme->id; ?>";
		var nounce = "<?php echo Config::nounce(); ?>";
	</script>
	<script src="<?php echo Config::urlAssets() ?>js/fcp.js"></script>
	<script src="<?php echo URL_ASSETS ?>js/spectrum.js"></script>

	<?php
	$return = ob_get_contents();
	ob_clean();


	return $return;
}

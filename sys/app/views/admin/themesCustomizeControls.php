<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo $title; ?></title>
		<link href="<?php echo URL_ASSETS; ?>css/normace.min.css?v=v8.0.2" rel="stylesheet" type="text/css" />
		<link href="<?php echo URL_ASSETS; ?>css/font-awesome.min.css?v=v4.7" rel="stylesheet" type="text/css" />
		<link href="<?php echo URL_ASSETS; ?>css/screen.min.css?v=<?php echo Config::VERSION ?>" rel="stylesheet" type="text/css" />
		<script src="<?php echo URL_ASSETS; ?>js/jquery-1.12.4.min.js"	type="text/javascript"></script>
		<script src="<?php echo URL_ASSETS; ?>js/admin.min.js?v=<?php echo Config::VERSION ?>"	type="text/javascript"></script>
	</head>
	<body class="theme_customize layout_backend">
		<form class="theme_customize_form" target="_top" method="post">
			<div class="top_controls">
				<?php
				$add_spectrum_controls = false;
				if ($theme->id == Theme::currentThemeId())
				{
					$save_title = __('Publish');
				}
				else
				{
					$save_title = __('Activate');
				}
				?>
				<div class="button-group button-group-block">
					<a href="#collapse" class="button collapse" title="<?php echo View::escape(__('Minimize')); ?>"><i class="fa fa-chevron-left" aria-hidden="true"></i></a>
					<a href="<?php echo Language::get_url('admin/themes/'); ?>" class="button close" target="_top" title="<?php echo View::escape(__('Close')) ?>"><i class="fa fa-close" aria-hidden="true"></i></a>
					<button name="submit" id="submit" type="submit" class="button blue save nowrap"><i class="fa fa-save" aria-hidden="true"></i> <?php echo $save_title; ?></button>
				</div>
			</div>

			<div class="controls">				
				<div class="group">
					<h2><?php echo View::escape($theme->info['name']) ?></h2>
					<div class="body">
						<p><img src="<?php echo $theme->screenshot() ?>" alt="<?php echo View::escape($theme->info['name']); ?>" /></p>
						<p><?php echo __('By') . ': ' . $theme->author() . ' | ' . __('Version') . ': ' . View::escape($theme->info['version']); ?></p>
						<p><?php echo View::escape($theme->info['description']); ?></p>
						<p><?php echo __('Theme files located in ') . '<code>/themes/' . $theme->id . '/</code>'; ?></p>
					</div>
				</div>
				<div class="group">
					<h2><?php echo __('Site title and description') ?></h2>
					<div class="body">
						<?php
						$language = Language::getLanguages();

						$site_title = Config::option('site_title', null, true);
						$site_description = Config::option('site_description', null, true);

						$echo = '';
						$tab_key = 'site_title_';
						foreach ($language as $lng)
						{
							$lng_label = Language::tabsLabelLngInfo($language, $lng);

							$echo .= '<p class="' . Language::tabsTabKey($tab_key, $lng) . '">
									<label for="site_title[' . $lng->id . ']">' . __('Title') . $lng_label . ':</label>
									<input name="site_title[' . $lng->id . ']" type="text" 
										id="site_title[' . $lng->id . ']" 
										value="' . View::escape($site_title[$lng->id]) . '" /></p>';


							$echo .= '<p class="' . Language::tabsTabKey($tab_key, $lng) . '">
									<label for="site_description[' . $lng->id . ']">' . __('Description') . $lng_label . ':</label>
									<textarea name="site_description[' . $lng->id . ']" 
										id="site_description[' . $lng->id . ']">'
									. View::escape($site_description[$lng->id]) . '</textarea></p>';


							$echo .= '<p class="' . Language::tabsTabKey($tab_key, $lng) . '">
									<label for="site_button_title[' . $lng->id . ']">' . __('Post button title') . $lng_label . ':</label>
									<input name="site_button_title[' . $lng->id . ']" type="text" 
										id="site_button_title[' . $lng->id . ']" 
										value="' . View::escape(Config::optionElseDefault('site_button_title', __('Post ad'), $lng->id)) . '" /></p>';
						}

						echo Language::tabs($language, $tab_key, '<div class="tabs tabs_compact">{tabs}</div>') . $echo;
						?>
					</div>
				</div>

				<?php
				if ($theme->info['customize'])
				{
					//'customize' => array(
					//	'theme_presets' => array(
					//		'title' => __('Theme Color Presets'),
					//		'fields' => array(
					//			'theme_presets' => array(
					//				'label' => __('Theme Color Presets'),
					//				'type' => 'dropdown',
					//				'value' => array('green', 'red', 'black', 'white', 'blue'),
					//			),
					//			'theme_presets' => array(
					//				'label' => __('Theme Color Presets'),
					//				'type' => 'dropdown',
					//				'value' => array('green', 'red', 'black', 'white', 'blue'),
					//			),
					//		)
					//	)
					//),


					foreach ($theme->info['customize'] as $group_id => $v)
					{
						$group_id = View::escape($group_id);
						$title = View::escape($v['title']);
						$description = View::escape($v['description']);
						$echo = '';
						$tabs = '';

						foreach ($v['fields'] as $field_id => $feild)
						{
							if (strlen($feild['label']))
							{
								$label = '<label for="' . $field_id . '">' . View::escape($feild['label']) . '</label> ';
							}
							else
							{
								$label = '';
							}

							switch ($feild['type'])
							{
								case 'dropdown':
									$select = '';
									foreach ($feild['value'] as $k => $v)
									{
										$select .= '<option value="' . View::escape($k) . '">' . View::escape($v) . '</option>';
									}
									$selected_value = $theme->option($field_id, false, true);
									$select = str_replace('<option value="' . View::escape($selected_value) . '"', '<option value="' . View::escape($selected_value) . '" selected="selected"', $select);
									$select = '<select name="' . $field_id . '" id="' . $field_id . '">' . $select . '</select>';

									$echo .= '<p>' . $label . $select . '</p>';
									break;
								case 'image':
									// upload image or select from previous images
									$hidden_field = '_' . $field_id . '_selected';
									$_field = '_' . $field_id;


									// display existing image 
									$existing_images = $theme->option('_' . $field_id);
									$existing_images_html = '';
									$existing_images_pattern = '<div class="image_row{class}">
																	<a href="#" dataimg="{src}" 
																				datatarget="' . $hidden_field . '" 
																				datatargetoriginal="' . $field_id . '" 
																				class="select_image">
																		<img src="{src_url}" srcprefix="' . $theme->uploadUrl() . '" /></a>
																	<div class="image_controls">
																		<a href="#use" class="button blue use">' . __('Use image') . '</a>
																		<a href="#use" class="button red remove">' . __('Remove') . '</a>														
																	</div>
																</div>';
									$arr_search = array('{src}', '{src_url}', '{class}');

									if ($existing_images)
									{
										foreach ($existing_images as $img)
										{
											if (strlen($img))
											{
												$arr_replace = array(View::escape($img), $theme->uploadUrl($img), '');
												$existing_images_html .= str_replace($arr_search, $arr_replace, $existing_images_pattern);
											}
										}
									}

									$arr_replace = array('', '', ' display-none');
									$existing_images_html = '<div id="' . $_field . '" class="existing_images">'
											. $existing_images_html
											. str_replace($arr_search, $arr_replace, $existing_images_pattern)
											. '</div>';

									// image upload element
									$echo .= '<p>'
											. $label
											. '<input id="' . $field_id . '" name="' . $field_id . '" type="file" datatarget="' . $_field . '" />'
											. '<input id="' . $hidden_field . '" name="' . $hidden_field . '" type="hidden" value="' . $theme->option($field_id) . '" />'
											. '</p>'
											. $existing_images_html;
									break;
								case 'color':
									if ($feild['multilingual'])
									{
										$echo = '';
										$tab_key = 'tabs_' . $group_id . '_';
										foreach ($language as $lng)
										{
											$lng_label = Language::tabsLabelLngInfo($language, $lng);
											$_field_id = $field_id . '[' . $lng->id . ']';
											$value = $theme->option($field_id, $lng->id, true);
											$echo .= '<p class="' . Language::tabsTabKey($tab_key, $lng) . '">
														<label for="' . $_field_id . '">' . View::escape($feild['label']) . $lng_label . ':</label>
														<input name="' . $_field_id . '" type="text" 
															id="' . $_field_id . '" 
															value="' . View::escape($value) . '" class="color" '
													. ($feild['palette'] ? ' palette="' . $feild['palette'] . '"' : '') . ' /></p>';
										}

										$tabs = Language::tabs($language, $tab_key, '<div class="tabs tabs_compact">{tabs}</div>');
									}
									else
									{
										$echo .= '<p>'
												. $label
												. '<input id="' . $field_id . '" name="' . $field_id . '" type="text" values="' . $theme->option($field_id, null, true) . '" class="color" '
												. ($feild['palette'] ? ' palette="' . $feild['palette'] . '"' : '') . ' />
											</p>';
									}
									// add color js and css
									$add_spectrum_controls = true;
									break;
								case 'checkbox':
									if ($feild['multilingual'])
									{
										$echo = '';
										$tab_key = 'tabs_' . $group_id . '_';
										foreach ($language as $lng)
										{
											$lng_label = Language::tabsLabelLngInfo($language, $lng);
											$_field_id = $field_id . '[' . $lng->id . ']';
											$value = $theme->option($field_id, $lng->id, true);
											$echo .= '<p class="' . Language::tabsTabKey($tab_key, $lng) . '">														
													<input name="' . $_field_id . '" id="' . $_field_id . '" type="checkbox" value="1" ' . ($value ? 'checked="checked"' : '') . ' /> 
													<label for="' . $_field_id . '">' . View::escape($feild['label']) . $lng_label . '</label></p>';
										}

										$tabs = Language::tabs($language, $tab_key, '<div class="tabs tabs_compact">{tabs}</div>');
									}
									else
									{
										$echo .= '<p><input id="' . $field_id . '" name="' . $field_id . '" type="checkbox" value="1"'
												. ($theme->option($field_id, null, true) ? 'checked="checked"' : '') . ' /> ' . $label . '</p>';
									}
									break;
								case 'text':
								default:
									if ($feild['multilingual'])
									{
										$echo = '';
										$tab_key = 'tabs_' . $group_id . '_';
										foreach ($language as $lng)
										{
											$lng_label = Language::tabsLabelLngInfo($language, $lng);
											$_field_id = $field_id . '[' . $lng->id . ']';
											$value = $theme->option($field_id, $lng->id, true);
											$echo .= '<p class="' . Language::tabsTabKey($tab_key, $lng) . '">
														<label for="' . $_field_id . '">' . View::escape($feild['label']) . $lng_label . ':</label>
														<input name="' . $_field_id . '" type="text" 
															id="' . $_field_id . '" 
															value="' . View::escape($value) . '" /></p>';
										}

										$tabs = Language::tabs($language, $tab_key, '<div class="tabs tabs_compact">{tabs}</div>');
									}
									else
									{
										$echo .= '<p>'
												. $label
												. '<input id="' . $field_id . '" name="' . $field_id . '" type="text" values="' . $theme->option($field_id, null, true) . '" />
											</p>';
									}
							}
						}

						echo '<div class="group">
						<h2>' . $title . '</h2>'
						. '<div class="body">'
						. ($description ? '<p>' . $description . '</p>' : '')
						. $tabs . $echo
						. '</div>
						</div>';
					}
				}
				?>
				<div class="group">
					<h2><?php echo __('Custom Styles') ?></h2>
					<div class="body">
						<textarea name="custom_styles"><?php echo View::escape($theme->option('custom_styles')); ?></textarea>
						<p><?php echo __('Custom styles related to this theme. You can use php tags as well.') ?></p>
					</div>
				</div>
			</div>
		</form>

		<?php
		if ($add_spectrum_controls)
		{
			echo '<link rel="stylesheet" href="' . URL_ASSETS . 'css/spectrum.css" type="text/css" />
					<script src="' . URL_ASSETS . 'js/spectrum.js"></script>
					<script>
					$("input.color").each(function(){
					var $me = $(this);
					var palette = [];
					if($me.prop("palette"))
					{
						palette = $me.prop("palette");
					}
					$me.spectrum({
						showPalette: true,
						showSelectionPalette: true,
						palette: palette,
						localStorageKey: "spectrum.homepage", // Any Spectrum with the same string will share selection
						clickoutFiresChange: true,
						showInitial: true,
						showInput: true,
						preferredFormat: "hex6"	
					});
					});
					</script>';
		}
		?>
		<script type="text/javascript">
			
			//burda hersey hazir olacak hizli calismasi icin 
			
			var BASE_URL = "<?php echo Language::get_url(); ?>";
			var theme_id = "<?php echo $theme->id; ?>";
			var customize_url = "<?php echo Language::get_url('admin/themesCustomizeAjax/'); ?>" + theme_id + "/";
			
			var old = "";
			var cur = "";
			var org = "";
			var last_url = BASE_URL;
			
			$(function ()
			{
				
				updateView();
				
				// append arrow
				$('.controls .group h2').append('<span class="arrow_down"></span>');
				
				$('.collapse').click(collapseControls);
				
				$('.controls .group h2').click(selectMenu);
				
				// on every input field hange ipdate view 
				$('form input[type!="file"],form select,form textarea').change(updateView);
				
				// file uploading selector
				$('input:file').change(fileSelected);
				
				// define select image actions
				$('a.select_image').click(selectImage);
				$('.image_row a.use').click(function ()
				{
					$(this).parents('.image_row').find('a.select_image').click();
					return false;
				});
				$('.image_row a.remove').click(deleteImage);
			});
			
			function selectMenu()
			{
				var $me = $(this);
				if ($me.hasClass('active'))
				{
					$('.controls .group h2').removeClass('active');
					$('.controls .group .body').slideUp('fast');
				}
				else
				{
					$('.controls .group h2').removeClass('active');
					$('.controls .group .body').slideUp('fast');
					$me.addClass('active');
					$('.body', $me.parents('.group:first')).slideDown('fast');
				}
				return false;
			}
			
			function collapseControls()
			{
				var d = top.framesetMain;
				if ($('.theme_customize').hasClass('theme_customize_collapsed'))
				{
					// expand
					d.cols = "300,*";
					$('.theme_customize').removeClass('theme_customize_collapsed');
					$(this).find('i').addClass('fa-chevron-left').removeClass('fa-chevron-right');
				}
				else
				{
					// collapse
					d.cols = "30,*";
					$('.theme_customize').addClass('theme_customize_collapsed');
					$(this).find('i').addClass('fa-chevron-right').removeClass('fa-chevron-left');
				}
				
				return false;
			}
			
			function updateView()
			{
				var data = $('form.theme_customize_form').serialize();
				
				$.post(last_url, {
					nounce: '<?php echo Config::nounce(); ?>',
					data: data,
					preview_theme: theme_id
				}, function (data)
				{
					//alert(data);
					if (old != data)
					{
						old = data;
						
						var d = top.dynamicframe.document;
						var old_ = old + '<scr' + 'ipt>$(function(){\
						$(\'a\').click(function(){top.topFrame.updateViewUrl($(this).attr(\'href\'));return false;});\
						$(\'form\').submit(function(){top.topFrame.updateViewUrl($(this).attr(\'action\'));return false;});\
						});</scr' + 'ipt>';
						
						d.open();
						d.write(old_);
						d.close();
						
						updateViewCSS();
					}
				});
			}
			
			function updateViewCSS()
			{
				//alert('updateViewCSS');
				//alert($('head',top.dynamicframe.document).html());
				//$('head',top.dynamicframe.document).append('<style>body{background-color:#000;}</style>');				
			}
			
			function updateViewUrl(url)
			{
				// if url is valid then update view 
				if (url.search(BASE_URL) > -1)
				{
					if (url.search('/admin/') == -1 && url.search('/login/') == -1
							&& url.search('.jpg') == -1 && url.search('.jpeg') == -1 && url.search('.gif') == -1 && url.search('.png') == -1)
					{
						// if no admin or login page 
						last_url = url;
						updateView();
					}
				}
				return false;
			}
			
			function fileSelected()
			{
				var $me = $(this);
				var $me_clone = $me.clone();
				var $form = $('<form method="post" enctype="multipart/form-data" action="' + customize_url + '"></form>');
				var $parent_form = $me.parents('form');
				var $uploading = $('<div class="uploading">uploading...</div>');
				var $preview_area = $('#' + $me.attr('datatarget'));
				
				// unbind this action to prevent repeat submission 
				$me.unbind('change');
				
				
				// leave empty clone of file input
				$me.after($me_clone);
				$me_clone.attr('value', '').removeAttr('value').addClass('clone').hide();
				
				
				// create new form and submit it to iframe
				//$form.append($me).hide();
				$parent_form.after($form);
				$form.append($me);
				$form.append('<input type="submit" name="submit" value="submit" />');
				$form.hide();
				
				AIM.submit($form.get(0), {
					'onStart': function ()
					{
						$me_clone.after($uploading);
						return true;
					},
					'onComplete': function (data)
					{
						var arr_data = data.split('{SEP}');
						if (arr_data[0] == 'ok')
						{
							$uploading.remove();
							
							// prepend image to preview area 
							var $preview = $('.image_row.display-none:first', $preview_area).clone(true);
							var $preview_img = $('img', $preview);
							var src = arr_data[1];
							
							// set values
							$preview_img.attr('src', $preview_img.attr('srcprefix') + src);
							$('a.select_image', $preview).attr('dataimg', src);
							
							// display 
							$preview_area.prepend($preview);
							$preview.removeClass('display-none');
							$preview.show();
							
							// select this image
							$('a.select_image', $preview).click();
							
						}
						else
						{
							alert('Couldn\'t upload image. Please try again. ' + data);
							$me.removeAttr('disabled');
						}
						
						$me_clone.show();
						$me_clone.change(fileSelected);
						$form.remove();
						$uploading.remove();
						return true;
					}
				});
				
				$('input:submit', $form).click();
				
				return false;
			}
			
			function selectImage()
			{
				var $me = $(this);
				var $field = $('input[name="' + $me.attr('datatarget') + '"]');
				
				$field.val($me.attr('dataimg'));
				
				$me.parents('.existing_images:first').find('a.select_image').removeClass('active');
				$me.addClass('active');
				
				updateView();
				
				return false;
			}
			
			function deleteImage()
			{
				var $me = $(this);
				var $parent = $me.parents('.image_row:first');
				var $existing_images = $parent.parents('.existing_images:first');
				var $img = $('a.select_image', $parent);
				var img = $img.attr('dataimg');
				var field = $img.attr('datatargetoriginal');
				
				$.post(customize_url, {img: img, field: field, custom_action: 'remove_image'}, function (data)
				{
					// check if no image selected then select first image and update view
					if (data == 'ok')
					{
						$parent.remove();
						// check if no image selected then select first available
						if ($existing_images.find('a.active').length < 1)
						{
							// no active image check if has any image 
							if ($existing_images.has('a.select_image'))
							{
								$existing_images.find('a.select_image:first').click();
							}
							else
							{
								// no image to select remove old selected image 
							}
						}
					}
					else
					{
						alert('Couldn\'t delete image. Please try again. ' + data);
					}
				});
				
				return false;
			}
			
			
			/**
			 *
			 *  AJAX IFRAME METHOD (AIM)
			 *  http://www.webtoolkit.info/
			 *
			 *  onsubmit="return AIM.submit(this, {'onStart' : startCallback, 'onComplete' : completeCallback})"
			 *
			 **/
			var AIM = {
				frame: function (c)
				{
					
					var n = 'f' + Math.floor(Math.random() * 99999);
					var d = document.createElement('DIV');
					d.innerHTML = '<iframe style="display:none" src="about:blank" id="' + n + '" name="' + n + '" onload="AIM.loaded(\'' + n + '\')"></iframe>';
					document.body.appendChild(d);
					
					var i = document.getElementById(n);
					if (c && typeof (c.onComplete) == 'function')
					{
						i.onComplete = c.onComplete;
					}
					
					return n;
				},
				form: function (f, name)
				{
					f.setAttribute('target', name);
				},
				submit: function (f, c)
				{
					AIM.form(f, AIM.frame(c));
					if (c && typeof (c.onStart) == 'function')
					{
						return c.onStart();
					}
					else
					{
						return true;
					}
				},
				loaded: function (id)
				{
					var i = document.getElementById(id);
					var d;
					if (i.contentDocument)
					{
						d = i.contentDocument;
					}
					else if (i.contentWindow)
					{
						d = i.contentWindow.document;
					}
					else
					{
						d = window.frames[id].document;
					}
					if (d.location.href == "about:blank")
					{
						return;
					}
					
					if (typeof (i.onComplete) == 'function')
					{
						i.onComplete(d.body.innerHTML);
					}
				}
			}
		</script>
	</body>
</html>
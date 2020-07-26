<?php echo $this->validation()->messages() ?>
<h1 class="mt0">
	<?php echo __('Widgets') ?> 
	<span class="saving_action saving_widgets"><?php echo __('saving widgets ...'); ?></span>
</h1>
<?php
$tabs = '';
$echo = '';

/**
 * available_widgets
 */
$_description = __('Drag widgets from here to a sidebar on the right to activate them. Drag widgets back here to deactivate them and delete their settings.');
$_content = '';
foreach ($widget_types as $widget_type)
{
	$_content .= Widget::optionsForm($widget_type);
}
$echo .= Widget::optionsBox('available_widgets', __('Available Widgets'), $_description, $_content);




/**
 * sidebar widgets 
 */
$arr_theme_locations = array();
foreach ($sidebar_widgets->sidebars_obj as $location_id => $arr)
{
	// location valid then add to location 
	foreach ($arr as $w_id => $widget)
	{
		$arr_theme_locations[$location_id] .= Widget::optionsForm($widget->typeGet(), $widget);
	}
}
// display theme locations
foreach ($theme_locations as $location_id => $location)
{
	$key = 'lw_' . $location_id;
	$echo .= Widget::optionsBox('location_widgets ' . $key, $location['title'], $location['description'], $arr_theme_locations[$location_id], $location_id);
	$tabs .= '<a href="#' . $key . '" data-hide="box" data-show="' . $key . '">' . $location['title'] . '</a> ';
}
/* if ($theme->info['locations_preview'])
  {
  echo '<div class="theme_locations_peview original hidden">'
  . $theme->info['locations_preview']
  . '<p class="legend_location">' . __('Widget location') . '</p>
  <p class="legend_location_other">' . __('Other widget locations') . '</p>
  <p class="legend_content">' . __('Content') . '</p>
  </div>';
  } */


/**
 *  page widgets 
 */
$arr_theme_pages = array();
foreach ($sidebar_widgets->pages_obj as $page_id => $arr)
{
	// location valid then add to location 
	foreach ($arr as $w_id => $widget)
	{
		$arr_theme_pages[$page_id] .= Widget::optionsForm($widget->typeGet(), $widget);
	}
}
// display theme locations
foreach ($page_types as $page_id => $page_type)
{
	if ($page_type->widgets == true)
	{
		$key = 'pw_' . $page_id;
		$echo .= Widget::optionsBox('page_widgets ' . $key, $page_type->title, $page_type->description, $arr_theme_pages[$page_id], $page_id);
		$tabs .= '<a href="#' . $key . '" data-hide="box" data-show="' . $key . '">' . __('Page') . ': ' . $page_type->title . '</a> ';
	}
}




/**
 * inactive_widgets
 */
$_description = __('Widgets with saved settings that are not used by current theme. Drag widgets here to remove them from the sidebar but keep their settings.');
$_content = '';
foreach ($sidebar_widgets->inactive_widgets_obj as $w_id => $widget)
{
	$_content .= Widget::optionsForm($widget->typeGet(), $widget);
}
$key = 'inactive_widgets';
$echo .= Widget::optionsBox($key, __('Inactive Widgets'), $_description, $_content);
$tabs .= '<a href="#' . $key . '" data-hide="box" data-show="' . $key . '">' . __('Inactive Widgets') . '</a>';
?>
<p class="tabs tabs_widgets"><?php echo $tabs; ?></p>
<?php echo $echo; ?>

<div id="jq-dropdown-widget-move" class="jq-dropdown">
	<ul class="jq-dropdown-menu">
		<!-- list available locations here with js -->
	</ul>
</div>

<div id="jq-dropdown-widget-add" class="jq-dropdown">
	<ul class="jq-dropdown-menu">
		<!-- list available widgets here -->
	</ul>
</div>

<script type="text/javascript">
	var nounce = '<?php echo Config::nounce(); ?>';
	addLoadEvent(function ()
	{
		cbWedit.init();
	});

	/* widget editing functions 
	 *****************************************************************/
	var cbWedit = {
		init: function ()
		{
			// setup dropdowns 
			cbWedit.setupWmovePopulate();
			cbWedit.setupWmoveClick();
			cbWedit.setupWaddPopulate();
			cbWedit.setupWaddClick();
			cbWedit.showCount();
			cbWedit.reorderAvailableWidgets();
			// cbWedit.initLocationsPreview();

			// edit widget in modal			
			cb.modal.init(function ()
			{
				$('.widget_wrap>.title>a.edit').click(cbWedit.editWidget);
			});
		},
		setupWmoveShow: function ()
		{
			// moving and removing cf populate #jq-dropdown-cfmove
			$('#jq-dropdown-widget-move').on('show', function ()
			{
				console.log('#jq-dropdown-widget-move');
				// hide self from list
				// hide parent if it is only element in it 
				// hide inactive if it is already there 


			});
		},
		setupWmovePopulate: function ()
		{
			// populate move dropdown
			console.log('setupWmovePopulate');
			// list all pissible widget locations and items inside them 
			var move_before = $('.location_widgets,.page_widgets').map(function ()
			{
				var $me = $(this);
				var location_id = $me.data('location');
				var wa_path = '.location_widgets.lw_' + location_id;
				if (!$me.is(wa_path))
				{
					wa_path = '.page_widgets.pw_' + location_id;
				}
				wa_path += ' .items';
				var title = cbWedit.getTitle($me);
				var existing_widgets = '';
				existing_widgets = $me.find('.defined_widget').map(function ()
				{
					var $me = $(this);
					var wa_path = '.defined_widget#';
					var title = cbWedit.getTitle($me);
					return '<li><a data-v="' + wa_path + $me.attr('id') + '" data-a="before">' + title + '</a></li>';
				}).get().join('');
				if (existing_widgets.length)
				{
					existing_widgets = '<ul>'
							+ existing_widgets
							+ '<li><a data-v="' + wa_path + '" data-a="append">END</a></li>'
							+ '</ul>';
					wa_path_str = '';
				}
				else
				{
					wa_path_str = ' data-v="' + wa_path + '" data-a="append"';
				}
				return '<li><a' + wa_path_str + '>'
						+ title + '</a>'
						+ existing_widgets + '</li>';
			}).get().join('');
			// add to inactive widgets 
			var inactive_title = cbWedit.getTitle($('.inactive_widgets'));
			move_before += '<li><a data-v=".inactive_widgets .items" data-a="append">' + inactive_title + '</a></li>';
			// add remove button 
			move_before += '<li><a data-v="REMOVE"><i class="fa fa-trash" aria-hidden="true"></i> <?php echo View::escape(__('Remove')) ?></a></li>';
			// append to dropdown 
			$('#jq-dropdown-widget-move').find('.jq-dropdown-menu').html(move_before);
		},
		getTitle: function ($obj)
		{
			// find first .title and strip .muted from it
			var $title = $obj.find('.title:first').clone();
			$title.find('.muted').remove();
			return $title.text();
		},
		setupWmoveClick: function ()
		{
			// perform moving or removing item 
			$('#jq-dropdown-widget-move').on('click', 'li a[data-v]', function ()
			{
				var $me = $(this);
				var datav = $me.attr('data-v');
				var trigger = $me.parents('.jq-dropdown:first').data('jq-dropdown-trigger');
				var $item = trigger.parents('.defined_widget').first();
				switch (datav)
				{
					case 'REMOVE':
						// remove from submit form 
						cbWedit.editWidgetDelete($item, cbWedit.widgetOrderSave);
						break;
					default:
						var action = $me.data('a');
						switch (action)
						{
							case 'append':
								$(datav).append($item);
								break;
							case 'before':
								$(datav).before($item);
								break;
						}
						cbWedit.widgetOrderSave();
				}

				// populate menu again 
				cbWedit.setupWmovePopulate();

				// populate cf
				//cbWedit.populate();
			});
		},
		setupWaddClick: function ()
		{
			// perform adding new widget to container
			$('#jq-dropdown-widget-add').on('click', 'li a[data-v]', function ()
			{
				console.log('setupWaddClick');
				var $me = $(this);
				// type of widget 
				var datav = $me.attr('data-v');
				var trigger = $me.parents('.jq-dropdown:first').data('jq-dropdown-trigger');
				// try widget location first 
				var $widget_location = trigger.parents('.location_widgets,.page_widgets').first();
				var $widget = $('.available_widgets').find('.defined_widget[data-type_id="' + datav + '"]');

				if ($widget_location.length && $widget.length)
				{
					console.log('setupWaddClick:APPEND');
					$widget_location.find('.items').append($widget);

					// perform adding and saving action 
					cbWedit.saveAndUpdateOrder();
				}
			});
		},
		setupWaddPopulate: function ()
		{
			// populate add dropdown
			console.log('setupWaddPopulate');
			// list all pissible widgets locations and items inside them 
			var new_widgets = $('.available_widgets').find('.defined_widget').map(function ()
			{
				var $me = $(this);
				var title = cbWedit.getTitle($me);
				return '<li><a data-v="' + $me.data('type_id') + '">' + title + '</a></li>';
			}).get().join('');

			// append to dropdown 
			$('#jq-dropdown-widget-add').find('.jq-dropdown-menu').html(new_widgets);
		},
		showCount: function ()
		{
			// remove existing counts 
			$('.tabs_widgets a').each(function ()
			{
				var $me = $(this);
				var cnt = $('.' + $me.attr('data-show')).find('.defined_widget').length;
				var $cnt_holder_empty = $('<sup class="cnt_holder muted"></sup>');
				var $cnt_holder = $me.find('.cnt_holder');
				var cnt_holder_val = $cnt_holder.text();
				if (cnt)
				{
					if (cnt != cnt_holder_val)
					{
						// set new value 
						$cnt_holder.remove();
						$me.append($cnt_holder_empty.text(cnt));
					}
				}
				else
				{
					$cnt_holder.remove();
				}
			});
		},
		adWidgetListModeChange: function ()
		{
			/* add list style change event. if gallery selected then display image width x height inputs */
			var $me = $(this);
			var $form = $me.parents('form:first');
			var $hit_period = $form.find('.hit_period');
			var $blend_featured = $form.find('.blend_featured');
			if ($hit_period.length)
			{
				//alert('$me.val():' + $me.val());
				$hit_period.hide();
				if ($me.val() == 'hit')
				{
					// display size
					$hit_period.show();
				}
			}

			if ($hit_period.length)
			{
				// hide blend fetured for featured items 
				$blend_featured.show();
				if ($me.val() == 'featured' || $me.val() == 'viewed')
				{
					$blend_featured.hide();
				}
			}
		},
		adWidgetListStyleChange: function ()
		{
			/* add list style change event. if gallery selected then display image width x height inputs */
			var $me = $(this);
			var $form = $me.parents('form:first');
			var $thumb_size = $form.find('.thumb_size');
			var $thumb_style = $form.find('.thumb_style');
			var me_val = $me.val();
			//alert('$me.val():' + $me.val());

			$thumb_size.hide();
			if (me_val === 'thumbs' || me_val === 'carousel')
			{
				// display size
				$thumb_size.show();
			}
			$thumb_style.hide();
			if (me_val === 'thumbs')
			{
				// display size
				$thumb_style.show();
			}
		},
		catchConnectionErrors: function ()
		{
			// catch widget saving errors and enable save button again 
			var $body = $('body:first');
			// monitor for request errors
			$body.unbind('ajaxError');
			$body.ajaxError(function (event, request, settings)
			{
				alert("Error requesting page " + settings.url);
				$('.submit[disabled]').removeAttr('disabled');
				$('.saving_widgets').hide('fast');
			});
		},
		editWidget: function ()
		{
			/* show widget edit dialog */
			var $me = $(this);
			var $parent = $me.parents('.widget_wrap:first');
			var $body = $parent.find('.widget_options');
			var $body_clone = $body.clone(true);
			//$body_clone.removeClass('body');
			// hide page type setting if it is page widget 
			if ($body.parents('.page_widgets:first').length > 0)
			{
				$('label[for="page_type_hide"]', $body_clone).parents('.form-row:first').hide();
				//alert($('label[for="page_type_hide"]',$body_clone).html());
			}

			// add current string to ids to fix label focusing
			$body_clone.find('input[id],textarea[id]').attr('id', function ()
			{
				return $(this).attr('id') + '_current';
			});
			$body_clone.find('label[for]').attr('for', function ()
			{
				return $(this).attr('for') + '_current';
			});
			$body_clone.find('[data-jq-dropdown]').attr('data-jq-dropdown', function ()
			{
				return $(this).attr('data-jq-dropdown') + '_current';
			});
			$body_clone.find('.jq-dropdown').attr('id', function ()
			{
				return $(this).attr('id') + '_current';
			});
			// make relative to correctly position
			$body_clone.find('.jq-dropdown').addClass('jq-dropdown-relative');


			// perform custom action to show hide cirtain parts of widget depending on selected settings
			$body_clone.find('[name="list_style"]').change(cbWedit.adWidgetListStyleChange).change();
			$body_clone.find('[name="list_mode"]').change(cbWedit.adWidgetListModeChange).change();


			// set action buttons 
			$body_clone.find('p:last').addClass('action_buttons');
			// open modal 
			cb.modal.open({
				$content: $body_clone,
				classClose: '.cancel'
			});

			// select first language tab
			$body_clone.find('.tabs a:first').click();

			// save widget on click 
			$body_clone.on('click', '.submit', function ()
			{
				cbWedit.editWidgetSave($body_clone, $body);
				return false;
			});
			// remove widget 
			$body_clone.on('click', '.remove', function ()
			{
				cbWedit.editWidgetDelete($parent.parents('.defined_widget:first'), cbWedit.widgetOrderSave);
				cb.modal.close();
				return false;
			});
			return false;
		},
		editWidgetDelete: function ($widget, callback)
		{
			/* save via post and remove widget */
			if (!$widget.length)
			{
				return false;
			}

			// show saving 
			$('.saving_widgets').show('fast');
			cbWedit.catchConnectionErrors();

			//alert($body_clone.html());
			//var id = $('input[name="id"]', $widget).val();
			// delete multiple widgets at once 
			var id = $widget.map(function ()
			{
				return $(this).find('input[name="id"]:first').val();
			}).get().join(',');

			$.post(BASE_URL + 'admin/widgetsSave/', {
				action: 'widget-delete',
				nounce: nounce,
				id: id
			}, function (data)
			{
				if (data == 'ok')
				{
					// deleted, remove from html
					$widget.remove();

					$('.saving_widgets').hide('fast');

					(callback)();

					cbWedit.showCount();

				}
				else
				{
					alert('Couldn\'t update widget. Please try again. ' + data);
				}
			});

			cbWedit.showCount();

			return false;
		},
		widgetOrderSave: function ()
		{
			// when widget thrown to new location then check if it is new. 
			// if yes then save with default values

			// check for changes in location and order and save accordingly only location an position 

			// convert widgets to string in this format: id{SEP}type_id{SEP}location{SEP2}id{SEP}type_id{SEP}location{SEP2}...		
			var post_data = {};

			// list active sidebar widgets 
			$('.location_widgets .defined_widget').each(function (i)
			{
				var $me = $(this);
				var $parent = $me.parents('.location_widgets:first');
				var key = 'sidebars[' + $parent.data('location') + ']';
				if (post_data[key] == undefined)
				{
					post_data[key] = '';
				}
				post_data[key] += $('input[name="id"]', $me).val() + ',';
			});

			// list active page widgets 
			$('.page_widgets .defined_widget').each(function (i)
			{
				var $me = $(this);
				var $parent = $me.parents('.page_widgets:first');
				var key = 'pages[' + $parent.data('location') + ']';
				if (post_data[key] == undefined)
				{
					post_data[key] = '';
				}
				post_data[key] += $('input[name="id"]', $me).val() + ',';
			});

			// list iactive widgets 
			$('.inactive_widgets .defined_widget').each(function (i)
			{
				var $me = $(this);
				var key = 'inactive_widgets';
				if (post_data[key] == undefined)
				{
					post_data[key] = '';
				}
				post_data[key] += $('input[name="id"]', $me).val() + ',';
			});

			post_data.action = 'widgets-order-save';
			post_data.nounce = nounce;


			// display saving animation 
			$('.saving_widgets').show('fast');
			cbWedit.catchConnectionErrors();

			$.post(BASE_URL + 'admin/widgetsSave/', post_data, function (data)
			{
				if (data != 'ok')
				{
					alert('Error saving widgets. Please try again.');
				}

				// hide saving animation 
				$('.saving_widgets').hide('fast');
			});

			cbWedit.showCount();

			return false;
		},
		editWidgetSave: function ($body_clone, $body, callback)
		{
			/* save via post and update widget form */
			$('.saving_widgets').show('fast');

			//alert($body_clone.html());
			var $submit = $('.submit', $body_clone);
			var data = $('form', $body_clone).serialize();


			// set as updating
			$submit.attr('disabled', 'disabled');

			// monitor for request errors
			cbWedit.catchConnectionErrors();


			$.post(BASE_URL + 'admin/widgetsSave/', {
				action: 'widget-save',
				nounce: nounce,
				data: data
			}, function (data)
			{
				var arr_data = data.split('{SEP}');
				if (arr_data[0] == 'ok')
				{
					var $new_body = $(arr_data[1]);
					$body.after($new_body);
					$body.remove();
					// inits tabs
					cb.initTabs($new_body);
					// init selects 
					cb.select.init();

					cb.modal.close();

					$('.saving_widgets').hide('fast');
					if (callback !== undefined)
					{
						(callback)();
					}

					cbWedit.showCount();

				}
				else
				{
					alert('Couldn\'t update widget. Please try again. ' + data);
					$submit.removeAttr('disabled');
				}
			});

			return false;

		},
		initLocationsPreview: function ()
		{
			/* cuttrently not used may be removed in future */
			return false;
			/*    locations_preview	 */
			var $preview = $('div.theme_locations_peview');
			if ($preview.length)
			{
				// add action to preview 
				$('.locations_peview td', $preview).click(function ()
				{
					var $me = $(this);
					if ($me.hasClass('current') || $me.hasClass('preview_content'))
					{
						return false;
					}
					var name = $me.attr('class');
					name = name.replace(/preview_/gi, '');
					var $new = $('.box[data-location="' + name + '"]');
					if ($new.length)
					{
						// hide others
						$('.location_widgets>.body').hide();
						$('.theme_locations_peview').hide();
						$('.body:first', $new).slideDown("fast");
					}

					return false;
				});

				// add preview button to all location edit boxes
				$('.location_widgets .location_description').append(' <a href="#preview" class="preview_location"><?php echo __('Preview'); ?></a>');

				// assign action 
				$('.location_widgets a.preview_location').click(cbWedit.previewLocation);
			}
		},
		previewLocation: function ()
		{
			/* cuttrently not used may be removed in future */
			return false;

			var $me = $(this);
			var $location = $me.parents('#location_widgets:first');
			var location_name = $location.data('location');

			var $preview = $('div.theme_locations_peview', $location);
			if (!$preview.length)
			{
				$preview = $('div.theme_locations_peview.original').clone(true);
				$preview.removeClass('original');
				$preview.hide();
				$('.preview_' + location_name, $preview).addClass('current');
				$('.location_description', $location).after($preview);
			}
			$preview.slideToggle();

			return false;
		},
		reorderAvailableWidgets: function ()
		{
			// reorder available widgets by name
			$(".available_widgets .items .default").sort(function (a, b)
			{
				var upA = $(a).text().toUpperCase();
				var upB = $(b).text().toUpperCase();
				return (upA < upB) ? -1 : (upA > upB) ? 1 : 0;
			}).appendTo('.available_widgets .items');
		},
		saveAndUpdateOrder: function (event, ui)
		{
			/**
			 * celan up .available_widgets and leave default widgets, reorder
			 * save newly added widget, then save its order
			 * or just save order of all widgets
			 * update widget count in tabs 
			 */
			console.log('saveAndUpdateOrder');
			var $new_me = undefined;
			var skip_reorder = false;

			// leave default objects in .available_widgets
			$('.items .default').each(function (i)
			{
				// check if has parent .available_widgets
				var $me = $(this);
				var $in_available = $me.parents('.available_widgets').length;
				if (!$in_available)
				{
					//alert('fix : '+$me.html());
					// clone this object and move original back to .available_widgets
					$new_me = $me.clone(true);
					$new_me.removeClass('default');
					$new_me.addClass('custom');
					$me.after($new_me);
					// move me back to .available_widgets
					$me.prependTo('.available_widgets .items');

					console.log('saveAndUpdateOrder:$new_me:' + $new_me);
				}
			});

			// remove not default object from .available_widgets
			cbWedit.editWidgetDelete($('.available_widgets .custom'), cbWedit.widgetOrderSave);

			cbWedit.reorderAvailableWidgets();

			// check if id set 
			if ($new_me != undefined && $('input[name="id"]', $new_me).val() == '')
			{
				console.log('saveAndUpdateOrder:$new_me:save');

				// save this item firt then save order
				var $body = $new_me.find('.widget_options');
				if ($body.length)
				{
					console.log('saveAndUpdateOrder:$new_me:save:fond');
					skip_reorder = true;
					cbWedit.editWidgetSave($body, $body, cbWedit.widgetOrderSave);
				}
			}
			else
			{
				console.log('saveAndUpdateOrder:$new_me:DONTsave');
			}

			if (!skip_reorder)
			{
				// just save widgets in new order
				cbWedit.widgetOrderSave();
			}

			cbWedit.showCount();
		}
	};
</script>
<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo $title ?></h1>

<h4>
	<?php
	echo Location::getFullNameById($location, __('All locations'))
	. ' <i class="fa fa-arrows-h" aria-hidden="true"></i> '
	. Category::getFullNameById($category_id, __('All categories'));
	?>
</h4>

<div class="clearfix">
	<div class="col col-12 md-col-8 lg-col-6">
		<div class="cf_submit_form">
			<?php
			$group = null;
			$group_close = '';
			$used_groups = array();
			$used_fields = array();
			$arr_field_str = array();

			$button_menu = '<a href="#menu" title="' . __('Menu') . '" class="button" data-jq-dropdown="#jq-dropdown-cfmove"><i class="fa fa-ellipsis-v" aria-hidden="true"></i></a>';
			$controls_group = '<div class="controls col col-right right-align">' . $button_menu . '</div>';

			$pattern_controls = '<div class="controls col col-right right-align">
						<div>' . $button_menu . '</div>
						<a href="#is_search" class="button small is_search {is_search}' . ($cf->is_search ? ' primary' : '') . '" title="' . __('Make this field searchable') . '">S</a>	
						<a href="#is_list" class="button small is_list {is_list}' . ($cf->is_list ? ' primary' : '') . '" title="' . __('Make this field visible in listing') . '">L</a>	
					</div>';
			$arr_controls_search = array('{is_search}', '{is_list}');
			// default controls for cf
			$controls = str_replace($arr_controls_search, array('', ''), $pattern_controls);


			//print_r($catfields);
			$arr_groups_fields = array();
			foreach ($catfields as $cf)
			{
				// mark group as used
				$used_groups[$cf->group_id] = $cf->group_id;
				$used_fields[$cf->adfield_id] = $cf->adfield_id;

				$arr_controls_val = array();
				$arr_controls_val[] = ($cf->is_search ? ' primary' : '');
				$arr_controls_val[] = ($cf->is_list ? ' primary' : '');

				//arr[i] = group_id + '_' + $me . attr('id') + '_' + is_search + '_' + is_list;

				echo CategoryFieldGroup::htmlGroupOpen($cf->CategoryFieldGroup, '<div class="category_field_group" id="{id}">
					<div class="clearfix">' . $controls_group . '
					<h4>{name}</h4></div>
					<div class="category_field_group_holder">', '</div></div>');

				if ($cf->AdField)
				{
					// field
					echo '<div class="category_field clearfix" id="' . $cf->AdField->id . '">
					' . str_replace($arr_controls_search, $arr_controls_val, $pattern_controls) . '
					<h4>' . View::escape(AdField::getName($cf->AdField)) . '</h4>
					<p>' . View::escape(AdField::getType($cf->AdField)) . '</p>
				</div>';
				}

				//arr[i] = group_id + '_' + $me.attr('id') + '_' + is_search + '_' + is_list;
				$arr_field_str[] = $cf->group_id . '_'
						. $cf->adfield_id . '_'
						. ($cf->is_search ? '1' : '0') . '_'
						. ($cf->is_list ? '1' : '0');
			}

			echo CategoryFieldGroup::htmlGroupClose();
			?>
		</div>
		<p>
			<a class="button primary block" href="#add" data-jq-dropdown="#jq-dropdown-cfedit">
				<i class="fa fa-plus" aria-hidden="true"></i> <?php echo __('Add custom field') ?>
			</a>
		</p>
	</div>
</div>


<div class="display-none">
	<div class="cf_groups">
		<?php
		// display not used groups
		foreach ($categoryfieldgroups as $cfg)
		{
			if (!isset($used_groups[$cfg->id]))
			{
				echo '<div class = "category_field_group clearfix" id = "' . $cfg->id . '">
						<div class="clearfix">' . $controls_group . '
							<h4>' . View::escape(CategoryFieldGroup::getName($cfg)) . '</h4>
							</div>
						<div class = "category_field_group_holder"></div>
					</div>';
			}
		}
		?>
	</div>

	<div class="cf_fields">
		<?php
		// display not used fields
		foreach ($adfields as $af)
		{
			if (!isset($used_fields[$af->id]))
			{
				echo '<div class = "category_field clearfix" id = "' . $af->id . '">
			' . $controls . '
			<h4>' . View::escape(AdField::getName($af)) . '</h4>
			<p>' . View::escape(AdField::getType($af)) . '</p>
			</div>';
			}
		}
		?>
	</div>
</div>

<div id="jq-dropdown-cfedit" class="jq-dropdown">
	<ul class="jq-dropdown-menu">

		<li class="cf_group_add"><a data-v="custom_field_group"><?php echo __('Custom Field Group') ?></a>
			<ul></ul>
		</li>
		<!--<li><a data-v="val">name</a></li>-->
	</ul>
</div>
<div id="jq-dropdown-cfmove" class="jq-dropdown">
	<ul class="jq-dropdown-menu">
		<li class="cf_move_before"><a><i class="fa fa-arrows" aria-hidden="true"></i> <?php echo __('Move before') ?></a>
			<ul></ul>
		</li>
		<li class="cf_move_inside"><a><i class="fa fa-arrow-right" aria-hidden="true"></i> <?php echo __('Move inside') ?></a>
			<ul></ul>
		</li>
		<li class="cf_remove"><a data-v="REMOVE"><i class="fa fa-trash" aria-hidden="true"></i> <?php echo __('Remove') ?></a></li>
	</ul>
</div>

<form method="post">
	<input type="hidden" name="groups_fields" id="groups_fields" value="<?php echo implode('|', $arr_field_str) ?>" />
	<input type="hidden" name="location_id" id="location_id" value="<?php echo $location_id ?>"  />
	<input type="hidden" name="category_id" id="category_id" value="<?php echo $category_id ?>"  />

	<input type="submit"  value="<?php echo __('Save') ?>"/>
	<a href="<?php echo Language::get_url('admin/categoryfield/') ?>" class="button link"><?php echo __('Cancel') ?></a>
</form>
<script type="text/javascript">
	addLoadEvent(function () {
		cbCFedit.init();
	});

	var cbCFedit = {
		init: function () {
			// set search and list selecter toggles
			$('.cf_submit_form').on('click', '.is_search,.is_list', function () {
				$(this).toggleClass('primary');
				cbCFedit.populate();
				return false;
			});

			// setup dropdowns 
			cbCFedit.setupCfeditClick();
			cbCFedit.setupCfmoveClick();
			cbCFedit.setupCfmoveShow();
			cbCFedit.populate();
		},
		setupCfeditClick: function () {
			// setup adding new cf or group to form 
			$('#jq-dropdown-cfedit').on('click', 'li a[data-v]', function () {
				var $me = $(this);
				var datav = $me.attr('data-v');
				var $target = $('.cf_submit_form');
				var $cf = $('.cf_fields .category_field');
				var $cfg = $('.cf_groups .category_field_group');
				if (datav.length)
				{
					if ($me.parents('.cf_group_add:first').length)
					{
						// it is group
						$cfg.filter('#' + datav).appendTo($target);
					}
					else
					{
						// cf clicked add to form 
						$cf.filter('#' + datav).appendTo($target);
					}
					cbCFedit.populate();
				}
			});
		},
		setupCfmoveShow: function () {
			// moving and removing cf populate #jq-dropdown-cfmove
			$('#jq-dropdown-cfmove').on('show', function () {
				var $me = $(this);
				var trigger = $me.data('jq-dropdown-trigger');
				var $item = trigger.parents('.category_field,.category_field_group').first();

				// list all other cf and cfg as moveble item in menu 
				var $target = $('.cf_submit_form');
				var move_before = $target.find('.category_field,.category_field_group').not($item).map(function () {
					var $me = $(this);
					var move_before_cf = '.category_field#';
					var title = $me.find('h4:first').text();
					if ($me.is('.category_field_group'))
					{
						title = 'Group: ' + title;
						move_before_cf = '.category_field_group#';
					}
					return '<li><a data-v="' + move_before_cf + $me.attr('id') + '">' + title + '</a></li>';

				}).get().join('');
				move_before += '<li><a data-v="END">END</a></li>';
				$me.find('li.cf_move_before ul').html(move_before);

				// populate move inside submenu for fields only, not categories 
				$me.find('li.cf_move_inside').hide();
				if (!$item.is('.category_field_group'))
				{
					var $group_with_fields = $target.find('.category_field_group').has('.category_field');
					var move_inside = $target.find('.category_field_group').not($group_with_fields).map(function () {
						var $me = $(this);
						var move_inside_cfg = '.category_field_group#' + $me.attr('id') + ' .category_field_group_holder';
						var title = $me.find('h4:first').text();
						return '<li><a data-v="' + move_inside_cfg + '">' + title + '</a></li>';
					}).get().join('');
					if (move_inside.length)
					{
						$me.find('li.cf_move_inside ul').html(move_inside);
						$me.find('li.cf_move_inside').show();
					}
				}
			});
		},
		setupCfmoveClick: function () {
			// perform moving or removing item 
			$('#jq-dropdown-cfmove').on('click', 'li a[data-v]', function () {
				var $me = $(this);
				var datav = $me.attr('data-v');
				var trigger = $me.parents('.jq-dropdown:first').data('jq-dropdown-trigger');
				var $item = trigger.parents('.category_field,.category_field_group').first();

				switch (datav)
				{
					case 'END':
						// move to the end 
						$('.cf_submit_form').append($item);
						break;
					case 'REMOVE':
						// remove from submit form 
						if ($item.is('.category_field_group'))
						{
							// move to groups
							$('.cf_groups').append($item);

						}
						else
						{
							// move to other items 
							$('.cf_fields').append($item);
						}
						break;
					default:
						if ($me.parents('.cf_move_inside:first').length)
						{
							// move inside group
							$(datav).append($item);
						}
						else
						{
							// move before datav
							$(datav).before($item);
						}

				}

				// populate cf
				cbCFedit.populate();
			});
		},
		setupCfeditShow: function () {
			// build jqdropdown content 
			var $jq_cfedit = $('#jq-dropdown-cfedit');
			var $cf = $('.cf_fields .category_field');
			var $cfg = $('.cf_groups .category_field_group');
			var $cf_group_add = $jq_cfedit.find('.cf_group_add');

			$jq_cfedit.find('li').not($cf_group_add).remove();
			var available_cf = $cf.map(function () {
				var $me = $(this);
				return '<li><a data-v="' + $me.attr('id') + '">' + $me.find('h4:first').text() + '</a></li>';
			}).get().join('');

			$cf_group_add.before(available_cf);

			if ($cfg.length)
			{
				// has available group for adding. show them 
				$cf_group_add.show();
				var available_cfg = $cfg.map(function () {
					var $me = $(this);
					return '<li><a data-v="' + $me.attr('id') + '">' + $me.find('h4:first').text() + '</a></li>';
				}).get().join('');
				$cf_group_add.find('ul').html(available_cfg);
			}
			else
			{
				// no cfg
				$cf_group_add.find('ul li').remove();
				$cf_group_add.hide();
			}
		},
		cleanup: function () {
			// check if there is group in field lits 
			$('.cf_fields .category_field_group').appendTo('.cf_groups');
			// move any field inside group to fields
			$('.cf_groups .category_field').appendTo('.cf_fields');
			// fix nested groups
			$('.cf_submit_form .category_field_group .category_field_group').each(function (i) {
				var $me = $(this);
				$me.insertAfter($me.parents('.category_field_group:first'));
			});

			// populate ad dropdown content. uses .category_field, .category_field_group
			cbCFedit.setupCfeditShow();
		},
		populate: function (event, ui) {
			// cleanup before populating
			cbCFedit.cleanup();

			// serialize selected fields and groups 
			var arr = new Array();
			$('.cf_submit_form .category_field').each(function (i) {
				var $me = $(this);
				var $group = $me.parents('.category_field_group:first');
				var group_id = $group.length > 0 ? $group.attr('id') : 0;
				var is_search = $('.is_search', $me).hasClass('primary') ? 1 : 0;
				var is_list = $('.is_list', $me).hasClass('primary') ? 1 : 0;

				arr[i] = group_id + '_' + $me.attr('id') + '_' + is_search + '_' + is_list;

			});
			$('#groups_fields').val(arr.join('|'));
		}
	};

</script>
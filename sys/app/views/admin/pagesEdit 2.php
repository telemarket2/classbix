<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo $title ?></h1>
<form action="" method="post">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="parent_id"><?php echo __('Parent page'); ?></label></div>
		<div class="col col-12 sm-col-10 px1"><?php echo Page::selectBox($page->parent_id, 'parent_id'); ?></div>
	</div>
	<?php
	$available_vals = '<p>' . __('Add') . ':  '
			. '<a href="#' . View::escape('{@CONTACTUSFORM}') . '" 
				data-id="' . View::escape('{@CONTACTUSFORM}') . '" 
				class="add_to_body button white small" data-target="">'
			. '<i class="fa fa-plus" aria-hidden="true"></i> '
			. View::escape(__('Contact us form')) . '</a>
			<a href="#' . View::escape('{@LOCATIONS}') . '" 
				data-id="' . View::escape('{@LOCATIONS}') . '" 
				class="add_to_body button white small" data-target="">'
			. '<i class="fa fa-plus" aria-hidden="true"></i> '
			. View::escape(__('All locations')) . '</a>
			<a href="#' . View::escape('{@CATEGORIES}') . '" 
				data-id="' . View::escape('{@CATEGORIES}') . '" 
				class="add_to_body button white small" data-target="">'
			. '<i class="fa fa-plus" aria-hidden="true"></i> '
			. View::escape(__('All categories')) . '</a>
			</p>';

	$tab_key = 'name_';
	foreach ($language as $lng)
	{
		$lng_label = Language::tabsLabelLngInfo($language, $lng);
		$echo .= '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
					<div class="col col-12 sm-col-2 px1 form-label">
						<label for="page_description[' . $lng->id . '][name]">' . __('Name') . $lng_label . '</label></div>
					<div class="col col-12 sm-col-10 px1">
						<input name="page_description[' . $lng->id . '][name]" type="text" class="input input-long"
							id="page_description[' . $lng->id . '][name]" ' . Language::tabsRelDefault($lng) . '
							value="' . View::escape(Page::getName($page, $lng->id)) . '" required />
					</div>
				</div>'
				. '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
					<div class="col col-12 sm-col-2 px1 form-label">
						<label for="page_description[' . $lng->id . '][description]">' . __('Description') . $lng_label . '</label>
					</div>
					<div class="col col-12 sm-col-10 px1">
						<textarea name="page_description[' . $lng->id . '][description]" 
							id="page_description[' . $lng->id . '][description]" 
							class="page_description_' . $lng->id . '_description" cols="40">' . View::escape(Page::getDescription($page, $lng->id)) . '</textarea>'
				. str_replace('data-target=""', 'data-target="page_description_' . $lng->id . '_description"', $available_vals) . '
					</div>
				</div>';
	}

	$tabs_pattern = '<div class="clearfix form-row"><div class="col col-12 px1 tabs">{tabs}</div></div>';
	echo Language::tabs($language, $tab_key, $tabs_pattern) . $echo;
	?>	
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input name="enabled" type="checkbox" id="enabled" value="1" <?php echo ($page->enabled ? 'checked="checked"' : '') ?> required />
				<span class="checkmark"></span>
				<?php echo __('Enabled') ?>
			</label>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<input type="hidden" name="id" id="id" value="<?php echo $page->id ?>"  />
			<a href="<?php echo Language::get_url('admin/pages/' . $page->parent_id . '/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>


<script language="javascript">
	addLoadEvent(function () {
		$('.add_to_body').click(insertVar);
	});
</script>
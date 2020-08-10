<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo $title ?></h1>
<form action="" method="post">
	<?php
	$tab_key = 'name_';
	foreach ($language as $lng)
	{
		$lng_label = Language::tabsLabelLngInfo($language, $lng);

		$echo .= '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
						<div class="col col-12 sm-col-2 px1 form-label">
							<label for="cfg_description[' . $lng->id . '][name]">' . __('Name') . $lng_label . '</label>
						</div>
						<div class="col col-12 sm-col-10 px1">
							<input name="cfg_description[' . $lng->id . '][name]" type="text" required
								id="cfg_description[' . $lng->id . '][name]" ' . Language::tabsRelDefault($lng) . '
								value="' . View::escape(CategoryFieldGroup::getNameByLng($catfieldgroup, $lng->id)) . '" />
						</div>
					</div>';
	}

	$tabs_pattern = '<div class="clearfix form-row"><div class="col col-12 px1 tabs">{tabs}</div></div>';
	echo Language::tabs($language, $tab_key, $tabs_pattern) . $echo;
	?>	

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<input type="hidden" name="id" id="id" value="<?php echo $catfieldgroup->id ?>"  />
			<input type="hidden" name="space" id="space" value="<?php echo $catfieldgroup->space ?>"  />
			<a href="<?php echo Language::get_url('admin/categoryFieldGroup/') ?>" class="button link"><?php echo __('Cancel') ?></a>

		</div>
	</div>
</form>
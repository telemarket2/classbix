<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo $title ?></h1>
<form action="" method="post">


	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="parent_id"><?php echo __('Parent location'); ?></label></div>
		<div class="col col-12 sm-col-10 px1"><?php
			//echo Location::selectBox($location->parent_id, 'parent_id');
			echo '<input name="parent_id" value="' . View::escape($location->parent_id) . '" 
					data-src="' . Config::urlJson(Location::STATUS_ALL) . '"
					data-key="location"
					data-selectalt="1"
					data-rootname="' . View::escape(__('No parent')) . '"
					data-currentname="' . View::escape(Category::getNameById($location->parent_id)) . '"
					data-allpattern="' . View::escape(__('Parent: <b>{name}</b>')) . '"
					data-allallow="1"
					data-disable="' . $location->id . '"
					class="display-none"
					>';
			?>
		</div>
	</div>
	<?php
	$tab_key = 'name_';
	$echo = '';
	foreach ($language as $lng)
	{
		$lng_label = Language::tabsLabelLngInfo($language, $lng);

		/* $echo .= '<tr class="' . Language::tabsTabKey($tab_key, $lng) . '">
		  <td><label for="location_description[' . $lng->id . '][name]">' . __('Name') . $lng_label . ':</label></td>
		  <td><input name="location_description[' . $lng->id . '][name]" type="text"
		  id="location_description[' . $lng->id . '][name]" ' . Language::tabsRelDefault($lng) . '
		  value="' . View::escape(Location::getNameByLng($location, $lng->id)) . '" /></td>
		  </tr>
		  <tr class="' . Language::tabsTabKey($tab_key, $lng) . '">
		  <td><label for="location_description[' . $lng->id . '][description]">' . __('Description') . $lng_label . ':</label></td>
		  <td><textarea name="location_description[' . $lng->id . '][description]"
		  id="location_description[' . $lng->id . '][description]"
		  cols="40">' . View::escape(Location::getDescriptionByLng($location, $lng->id)) . '</textarea></td>
		  </tr>'; */

		$echo .= '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
					<div class="col col-12 sm-col-2 px1 form-label">
						<label for="location_description[' . $lng->id . '][name]">' . __('Name') . $lng_label . '</label>
					</div>
					<div class="col col-12 sm-col-10 px1">
						<input name="location_description[' . $lng->id . '][name]" type="text" required
							id="location_description[' . $lng->id . '][name]" ' . Language::tabsRelDefault($lng) . '
							value="' . View::escape(Location::getNameByLng($location, $lng->id)) . '" class="input input-long" />
					</div>
				</div>'
				. '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
					<div class="col col-12 sm-col-2 px1 form-label">
						<label for="location_description[' . $lng->id . '][description]">' . __('Description') . $lng_label . '</label>
					</div>
					<div class="col col-12 sm-col-10 px1">
						<textarea name="location_description[' . $lng->id . '][description]" 
							id="location_description[' . $lng->id . '][description]" 
							cols="40">' . View::escape(Location::getDescriptionByLng($location, $lng->id)) . '</textarea>
					</div>
				</div>';
	}
	$tabs_pattern = '<div class="clearfix form-row"><div class="col col-12 px1 tabs">{tabs}</div></div>';
	echo Language::tabs($language, $tab_key, $tabs_pattern) . $echo;
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="slug"><?php echo __('Permalink') ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<div class="input-group input-group-block" 
				 data-editableslug="<?php echo $location->id ?>"
				 data-url="admin/locationsSlug/" 
				 data-listen="input[rel='default_name']" 
				 data-hideclass="display-none" 
				 >
				<input name="slug" type="text" id="slug" value="<?php echo View::escape($location->slug) ?>" class="input" readonly="readonly" />
				<a href="#edit" class="button edit_slug" title="<?php echo View::escape(__('Edit')) ?>"><i class="fa fa-edit" aria-hidden="true"></i></a>	
				<a href="#edit_ok" class="button display-none edit_slug_ok" title="<?php echo View::escape(__('Ok')) ?>"><i class="fa fa-check" aria-hidden="true"></i></a>
				<a href="#edit_cancel" class="button display-none edit_slug_cancel" title="<?php echo View::escape(__('Cancel')) ?>"><i class="fa fa-times" aria-hidden="true"></i></a>
			</div>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input name="enabled" type="checkbox" id="enabled" value="1" <?php echo ($location->enabled ? 'checked="checked"' : '') ?> />
				<span class="checkmark"></span>
				<?php echo __('Enabled') ?>
			</label>
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<input type="hidden" name="id" id="id" value="<?php echo $location->id ?>"  />
			<a href="<?php echo Language::get_url('admin/locations/' . $location->parent_id . '/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>
<script>
	addLoadEvent(function () {
		cb.editSlug($('[data-editableslug]'));
	});
</script>
<?php $this->validation()->messages() ?>
<h1 class="mt0"><?php echo $title ?></h1>
<form action="" method="post">

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="parent_id"><?php echo __('Parent category'); ?></label></div>
		<div class="col col-12 sm-col-10 px1"><?php
			//echo Category::selectBox($category->parent_id, 'parent_id');
			echo '<input name="parent_id" value="' . View::escape($category->parent_id) . '" 
					data-src="' . Config::urlJson(Location::STATUS_ALL) . '"
					data-key="category"
					data-selectalt="1"
					data-rootname="' . View::escape(__('No parent')) . '"
					data-currentname="' . View::escape(Category::getNameById($category->parent_id)) . '"
					data-allpattern="' . View::escape(__('Parent: <b>{name}</b>')) . '"
					data-allallow="1"
					data-disable="' . $category->id . '"
					class="display-none"
					>';
			?></div>
	</div>
	<?php
	$tab_key = 'name_';
	foreach ($language as $lng)
	{
		$lng_label = Language::tabsLabelLngInfo($language, $lng);

		$echo .= '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
						<div class="col col-12 sm-col-2 px1 form-label">
							<label for="category_description[' . $lng->id . '][name]">
								' . __('Name') . $lng_label . '
							</label>
						</div>
						<div class="col col-12 sm-col-10 px1">
							<input name="category_description[' . $lng->id . '][name]" type="text" required
								id="category_description[' . $lng->id . '][name]" ' . Language::tabsRelDefault($lng) . '
								value="' . View::escape(Category::getNameByLng($category, $lng->id)) . '" class="input input-long" />
						</div>
					</div>'
				. '<div class="clearfix form-row ' . Language::tabsTabKey($tab_key, $lng) . '">
						<div class="col col-12 sm-col-2 px1 form-label">
							<label for="category_description[' . $lng->id . '][description]">
								' . __('Description') . $lng_label . '
							</label>
						</div>
						<div class="col col-12 sm-col-10 px1">
							<textarea name="category_description[' . $lng->id . '][description]" 
								id="category_description[' . $lng->id . '][description]" 
								class="category_description_' . $lng->id . '_description" cols="40">' . View::escape(Category::getDescriptionByLng($category, $lng->id)) . '</textarea>
							<p>' . __('Insert variable') . ': 
								<a href="#{@LOCATION_OR_SITETITLE}" data-id="{@LOCATION_OR_SITETITLE}" data-target="category_description_' . $lng->id . '_description" class="add_to_body button small white"><i class="fa fa-plus" aria-hidden="true"></i> ' . __('Location or Site title') . '</a>
							</p>
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
				 data-editableslug="<?php echo $category->id ?>"
				 data-url="admin/categoriesSlug/" 
				 data-listen="input[rel='default_name']" 
				 data-hideclass="display-none" 
				 >
				<input name="slug" type="text" id="slug" value="<?php echo View::escape($category->slug) ?>" class="input" readonly="readonly" />
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
				<input name="locked" type="checkbox" id="locked" value="1" <?php echo ($category->locked ? 'checked = "checked"' : '') ?> />
				<span class="checkmark"></span>
				<?php echo __('Locked') ?>
			</label>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input name="enabled" type="checkbox" id="enabled" value="1" <?php echo ($category->enabled ? 'checked = "checked"' : '') ?> />
				<span class="checkmark"></span>
				<?php echo __('Enabled') ?>
			</label>
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<input type="hidden" name="id" id="id" value="<?php echo $category->id ?>"  />
			<a href="<?php echo Language::get_url('admin/categories/' . $category->parent_id . '/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>
<script>
	addLoadEvent(function () {
		cb.editSlug($('[data-editableslug]'));
		$('.add_to_body').click(insertVar);
	});
</script>
<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo $title ?></h1>
<p><?php echo __('Select location and category to add custom fields.') ?></p>

<form method="post">


	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="location_id"><?php echo __('Location') ?></label></div>
		<div class="col col-12 sm-col-10 px1"><?php
			echo '<input name="location_id" value="' . View::escape($location_id) . '" 
					data-src="' . Config::urlJson(Location::STATUS_ALL) . '"
					data-key="location"
					data-selectalt="1"
					data-rootname="' . View::escape(__('All locations')) . '"
					data-currentname="' . View::escape(Location::getNameById($location_id)) . '"
					data-allpattern="' . View::escape(__('All <b>{name}</b>')) . '"
					data-allallow="1"
					class="display-none"
					>';
			?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="category_id"><?php echo __('Category') ?></label></div>
		<div class="col col-12 sm-col-10 px1"><?php
			echo '<input name="category_id" value="' . View::escape($category_id) . '" 
					data-src="' . Config::urlJson(Location::STATUS_ALL) . '"
					data-key="category"
					data-selectalt="1"
					data-rootname="' . View::escape(__('All categories')) . '"
					data-currentname="' . View::escape(Category::getNameById($category_id)) . '"
					data-allpattern="' . View::escape(__('All <b>{name}</b>')) . '"
					data-allallow="1"
					class="display-none"
					>';
			?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit') ?>" />
			<input type="hidden" value="1" name="step" id="step" />
			<a href="<?php echo Language::get_url('admin/categoryfield/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>


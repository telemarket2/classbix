<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Confirm delete custom field') ?></h1>
<form action="" method="post">
	<?php
	$arr_adfield = array();
	foreach ($catfields as $cf)
	{
		$location = $cf->Location;
		$category = $cf->Category;
		if ($cf->AdField)
		{
			$arr_adfield[] = AdField::getName($cf->AdField);
		}
	}
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Location') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo Location::getFullName($location, __('All locations')) ?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Category') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo Category::getFullName($category, __('All categories')) ?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Custom fields') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo View::escape(implode(', ', $arr_adfield)) ?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input type="checkbox" name="confirm_delete" id="confirm_delete" value="1" required />  
				<span class="checkmark"></span>
				<?php echo __('Yes, delete this category custom fields.') ?>
			</label>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<input type="hidden" name="location_id" id="location_id" value="<?php echo $cf->location_id ?>"  />
			<input type="hidden" name="category_id" id="category_id" value="<?php echo $cf->category_id ?>"  />
			<a href="<?php echo Language::get_url('admin/categoryfield/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>
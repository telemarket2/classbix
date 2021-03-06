<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Confirm delete category') ?></h1>
<form action="" method="post">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label><?php echo __('Name') ?></label></div>
		<div class="col col-12 sm-col-10 px1"><?php echo View::escape(Category::getName($category)) ?></div>
	</div>
	<?php
	if ($subcategory_tree)
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label><?php echo __('Subcategories') ?></label></div>
			<div class="col col-12 sm-col-10 px1"><?php echo $subcategory_tree ?></div>
		</div>

		<?php
		$confirm_message = __('Yes, delete this category and subcategories.');
	}
	else
	{
		$confirm_message = __('Yes, delete this category.');
	}
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input type="checkbox" name="confirm_delete" id="confirm_delete" value="1" required />
				<span class="checkmark"></span>
				<?php echo $confirm_message ?>
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
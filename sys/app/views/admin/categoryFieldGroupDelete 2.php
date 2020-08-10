<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Confirm delete category field group') ?></h1>
<form action="" method="post">


	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Name') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo View::escape(CategoryFieldGroup::getName($catfieldgroup)) ?></div>
	</div>

	<?php
	if ($catfieldgroup->CategoryFieldRelation)
	{
		foreach ($catfieldgroup->CategoryFieldRelation as $cr)
		{
			$echo_loc_cat .= '<li>' . Location::getFullName($cr->Location, __('All locations')) . ' | ' .
					Category::getFullName($cr->Category, __('All categories')) . '</li>';
		}

		echo '
				<div class="clearfix form-row">
					<div class="col col-12 px1">
						<div class="msg-error">
							' . __('This group will be removed from these category custom field forms.') . '
							<ul>' . $echo_loc_cat . '</ul>
						</div>
					</div>
				</div>';
	}
	?>


	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input type="checkbox" name="confirm_delete" id="confirm_delete" value="1" required />  
				<span class="checkmark"></span> 
				<?php echo __('Yes, delete this category field group.') ?>
			</label>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<input type="hidden" name="id" id="id" value="<?php echo $catfieldgroup->id ?>"  />
			<a href="<?php echo Language::get_url('admin/categoryFieldGroup/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>
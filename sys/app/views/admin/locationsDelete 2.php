<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Confirm delete location') ?></h1>
<form action="" method="post">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Name') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo View::escape(Location::getName($location)) ?></div>
	</div>
	<?php
	if ($sublocation_tree)
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Sublocations') ?></div>
			<div class="col col-12 sm-col-10 px1"><?php echo $sublocation_tree ?></div>
		</div>
		<?php
		$confirm_message = __('Yes, delete this location and sublocations.');
	}
	else
	{
		$confirm_message = __('Yes, delete this location.');
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
			<input type="hidden" name="id" id="id" value="<?php echo $location->id ?>"  />
			<a href="<?php echo Language::get_url('admin/locations/' . $location->parent_id . '/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>
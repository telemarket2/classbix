<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Confirm delete theme') ?></h1>
<form action="" method="post">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Name') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo View::escape($theme->info['name']) ?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Theme files located in '); ?></div>
		<div class="col col-12 sm-col-10 px1"><code>/themes/<?php echo View::escape($theme->id) ?>/</code></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<p class="mt0"><?php echo __('Theme files and stored options will be deleted. Are you sure you want delete this theme?') ?></p>
			<p>
				<label class="input-checkbox">
					<input type="checkbox" name="confirm_delete" id="confirm_delete" value="1" required />  
					<span class="checkmark"></span>
					<?php echo __('Yes, delete this theme.') ?>
				</label>
			</p>
			<p>
				<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
				<input type="hidden" name="theme_id" id="theme_id" value="<?php echo View::escape($theme->id) ?>"  />
				<a href="<?php echo Language::get_url('admin/themes/') ?>" class="button link"><?php echo __('Cancel') ?></a>
			</p>
		</div>
	</div>
</form>
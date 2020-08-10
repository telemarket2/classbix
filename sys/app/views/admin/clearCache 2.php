<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Clear cache') ?></h1>
<p><?php echo __('Clear cache only if you have any problems with cache or resized images. After clearing cache, new content will be populated on first page view.') ?></p>
<form method="post">
	<p>
		<button type="submit" name="clear_data" value="1" class="button"><i class="fa fa-database"></i> <?php echo __('Clear data cache'); ?></button>	
		<button type="submit" name="clear_image" value="1" class="button"><i class="fa fa-image"></i> <?php echo __('Clear image cache'); ?></button>	
		<button type="submit" name="clear" value="1" class="button"><i class="fa fa-check-circle"></i> <?php echo __('Clear all cache'); ?></button>	
	</p>
	<p><a href="<?php echo Language::get_url('admin/') ?>" class="button link"><?php echo __('Cancel') ?></a></p>
</form>
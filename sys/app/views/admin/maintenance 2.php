<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Maintenance') ?></h1>
<p><?php echo __('While in maintenance mode, users can\'t access your website. Useful if you need to make changes on your website.') ?></p>
<form method="post">
	<p>
		<?php echo '<b>' . __('Maintenance mode') . '</b> '; ?>
		<input type="hidden" name="maintenance" value="<?php echo ($maintenance_mode ? 0 : 1); ?>" />
		<button name="submit" type="submit" class="button <?php echo $maintenance_mode ? 'green' : '' ?>"><?php echo ($maintenance_mode ? __('Enabled') : __('Disabled')); ?></button>
	</p>
</form>

<h2><?php echo __('Debug mode') ?></h2>
<p><?php echo __('While in debug mode admins will see database queries, execution time, memory usage.') ?></p>
<form method="post">
	<p>
		<?php echo '<b>' . __('Debug mode') . '</b> '; ?>
		<input type="hidden" name="debug_mode" value="<?php echo ($debug_mode ? 0 : 1); ?>" />
		<button name="submit" type="submit" class="button <?php echo $debug_mode ? 'green' : '' ?>"><?php echo ($debug_mode ? __('Enabled') : __('Disabled')); ?></button>
	</p>
</form>


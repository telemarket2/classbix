<div class="sidebar">
	<div class="sidebar_content">
		<?php
		echo Config::renderMenu(array(
			'menu_items'	 => $sidemenu,
			'menu_selected'	 => $menu_selected,
		));
		?>
		<p><small class="muted"><?php echo __('Server time') . ':<br/>' . date('r') ?></small></p>
	</div>
	<a class="sidebar_close" title="<?php echo View::escape(__('Close menu')); ?>"><i class="fa fa-fw fa-close fa-2x" aria-hidden="true"></i></a>
</div>
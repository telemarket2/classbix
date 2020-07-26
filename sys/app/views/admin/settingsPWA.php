<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('PWA') ?></h1>
<form action="" method="post">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="pwa_enable"><?php echo __('PWA (Progressive Web App)'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<select name="pwa_enable" id="pwa_enable">
				<?php
				$arr_pwa_enable[Config::option('pwa_enable')] = ' selected="selected"';
				?>
				<option value="none" <?php echo $arr_pwa_enable['none']; ?>><?php echo __('none') ?></option>
				<option value="enable" <?php echo $arr_pwa_enable['enable']; ?>><?php echo __('enable') ?></option>
				<option value="disable" <?php echo $arr_pwa_enable['disable']; ?>><?php echo __('disable') ?></option>
			</select>
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="pwa_display"><?php echo __('Display'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<select name="pwa_display" id="pwa_display">
				<?php
				$arr_pwa_display[Config::option('pwa_display')] = ' selected="selected"';
				?>
				<option value="fullscreen" <?php echo $arr_pwa_display['fullscreen']; ?>><?php echo __('fullscreen') ?></option>
				<option value="standalone" <?php echo $arr_pwa_display['standalone']; ?>><?php echo __('standalone') ?></option>
				<option value="minimal-ui" <?php echo $arr_pwa_display['minimal-ui']; ?>><?php echo __('minimal-ui') ?></option>
				<option value="browser" <?php echo $arr_pwa_display['browser']; ?>><?php echo __('browser') ?></option>
			</select>
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="pwa_start_url"><?php echo __('Start URL'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="pwa_start_url" type="text" id="pwa_start_url" value="<?php echo View::escape(Config::option('pwa_start_url')); ?>" class="input input-long" placeholder="./?utm_source=pwa&utm_medium=pwa&utm_campaign=pwa" />
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="pwa_theme_color"><?php echo __('Theme color'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="pwa_theme_color" type="text" id="pwa_theme_color" value="<?php echo View::escape(Config::option('pwa_theme_color')); ?>" placeholder="#eeeeee" />
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<a href="<?php echo Language::get_url('admin/') ?>" class="button link"><?php echo __('Cancel'); ?></a>
		</div>
	</div>
</form>
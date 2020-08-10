<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Header / footer') ?></h1>
<form action="" method="post" id="settings_form">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="custom_header"><?php echo __('Custom header'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<textarea name="custom_header" id="custom_header"><?php echo View::escape(Config::option('custom_header')); ?></textarea>
			<p><em><?php echo __('This will be placed between head tags on every page in front end and admin.') ?></em></p>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="custom_footer"><?php echo __('Custom footer'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<textarea name="custom_footer" id="custom_footer"><?php echo View::escape(Config::option('custom_footer')); ?></textarea>
			<p><em><?php echo __('This will be placed at the bottom of every page before closing body tag. Used to set custom tracking code like google analytics. Javascripts suggested to place at bottom to improve page load speed.') ?></em></p>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="custom_css"><?php echo View::escape(__('Code inside {name} tags',array('{name}'=>'<style>'))); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<textarea name="custom_css" id="custom_css"><?php echo View::escape(Config::option('custom_css')); ?></textarea>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="custom_js"><?php echo View::escape(__('Code inside {name} tags',array('{name}'=>'<script>'))); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<textarea name="custom_js" id="custom_js"><?php echo View::escape(Config::option('custom_js')); ?></textarea>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="powered_by_link"><?php echo __('Powered by link'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="powered_by_link" type="text" id="powered_by_link" class="input input-long" value="<?php echo View::escape(Config::scriptName()); ?>" />
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input type="checkbox" name="powered_by_hide_front" id="powered_by_hide_front" value="1"
					   <?php echo (Config::option('powered_by_hide_front') ? 'checked="checked"' : ''); ?> />
				<span class="checkmark"></span>
				<?php echo __('Hide powered by link on front pages'); ?>
			</label>
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
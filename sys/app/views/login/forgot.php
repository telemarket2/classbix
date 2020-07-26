<?php echo $this->validation()->messages(); ?>

<form action="<?php echo Language::get_url('login/forgot/'); ?>" method="post" class="form_large">
	<h1 class="center"><?php echo __('Forgot password'); ?></h1>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label"><label for="email"><?php echo __('Email'); ?>:</label></div>
		<div class="col col-12 sm-col-8 px1">
			<input id="email" size="30" type="email" name="email" value="<?php echo View::escape($email); ?>" class="input input-long" required="required" />
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-4 px1 form-label"></div>
		<div class="col col-12 sm-col-8 px1">
			<input class="submit" type="submit" accesskey="s" value="<?php echo __('Send password'); ?>" class="button" /> 
			<a href="<?php echo Language::get_url('login/'); ?>" class="button link"><?php echo __('Login'); ?></a>
		</div>
	</div>

</form>
<script type="text/javascript" language="javascript" charset="utf-8">
	// <![CDATA[
	document.getElementById('email').focus();
	// ]]>
</script>
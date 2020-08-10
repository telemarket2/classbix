<form action="" method="post" id="contact_form">
	<h3 class="widget_title"><?php echo __('Contact form') ?></h3>

	<p><label for="email"><?php echo __('Your email') . Config::markerRequired(); ?></label>
		<input type="text" name="email" id="email" value="<?php echo View::escape($contact->email) ?>" />
		<?php echo View::validation()->email_error; ?></p>

	<p><label for="message"><?php echo __('Message') . Config::markerRequired(); ?></label>
		<textarea name="message" id="message"><?php echo View::escape($contact->message) ?></textarea>
		<?php echo View::validation()->message_error; ?></p>

	<?php echo Captcha::render('<p><label for="{name}">{label}: {marker}</label> {input}</p>'); ?>

	<p><input type="submit" name="submit" id="submit" value="<?php echo __('Send') ?>" />
		<a href="#cancel" class="cancel"><?php echo __('Cancel') ?></a>
		<input type="hidden" name="id" id="id" value="<?php echo View::escape($ad->id); ?>" />
		<input type="hidden" name="action" id="action" value="contact_form" />
		<?php echo Config::nounceInput(); ?>
	</p>
</form>
<form action="" method="post" id="contact_us_form">
	<h3 class="widget_title"><?php echo __('Contact us form') ?></h3>

	<p><label for="email"><?php echo __('Your email') . Config::markerRequired();?></label>
		<input type="text" name="email" id="email" value="<?php echo View::escape($contact->email) ?>" />
		<?php echo View::validation()->email_error; ?></p>

	<p><label for="subject"><?php echo __('Subject') . Config::markerRequired();?></label>
		<input type="text" name="subject" id="subject" value="<?php echo View::escape($contact->subject) ?>" />
		<?php echo View::validation()->subject_error; ?></p>

	<p><label for="message"><?php echo __('Message') . Config::markerRequired();?></label>
		<textarea name="message" id="message"><?php echo View::escape($contact->message) ?></textarea>
		<?php echo View::validation()->message_error; ?></p>

	<?php echo Captcha::render('<p><label for="{name}">{label}: {marker}</label> {input}</p>'); ?>

	<p><input type="submit" name="submit" id="submit" value="<?php echo __('Send') ?>" />
		<input type="hidden" name="action" id="action" value="contact_us" />
		<?php echo Config::nounceInput(); ?>
	</p>
</form>
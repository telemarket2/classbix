<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Mail server') ?></h1>
<form action="" method="post" id="settings_form">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="email_from_name"><?php echo __('Email from name'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="email_from_name" type="text" id="email_from_name" value="<?php echo View::escape(MailTemplate::emailFromName()); ?>" />
			<em><?php echo __('Suggested value name of the site.'); ?></em>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="email_from_address"><?php echo __('Email from address'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="email_from_address" type="text" id="email_from_address" value="<?php echo View::escape(MailTemplate::emailFromAddress()); ?>" />
			<em><?php echo __('Suggested value support@your-domain.com'); ?></em>
		</div>
	</div>

	<!-- MAIL PROTOCOL -->
	<h2 colspan="2" id="grp_image"><?php echo __('Mail protocol'); ?></h2>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="mail_protocol"><?php echo __('Mail protocol'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$mail_protocol[Config::option('mail_protocol')] = 'selected="selected"';
			?>
			<select name="mail_protocol" id="mail_protocol" class="short">
				<option value="mail" <?php echo $mail_protocol['mail'] ?>><?php echo __('Mail'); ?></option>
				<option value="smtp" <?php echo $mail_protocol['smtp'] ?>><?php echo __('SMTP'); ?></option>
			</select>
			<a href="#test_mail_server" class="test_mail_server button">
				<i class="fa fa-play" aria-hidden="true"></i> 
				<?php echo __('Test mail server'); ?>
			</a>
			<div class="test_mail_server_result"></div>
		</div>
	</div>
	<div class="mail_protocol_smtp">
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="smtp_host"><?php echo __('Smtp host'); ?></label></div>
			<div class="col col-12 sm-col-10 px1">
				<input name="smtp_host" type="text" id="smtp_host" value="<?php echo View::escape(Config::option('smtp_host')); ?>" />
			</div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="smtp_port"><?php echo __('Smtp port'); ?></label></div>
			<div class="col col-12 sm-col-10 px1">
				<input name="smtp_port" type="text" id="smtp_port" value="<?php echo View::escape(Config::option('smtp_port')); ?>" />
			</div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="smtp_encryption"><?php echo __('Smtp encryption'); ?></label></div>
			<div class="col col-12 sm-col-10 px1">
				<?php
				$smtp_encryption[Config::option('smtp_encryption')] = 'selected="selected"';
				?>
				<select name="smtp_encryption" id="smtp_encryption" class="short">
					<option value="" <?php echo $smtp_encryption[''] ?>><?php echo __('None'); ?></option>
					<option value="tls" <?php echo $smtp_encryption['tls'] ?>><?php echo __('tls'); ?></option>
					<option value="ssl" <?php echo $smtp_encryption['ssl'] ?>><?php echo __('ssl'); ?></option>
				</select>
			</div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="smtp_user"><?php echo __('Smtp user'); ?></label></div>
			<div class="col col-12 sm-col-10 px1">
				<input name="smtp_user" type="text" id="smtp_user" value="<?php echo View::escape(Config::option('smtp_user')); ?>" />
			</div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="smtp_password"><?php echo __('Smtp password'); ?></label></div>
			<div class="col col-12 sm-col-10 px1">
				<input name="smtp_password" type="text" id="smtp_password" value="<?php echo View::escape(Config::option('smtp_password')); ?>" />
			</div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="smtp_timeout"><?php echo __('Smtp timeout'); ?></label></div>
			<div class="col col-12 sm-col-10 px1">
				<input name="smtp_timeout" type="text" id="smtp_timeout" value="<?php echo View::escape(Config::option('smtp_timeout')); ?>" />
			</div>
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
<script>
	addLoadEvent(function () {
		$('.test_mail_server').click(test_mail_server);
		$('[name="mail_protocol"]').change(show_hide_mail_protocol_smtp);
		show_hide_mail_protocol_smtp();
	});

	function test_mail_server()
	{
		var data = $('#settings_form').serialize();
		var $me = $(this);
		$me.addClass('loading');
		$('.test_mail_server_result').html('<?php echo View::escape(__('testing mail server ...')) ?>');
		$.post(BASE_URL + 'admin/testMailServer/', {data: data}, function (data) {
			$('.test_mail_server_result').html(data);
		}).always(function () {
			$me.removeClass('loading');
		});

		return false;
	}

	function show_hide_mail_protocol_smtp()
	{
		console.log('show_hide_mail_protocol_smtp:' + $('[name="mail_protocol"]').val());
		if ($('[name="mail_protocol"]').val() == 'smtp') {
			console.log('show_hide_mail_protocol_smtp:smtp');
			$('.mail_protocol_smtp').slideDown('slow');
		} else {
			console.log('show_hide_mail_protocol_smtp:NOT smtp');
			$('.mail_protocol_smtp').hide();
		}
	}
</script>
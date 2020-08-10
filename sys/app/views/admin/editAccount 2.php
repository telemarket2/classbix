<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo View::escape($title) ?></h1>
<form action="" method="post" enctype="multipart/form-data">

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Account type') ?></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			echo User::getLevel($user->level);

			if (User::canUpgradeToDealer($user))
			{
				// users can upgrade to dealer account 
				echo ' - <a href="' . Language::get_url('admin/upgradetodealer/') . '">' . __('Upgrade to dealer account') . '</a>';
				echo '<p>' . __('Dealer account can have link to website, company logo and more information like contact phone, opening hours etc.') . '</p>';
			}
			?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="name"><?php echo __('Name') ?></label></div>
		<div class="col col-12 sm-col-10 px1"><input name="name" type="text" id="name" value="<?php echo View::escape($user->name) ?>" size="40" required /></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="username"><?php echo __('Permalink') ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<div class="input-group" 
				 data-editableslug="<?php echo $user->id ?>"
				 data-url="admin/usersSlug/" 
				 data-listen="input#name" 
				 data-hideclass="display-none" 
				 >
				<input name="username" type="text" id="username" value="<?php echo View::escape($user->username) ?>" class="input" readonly="readonly" />
				<a href="#edit" class="button edit_slug" title="<?php echo View::escape(__('Edit')) ?>"><i class="fa fa-edit" aria-hidden="true"></i></a>	
				<a href="#edit_ok" class="button display-none edit_slug_ok" title="<?php echo View::escape(__('Ok')) ?>"><i class="fa fa-check" aria-hidden="true"></i></a>
				<a href="#edit_cancel" class="button display-none edit_slug_cancel" title="<?php echo View::escape(__('Cancel')) ?>"><i class="fa fa-times" aria-hidden="true"></i></a>
			</div>	
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Email') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo View::escape($user->email) ?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="password"><?php echo ( $add ? __('Password') : __('New Password (leave blank if not changing)')) ?></label></div>
		<div class="col col-12 sm-col-10 px1"><input name="password" type="password" id="password" value="" size="40" /></div>
	</div>


	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="password_repeat"><?php echo ($add ? __('Password Repeat') : __('New Password Repeat')) ?></label></div>
		<div class="col col-12 sm-col-10 px1"><input name="password_repeat" type="password" id="password_repeat" value="" size="40" /></div>
	</div>
	<?php
	// dealer options
	if ($user->level == User::PERMISSION_DEALER)
	{
		echo new View('admin/_dealerFields', $this->vars);
	}
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit') ?>" />
			<input type="hidden" name="id" id="id" value="<?php echo $user->id ?>"  />
			<a href="<?php echo Language::get_url('admin/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>


<script>
	addLoadEvent(function () {
		cb.editSlug($('[data-editableslug]'));
		$('.remove_dealer_logo').click(removeLogo);
	});

	function removeLogo()
	{
		var id = $('#id').val();
		$.post(BASE_URL + 'admin/usersRemoveLogo/', {id: id}, function (data) {
			if (data == 'ok') {
				$('img.dealer_logo,.remove_dealer_logo').remove();
			} else {
				// error
				alert(data);
			}
		});
		return false;
	}
</script>
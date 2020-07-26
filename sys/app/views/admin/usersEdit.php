<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php
	echo $title
	. ($user->id && User::countAdType($user, 'listed') ? ' <a href="' . User::url($user) . '" class="button small"><i class="fa fa-eye" aria-hidden="true"></i> ' . __('View') . '</a>' : '');
	?></h1>
<form action="" method="post" enctype="multipart/form-data" autocomplete="off">

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="name"><?php echo __('Name') ?>*</label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="name" type="text" id="name" value="<?php echo View::escape($user->name) ?>" class="input input-long" required />
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="email"><?php echo __('Email') ?>*</label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="email" type="email" id="email" value="<?php echo View::escape($user->email) ?>" class="input input-long" required />
			<?php echo $this->validation()->email_error; ?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="username"><?php echo __('Permalink') ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<div class="input-group input-group-block" 
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
		<div class="col col-12 sm-col-2 px1 form-label"><label for="level"><?php echo __('Permission') ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<select name="level" id="level">
				<?php
				foreach ($levels as $k => $v)
				{
					if (!User::canEditModerator(User::PERMISSION_MODERATOR))
					{
						if ($k == User::PERMISSION_ADMIN || $k == User::PERMISSION_MODERATOR)
						{
							continue;
						}
					}

					if ($user->level == $k)
					{
						$sel = 'selected="selected"';
					}
					else
					{
						$sel = '';
					}
					echo '<option value="' . $k . '" ' . $sel . '>' . View::escape($v) . '</option>';
				}
				?>
			</select>
		</div>
	</div>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label">
			<label for="password"><?php
				echo ( $add ? __('Password') : __('New Password (leave blank if not changing)'));
				?></label>
		</div>
		<div class="col col-12 sm-col-10 px1">
			<input name="password" type="password" id="password" value="" autocomplete="off" class="input" />
			<?php echo $this->validation()->password_error; ?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label">
			<label for="password_repeat"><?php
				echo ($add ? __('Password Repeat') : __('New Password Repeat'));
				?></label>
		</div>
		<div class="col col-12 sm-col-10 px1">
			<input name="password_repeat" type="password" id="password_repeat" value="" class="input" />
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input name="enabled" type="checkbox" id="enabled" value="1" <?php echo ($user->enabled ? 'checked="checked"' : '') ?> />
				<span class="checkmark"></span>
				<?php echo __('Approved') ?>
			</label>
		</div>
	</div>
	<?php
	if ($user->level == User::PERMISSION_DEALER)
	{
		$class_hidden = '';
	}
	else
	{
		$class_hidden = ' display-none';
	}

	$this->vars['class_hidden'] = $class_hidden;
	echo new View('admin/_dealerFields', $this->vars);


	if (!$add)
	{
		echo '<hr>'
		. '<div class="clearfix form-row">
				<div class="col col-12 sm-col-2 px1 form-label">' . __('Date') . '</div>
				<div class="col col-12 sm-col-10 px1">' . Config::dateTime($user->added_at) . '</div>
			</div>'
		. '<div class="clearfix form-row">
				<div class="col col-12 sm-col-2 px1 form-label">' . __('Last login date') . '</div>
				<div class="col col-12 sm-col-10 px1">' . ( $user->logged_at ? Config::dateTime($user->logged_at) : '') . '</div>
			</div>';

		$count_buttons = User::countAdTypeButtons($user);
		if ($count_buttons)
		{
			echo '<div class="clearfix form-row">
				<div class="col col-12 sm-col-2 px1 form-label">' . __('Ads') . '</div>
				<div class="col col-12 sm-col-10 px1">'
			. $count_buttons
			. '</div></div>';
		}

		// karma 
		$karma = User::karma($user);
		if ($karma)
		{
			echo '<div class="clearfix form-row">
				<div class="col col-12 sm-col-2 px1 form-label">' . __('Karma') . '</div>
				<div class="col col-12 sm-col-10 px1">
				' . $karma . '%
				</div></div>';
		}
	}
	?>


	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit') ?>" />
			<?php echo Config::nounceInput(); ?>
			<input type="hidden" name="id" id="id" value="<?php echo $user->id ?>"  />
			<a href="<?php echo Language::get_url('admin/users/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
	<?php
	if ($user->id && $user->id != AuthUser::$user->id)
	{
		// cannot delete self 
		?>
		<p class="mt4">
			<button type="submit" name="delete_with_ads" id="delete_with_ads" class="button red">
				<?php
				$total_all = User::countAdType($user, 'total_all');
				echo __('Delete user and related ads {num}', array(
					'{num}' => '<span class="label_text small white">' . $total_all . '</span>'
				));
				?>
			</button>
		</p>
		<?php
	}
	?>
</form>

<script>
	addLoadEvent(function ()
	{
		cb.editSlug($('[data-editableslug]'));

		$('.dealer.display-none').hide().removeClass('display-none');
		$('#level').change(levelChange);
		$('.remove_dealer_logo').click(removeLogo);

		// delete user
		$('#delete').click(function ()
		{
			return confirm("<?php echo __('Do you want to delete this user?'); ?>");
		});
		$('#delete_with_ads').click(function ()
		{
			return confirm("<?php echo __('Do you want to delete this user and all related ads?'); ?>");
		});
	});
	function levelChange()
	{
		if ($('#level').val() == '<?php echo User::PERMISSION_DEALER ?>')
		{
			$('.dealer').slideDown();
		}
		else
		{
			$('.dealer').slideUp()
		}
	}

	function removeLogo()
	{
		var id = $('#id').val();
		$.post(BASE_URL + 'admin/usersRemoveLogo/', {id: id}, function (data)
		{
			if (data == 'ok')
			{
				$('img.dealer_logo,.remove_dealer_logo').remove();
			}
			else
			{
				// error
				alert(data);
			}
		});
		return false;
	}
</script>
<?php echo $this->validation()->messages() ?>

<h1 class="mt0"><?php echo __('Users') . ($title ? ' - ' . View::escape($title) : ''); ?></h1>


<?php
if (count($users))
{
	?>

	<div class="clearfix mxn1">

		<div class="col col-12 sm-col-3 p1">
			<form method="get" action="<?php echo Language::get_url('admin/users/search/'); ?>">
				<div class="input-group input-group-block">
					<input type="search" name="search" id="search" value="<?php echo View::escape($_GET['search']); ?>" class="input" aria-label="<?php echo View::escape(__('Search term')); ?>" />
					<button type="submit" name="submit" id="submit" class="button"><i class="fa fa-fw fa-search" aria-hidden="true"></i> <?php echo __('Search'); ?></button>
				</div>
			</form>
		</div>

		<div class="col col-12 sm-col-9 p1">
			<?php
			if ($user_count)
			{
				foreach ($user_count as $uc)
				{
					if ($uc->level == $mode)
					{
						$arr_uc[] = '<span class="button primary">' . User::getLevel($uc->level) . ' <sup class="muted">' . $uc->level_cnt . '</sup>' . '</span>';
					}
					else
					{
						$arr_uc[] = '<a href="' . Language::get_url('admin/users/' . $uc->level . '/') . '" class="button">' . User::getLevel($uc->level) . ' <sup class="muted">' . $uc->level_cnt . '</sup></a>';
					}
					$cnt_all += $uc->level_cnt;
				}
				// add all tab 
				if ('all' == $mode)
				{
					$cnt_all_ = '<span class="button primary">' . __('All') . ' <sup class="muted">' . $cnt_all . '</sup>' . '</span>';
				}
				else
				{
					$cnt_all_ = '<a href="' . Language::get_url('admin/users/') . '" class="button">' . __('All') . ' <sup class="muted">' . $cnt_all . '</sup></a>';
				}
				array_unshift($arr_uc, $cnt_all_);

				// add search tab 

				if ('search' == $mode)
				{
					$cnt_search = '<span class="button primary">' . __('Search') . ': ' . View::escape($_GET['search']) . ' <sup class="muted">' . $total_users . '</sup>' . '</span>';
					array_push($arr_uc, $cnt_search);
				}



				echo '<div class="button-group nowrap">' . implode(' ', $arr_uc) . '</div>';
			}
			?>
		</div>
	</div>
	<?php
}// users

if ($users)
{
	?>
	<table class="grid tblmin">
		<tr>
			<th><?php echo __('Email') . ' <span class="button small green circle">' . __('Ads') . '<span>' ?></th>
			<th><?php echo __('Name') ?></th>
			<th><?php echo __('Permission') ?></th>		
			<th><?php echo __('Date') ?></th>
			<th></th>
		</tr>
		<?php
		foreach ($users as $u)
		{
			$user_action = '';
			if ($u->pending_level == User::PERMISSION_DEALER)
			{
				if ($mode == 'upgradetodealer')
				{
					// hide row after update
					$no_hide = 'data-nohide="0"';
				}
				else
				{
					$no_hide = 'data-nohide="1"';
				}
				$user_action = ' <a href="#upgrade" class="upgrade button green" data-id="' . $u->id . '" data-action="upgrade" ' . $no_hide . '>' . __('upgrade to dealer') . '</a>';
				$user_action .= ' <a href="#upgrade_deny" class="upgrade_deny button" data-id="' . $u->id . '" data-action="upgrade_deny" ' . $no_hide . '>' . __('deny') . '</a>';
			}


			if ($u->activation)
			{
				if ($mode == 'notverified')
				{
					// hide row after update
					$no_hide = 'data-nohide="0"';
				}
				else
				{
					$no_hide = 'data-nohide="1"';
				}
				$user_action .= ' <a href="#active" class="activate button green" data-id="' . $u->id . '" data-action="activate" ' . $no_hide . '>' . ($u->activation ? __('Verify') : __('unverify')) . '</a>';
			}


			if (!$u->enabled)
			{
				if ($mode == 'notenabled')
				{
					// hide row after update
					$no_hide = 'data-nohide="0"';
				}
				else
				{
					$no_hide = 'data-nohide="1"';
				}
				$user_action .= ' <a href="#approve" class="approve button green" data-id="' . $u->id . '" data-action="approve" ' . $no_hide . '>' . ($u->enabled ? __('Unapprove') : __('Approve')) . '</a>';
			}

			if ($user_action || ($u->level != User::PERMISSION_USER))
			{
				$user_action = User::getLevel($u->level) . $user_action;
			}


			/* <i class="fa fa-file-o"></i> */
			echo '<tr class="r' . ($tr++ % 2) . '">';
			echo '<td>' . View::escape($u->email)
			. ($u->countAds ? ' <a href="' . Language::get_url('admin/items/?added_by=' . $u->id) . '" class="button green circle small" title="' . View::escape(__('{num} items', array('{num}' => $u->countAds))) . '">' . View::escape($u->countAds) . '</a>' : '')
			. '</td>';
			echo '<td data-title="' . View::escape(__('Name')) . '">' . View::escape($u->name) . '</td>';
			echo '<td>' . $user_action . '</td>';
			echo '<td>' . Config::dateTime($u->added_at) . '</td>';
			echo '<td class="right-align">'
			. '<a href="' . Language::get_url('admin/users/edit/' . $u->id . '/') . '" class="button" title="' . View::escape(__('Edit')) . '"><i class="fa fa-edit" aria-hidden="true"></i></a> '
			. '<a href="#delete" class="delete button red" u_id="' . $u->id . '" title="' . View::escape(__('Delete')) . '"><i class="fa fa-trash" aria-hidden="true"></i></a>'
			. '</td>';
			echo '</tr>';
		}
		?>
	</table>

	<?php
}
else
{
	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No users found.') . '</p></div>';
}

echo $paginator;
?>

<script>
	$(function ()
	{
		$('.activate').click(verifyUser);
		$('.approve').click(verifyUser);
		$('.upgrade').click(verifyUser);
		$('.upgrade_deny').click(verifyUser);
		$('.delete').click(deleteUser);
	});

	function verifyUser()
	{
		var $me = $(this);
		var id = $me.data('id');
		var action = $me.data('action');
		var nohide = $me.data('nohide');
		var $tr = $me.parents('tr:first');
		$.post(BASE_URL + 'admin/users/verify/', {id: id, action: action}, function (data)
		{
			if (data == 'ok')
			{
				if (!nohide)
				{
					$tr.remove();
				}
				else
				{
					if (action == 'upgrade')
					{
						var $td = $me.parents('td:first');
						$td.text('<?php echo User::getLevel(User::PERMISSION_DEALER); ?>');
					}
					else if (action == 'upgrade_deny')
					{
						var $td = $me.parents('td:first');
						$me.remove();
						$('.upgrade', $td).remove();
					}
					else
					{
						$me.remove();
					}
				}
			}
			else
			{
				alert('Error: ' + data);
			}
		});
		return false;
	}

	function deleteUser()
	{
		var $me = $(this);
		var id = $me.attr('u_id');
		var $tr = $me.parents('tr:first');

		if (confirm('Do you wnat to delete this user?'))
		{
			$.post(BASE_URL + 'admin/users/delete/', {id: id}, function (data)
			{
				if (data == 'ok')
				{
					$tr.remove();
				}
				else
				{
					alert('Error: ' + data);
				}
			});
		}
		return false;
	}
</script>
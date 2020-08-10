<?php
$_img_placeholder_src = Adpics::imgPlaceholder();
echo '<style>.list_style_admin .thumb{background-image:url(' . $_img_placeholder_src . ');}</style>';
$permission_moderator = AuthUser::hasPermission(User::PERMISSION_MODERATOR, false, false);
?>
<form method="post" action="" name="frmAds" class="clearfix">
	<ul class="list_style_admin">
		<li class="bulk_actions display-none">
			<div class="button-group">
				<a class="button primary" data-jq-dropdown="#jq-dropdown-item">
					<?php echo __('Bulk actions') ?>  
					<span class="label_text white small bulk_actions_count">0</span> 
					<i class="fa fa-chevron-down" aria-hidden="true"></i> 
				</a>
				<a class="button primary select_all" title="<?php echo View::escape(__('Select all')) ?>">					
					<i class="fa fa-check-square-o" aria-hidden="true"></i> 
				</a>
				<a class="button primary select_none" title="<?php echo View::escape(__('Select none')) ?>">					
					<i class="fa fa-square-o" aria-hidden="true"></i> 
				</a>
			</div>
			<span class="jq-dropdown-item-prepend display-none" data-hide="select">	</span>
		</li>
		<?php
		//var_dump($ads);
		foreach ($ads as $ad)
		{
			if (!$permission_moderator && !Ad::ownerCan($ad, 'know'))
			{
				// not mod can owner cannot know existence of this item
				continue;
			}

			// get picture url
			$_img_thumb = Adpics::imgThumb($ad->Adpics, '', $ad->User, 'lazy', $ad);
			if ($_img_thumb)
			{
				$_data_lazy = 'data-src="' . $_img_thumb . '"';
				$_thumb_class = 'img-yes';
				$thumb = '<a href="' . Ad::url($ad) . '" class="item_thumb lazy" ' . $_data_lazy . '></a>';
			}
			else
			{
				$_data_lazy = '';
				$_thumb_class = 'img-no';
				$thumb = '';
			}

			$link_email = Language::get_url('admin/items/?email=' . urldecode($ad->email));

			$date_title = __('Added at') . ": \t" . Config::dateTime($ad->added_at);
			if (strcmp(date("Y-m-d", $ad->added_at), date("Y-m-d", $ad->published_at)) != 0)
			{
				$date_title .= "\n" . __('Published at') . ": \t" . Config::dateTime($ad->published_at);
			}
			$date_title .= "\n" . __('Expires at') . ": \t" . Config::dateTime($ad->expireson);
			if ($ad->featured)
			{
				$date_title .= "\n" . __('Featured until') . ": \t" . Config::dateTime($ad->featured_expireson);
			}

			// title link or regular text 
			if ($permission_moderator || Ad::ownerCan($ad, 'view'))
			{
				// mod can view and edit any item
				$title = '<a class="item_title" href="' . Ad::url($ad) . '">' . View::escape(Ad::getTitle($ad)) . '</a>';
			}
			else
			{
				$title = '<span class="item_title">' . View::escape(Ad::getTitle($ad)) . '</span>';
			}
			?>
			<li class="item <?php echo $_thumb_class . ' r' . ($row++ % 2) . ($ad->listed ? '' : ' not_listed_ad'); ?>" data-item="<?php echo $ad->id; ?>">
				<?php
				echo $thumb;
				?>
				<div class="item_content">
					<?php
					echo Ad::labelExpired($ad) . ' '
					. Ad::labelFeatured($ad) . ' '
					. Ad::labelAbused($ad) . ' '
					. Ad::labelVerified($ad) . ' '
					. Ad::labelEnabled($ad) . ' '
					. Ad::labelPayment($ad) . ' ';

					echo $title;
					?>
					<div class="item_description"><?php echo Ad::snippet($ad); ?></div>
					<ul class="item_extra">
						<?php
						// format every part of meta 
						$item_meta = array();
						$item_meta[] = __('ID:{name}', array('{name}' => $ad->id));
						$item_meta[] = '<i class="fa fa-eye" aria-hidden="true"></i> ' . number_format($ad->hits);

						$cat_name = Category::getFullNameById($ad->category_id);
						$loc_name = Location::getFullNameById($ad->location_id);
						if (strlen($cat_name))
						{
							$item_meta[] = '<i class="fa fa-sitemap" aria-hidden="true"></i> ' . $cat_name;
						}

						if (strlen($loc_name))
						{
							$item_meta[] = '<i class="fa fa-map-marker" aria-hidden="true"></i> ' . $loc_name;
						}


						if ($permission_moderator)
						{
							// hide some details from users
							$item_meta[] = '<a href="' . $link_email . '">' . $ad->email . '</a>';
							$item_meta[] = $ad->ip;
						}

						$item_meta[] = '<abbr title="' . $date_title . '">'
								. '<i class="fa fa-clock-o" aria-hidden="true"></i> '
								. Config::date($ad->published_at)
								. '</abbr>';





						echo '<li>' . implode('</li><li>', $item_meta) . '</li>';
						?>
					</ul>
				</div>


				<?php
				// hide not related menu options 
				$arr_hide_menu = array();
				if (Ad::isVerified($ad))
				{
					$arr_hide_menu['verify'] = true;
				}

				if (Ad::isApproved($ad))
				{
					// it is appreved and not paused
					$arr_hide_menu['approve'] = true;
					$arr_hide_menu['unpause'] = true;
				}
				elseif (Ad::isPaused($ad))
				{
					// you cannot pause, approve, unapprove
					$arr_hide_menu['pause'] = true;
					$arr_hide_menu['approve'] = true;
					$arr_hide_menu['unapprove'] = true;
				}
				else
				{
					// it is not approved, not paused, you cannot pause it
					$arr_hide_menu['unapprove'] = true;
					$arr_hide_menu['unpause'] = true;
					$arr_hide_menu['pause'] = true;
					$arr_hide_menu['completed'] = true;

					if (!Ad::isStatus($ad, Ad::STATUS_COMPLETED))
					{
						$arr_hide_menu['extend'] = true;
						$arr_hide_menu['renew_item'] = true;
					}
				}

				if (!Ad::isStatus($ad, Ad::STATUS_TRASH))
				{
					// delete only itms in trash 
					$arr_hide_menu['del'] = true;
				}

				if (Ad::isFeatured($ad))
				{
					$arr_hide_menu['make_featured'] = true;
				}
				else
				{
					$arr_hide_menu['disable_featured'] = true;
				}

				if ($permission_moderator || Ad::ownerCan($ad, 'edit'))
				{
					// can edit this item 
					$menu_edit = '<li><a href="' . Ad::urlEdit($ad, $returl) . '">'
							. '<i class="fa fa-fw fa-edit" aria-hidden="true"></i> ' . __('Edit') . '</a></li>';
					// mod or user with edit rights can have dropdown menu 
					?>
					<div class="controls">
						<label class="input-checkbox display-none">
							<input type="checkbox" name="ad[]" value="<?php echo $ad->id; ?>">
							<span class="checkmark"></span>
						</label>
						<a class="button link" title="<?php echo __('Menu'); ?>" data-jq-dropdown="#jq-dropdown-item"><i class="fa fa-ellipsis-v" aria-hidden="true"></i></a>
					</div>
					<ul class="jq-dropdown-item-append display-none" data-hide="<?php echo implode(',', array_keys($arr_hide_menu)); ?>">
						<li class="jq-dropdown-divider"></li>
						<?php
						echo $menu_edit;
						if ($permission_moderator && $ad->added_by > 0)
						{
							?>
							<li><a href="<?php echo Language::get_url('admin/items/?added_by=' . $ad->added_by); ?>"><?php echo __('User listings') ?></a></li>
							<li><a href="<?php echo Language::get_url('admin/users/edit/' . $ad->added_by . '/'); ?>"><?php echo __('User profile') ?></a></li>
							<?php
						}
						?>

					</ul>
					<?php
				}
				?>
			</li>
			<?php
		}
		?>

	</ul>

	<div id="jq-dropdown-item" class="jq-dropdown">
		<ul class="jq-dropdown-menu">
			<li><a data-v="select"><i class="fa fa-square-o" aria-hidden="true"></i> <?php echo __('Select') ?></a></li>
			<?php
			$extend_ad_days = Config::getExtendAdDays();
			$echo_extend = '';
			if (count($extend_ad_days) > 3)
			{
				// use submenu
				$echo_extend .= '<li><a data-n="extend"><i class="fa fa-fw fa-calendar-plus-o" aria-hidden="true"></i> ' . __('Extend') . '</a><ul>';
				foreach ($extend_ad_days as $day)
				{
					$echo_extend .= '<li><a data-v="extend_' . $day . '">' . __('Extend {num} days', array('{num}' => $day)) . '</a></li>';
				}
				$echo_extend .= '</ul></li>';
			}
			else
			{
				// show regular menu
				foreach ($extend_ad_days as $day)
				{
					$echo_extend .= '<li><a data-v="extend_' . $day . '" data-n="extend"><i class="fa fa-fw fa-calendar-plus-o" aria-hidden="true"></i> ' . __('Extend {num} days', array('{num}' => $day)) . '</a></li>';
				}
			}

			$echo_renew = '';
			if (Config::option('renew_ad') || $permission_moderator)
			{
				// if feature enabled or if moderator then show renew option
				$echo_renew = '<li><a data-v="renew_item"><i class="fa fa-fw fa-arrow-up" aria-hidden="true"></i> ' . __('Renew') . '</a></li>';
			}



			if ($permission_moderator)
			{
				// moderator menu
				?>
				<li><a data-v="verify"><?php echo __('Verify') ?></a></li>

				<li><a data-v="approve"><?php echo __('Approve') ?></a></li>

				<li><a><?php echo __('Status') ?></a>
					<ul>
						<li><a data-v="unapprove"><?php echo __('Unapprove'); ?></a></li>
						<li><a data-v="pause"><?php echo __('Pause'); ?></a></li>
						<li><a data-v="unpause"><?php echo __('Unpause'); ?></a></li>

						<li><a data-v="incomplete"><?php echo Ad::statusName(Ad::STATUS_INCOMPLETE); ?></a></li>
						<li><a data-v="completed"><?php echo Ad::statusName(Ad::STATUS_COMPLETED); ?></a></li>
						<li><a data-v="ban"><?php echo Ad::statusName(Ad::STATUS_BANNED); ?></a></li>
						<li><a data-v="duplicate"><?php echo Ad::statusName(Ad::STATUS_DUPLICATE); ?></a></li>
						<li><a data-v="trash"><?php echo Ad::statusName(Ad::STATUS_TRASH); ?></a></li>
					</ul>
				</li>


				<li><a data-v="del" data-confirm="<?php echo View::escape(__('Do you want to delete selected ads?')); ?>"><i class="fa fa-fw fa-trash color-danger" aria-hidden="true"></i> <?php echo __('Delete') ?></a></li>
				<li class="jq-dropdown-divider"></li>
				<li><a data-v="mark_as_paid"><?php echo __('Mark as paid'); ?></a></li>
				<li><a data-v="make_featured"><?php echo __('Make featured'); ?></a></li>
				<li><a data-v="disable_featured"><?php echo __('Disable featured'); ?></a></li>
				<li><a data-v="resetabuse"><?php echo __('Reset abuse reports'); ?></a></li>
				<li><a data-v="ip_block"><?php echo __('Block IPs'); ?></a></li>
				<li class="jq-dropdown-divider"></li>
				<?php
				echo $echo_extend . $echo_renew;
			}
			else
			{
				// regular user menu
				?>
				<li><a data-v="verify"><?php echo __('Verify') ?></a></li>
				<li><a data-v="completed"><?php echo Ad::statusName(Ad::STATUS_COMPLETED); ?></a></li>
				<li><a data-v="pause"><?php echo __('Pause'); ?></a></li>
				<li><a data-v="unpause"><?php echo __('Unpause'); ?></a></li>				
				<?php
				echo $echo_extend . $echo_renew;
			}
			?>
		</ul>
	</div>
	<input type="hidden" id="bulk_actions" name="bulk_actions"/>
	<?php echo Config::nounceInput(); ?>

</form>

<script language="javascript">
	addLoadEvent(function ()
	{
		cb.setupItemDropdown();
	});
</script>
<?php
echo $paginator;




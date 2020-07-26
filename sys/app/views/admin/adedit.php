<?php echo $this->validation()->messages(); ?>
<h1 class="mt0"><?php
	echo __('Edit ad');

	if ($ad->id)
	{
		echo ' <a href="' . Ad::url($ad) . '" class="button small"><i class="fa fa-eye" aria-hidden="true"></i> ' . __('View') . '</a>';
	}
	?></h1>

<form action="" method="post" enctype="multipart/form-data">
	<?php
	// image upload fields
	echo Adpics::renderUploadFields(array(
		'ad'		 => $ad,
		'pattern'	 => '<div class="clearfix form-row">
								<div class="col col-12 sm-col-2 px1 form-label">{TITLE}</div>
								<div class="col col-12 sm-col-10 px1">{FIELDS}{DESCRIPTION}</div>
							</div>'
	));
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="title"><?php echo __('Title') ?></label></div>
		<div class="col col-12 sm-col-10 px1"><input name="title" type="text" id="title" size="20" maxlength="100" class="input input-long" value="<?php echo View::escape($ad->title) ?>" /></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="description"><?php echo __('Description') . ' ' . Config::markerRequired(); ?></label></div>
		<div class="col col-12 sm-col-10 px1"><textarea name="description" rows="10" id="description" required><?php echo View::escape($ad->description) ?></textarea>
			<?php echo Validation::getInstance()->description_error; ?></div>
	</div>
	<?php
	if (Location::hasValidPostingLocations())
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="location_id"><?php echo __('Location') ?></label></div>
			<div class="col col-12 sm-col-10 px1"><?php
				// check if can change location and category 
				if (Ad::canChangeLocationCategory($ad))
				{
					//echo Location::selectBox($ad->location_id, 'location_id', Location::STATUS_ENABLED, true, __('Select location'), 0, true);
					//echo Location::selectBoxChain($ad->location_id, 'location_id', Location::STATUS_ENABLED, __('Select location'));

					echo '<input name="location_id" value="' . $ad->location_id . '" 
							data-src="' . Config::urlJson() . '"
							data-key="location"
							data-selectalt="1"
							data-rootname="' . View::escape(__('Select location')) . '"
							data-currentname="' . View::escape(Location::getNameById($ad->location_id)) . '"
							class="display-none"							
							required
							>';
				}
				else
				{
					// display category name 
					echo Location::getFullName($ad->Location);
					echo '<input type="hidden" name="location_id" value="' . View::escape($ad->location_id) . '" />';
				}
				?></div>
		</div>

		<?php
	}// location 

	if (Category::hasValidPostingCategories())
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="category_id"><?php echo __('Category') ?></label></div>
			<div class="col col-12 sm-col-10 px1"><?php
				// check if can change location and category 
				if (Ad::canChangeLocationCategory($ad))
				{
					//echo Category::selectBox($ad->category_id, 'category_id', Category::STATUS_ENABLED, true, __('Select category'), 0, true);
					//echo Category::selectBoxChain($ad->category_id, 'category_id', Category::STATUS_ENABLED, __('Select category'));


					echo '<input name="category_id" value="' . View::escape($ad->category_id) . '" 
							data-src="' . Config::urlJson() . '"
							data-key="category"
							data-selectalt="1"
							data-rootname="' . View::escape(__('Select category')) . '"
							data-currentname="' . View::escape(Category::getNameById($ad->category_id)) . '"
							class="display-none"
							required
							>';
				}
				else
				{
					// display category name 
					echo Category::getFullName($ad->Category);
					echo '<input type="hidden" name="category_id" value="' . View::escape($ad->category_id) . '" />';
				}
				?></div>
		</div>

		<?php
	}// categories 
	// add custom fields as hidden 
	// then use template and js to show properly styled dynamic custom fields 
	$hidden_cf = '';
	foreach ($catfields as $cf)
	{
		// store error messages to display if needed
		$name = 'cf[' . $cf->adfield_id . ']';
		$error = Validation::getInstance()->{$name . '_error'};
		if ($error)
		{
			$hidden_cf .= '<span id="' . $name . '_error" class="hidden_error">' . $error . '</span>';
		}

		// custom field ad hidden value in case js not loaded but form submitted with updates 
		$hidden_cf .= AdField::htmlField($cf, $ad->AdFieldRelation[$cf->AdField->id]->val, 'hidden');

		// remove from posted values and append left post values at the end as hidden for use in update id needed
		unset($_POST['cf'][$cf->adfield_id]);
	}
	?>
	<hr> 
	<!-- DYNAMIC CONTENT -->
	<div id="wrap_cf">
		<?php echo '<div class="diplay-none">' . $hidden_cf . '</div>'; ?>
	</div>
	<script>
		// define dynamic template as var 
		// load custom fields json
		// then call rendering function stored in js 
		addLoadEvent(function ()
		{
			cb.cf.init({
				datasrc: '<?php echo View::escape(Config::urlJson()); ?>',
				template: '<div class="clearfix form-row">'
						+ '<div class="col col-12 sm-col-2 px1 form-label">${label}</div>'
						+ '<div class="col col-12 sm-col-10 px1">${input} ${help}</div>'
						+ '</div>',
				target: '#wrap_cf',
				loc: 'input[name="location_id"]',
				cat: 'input[name="category_id"]'
			});
		});

	</script>
	<!-- DYNAMIC CONTENT ENDs -->
	<?php
	/* foreach ($catfields as $cf)
	  {
	  // list custom fields
	  echo CategoryFieldGroup::htmlGroupOpen($cf->CategoryFieldGroup, '<div class="clearfix form-row"><div class="col col-12px1">{name}</div></div>', '');
	  $name = 'cf[' . $cf->adfield_id . ']';
	  echo '<div class="clearfix form-row">
	  <div class="col col-12 sm-col-2 px1 form-label"><label for="' . $name . '">' . View::escape(AdField::getName($cf->AdField)) . '</label></div>
	  <div class="col col-12 sm-col-10 px1">'
	  . AdField::htmlField($cf, $ad->AdFieldRelation[$cf->AdField->id]->val)
	  . Validation::getInstance()->{$name . '_error'}
	  . '</div>
	  </div>';

	  unset($_POST['cf'][$cf->adfield_id]);
	  }
	  echo CategoryFieldGroup::htmlGroupClose();
	 */
// custom fields hidden values 
	?>
	<hr>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="phone"><?php echo __('Phone') . ' ' . Config::markerRequired(Config::option('required_phone')); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			$phone_regex = Config::option('phone_regex');
			$phone_regex_attr = $phone_regex ? ' pattern="' . View::escape($phone_regex) . '"' : '';
			$phone_required_attr = Config::option('required_phone') ? ' required ' : '';
			$_ml_phone_hint = Config::option('_ml_phone_hint');
			$_ml_phone_hint_attr = $_ml_phone_hint ? ' title="' . View::escape($_ml_phone_hint) . '"' : '';
			$_ml_phone_hint_info = $_ml_phone_hint ? '<em>' . View::escape($_ml_phone_hint) . '</em>' : '';
			?>
			<input class="input" name="phone" id="phone" type="tel" size="20" 
				   value="<?php echo View::escape($ad->phone) ?>" <?php echo $phone_required_attr . $phone_regex_attr . $_ml_phone_hint_attr ?>>
				   <?php echo Validation::getInstance()->phone_error; ?>
				   <?php echo $_ml_phone_hint_info ?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="email"><?php echo __('Email') . ' ' . Config::markerRequired(); ?></label></div>
		<div class="col col-12 sm-col-10 px1"><input class="input" name="email" id="email" type="email" size="20" value="<?php echo View::escape($ad->email) ?>" required>
			<?php echo Validation::getInstance()->email_error; ?>
			<!-- contact options -->
			<?php
			if (count($contact_options) > 1)
			{
				// more than one option available then display them
				foreach ($contact_options as $k => $v)
				{
					echo '<p><label class="input-radio">'
					. '<input name="showemail" id="' . $k . '" type="radio" 
									value="' . $v['value'] . '" ' . ($ad->showemail == $v['value'] ? 'checked="checked"' : '') . ' />'
					. '<span class="checkmark"></span> '
					. View::escape($v['label']) . '</label>'
					. ($v['message'] ? ' <em class="msg-error-line">' . View::escape($v['message']) . '</em>' : '')
					. '</p>';
				}
			}
			else
			{
				// only one option available then no need to display input form
				// display only info message 
				foreach ($contact_options as $k => $v)
				{
					echo '<em>' . View::escape($v['message_selected']) . '</em> '
					. ($v['message'] ? '<em class="msg-error-line">' . View::escape($v['message']) . '</em>' : '');
				}
			}
			?></div>
	</div>


	<?php
// other contact 
	if (!Config::option('hide_othercontactok'))
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"></div>
			<div class="col col-12 sm-col-10 px1">
				<label class="input-checkbox">
					<input type="checkbox" name="othercontactok" id="othercontactok" value="1" <?php echo ($ad->othercontactok ? 'checked="checked"' : '') ?> >
					<span class="checkmark"></span>
					<?php echo __('People with other commercial requests can contact me.') ?></label>
			</div>
		</div>
		<?php
	}
	?>

	<hr>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Ad ID') ?></div>
		<div class="col col-12 sm-col-10 px1"><a href="<?php echo Language::get_url('admin/items/?search=' . $ad->id); ?>"><?php echo View::escape($ad->id) ?></a> <?php
			echo Ad::labelAbused($ad)
			. Ad::labelEnabled($ad)
			. Ad::labelExpired($ad)
			. Ad::labelFeatured($ad)
			. Ad::labelPayment($ad, true)
			. Ad::labelVerified($ad);
			?></div>
	</div>
	<?php
	if (AuthUser::hasPermission(User::PERMISSION_MODERATOR))
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Added by') ?></div>
			<div class="col col-12 sm-col-10 px1"><?php
				if ($ad->User)
				{
					$karma = User::karma($ad->User);
					$karma_badge = ' <span class="label_text small ' . ($karma > 50 ? '' : 'red') . '">' . $karma . '%</span>';

					echo '<a href="' . Language::get_url('admin/users/edit/' . $ad->User->id . '/') . '" class="button link small">' . View::escape($ad->User->email) . $karma_badge . '</a>'
					. ' <a href="' . Language::get_url('admin/items/?added_by=' . $ad->User->id) . '" class="button small">' . __('{num} ads', array('{num}' => $ad->User->countAds)) . '</a>';
				}
				?></div>
		</div>
		<?php
	}
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Added at') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo Config::dateTime($ad->added_at); ?></div>
	</div>
	<?php
	if (strcmp(date("Y-m-d", $ad->added_at), date("Y-m-d", $ad->published_at)) != 0)
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Published at'); ?></div>
			<div class="col col-12 sm-col-10 px1"><?php echo Config::date($ad->published_at); ?></div>
		</div>
		<?php
	}
	?>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Expires at'); ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo Config::date($ad->expireson); ?></div>
	</div>
	<?php
	if ($ad->featured)
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Featured until'); ?></div>
			<div class="col col-12 sm-col-10 px1"><?php echo Config::date($ad->featured_expireson); ?></div>
		</div>
		<?php
	}
	?>

	<?php
	if ($ad->updated_at != $ad->added_at)
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Last updated at') ?></div>
			<div class="col col-12 sm-col-10 px1"><?php echo Config::dateTime($ad->updated_at) ?></div>
		</div>
		<?php
	}
	?>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"> </div>
		<div class="col col-12 sm-col-10 px1"><?php
// append removed custom fields
// we need this if user decides to switch back to old category and location old values will be saved
			if ($_POST['cf'])
			{
				foreach ($_POST['cf'] as $cf_k => $cf_old)
				{
					if (is_array($cf_old))
					{
						foreach ($cf_old as $cf_old_key => $cf_old_val)
						{
							echo '<input type="hidden" name="cf[' . $cf_k . '][' . $cf_old_key . ']" value="' . View::escape($cf_old_val) . '" />';
						}
					}
					else
					{
						echo '<input type="hidden" name="cf[' . $cf_k . ']" value="' . View::escape($cf_old) . '" />';
					}
				}
			}
			?>
			<?php echo Config::nounceInput(); ?>
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit') ?>" />	
			<a href="<?php echo Ad::urlReturn() ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
	<?php
	// only active and paused items can be completed
	if ($ad->enabled === Ad::STATUS_ENABLED || $ad->enabled === Ad::STATUS_PAUSED)
	{
		?>
		<div class="clearfix mxn1 my2 mt4">
			<div class="col col-12 sm-col-2 px1 form-label"> </div>
			<div class="col col-12 sm-col-10 px1"><input type="submit" name="completed" id="completed" class="button red" value="<?php echo __('Completed') ?>" /> <em class="msg-error-line"><?php echo __('Mark this item as completed and remove from listings.') ?></em></div>
		</div>
		<?php
	}
	?>
</form>
<script>
	addLoadEvent(function ()
	{
		$('#completed').click(function ()
		{
			return confirm("<?php echo __('Do you want to mark this item as completed?'); ?>");
		});
	});
</script>


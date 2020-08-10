<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Confirm delete custom field') ?></h1>
<form action="" method="post">



	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label><?php echo __('Name') ?></label></div>
		<div class="col col-12 sm-col-10 px1"><?php echo View::escape(AdField::getName($adfield)) ?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label><?php echo __('Type') ?></label></div>
		<div class="col col-12 sm-col-10 px1"><?php echo View::escape($adfield->type) ?></div>
	</div>
	<?php
	$val = AdField::formatPredefinedValue($adfield);
	if (strlen($val))
	{
		?>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label><?php echo __('Value') ?></label></div>
			<div class="col col-12 sm-col-10 px1"><?php echo $val ?></div>
		</div>
		<?php
	}

	$msg_warning = '';
	if ($adfield->countRelatedAds)
	{
		$msg_warning .= '<li>' . __('This custom field will be removed from {num} ads that has values related to them.', array(
					'{num}' => $adfield->countRelatedAds
				)) . '</li>';
	}



	if ($adfield->CategoryFieldRelation)
	{
		foreach ($adfield->CategoryFieldRelation as $cr)
		{
			$echo_loc_cat .= '<li>'
					. Location::getFullName($cr->Location, __('All locations')) . ' | '
					. Category::getFullName($cr->Category, __('All categories'))
					. '</li>';
		}

		$msg_warning .= '<li>'
				. __('This custom field will be removed from these location and category relations.')
				. '<ul>' . $echo_loc_cat . '</ul>'
				. '</li>';
	}

	if (strlen($msg_warning))
	{
		echo '<div class="msg-error"><ul>' . $msg_warning . '</ul></div>';
	}
	?>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input type="checkbox" name="confirm_delete" id="confirm_delete" value="1" required />
				<span class="checkmark"></span>
				<?php echo __('Yes, delete this custom field.') ?>
			</label>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1"><input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<input type="hidden" name="id" id="id" value="<?php echo $adfield->id ?>"  />
			<a href="<?php echo Language::get_url('admin/itemfield/') ?>" class="button link"><?php echo __('Cancel') ?></a></div>
	</div>
</form>
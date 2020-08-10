<?php $this->validation()->messages() ?>
<h2 class="mt0"><?php echo __('Promote') ?></h2>
<p><?php echo __('Payment processing via paypal.'); ?></p>
<form method="post">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Title') ?></div>
		<div class="col col-12 sm-col-10 px1">
			<a class="title" href="<?php echo Ad::url($ad) ?>" target="_blank"><?php echo View::escape(Ad::getTitle($ad)); ?></a>
			<div class="preview"><?php echo Ad::snippet($ad); ?></div>
			<div class="extra"><?php echo __('ID') . ':' . $ad->id; ?></div>
		</div>
	</div>
	<?php
	if ($ad->Category)
	{
		echo '<div class="clearfix form-row">
				<div class="col col-12 sm-col-2 px1 form-label">' . __('Category') . '</div>
				<div class="col col-12 sm-col-10 px1">' . Category::getFullNameById($ad->category_id) . '</div>
			</div>';
	}
	if ($ad->Location)
	{
		echo '<div class="clearfix form-row">
				<div class="col col-12 sm-col-2 px1 form-label">' . __('Location') . '</div>
				<div class="col col-12 sm-col-10 px1">' . Location::getFullNameById($ad->location_id) . '</div>
			</div>';
	}

	if ($ad->requires_posting_payment)
	{
		// display payment requirement 
		echo '<div class="clearfix form-row">
				<div class="col col-12 sm-col-2 px1 form-label">' . __('Posting price') . '</div>
				<div class="col col-12 sm-col-10 px1">' . Payment::formatAmount($ad->PaymentPrice->price_post) . '</div>
			</div>';
	}

	if (!$ad->featured && $ad->PaymentPrice->price_featured > 0)
	{
		echo '<div class="clearfix form-row">
				<div class="col col-12 sm-col-2 px1 form-label">' . __('Featured listing') . '</div>
				<div class="col col-12 sm-col-10 px1">
					<label class="input-checkbox">
					<input type="checkbox" name="price_featured_requested" 
						id="price_featured_requested" value="1" checked="checked" /> 
					<span class="checkmark"></span>'
		. __('Enable featured listing ({num} days listing {price})', array(
			'{num}'		 => intval(Config::option('featured_days')),
			'{price}'	 => Payment::formatAmount($ad->PaymentPrice->price_featured)
		))
		. '</label>
				</div>
			</div>';
	}
	?>

	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Continue') ?>" />				
			<a href="<?php echo Language::get_url('admin/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>
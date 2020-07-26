<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Confirm delete price') ?></h1>
<form action="" method="post">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Location') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo Location::getFullName($payment_price->Location, __('All locations')) ?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Category') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo Category::getFullName($payment_price->Category, __('All categories')) ?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Featured price') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo Payment::formatAmount($payment_price->price_featured) ?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><?php echo __('Posting price') ?></div>
		<div class="col col-12 sm-col-10 px1"><?php echo Payment::formatAmount($payment_price->price_post) ?></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<label class="input-checkbox">
				<input type="checkbox" name="confirm_delete" id="confirm_delete" value="1" required />  
				<span class="checkmark"></span>
				<?php echo __('Yes, delete this price.') ?>
			</label>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit'); ?>" />
			<a href="<?php echo Language::get_url('admin/paymentPrice/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>
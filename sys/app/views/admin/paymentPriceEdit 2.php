<?php $this->validation()->messages(); ?>
<h1 class="mt0"><?php echo $title ?></h1>
<form method="post">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="location_id"><?php echo __('Location') ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			echo '<input name="location_id" value="' . View::escape($payment_price->location_id) . '" 
					data-src="' . Config::urlJson(Location::STATUS_ALL) . '"
					data-key="location"
					data-selectalt="1"
					data-rootname="' . View::escape(__('All locations')) . '"
					data-currentname="' . View::escape(Location::getNameById($payment_price->location_id)) . '"
					data-allpattern="' . View::escape(__('All <b>{name}</b>')) . '"
					data-allallow="1"
					class="display-none"
					>';
			?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="category_id"><?php echo __('Category') ?>:</label></div>
		<div class="col col-12 sm-col-10 px1">
			<?php
			echo '<input name="category_id" value="' . View::escape($payment_price->category_id) . '" 
					data-src="' . Config::urlJson(Location::STATUS_ALL) . '"
					data-key="category"
					data-selectalt="1"
					data-rootname="' . View::escape(__('All categories')) . '"
					data-currentname="' . View::escape(Category::getNameById($payment_price->category_id)) . '"
					data-allpattern="' . View::escape(__('All <b>{name}</b>')) . '"
					data-allallow="1"
					class="display-none"
					>';
			?>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="price_featured"><?php echo __('Featured price') ?>:</label></div>
		<div class="col col-12 sm-col-10 px1"><input type="text" name="price_featured" id="price_featured" value="<?php echo View::escape($payment_price->price_featured) ?>" /></div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="price_post"><?php echo __('Posting price') ?>:</label></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="text" name="price_post" id="price_post" value="<?php echo View::escape($payment_price->price_post) ?>" />
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Submit') ?>" />
			<a href="<?php echo Language::get_url('admin/paymentPrice/') ?>" class="button link"><?php echo __('Cancel') ?></a>
		</div>
	</div>
</form>
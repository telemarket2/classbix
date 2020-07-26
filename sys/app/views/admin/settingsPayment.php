<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Payment') ?>
	<a href="<?php echo Language::get_url('admin/paymentPrice/') ?>" class="button white"><?php echo __('Prices') ?></a>
</h1>
<form action="" method="post" id="settings_form">
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"></div>
		<div class="col col-12 sm-col-10 px1">
			<p>
				<label class="input-checkbox">
					<input name="enable_payment" type="checkbox" id="enable_payment" value="1" 
						   <?php echo (Config::option('enable_payment') ? 'checked="checked"' : ''); ?>/>
					<span class="checkmark"></span>
					<?php echo __('Enable payment'); ?>
				</label>
			</p>
			<p>
				<label class="input-checkbox">
					<input name="paypal_sandbox" type="checkbox" id="paypal_sandbox" value="1" 
						   <?php echo (Config::option('paypal_sandbox') ? 'checked="checked"' : ''); ?>/>
					<span class="checkmark"></span>
					<?php echo __('Paypal sandbox'); ?>
				</label>
			</p>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="paypal_email"><?php echo __('Paypal email'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="paypal_email" type="text" id="paypal_email" value="<?php echo View::escape(Config::option('paypal_email')); ?>" />
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="paypal_currency"><?php echo __('Paypal currency'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<select name="paypal_currency" id="paypal_currency" class="short">
				<?php
				$paypal_currency = Payment::getCurrency();
				foreach ($currencies as $currency)
				{
					echo '<option value="' . View::escape($currency) . '"'
					. ($currency == $paypal_currency ? ' selected="selected"' : '') . '>'
					. View::escape($currency) . '</option>';
				}
				?>
			</select>
		</div>
	</div>
	<div class="clearfix form-row">
		<div class="col col-12 sm-col-2 px1 form-label"><label for="featured_days"><?php echo __('Featured days'); ?></label></div>
		<div class="col col-12 sm-col-10 px1">
			<input name="featured_days" type="text" id="featured_days" value="<?php echo View::escape(Config::option('featured_days')); ?>" />
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
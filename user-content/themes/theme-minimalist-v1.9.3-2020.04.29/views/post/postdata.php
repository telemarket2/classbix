<h1><?php echo __('Post ad') ?></h1>
<?php
if(!AuthUser::isLoggedIn(false))
{
	echo '<p>' . __('If you have an account please <a href="{url}">log in</a>.', array('{url}' => Language::get_url('post/login/?login=1'))) . '</p>';
}

$change_location_category = ' <a href="' . Ad::urlPost($location, $category, true) . '" class="button small" rel="nofollow">' . __('change') . '</a>';
?>
<form action="" method="post" enctype="multipart/form-data">
	<table class="grid" border="0" cellspacing="0" cellpadding="0">
		<?php
		if($location)
		{
			echo '<tr class="r' . ($tr++ % 2) . '">
					<td><label>' . __('Location') . ':</label></td>
					<td>' . Location::getFullName($location) . ' ' . $change_location_category . '</td>
				</tr>';
		}
		if($category)
		{
			echo '<tr class="r' . ($tr++ % 2) . '">
					<td><label>' . __('Category') . ':</label></td>
					<td>' . Category::getFullName($category) . ' ' . $change_location_category . '</td>
				</tr>';
		}
		?>
		<tr class="<?php echo 'r' . ($tr++ % 2); ?>">
			<td><label for="title"><?php echo __('Title') ?>:</label></td>
			<td><input name="title" type="text" id="title" size="20" maxlength="100" class="long" value="<?php echo View::escape($ad->title) ?>" /></td>
		</tr>
		<tr class="<?php echo 'r' . ($tr++ % 2); ?>">
			<td colspan="2"><label for="description"><?php echo __('Description') ?>: <?php echo Config::markerRequired(); ?></label></br>
				<textarea name="description" rows="10" id="description"><?php echo View::escape($ad->description) ?></textarea>
				<?php echo Validation::getInstance()->description_error; ?>
			</td>
		</tr>
		<?php
		// list custom fields
		foreach($catfields as $cf)
		{
			echo CategoryFieldGroup::htmlGroupOpen($cf->CategoryFieldGroup, '<tr><td colspan="2"><h4>{name}</h4></td></tr>', '');

			$name = 'cf[' . $cf->adfield_id . ']';
			echo '<tr class="r' . ($tr++ % 2) . '">
						<td><label for="' . $name . '">' . View::escape(AdField::getName($cf->AdField)) . ':</label></td>
						<td>'
			. AdField::htmlField($cf, $ad->AdFieldRelation[$cf->AdField->id]->val)
			. Validation::getInstance()->{$name . '_error'}
			. '</td>
					</tr>';
		}
		echo CategoryFieldGroup::htmlGroupClose();
		?>
		<tr class="<?php echo 'r' . ($tr++ % 2); ?>">
			<td><label for="phone"><?php echo __('Phone') ?>: <?php echo Config::markerRequired(Config::option('required_phone')); ?></label></td>
			<td><input name="phone" id="phone" type="text" size="20" value="<?php echo View::escape($ad->phone) ?>">
				<?php echo Validation::getInstance()->phone_error; ?></td>
		</tr>
		<tr class="<?php echo 'r' . ($tr++ % 2); ?>">
			<td><label for="email"><?php echo __('Email') ?>: <?php echo Config::markerRequired(); ?></label></td>
			<td>
				<input name="email" id="email" type="text" size="20" value="<?php echo View::escape($ad->email) ?>">
				<?php echo Validation::getInstance()->email_error; ?>
				<!-- contact options -->
				<?php
				if(count($contact_options) > 1)
				{
					// more than one option available then display them
					foreach($contact_options as $k => $v)
					{
						echo '<p><input name="showemail" id="' . $k . '" type="radio" 
								value="' . $v['value'] . '" ' . ($ad->showemail == $v['value'] ? 'checked="checked"' : '') . ' /> 
								<label for="' . $k . '">' . View::escape($v['label']) . '</label>'
						. ($v['message'] ? '<em class="msg-error-line">' . View::escape($v['message']) . '</em>' : '')
						. '</p>';
					}
				}
				else
				{
					// only one option available then no need to display input form
					// display only info message 
					foreach($contact_options as $k => $v)
					{
						echo '<em>' . View::escape($v['message_selected']) . '</em> '
						. ($v['message'] ? '<em class="msg-error-line">' . View::escape($v['message']) . '</em>' : '');
					}
				}
				?>
			</td>
		</tr>
		<?php
		
		// image upload fields
		echo Adpics::renderUploadFields(array('ad' => $ad));


		if($ad->PaymentPrice)
		{
			$display_payment_info = false;
			if($ad->PaymentPrice->price_post > 0)
			{
				echo '<tr class="r' . ($tr % 2) . '">
						<td><label for="price_post">' . __('Posting price') . ':</label></td>
						<td>' . Payment::formatAmount($ad->PaymentPrice->price_post) . '</td>
					</tr>';
				$display_payment_info = true;
			}
			if($ad->PaymentPrice->price_featured > 0)
			{
				echo '<tr class="r' . ($tr % 2) . '">
						<td><label>' . __('Featured listing') . ':</label></td>
						<td><input type="checkbox" name="price_featured_requested" id="price_featured_requested" value="1" /> 
						<label for="price_featured_requested">' . __('Enable featured listing ({num} days listing {price})', array(
					'{num}' => intval(Config::option('featured_days')),
					'{price}' => Payment::formatAmount($ad->PaymentPrice->price_featured)
				)) . '</label></td>
					</tr>';
				$display_payment_info = true;
			}
			if($display_payment_info)
			{
				echo '<tr class="r' . ($tr % 2) . '"><td colspan="2">' . __('Payment processing via paypal.') . ' ' . Page::pageLink('page_id_payment') . '</td></tr>';
			}
			$tr++;
		}
		
		// display captcha
		echo $captcha_render = Captcha::render('<tr class="r' . ($tr++ % 2) . '">
									<td><label for="{name}">{label}: {marker}</label></td>
									<td>{input}</td>
								</tr>');
		if(!$captcha_render)
		{
			$tr--;
		}


		if(!Config::option('hide_othercontactok'))
		{
			?>
			<tr class="<?php echo 'r' . ($tr++ % 2); ?>">
				<td colspan="2">
					<input type="checkbox" name="othercontactok" id="othercontactok" value="1" > 
					<label for="othercontactok"><?php echo __('People with other commercial requests can contact me.') ?></label>
				</td>
			</tr>
			<?php
		}

		if(!Config::option('hide_agree'))
		{
			?>
			<tr class="<?php echo 'r' . ($tr++ % 2); ?>">
				<td colspan="2">
					<input type="checkbox" name="agree" id="agree" value="1"> 
					<label for="agree"><?php echo __('I have read and comply with {terms} for posting this ad.', array('{terms}' => Page::pageLink('page_id_terms'))) ?></label>
				</td>
			</tr>
			<?php
		}
		?>
	</table>

	<input name="category_id" type="hidden" id="category_id" value="<?php echo $ad->category_id ?>" />
	<input name="location_id" type="hidden" id="location_id" value="<?php echo $ad->location_id ?>" />
	<input name="step" type="hidden" id="step" value="2" />
	<p><input type="submit" name="submit" id="submit" value="<?php echo __('Submit') ?>" /></p>
		<?php echo Config::nounceInput(); ?>
</form>


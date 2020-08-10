<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Paid options') ?> 
	<a href="<?php echo Language::get_url('admin/paymentPriceEdit/') ?>" class="button blue"><?php echo __('Add price') ?></a>
	<a href="<?php echo Language::get_url('admin/settingsPayment/') ?>" class="button white"><?php echo __('Settings') ?></a>
</h1>

<?php
echo '<p>' . __('Define price for featured ads and paid ads by category and location. Prices apply to child location and categories.') . '</p>';
echo '<p>' . __('To make some profit suggested payment price must be at least 1$.') . ' <a href="https://www.paypal.com/webapps/mpp/merchant-fees" target="_blank">' . __('View Paypal fees') . '</a></p>';

if ($payment_prices)
{
	?>
	<table class="grid tblmin">
		<tr>
			<th><?php echo __('Location') . ' <i class="fa fa-arrows-h" aria-hidden="true"></i> ' . __('Category') ?></th>
			<th><?php echo __('Featured price') ?></th>
			<th><?php echo __('Posting price') ?></th>
			<th></th>
		</tr>
		<?php
		foreach ($payment_prices as $p)
		{
			echo '<tr class="r' . ($tr++ % 2) . '">';
			echo '<td>'
			. ($p->Location ? Location::getFullName($p->Location, __('All locations')) . ' <i class="fa fa-arrows-h" aria-hidden="true"></i> ' : '')
			. Category::getFullName($p->Category, __('All categories'))
			. '</td>';
			echo '<td data-title="' . View::escape(__('Featured price')) . '">' . Payment::formatAmount($p->price_featured) . '</td>';
			echo '<td data-title="' . View::escape(__('Posting price')) . '">' . Payment::formatAmount($p->price_post) . '</td>';
			echo '<td class="right-align">'
			. '<a href="' . Language::get_url('admin/paymentPriceEdit/' . $p->location_id . '/' . $p->category_id) . '/' . '" class="button" title="' . View::escape(__('Edit')) . '"><i class="fa fa-edit" aria-hidden="true"></i></a> '
			. '<a href="' . Language::get_url('admin/paymentPriceDelete/' . $p->location_id . '/' . $p->category_id . '/') . '" class="button red" title="' . View::escape(__('Delete')) . '"><i class="fa fa-trash" aria-hidden="true"></i></a>'
			. '</td>';
			echo '</tr>';
		}
		?>
	</table>
	<?php
}
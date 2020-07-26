<?php echo $this->validation()->messages() ?>

<h1 class="mt0"><?php echo __('Payment history') . ($ad ? ': <a href="' . Ad::url($ad) . '" target="_blank">' . View::escape(Ad::getTitle($ad)) . '</a>' : ''); ?></h1>

<?php
if ($ad && AuthUser::hasPermission(User::PERMISSION_MODERATOR))
{
	echo '<p><a href="' . Language::get_url('admin/paymentHistory/') . '">' . __('View payment history for all ads') . '</a></p>';
}

if ($payments)
{
	?>
	<table class="grid tblmin">
		<tr>
			<th><?php echo __('Txn') ?></th>
			<th><?php echo __('ID') ?></th>
			<th><?php echo __('Ad') ?></th>
			<th><?php echo __('Type') ?></th>
			<th><?php echo __('Payer email') ?></th>
			<th><?php echo __('Date') ?></th>		
			<th><?php echo __('Amount') ?></th>		
		</tr>
		<?php
		foreach ($payments as $payment)
		{
			$user = '';
			if ($payment->User)
			{
				$user = ' <a href="' . Language::get_url('admin/items/?email=' . urlencode($payment->User->email)) . '" target="_blank">' . View::escape($payment->User->email) . '</a>';
			}

			if ($payment->Ad)
			{
				$ad_title = $payment->ad_id . ' - <a href="' . Ad::url($payment->Ad) . '" target="_blank">' . View::escape($payment->Ad->title) . '</a>' . Ad::labelFeatured($payment->Ad) . Ad::labelAbused($payment->Ad);
			}
			else
			{
				$ad_title = $payment->ad_id;
			}


			echo '<tr class="r' . ($tr++ % 2) . '">';
			echo '<td>' . View::escape($payment->PaymentLog->txnid) . '</td>';
			echo '<td data-title="' . View::escape(__('ID')) . '">' . View::escape($payment->id) . '</td>';
			echo '<td>' . $ad_title . '</td>';
			echo '<td>' . View::escape(Payment::itemTypeName($payment->item_type)) . '</td>';
			echo '<td>' . View::escape($payment->PaymentLog->payeremail) . '</td>';
			echo '<td>' . Config::dateTime($payment->added_at) . '</td>';
			echo '<td>' . Payment::formatAmount($payment->amount, $payment->currency) . '</td>';
			echo '</tr>';
		}
		?></table>

	<?php
	echo $paginator;
}
else
{
	echo '<div class="empty"><p aria-hidden="true"><i class="fa fa-ban fa-5x" aria-hidden="true"></i></p>'
	. '<p class="h3">' . __('No records found.') . '</p>'
	. '</div>';
}
	


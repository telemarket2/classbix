<div class="big_message">
	<?php
	$message = $this->validation()->messages_dump();

	if ($message)
	{
		echo $message;
	}
	else
	{
		echo __('Error message expired.');
	}
	?>
</div>
<?php echo $this->validation()->messages() ?>
<h2 class="mt0"><?php echo __('Update') ?></h2>
<?php
echo $messages;

if ($update_core)
{
	?>
	<div class="log"></div>
	<p><a href="#update_start_over" class="update_start_over button"><?php echo __('Start over') ?></a> 
		<a href="#update_try_again" class="update_try_again button"><?php echo __('Try again') ?></a> </p>
	<script>
		addLoadEvent(function () {
			// start updating 
			updateStep(0);

			
			$('.update_start_over').click(function () {
				updateStep(1);
				return false;
			});
			$('.update_try_again').click(function () {
				updateStep(0);
				return false;
			});
		});

		function updateStep(restart)
		{
			$('.update_start_over,.update_try_again').hide();

			if (typeof restart === 'undefined')
			{
				restart = 0;
			}

			$.post(BASE_URL + 'admin/update/', {
				next_step: 1,
				restart: restart
			}, function (data) {
				var arr_data = data.split('{SEP}');
				if (arr_data[0] == 'ok') {
					$('.log').append(arr_data[1]);
					// continue 
					updateStep(0);
				} else if (arr_data[0] == 'completed') {
					$('.log').append(arr_data[1]);
				} else {
					// not completed show action buttons 
					$('.log').append(data);
					$('.update_start_over,.update_try_again').show();
				}
			});

			return false;
		}
	</script>
	<?php
}
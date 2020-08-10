<?php echo $this->validation()->messages() ?>
<h1 class="mt0"><?php echo __('Translation') . ' <small class="muted">(' . View::escape($lng) . ')</small>'; ?></h1>
<p>
	<?php
	echo __('If you made some changes to template or code remember to rebuild translation terms. Rebuilding will find new terms and remove unused translations.');
	?>
</p>
<form method="get" action="<?php echo Language::get_url('admin/translate/' . $lng . '/search/'); ?>">
	<p>
		<span class="input-group">

			<input type="search" name="search" id="search" value="<?php echo View::escape($_REQUEST['search']); ?>" class="input" aria-label="<?php echo View::escape(__('Search term')); ?>" /> 
			<button type="submit" name="submit" id="submit" class="button" title="<?php echo View::escape(__('Search')); ?>"><i class="fa fa-search" aria-hidden="true"></i></button>
		</span> 
		<?php
		echo ' <a href="' . Language::get_url('admin/translate/' . $lng . '/empty/') . '" class="button display_empty">' . __('Display empty values') . '</a> ';
		echo ' <a href="' . Language::get_url('admin/translateBuild/' . $lng . '/') . '" class="button blue">' . __('Rebuild translations terms') . '</a> ';
		if (I18nBuilder::isSupported($lng))
		{
			echo '<a href="#" class="button blue translate_empty">' . __('Auto translate empty fields') . '</a> ';
		}
		?>

	</p>
</form>
<?php
echo $form;
?>
<script>
	$(function ()
	{
		/*$('#filter').keyup(function(){
		 delay_execute(filterTranslationTerms,2000); 
		 return false;
		 });*/

		//$('#translation_terms #submit').click(submitTranslationForm);
		//$('#translation_terms').submit(submitTranslationForm);
	});


	var _delay_execute;

	function delay_execute(fnc, ms)
	{
		if (_delay_execute)
		{
			clearTimeout(_delay_execute);
		}
		_delay_execute = setTimeout(fnc, ms);
	}


	function filterTranslationTerms()
	{
		var term = $('#filter').val();
		var $table = $('table.translation_terms');
		var all_selector = 'tr';
		var term_selector = 'td[data-type="term"]:contains("' + term + '")';
		if (term.length > 0)
		{
			$('span.searching').removeClass('hidden');
			// hide all 
			$(all_selector, $table).hide();
			$(all_selector, $table).has('th').show();
			// display matched
			$(all_selector, $table).has(term_selector).show();
		}
		else
		{
			// show all
			$('tr', $table).show();
		}
		$('span.searching').addClass('hidden');
	}

	function submitTranslationForm()
	{
		// serialize data and submit serialized data
		var $form = $('#translation_terms');
		var data = $form.serialize();
		$('input[type="text"],input[type="hidden"]', $form).attr('disabled', 'disabled');
		$form.append('<textarea name="data"></textarea>');
		$('textarea[name="data"]', $form).val(data);
		return true;
	}
</script>



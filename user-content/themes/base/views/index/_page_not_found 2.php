<div class="page_not_found">
	<form action="<?php echo Language::get_url('search/'); ?>" method="get" id="search_form">
		<h2><?php echo $meta->title; ?></h2>
		<p><?php echo __('Sorry, page you are trying to load is not found. Use site navigation to find page you are looking for.'); ?></p>
		<p><input type="text" name="q" id="q" value="" placeholder="<?php echo __('Search'); ?>"> 
			<input type="submit" name="s" id="s" value="<?php echo __('Search'); ?>"></p>
		<p><a href="<?php echo Language::urlHome();?>"><i class="fa fa-arrow-left" aria-hidden="true"></i> <?php echo __('Back to home page')?></a></p>
	</form>
</div>
<?php echo $this->validation()->messages() ?>

<h1 class="mt0"><?php echo __('Import data'); ?> </h1>


<div class="tabs">
	<a href="#import_panel_loc" data-hide="import_panel" data-show="import_panel_loc">
		<?php echo __('Import locations') ?>
	</a>
	<a href="#import_panel_cat" data-hide="import_panel" data-show="import_panel_cat">
		<?php echo __('Import categories') ?>
	</a>
	<a href="#import_panel_item" data-hide="import_panel" data-show="import_panel_item">
		<?php echo __('Import items') ?>
	</a>
</div>




<form action="" method="post" enctype="multipart/form-data" class="import_panel import_panel_loc">
	<div class="panel my2">
		<div class="panel_header"><h3><?php echo __('Import locations') ?></h3></div>
		<div class="clearfix form-row">
			<div class="col col-12 px1">
				<?php echo __('Uploaded file should be formatted like below') ?>: 
				<pre class="bg-darken-1 p1 rounded overflow-auto">
Location1
Location1|Sub1
Location1|Sub2
Location1|Sub2|Subsub1
Location2
...</pre>
			</div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="file"><?php echo __('File'); ?></label></div>
			<div class="col col-12 sm-col-10 px1"><input type="file" name="file" id="file" class="input" /></div>
		</div>
		<div class="panel_footer">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Import'); ?>" />
			<input type="hidden" name="type" value="location" />
			<?php echo Config::nounceInput(); ?>
		</div>
	</div>
</form>

<form action="" method="post" enctype="multipart/form-data" class="import_panel import_panel_cat">
	<div class="panel my2">
		<div class="panel_header"><h3><?php echo __('Import categories') ?></h3></div>
		<div class="clearfix form-row">
			<div class="col col-12 px1">
				<?php echo __('Uploaded file should be formatted like below') ?>: <br/>
				<pre class="bg-darken-1 p1 rounded overflow-auto">Category1
Category1|Sub1
Category1|Sub2
Category1|Sub2|Subsub1
Category2
...</pre>
			</div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="file"><?php echo __('File'); ?></label></div>
			<div class="col col-12 sm-col-10 px1"><input type="file" name="file" id="file" class="input" /></div>
		</div>
		<div class="panel_footer">
			<input type="submit" name="submit" id="submit" value="<?php echo __('Import'); ?>" />
			<input type="hidden" name="type" value="category" />
			<?php echo Config::nounceInput(); ?>
		</div>
	</div>
</form>

<form action="" method="post" class="import_panel import_panel_item">
	<div class="panel my2">
		<div class="panel_header"><h3><?php echo __('Import Ads (with categories, locations, users, custom fields, images)') ?></h3></div>
		<div class="clearfix form-row">
			<div class="col col-12 px1">
				<p> <?php echo __('Rss file should be formatted like below. Export xml generator can be found here {url}', array('{url}' => 'http://classibase.com/how-to-import-ads-from-other-classifieds-website/')) ?>: </p>
				<?php
				$example_xml = '<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0">
	<channel>
	<title>SCRIPT_NAME export</title>
	<description>export of existing system data</description>
	<item>
		<title>Ad title here</title>
		<link/>
		<description>Ad description here</description>
		<pubDate>Sat, 11 Aug 2012 16:50:31 +0000</pubDate>
		<verified>1</verified>
		<enabled>1</enabled>
		<email>ad_author_email@test.com</email>
		<showemail>show</showemail>
		<ip>127.0.0.1</ip>
		<location>
			<val>Russia</val>
			<val>Moscow</val>
		</location>
		<category>
			<val>Stuff for Sale</val>
			<val>Household, furniture</val>
		</category>
		<image>http://test.com/adpics/5026713813f6ba03b46a1a9c1.jpg</image>
		<image>http://test.com/adpics/50267137e8b39181a2a9f1601.jpg</image>
		<image>http://test.com/adpics/502671380c92e7d9e98b2f1d7.jpg</image>
	</item>
	... other items below. keep it at most 10 records. per page. paginate adding ?page=2 after url
	</channel>
</rss>
';
				echo '<pre class="bg-darken-1 p1 rounded overflow-auto">' . View::escape($example_xml) . '</pre>';
				?>
			</div>
		</div>
		<div class="clearfix form-row">
			<div class="col col-12 sm-col-2 px1 form-label"><label for="url"><?php echo __('Url'); ?></label></div>
			<div class="col col-12 sm-col-10 px1">
				<input type="url" name="url" id="url" class="input input-long" />
				<em><?php echo __('Before starting make sure that url is opening properly coded xml like in description.') ?></em>
			</div>
		</div>
		<div class="panel_footer">
			<a href="#import" class="button import_ads"><?php echo __('Import'); ?></a>
			<?php echo Config::nounceInput(); ?>
			<div class="import_ads_log"></div>
		</div>
	</div>
</form>
<script>
	addLoadEvent(function () {
		$('a.import_ads').click(import_ads);

		$(document).ajaxError(function (event, request, settings) {
			// url = settings.url;			
			$(".import_ads_log").prepend("<p>Error requesting page. It may be due to inconsistensy of imported data with existing data.</p>");
		});
	});
	var last_page = 0;
	function import_ads()
	{
		last_page++;
		$('.import_ads_log').prepend('<p>loading page: ' + last_page + '</br>---------------</p>');
		var url = $('#url:first').val();
		var $form = $('#url:first').parents('form:first');
		var nounce = $('input[name="nounce"]', $form).val();
		$.post(BASE_URL + 'admin/importAds/', {url: url, page: last_page, nounce: nounce}, function (data) {

			var arr_data = data.split('{SEP}');

			if (arr_data[0] == 'ok')
			{
				// display data
				$('.import_ads_log').prepend('<p>' + arr_data[1] + '</p>');

				// continue importing 
				import_ads();
			} else
			{
				// stop importing loop
				$('.import_ads_log').prepend('<p>' + data + ' : stopped importing [page: ' + last_page + ']</p>');
			}

		});
		return false;
	}
</script>



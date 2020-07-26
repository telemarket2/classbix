<!DOCTYPE html>
<html style="height: 100%;">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo $title; ?></title>
		<link href="<?php echo URL_ASSETS ?>css/screen.min.css?v=<?php echo Config::VERSION ?>" rel="stylesheet" type="text/css" />
		<script src="<?php echo URL_ASSETS ?>js/jquery-1.12.4.min.js"	type="text/javascript"></script>
		<script src="<?php echo URL_ASSETS ?>js/admin.min.js?v=<?php echo Config::VERSION ?>"	type="text/javascript"></script>
		<script>
			var BASE_URL = '<?php echo Language::get_url(); ?>';
		</script>
	</head>
	<frameset cols="300,*" frameborder="1" border="1" framespacing="0" name="framesetMain" id="framesetMain">
		<frame src="<?php echo Language::get_url('admin/themesCustomizeControls/' . $theme_id . '/'); ?>" name="topFrame"  noresize="noresize" id="topFrame" title="topFrame" />
		<frame src="javascript:'loading...'" name="dynamicframe" id="dynamicframe" title="dynamicframe" />
	</frameset>
	<noframes><body>
		</body>
	</noframes></html><!--
	   - Unfortunately, Microsoft has added a clever new
	   - "feature" to Internet Explorer. If the text of
	   - an error's message is "too small", specifically
	   - less than 512 bytes, Internet Explorer returns
	   - its own error message. You can turn that off,
	   - but it's pretty tricky to find switch called
	   - "smart error messages". That means, of course,
	   - that short error messages are censored by default.
	   - IIS always returns error messages that are long
	   - enough to make Internet Explorer happy. The
	   - workaround is pretty simple: pad the error
	   - message with a big comment like this to push it
	   - over the five hundred and twelve bytes minimum.
	   - Of course, that's exactly what you're reading
	   - right now.
-->
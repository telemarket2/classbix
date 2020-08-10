<?php
header("HTTP/1.0 404 Not Found");
header("Status: 404 Not Found");
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html>
	<head>
		<title>404 Not Found</title>
	</head>
	<body>
		<h1>Not Found</h1>
		<p>The requested URL was not found on this server. Click <a href="<?php echo get_url() ?>">here to visit home page</a>.</p>
		<hr>
	</body>
</html>
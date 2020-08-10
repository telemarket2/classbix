<?php
/**
 * ClassiBase Classifieds Script
 *
 * ClassiBase Classifieds Script by Vepa Halliyev is licensed under a Creative Commons Attribution-Share Alike 3.0 License.
 *
 * @package		ClassiBase Classifieds Script
 * @author		Vepa Halliyev
 * @copyright	Copyright (c) 2009, Vepa Halliyev, veppa.com.
 * @license		http://classibase.com
 * @link		http://classibase.com
 * @since		Version 1.0
 * @filesource
 */
/*
  {DB_NAME}
  {DB_HOST}
  {DB_USER}
  {DB_PASS}
  {TABLE_PREFIX}
  {USE_MOD_REWRITE} true/false
  {USE_PDO}
 */
if (!defined('CORE_ROOT'))
{
	exit('Cannot call setup.php directly. Run <a href="index.php">index.php</a>');
}

if (!defined('REQUEST_TIME'))
{
	define('REQUEST_TIME', $_SERVER['REQUEST_TIME']);
}


$RewriteBase = dirname($_SERVER['SCRIPT_NAME']);

define('DEBUG', true);
// turn error reporting on 
error_reporting(E_ALL ^ E_NOTICE);
ini_set('display_errors', '1');

$file = 'sys/config.template.php';
$script_name = 'ClassiBase Classifieds Script';

// check if sample exists
if (!file_exists($file))
{
	exit('Sorry, I need a ' . $file . ' file to work from. Please re-upload this file from your ' . $script_name . ' installation.');
}


$configFile = file_get_contents($file);

if (!is_writable('sys/'))
	exit("Sorry, I can't write to the directory. You'll have to either change the permissions on your sys directory or create your config.php manually.");

// Check if config.php has been created
if (file_exists('sys/config.php'))
	exit("The file 'config.php' already exists. If you need to reset any of the configuration items in this file, please delete it first.");


if (!is_writable('user-content/'))
	exit("Sorry, I can't write to the /user-content directory. Please make that directory writable, it will have themes and user uploaded images.");


// default values
$dbname = 'classibase';
$uname = 'dbusername';
$passwrd = 'dbpassword';
$dbhost = 'localhost';
$prefix = 'cb_';

/**
 * parse given sqldump to seperate statements 
 * 
 * @param string $sql_str
 * @return array
 */
function parse_sql_dump($sql_str)
{
	// remove wrap comments. ex: /*!4000 SOME ACTION  */
	$rem_patterns[] = '\/\*[^*]+\*\/?;';
	// remove sinle line comments, ex: -- comment
	$rem_patterns[] = '\n-- [^\n]+';
	$sql_str = preg_replace('/' . implode('|', $rem_patterns) . '/', '', $sql_str);

	// seperate each statment 
	//$sql_str = preg_replace('/;.\n/', "{SQL_SEP}", $sql_str);
	$sql_str = preg_replace('/;[\r]?\n/', "{SQL_SEP}", $sql_str);
	//echo '<pre>' . $sql_str . '</pre>';

	$sql = explode("{SQL_SEP}", $sql_str);
	return $sql;
}

if (isset($_POST['submit']))
{
	// check db connection and create config file
	$dbname = trim($_POST['dbname']);
	$uname = trim($_POST['uname']);
	$passwrd = trim($_POST['pwd']);
	$dbhost = trim($_POST['dbhost']);
	$prefix = trim($_POST['prefix']);
	if (empty($prefix))
		$prefix = 'vws_';

	$email = trim($_POST['email']);
	$password = trim($_POST['password']);
	$password_repeat = trim($_POST['password_repeat']);


	$connections = array(
		'master' => array('DB_DSN'	 => 'mysql:dbname=' . $dbname . ';host=' . $dbhost,
			'DB_USER'	 => $uname,
			'DB_PASS'	 => $passwrd)
	);



	// include record class
	include CORE_ROOT . '/Framework.php';

	// use pdo 
	if (!defined('USE_PDO'))
	{
		define('USE_PDO', 1);
	}


	Record::$__CONNECTIONS__ = $connections;
	Record::getConnection('master');

	// connection is ok 
	$setup = true;

	// create admin for the site
	if (!strlen($email) || !strlen($password) || !strlen($password_repeat))
	{
		$error = 'Admin email and password values required.';
		$setup = false;
	}


	if (strpos($email, '@') === false || strpos($email, '.') === false)
	{
		$error = 'Admin email is not valid.';
		$setup = false;
	}

	if (strlen($password) < 4 || strlen($password) > 32)
	{
		$error = 'Admin password must be between 4-32 characters.';
		$setup = false;
	}

	if ($password !== $password_repeat)
	{
		$error = 'Admin password and password repeat is not matching.';
		$setup = false;
	}

	if ($setup)
	{
		// write information to database
		if (!file_exists('setup.sql'))
		{
			$error = 'setup.sql file not found.';
			$setup = false;
		}

		$sql_str = file_get_contents('setup.sql');
		$sql = parse_sql_dump($sql_str);

		foreach ($sql as $s)
		{
			$s = trim($s);
			if (strlen($s))
			{
				if ($setup)
				{
					$s = str_replace('nz_', $prefix, $s);
					if (strpos($s, '{AD_EXPIRESON}') !== false)
					{
						$arr_search = array(
							'{AD_EXPIRESON}' => time() + 100 * 2600 * 24,
							'{AD_EMAIL}'	 => $email,
						);
						$s = str_replace(array_keys($arr_search), array_values($arr_search), $s);
					}
					if (!Record::query($s))
					{
						$error = 'Error adding initial database records.';
						$setup = false;
					}
				}
			}
		}
	}

	if ($setup)
	{
		if (!defined('TABLE_PREFIX'))
		{
			define('TABLE_PREFIX', $prefix);
		}

		list($name, ) = explode('@', $email);
		$sql = "INSERT INTO " . $prefix . "user (`name`,`email`,`password`,`level`,`ip`,`activation`,`enabled`,`added_at`) 
			VALUES (?, ?, ?,'1','127.0.0.1','0','1','1568767800')";


		if (!Record::query($sql, array($name, $email, md5($password))))
		{
			$error = 'Error creating admin for the site.';
			$setup = false;
		}
	}



	if ($setup)
	{
		// create config file	
		$find = array(
			'{DB_NAME}',
			'{DB_HOST}',
			'{DB_USER}',
			'{DB_PASS}',
			'{TABLE_PREFIX}',
			'{USE_MOD_REWRITE}',
			'{USE_PDO}');

		$replace = array(
			$dbname,
			$dbhost,
			$uname,
			$passwrd,
			$prefix,
			true,
			true);

		$configFile = str_replace($find, $replace, $configFile);
		if (!file_put_contents('sys/config.php', $configFile))
		{
			exit('Error writing config file.');
		}
		chmod('sys/config.php', 0666);


		// write htaccess file 
		$RewriteBase = dirname($_SERVER['SCRIPT_NAME']) . '/';
		$htaccess = '#Options +FollowSymLinks
AddDefaultCharset UTF-8
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase ' . $RewriteBase . '
  
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteCond %{REQUEST_FILENAME} !-l
  # Main URL rewriting.
  RewriteRule ^(.*)$ index.php?$1 [L,QSA]
</IfModule>';

		if (!file_put_contents('.htaccess', $htaccess))
		{
			echo 'Error writing .htaccess file.';

			echo 'Please create .htaccess file manually with following content:';
			echo '<textarea rows="10" cols="40">' . $htaccess . '</textarea>';
		}

		// delete data and image cache 
		if (!defined('UPLOAD_ROOT'))
		{
			define('UPLOAD_ROOT', FROG_ROOT . '/user-content/uploads');
		}
		Config::clearAllCache();

		// display success message 
		$msg = '<p>Congratulations you installed ' . $script_name . '. <a href="' . get_url() . '">View your website</a>.</p>';
		exit($msg);
	}
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><?php echo $script_name; ?> &rsaquo; Setup Configuration File</title>
		<link rel="stylesheet" href="public/css/screen.min.css" type="text/css" />
	</head>
	<body class="setup">
		<div class="content">
			<h1><?php echo $script_name; ?></h1>


			<p>Welcome to <?php echo $script_name; ?>. Before getting started, we need some information on the database.</p>
			<p><strong>If for any reason this automatic file creation doesn't work, don't worry. All this does is fill in the database information to a configuration file. You may also simply open <code>sys/config.template.php</code> in a text editor, fill in your information, and save it as <code>sys/config.php</code>. </strong></p>


			<form method="post" action="">
				<p>Below you should enter your database connection details. If you're not sure about these, contact your host. </p>
				<?php
				if ($error)
				{
					echo '<p style="color:red;"><b>' . $error . '</b></p>';
				}
				?>
				<table class="grid">
					<tr>
						<th><label for="dbname">Database Name</label></th>
						<td><input name="dbname" id="dbname" type="text" size="25" value="<?php echo htmlspecialchars($dbname) ?>" /></td>
						<td>The name of the database you want to run WP in. </td>
					</tr>
					<tr>
						<th><label for="uname">User Name</label></th>
						<td><input name="uname" id="uname" type="text" size="25" value="<?php echo htmlspecialchars($uname) ?>" /></td>
						<td>Your MySQL username</td>
					</tr>
					<tr>
						<th><label for="pwd">Password</label></th>
						<td><input name="pwd" id="pwd" type="text" size="25" value="<?php echo htmlspecialchars($passwrd) ?>" /></td>
						<td>...and MySQL password.</td>
					</tr>
					<tr>
						<th><label for="dbhost">Database Host</label></th>
						<td><input name="dbhost" id="dbhost" type="text" size="25" value="<?php echo htmlspecialchars($dbhost) ?>" /></td>
						<td>99% chance you won't need to change this value.</td>
					</tr>
					<tr>
						<th><label for="prefix">Table Prefix</label></th>
						<td><input name="prefix" id="prefix" type="text" size="25" value="<?php echo htmlspecialchars($prefix) ?>" /></td>
						<td>If you want to run multiple <?php echo $script_name; ?> installations in a single database, change this.</td>
					</tr>
					<tr>
						<td colspan="3"><h3>Create admin for the site</h3></td>
					</tr>
					<tr>
						<th><label for="email">Admin email</label></th>
						<td><input name="email" id="email" type="text" size="25" value="<?php echo htmlspecialchars($email) ?>" /></td>
						<td>This will be used to login to admin panel.</td>
					</tr>
					<tr>
						<th><label for="password">Admin password</label></th>
						<td><input name="password" id="password" type="password" size="25" /></td>
						<td>This will be used to login to admin panel. Password must be between 4-32 characters.</td>
					</tr>
					<tr>
						<th><label for="password_repeat">Repeat password</label></th>
						<td><input name="password_repeat" id="password_repeat" type="password" size="25" /></td>
						<td></td>
					</tr>
				</table>
				<p class="step"><input name="submit" type="submit" value="Submit" class="button" /></p>
			</form>
		</div>
	</body>
</html>
<?php
exit;

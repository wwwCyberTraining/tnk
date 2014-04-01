<?php
/**
 * @file create.php - create a new database and a new db_connect_params.php file
 * @author Erel Segal
 * @date 2013-01-15
 */
error_reporting(E_ALL);
set_time_limit(0);

if (!defined("STDIN")) {
	die("Please run create.php from the console - not from a web-browser!");
}

print "
# Create a new database for Tanakh Navigation Kit

## Requirements

* MySQL 5+
* PHP 5+
* PHP-MySQL extension
";

$SCRIPT = dirname(__FILE__) . '/../script';
	
require_once("$SCRIPT/sql.php");
require_once("$SCRIPT/sql_backup.php");
require_once("$SCRIPT/coalesce.php");

show_create_page();
update_create_page();


function show_create_page() {
	@include_once(dirname(__FILE__) . "/db_connect_params.php"); // only if it exists
	set_coalesce($GLOBALS['db_name'], coalesce($GLOBALS['db_name'],''));
	set_coalesce($GLOBALS['db_user'], coalesce($GLOBALS['db_user'],''));
	set_coalesce($GLOBALS['db_pass'], coalesce($GLOBALS['db_pass'],''));

	print "
## Credentials

";
	$_POST['db_host'] = "localhost";
	print "MySQL username [$GLOBALS[db_user]]: "; $db_user = trim(fgets(STDIN));
	$_POST['db_user'] = $db_user? $db_user: $GLOBALS['db_user'];
	print "MySQL password: "; $db_pass = trim(fgets(STDIN));
	$_POST['db_pass'] = $db_pass;
		
	print "
## New database data

";
	print "New database name [$GLOBALS[db_name]]: "; $db_name = trim(fgets(STDIN));
	$_POST['db_name'] = $db_name? $db_name: $GLOBALS['db_name'];
	print "Drop existing database if it exists? [no]: "; $drop_db = trim(fgets(STDIN));
	$_POST['drop_db']=($drop_db=='yes'); 
}

function update_create_page() {
	print "
## New database creation

";

	print "* create_database_and_user();
";	create_database_and_user();

	print "* create_db_connect_params();
";	create_db_connect_params();
	
	print "* require('db_connect.php');
";	require(dirname(__FILE__) . "/db_connect.php");

	print "* create_database_tables();	
";	create_database_tables();

	print "

## Done!

Go to the search page: http://localhost/tnk/findpsuq.php

"; 
}


function create_database_and_user() {
	$link = sql_connect(
		$_POST['db_host'],
		$_POST['db_user'],
		$_POST['db_pass']);

	if (!$link)
		die('Could not connect as $_POST[db_user]: ' . sql_get_last_message());

	if (isset($_POST['drop_db']))
		sql_query_or_die("DROP DATABASE IF EXISTS $_POST[db_name]");

	if (sql_database_exists($_POST['db_name'])) {
		echo "Database $_POST[db_name] already exists - won't create it\n";
		$GLOBALS['db_created'] = false;
	} 	else {
		echo "Creating database $_POST[db_name]\n";
		sql_query_or_die("
			CREATE DATABASE $_POST[db_name] 
			CHARACTER SET utf8");
		sql_query_or_die("SET storage_engine=MYISAM");
		$GLOBALS['db_created'] = true;
	}

// 	$db_user_quoted = quote_smart($_POST['db_user'])."@".quote_smart($_POST['db_host']);
// 	sql_query_or_die("GRANT ALL PRIVILEGES ON $_POST[db_name].* 
// 		TO $db_user_quoted IDENTIFIED BY ".quote_all($_POST['db_pass'])." WITH GRANT OPTION");
// 	sql_query_or_die("GRANT RELOAD ON *.* 
// 		TO $db_user_quoted");

	sql_close($link); // root logs out
}

function create_db_connect_params() {
	$DONT_MAIL_JUST_LOG =  (isset($_POST['DONT_MAIL_JUST_LOG'])? "true": "false");
	$USE_HTTPS_FOR_LOGIN = (isset($_POST['USE_HTTPS_FOR_LOGIN'])? "true": "false");
	$ALTERNATIVE_SESSION_SAVE_PATH = (isset($_POST['ALTERNATIVE_SESSION_SAVE_PATH'])? "dirname(__FILE__) . '/../../../session'": "NULL");
	file_put_contents(dirname(__FILE__)."/db_connect_params.php", "<?php 
/**
 * @file parameters for db_connect.php and config.php
 * Automatically generated by $_SERVER[PHP_SELF] at $GLOBALS[current_time]
 */

\$GLOBALS['db_host'] = \$db_host = '$_POST[db_host]';
\$GLOBALS['db_user'] = \$db_user = '$_POST[db_user]';
\$GLOBALS['db_pass'] = \$db_pass = '$_POST[db_pass]';
\$GLOBALS['db_name'] = \$db_name = '$_POST[db_name]';
\$GLOBALS['BACKUP_FILEROOT'] = str_replace('admin','data',dirname(__FILE__));
?".">")  /* put dirname inside the ""! */
or die ("Can't create db_connect_params");
}


/**
 * Create the database tables based on the data_utf8 folder.
 */
function create_database_tables() {
	$configuration_tables = array("psuqim", "psuqim_niqud_milim", "sfrim", "prqim", "trgumim_im_ktovt", "QLT_mftx". "prt_tnk1", "board_tnk1");
	foreach ($configuration_tables as $table)
		restore_table($table);	
}
?>
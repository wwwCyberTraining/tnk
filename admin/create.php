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
* PHP 5 or 6 (but not 7)
* PHP-MySQL extension
";

if (!function_exists("mysql_query"))
	die("Function mysql_query not found! Either you do not have MySQL or PHP-MySQL extension, or you have PHP 7\n");

$SCRIPT = dirname(__FILE__) . '/../script';

require_once("$SCRIPT/sql.php");
require_once("$SCRIPT/sql_backup.php");
require_once("$SCRIPT/coalesce.php");

show_create_page();
update_create_page();

function read($vartitle, $varname) {
	$default = coalesce($GLOBALS[$varname],"");
	print "$vartitle [$default]: "; $varvalue = trim(fgets(STDIN));
	$_POST[$varname] = $varvalue? $varvalue: $default;
}


function show_create_page() {
	@include_once(dirname(__FILE__) . "/db_connect_params.php"); // only if it exists
	set_coalesce($GLOBALS['root_username'], coalesce($GLOBALS['root_username'],'root'));
	set_coalesce($GLOBALS['root_password'], coalesce($GLOBALS['root_password'],''));
	set_coalesce($GLOBALS['db_host'], coalesce($GLOBALS['db_host'],'localhost'));
	set_coalesce($GLOBALS['db_name'], coalesce($GLOBALS['db_name'],'tnk'));
	set_coalesce($GLOBALS['db_user'], coalesce($GLOBALS['db_user'],'tnk'));
	set_coalesce($GLOBALS['db_pass'], coalesce($GLOBALS['db_pass'],'tnk'));
	set_coalesce($GLOBALS['GOOGLE_API_KEY'], coalesce($GLOBALS['GOOGLE_API_KEY'],''));
	set_coalesce($GLOBALS['GOOGLE_CSE_ID'], coalesce($GLOBALS['GOOGLE_CSE_ID'],''));
	set_coalesce($GLOBALS['is_local'], coalesce($GLOBALS['is_local'],'false'));
	set_coalesce($GLOBALS['TNKUrl'], coalesce($GLOBALS['TNKUrl'],'http://tora.us.fm'));

	// check if tnk1 database exists
	$rows = mysql_query("SHOW DATABASES LIKE 'tnk1'");
	$tnk1_default_database = (mysql_num_rows($rows)>0? "tnk1": "");
	set_coalesce($GLOBALS['TNKDb'], coalesce($GLOBALS['TNKDb'],$tnk1_default_database));
	
	print "
## Credentials

";
	$_POST['db_host'] = $GLOBALS['db_host'];
	read("MySQL root username", "root_username");
	read("MySQL root password", "root_password");

	print "
## New database data

";
	read("New database name", "db_name");
	read("New user name", "db_user");
	read("New user password", "db_pass");
	print "Drop existing database if it exists? [no]: "; $drop_db = trim(fgets(STDIN));
	$_POST['drop_db']=($drop_db=='yes');
	
	print "
## Tanakh Navigation Site data
	
";
	read("Tanakh Navigation Site URL ", "TNKUrl");
	read("Tanakh Navigation Site database ", "TNKDb");
	
	print "
## Data for Google search (optional)

";
	read("Google API key", "GOOGLE_API_KEY");
	read("Google CSE ID", "GOOGLE_CSE_ID");
	print "Local? [$GLOBALS[is_local]]: "; $is_local = trim(fgets(STDIN));
	$_POST['is_local'] = $is_local? $is_local: $GLOBALS['is_local'];
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

Go to the search page: http://localhost/tnk/find.php

"; 
}


function create_database_and_user() {
	$link = sql_connect(
		$_POST['db_host'],
		$_POST['root_username'],
		$_POST['root_password']);

	if (!$link)
		die('Could not connect as root: ' . sql_get_last_message());

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
		$GLOBALS['db_created'] = true;
	}

	$db_user_quoted = quote_smart($_POST['db_user'])."@".quote_smart($_POST['db_host']);
	sql_query_or_die("GRANT ALL PRIVILEGES ON $_POST[db_name].* 
		TO $db_user_quoted IDENTIFIED BY ".quote_all($_POST['db_pass'])." WITH GRANT OPTION");
	if ($_POST['TNKDb']) {
		sql_query_or_die("GRANT ALL PRIVILEGES ON $_POST[TNKDb].*
		TO $db_user_quoted IDENTIFIED BY ".quote_all($_POST['db_pass'])." WITH GRANT OPTION");
	}
	sql_query_or_die("GRANT RELOAD ON *.* 
		TO $db_user_quoted");

	sql_close($link); // root logs out
}

function create_db_connect_params() {
	$BACKUP_FILEROOT = str_replace('admin','data',dirname(__FILE__));
	$BACKUP_WHATSNEW_FILEROOT = dirname(__FILE__) . '/../../whatsnew/tnk/data';
	mkpath($BACKUP_FILEROOT);
	mkpath($BACKUP_WHATSNEW_FILEROOT);
	
	file_put_contents(dirname(__FILE__)."/db_connect_params.php", "<?php 
/**
 * @file parameters for db_connect.php and config.php
 * Automatically generated by $_SERVER[PHP_SELF] at $GLOBALS[current_time]
 */

\$GLOBALS['db_host'] = \$db_host = '$_POST[db_host]';
\$GLOBALS['db_user'] = \$db_user = '$_POST[db_user]';
\$GLOBALS['db_pass'] = \$db_pass = '$_POST[db_pass]';
\$GLOBALS['db_name'] = \$db_name = '$_POST[db_name]';
\$GLOBALS['TNKUrl'] = \$TNKUrl = '$_POST[TNKUrl]';
\$GLOBALS['TNKDb'] = \$TNKDb = '$_POST[TNKDb]';
\$GLOBALS['BACKUP_FILEROOT'] = '$BACKUP_FILEROOT';
\$GLOBALS['BACKUP_WHATSNEW_FILEROOT'] = '$BACKUP_WHATSNEW_FILEROOT';
\$GLOBALS['GOOGLE_API_KEY'] = \$GOOGLE_API_KEY = '$_POST[GOOGLE_API_KEY]';
\$GLOBALS['GOOGLE_CSE_ID' ] = \$GOOGLE_CSE_ID = '$_POST[GOOGLE_CSE_ID]';
\$GLOBALS['is_local' ] = \$is_local = '$_POST[is_local]';
?".">")  /* put dirname inside the ""! */
or die ("Can't create db_connect_params");
}


/**
 * Create the database tables based on the data_utf8 folder.
 */
function create_database_tables() {
	$configuration_tables = array(
		"psuqim", "psuqim_niqud_milim", "sfrim", "prqim", 
		"sfrim_prqim", "qodm_hba",
		"mspry_psuqim", "psuq_qodm_hba",   // generated by psuq_qodm_hba.sql
		"miqraot_gdolot", 
		"QLT_mftx", "qjr_tnk1_psuq",
		"xodjim", "ymim", "tarikim", 
		"prjot_xgim",
		"prjot_jvua_html",                // generated by prjot_whftrot.sql
	);
	foreach ($configuration_tables as $table)
		restore_table($table);	
	// sql_queries_or_die(file_get_contents(dirname(__FILE__)."/psuq_qodm_hba.sql"));
	sql_queries_or_die(file_get_contents(dirname(__FILE__)."/prjot_whftrot.sql"));
}
?>

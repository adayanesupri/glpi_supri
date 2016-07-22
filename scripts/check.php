<!--#!/usr/bin/php-->
<?php
print "<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";

date_default_timezone_set('America/Sao_Paulo');

define('DEBUG', FALSE);
define('FILE_SOURCE', '../files/');
define('EXTENSAO', '*.{pdf}');

$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'glpi_supri';

$conn = mysql_connect($dbhost, $dbuser, $dbpass) or die('Error ao conectar ao mysql');
mysql_select_db($dbname);

print "<br>";

$sql = "	SELECT *
			from glpi_documents
			where date_mod > '2013-10-03'
			and users_id = 4086
			ORDER BY date_mod ";
$rs_sql = mysql_query( $sql ) or die(mysql_error());

print "Procesando:<br>";

$errors = 0;
while( $rs_row_sql = mysql_fetch_array( $rs_sql ))
{
	$file_path = FILE_SOURCE . $rs_row_sql["filepath"];
	$db_sha1 = $rs_row_sql["sha1sum"];


	$file_sha1 = sha1_file( $file_path );
	if ( $db_sha1 != $file_sha1 )
	{
		$errors++;
		print "<br>" . $file_path . "<br>";
		print "db_sha1: " . $db_sha1 . "<br>";
		print "file_sha1: " . $file_sha1 . "<br><br>";
	}
	else
	{
		//print $file_path . "ok.<br>";
	}
	flush();
}
print "errors: " . $errors;

mysql_close( $conn );

?>

<?php

require 'config.php';

db_connect();

$BASE_URL = 'http://api.cs50.net/courses/1.0/%s?output=csv';
$TABLES = array('courses', 'faculty', 'fields');

foreach ($TABLES as $table) {
	mysql_query("TRUNCATE TABLE $table") or die(mysql_error());

	$url = sprintf($BASE_URL, $table);
	$handle = fopen($url, 'r');

	$header = fgetcsv($handle);
	$fields = array();
	for ($i = 0, $n = count($header); $i < $n; $i++)
	    $fields[$header[$i]] = $i;

	while ($row = fgetcsv($handle)) {
	    $columns = implode(',', array_keys($fields));
	    $escaped_row = array_map('db_escape', $row);
	    $values = implode(',', $escaped_row);

	    $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)', $table, $columns, $values);
		mysql_query($sql) or die(mysql_error());
	}

	fclose($handle);
}
	
?>
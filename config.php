<?php
$DATABASE_HOST = getenv(['Q_SCRAPER_DATABASE_HOST']);
$DATABASE_USER = getenv(['Q_SCRAPER_DATABASE_USER']);
$DATABASE_PASSWORD = getenv(['Q_SCRAPER_DATABASE_PASSWORD']);
$DATABASE_NAME = getenv(['Q_SCRAPER_DATABASE_NAME']);

function db_connect() {
	global $DATABASE_HOST, $DATABASE_USER, $DATABASE_PASSWORD, $DATABASE_NAME;

	mysql_connect($DATABASE_HOST, $DATABASE_USER, $DATABASE_PASSWORD)
		or die(mysql_error());
	mysql_select_db($DATABASE_NAME)
		or die(mysql_error());
}

function db_escape($values, $quotes = true) { 
	if (is_array($values)) { 
		foreach ($values as $key => $value) { 
			$values[$key] = db_escape($value, $quotes); 
		} 
	} 
	else if ($values === null) { 
		$values = 'NULL'; 
	} 
	else if (is_bool($values)) { 
		$values = $values ? 1 : 0; 
	} else if ($values === 'FALSE') {
		$values = 0;
	} else if ($values === 'TRUE') {
		$values = 1;
	}
	else if (!is_numeric($values)) { 
		$values = mysql_real_escape_string($values); 
		if ($quotes) { 
			$values = '"' . $values . '"'; 
		} 
	} 
	return $values; 
} 
?>

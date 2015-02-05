<?php

require 'config.php';

db_connect();

$BASE_URL = 'http://api.cs50.net/courses/3/%s?output=json&key=a99b2d48a8519f5738ad2aa11269d082';
$TABLES = array('courses', 'faculty', 'fields');

foreach ($TABLES as $table) {
  mysql_query("TRUNCATE TABLE $table") or die(mysql_error());

  $url = sprintf($BASE_URL, $table);
  $rows = json_decode(file_get_contents($url));

  foreach ($rows as $row) {
    $row = get_object_vars($row);

    $faculties = $row["faculty"];

    unset($row["schedule"]);
    unset($row["locations"]);
    unset($row["faculty"]);

    $columns = array_keys($row);
    $values = array_map('db_escape', array_values($row));

    $sql = sprintf('INSERT IGNORE INTO %s (%s) VALUES (%s)', $table,
      implode(",", $columns), implode(",", $values));
    mysql_query($sql) or die(mysql_error());

    if ($faculties) {
      foreach ($faculties as $faculty) {
        $sql = sprintf('INSERT INTO courses_faculty (cat_num, faculty_id) VALUES (\'%s\', \'%s\')',
          $row["cat_num"], $faculty->id);
        mysql_query($sql) or die(mysql_error());
      }
    }
  }
}

?>

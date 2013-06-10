<?php

    // MUST BE RUN ON MY OWN MACHINE IF COOKIE IS FROM
    // MY OWN MACHINE, PRESUMABLY BECAUSE PIN COOKIE IS
    // TIED TO IP

    // ensure proper usage
    if ($argc != 2)
        die("Usage: 1-scrape cookie\n");

    // base URL
    $base = "https://webapps.fas.harvard.edu/course_evaluation_reports/fas";

    // prepare for HTTP requests
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIE, "JSESSIONID={$argv[1]}");
    curl_setopt($ch, CURLOPT_HEADER, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 16);
    curl_setopt($ch, CURLOPT_POST, FALSE);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    // years,terms to scrape
    // e.g., 2010,1 == Fall 2010; 2010,2 == Spring 2011
    foreach (array("2012", "2011", "2010", "2009", "2008", "2007", "2006") as $year)
    {
        foreach (array("1", "2") as $term)
        {
            echo "$year $term...\n";

            // ensure directory exists
            $pages = "pages/$year/$term";
            @mkdir($pages, 0777, TRUE);

            // get list of courses for this year/term
            $path = "list?yearterm={$year}_{$term}";
            echo "$base/$path...\n";
            curl_setopt($ch, CURLOPT_URL, "$base/$path");
            curl_setopt($ch, CURLOPT_POST, 0);
            $contents = curl_exec($ch);
            file_put_contents("$pages/$path", $contents);

            // get departments
            preg_match_all('/list\?dept=(.*?)#/', $contents, $matches);
            $departments = array_values(array_unique($matches[1]));

            // get each department's courses
            foreach ($departments as $dept)
            {
                echo " $dept...\n";

                // get list of courses
                $path = "guide_dept?dept=" . urlencode($dept) . "&term={$term}&year={$year}";
                echo " $base/$path...\n";
                curl_setopt($ch, CURLOPT_URL, "$base/$path");
                $contents = curl_exec($ch);
                file_put_contents("$pages/$path", $contents);

                // parse list
                $n = preg_match_all('{<a href="new_course_summary.html\?course_id=(\d+?)">(.+?) (\d*)(.*?):\s+(.+?)</a>}', $contents, $matches);
                $courses = $matches;
                for ($i = 0; $i < $n; $i++)
                {
                    $course_id = $courses[1][$i];
                    $code = $courses[2][$i];
                    $num_int = $courses[3][$i];
                    $num_char = $courses[4][$i];
                    $title = $courses[5][$i];

                    echo "  $code {$num_int}{$num_char}: $title...\n";
    
                    // save Course Eval. Summary
                    $path = "new_course_summary.html?course_id={$course_id}";
                    echo "  $base/$path...\n";
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_URL, "$base/$path");
                    $contents = curl_exec($ch);
                    file_put_contents("$pages/$path", $contents);

                    // save Instructor Eval. Summary
                    $path = "inst-tf_summary.html?course_id={$course_id}";
                    echo "  $base/$path...\n";
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_URL, "$base/$path");
                    $contents = curl_exec($ch);
                    file_put_contents("$pages/$path", $contents);

                    // ... for each instructor
                    $contents = preg_replace('{xmlns="http://www.w3.org/1999/xhtml"}', "", $contents);
                    $dom = simplexml_load_string($contents);
                    if ($dom && $values = $dom->xpath("//select[@name='current_instructor_or_tf_huid_param']/option/@value"))
                    {
                        foreach ($values as $current_instructor_or_tf_huid_param)
                        {
                            $path = "inst-tf_summary.html?current_instructor_or_tf_huid_param=" . urlencode($current_instructor_or_tf_huid_param) . "&course_id={$course_id}&current_tab=2&benchmark_type=no&sect_num=";
                            echo "  $base/$path...\n";
                            curl_setopt($ch, CURLOPT_POST, 0);
                            curl_setopt($ch, CURLOPT_URL, "$base/$path");
                            $contents = curl_exec($ch);
                            file_put_contents("$pages/$path", $contents);
                        }
                    }

                    // save View Comments By Question
                    $path = "view_comments.html?course_id={$course_id}";
                    echo "  $base/$path...\n";
                    curl_setopt($ch, CURLOPT_POST, 0);
                    curl_setopt($ch, CURLOPT_URL, "$base/$path");
                    $contents = curl_exec($ch);
                    file_put_contents("$pages/$path", $contents);
                    if (preg_match("/view_comments.html\?course_id={$course_id}&amp;qid=1487&amp;sect_num=/msU", $contents))
                    {
                        $path = "view_comments.html?course_id={$course_id}&qid=1487&sect_num=";
                        echo "  $base/$path...\n";
                        curl_setopt($ch, CURLOPT_POST, 0);
                        curl_setopt($ch, CURLOPT_URL, "$base/$path");
                        $contents = curl_exec($ch);
                        file_put_contents("$pages/$path", $contents);
                    }
                }
            }
        }
    }
?>

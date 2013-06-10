<?php
    require 'config.php';

    db_connect();

    ini_set("display_errors", TRUE);

    // path to scraped pages
    $PAGES = dirname(__FILE__) . "/pages";

    // tables involved in import
    $TABLES = array("Qcourses", "Qinstructors", "Qcomments");

    // iterate over years (note that Q interprets year n as Fall n plus Spring n+1)
    foreach (array("2012", "2011", "2010", "2009", "2008", "2007", "2006") as $year)
    {
        // iterate over terms (1 == Fall, 2 == Spring)
        foreach (array("1", "2") as $term)
        {
            echo "$year $term...\n";

            // prepare directory
            $pages = "$PAGES/$year/$term";

            // get list of courses for this year/term
            $path = "list?yearterm={$year}_{$term}";

            // TEMP
            /*
            if (!file_exists("$pages/$path"))
                continue;
            */

            $contents = file_get_contents("$pages/$path");
            echo "$pages/$path...\n";

            // get departments
            preg_match_all('/list\?dept=(.*?)#/', $contents, $matches);
            $departments = array_values(array_unique($matches[1]));

            // get each department's courses
            foreach ($departments as $dept)
            {
                echo " $dept...\n";

                // get list of courses
                $path = "guide_dept?dept=" . urlencode($dept) . "&term={$term}&year={$year}";

                // TEMP
                /*
                if (!file_exists("$pages/$path"))
                    continue;
                */

                $contents = file_get_contents("$pages/$path");
                echo " $pages/$path...\n";

                // parse list
                $n = preg_match_all('{<a href="new_course_summary.html\?course_id=(\d+?)">(.+?) (\d*)(.*?):\s+(.+?)</a>}', $contents, $matches);
                $courses = $matches;
                for ($i = 0; $i < $n; $i++)
                {
                    // parse fields
                    $course_id = $courses[1][$i];
                    $code = $courses[2][$i];
                    $num_int = $courses[3][$i];
                    $num_char = $courses[4][$i];
                    $title = $courses[5][$i];

                    // courses.number 
                    $number = htmlspecialchars_decode($code) . " {$num_int}{$num_char}";
                    echo "  $number: $title...\n";

                    // Course Eval. Summary
                    $path = "new_course_summary.html?course_id={$course_id}";
                    
                    // TEMP 
                    /*
                    if (!file_exists("$pages/$path"))
                        continue;
                    */

                    echo "  $pages/$path...\n";
                    $contents = file_get_contents("$pages/$path");
                    $contents = preg_replace('{xmlns="http://www.w3.org/1999/xhtml"}', "", $contents);
                    $dom = simplexml_load_string($contents);

                    // Enrollment, Evaluations
                    if ($nodes = $dom->xpath("//div[@id='summaryStats']"))
                    {
                        $Enrollment = (preg_match("/Enrollment:\s+(\d+)/", $nodes[0], $matches)) ? $matches[1] : FALSE;
                        $Evaluations = (preg_match("/Evaluations:\s+(\d+)/", $nodes[0], $matches)) ? $matches[1] : FALSE;
                        $ResponseRate = (preg_match("/Response Rate:\s+(\d*\.?\d*)/", $nodes[0], $matches)) ? $matches[1] : FALSE;
                    }
                    else
                        $Enrollment = $Evaluations = $ResponseRate = FALSE;

                    // Course Overall, Materials, Assignments, Feedback, Section
                    foreach (array("Course Overall", "Materials", "Assignments", "Feedback", "Section") as $field)
                    {
                        $var = preg_replace("/\s+/", "", $field);
                        if ($nodes = $dom->xpath("//table[@class='graphReport']/tr[td[1]/strong = '$field']/td[3]/div[1]/img/@alt"))
                            $$var = $nodes[0];
                        else
                            $$var = FALSE;
                        echo "$field: {$$var}\n";
                    }

                    // Workload
                    if ($nodes = $dom->xpath("//table[@class='graphReport']/tr[td[1]/strong = 'Workload (hours per week)']/td[3]/div[1]/img/@alt"))
                        $Workload = $nodes[0];
                    else
                        $Workload = FALSE;
                    echo "Workload: $Workload\n";

                    // Difficulty
                    if ($nodes = $dom->xpath("//table[@class='graphReport']/tr[td[1]/strong = 'Difficulty']/td[1]/ul/li[2]"))
                        $Difficulty = (preg_match("/Mean:\s+(\d*\.?\d*)/", $nodes[0], $matches)) ? $matches[1] : FALSE;
                    else
                        $Difficulty = FALSE;
                    echo "Difficulty: $Difficulty\n";

                    // Would You Recommend
                    if ($nodes = $dom->xpath("//table[@class='graphReport']/tr[td[1]/strong = 'Would You Recommend']/td[1]/ul/li[2]"))
                        $WouldYouRecommend = (preg_match("/Mean:\s+(\d*\.?\d*)/", $nodes[0], $matches)) ? $matches[1] : FALSE;
                    else
                        $WouldYouRecommend = FALSE;
                    echo "WouldYouRecommend: $WouldYouRecommend\n";

                    // find catalog number
                    $sql = sprintf("SELECT cat_num FROM courses WHERE CONCAT(field, ' ', number) = '%s' GROUP BY number, cat_num",
                     mysql_real_escape_string($number));
                    $result = mysql_query($sql);
                    if (mysql_num_rows($result) == 1)
                    {
                        $row = mysql_fetch_row($result);
                        $cat_num = $row[0];
                    }
                    else if (mysql_num_rows($result) > 1)
                    {
                        $cat_num = "";
                        echo "MULTIPLE cat_num: $number\n";
                    }
                    else if (@$QMAP[$nickname])
                    {
                        $cat_num = $QMAP[$nickname];
                        echo "USED QMAP FOR $nickname\n";
                    }
                    else
                    {
                        $cat_num = "";
                        echo "NOT IN DB: $number\n";
                        continue;
                    }

                    // INSERT
                    $sql = sprintf("INSERT INTO Qcourses (course_id, number, cat_num, year, term, enrollment, evaluations, response_rate, course_overall, materials, assignments, feedback, section, workload, difficulty, would_you_recommend) " .
                     "VALUES('%s', '%s', '%s', '%s', '%s', %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
                     mysql_real_escape_string($course_id),
                     mysql_real_escape_string($number),
                     mysql_real_escape_string($cat_num),
                     mysql_real_escape_string($year),
                     mysql_real_escape_string($term),
                     ($Enrollment) ? "'" . mysql_real_escape_string($Enrollment) . "'" : "NULL",
                     ($Evaluations) ? "'" . mysql_real_escape_string($Evaluations) . "'" : "NULL",
                     ($ResponseRate) ? "'" . mysql_real_escape_string($ResponseRate) . "'" : "NULL",
                     ($CourseOverall) ? "'" . mysql_real_escape_string($CourseOverall) . "'" : "NULL",
                     ($Materials) ? "'" . mysql_real_escape_string($Materials) . "'" : "NULL",
                     ($Assignments) ? "'" . mysql_real_escape_string($Assignments) . "'" : "NULL",
                     ($Feedback) ? "'" . mysql_real_escape_string($Feedback) . "'" : "NULL",
                     ($Section) ? "'" . mysql_real_escape_string($Section) . "'" : "NULL",
                     ($Workload) ? "'" . mysql_real_escape_string($Workload) . "'" : "NULL",
                     ($Difficulty) ? "'" . mysql_real_escape_string($Difficulty) . "'" : "NULL",
                     ($WouldYouRecommend) ? "'" . mysql_real_escape_string($WouldYouRecommend) . "'" : "NULL");
                    if (!mysql_query($sql))
                        echo "ERROR: " . mysql_error();

                    // What would you like to tell future students about this class?
                    $path = "view_comments.html?course_id={$course_id}&qid=1487&sect_num=";
                    if (file_exists("$pages/$path"))
                    {

                        echo "  $pages/$path...\n";
                        $contents = file_get_contents("$pages/$path");
                        $contents = preg_replace('{xmlns="http://www.w3.org/1999/xhtml"}', "", $contents);
                        $contents = preg_replace("/&#8;/", "", $contents); // weirdness in someone's comment
                        $dom = simplexml_load_string($contents);
                        if ($nodes = $dom->xpath("//div[@class='response']/p"))
                        {
                            foreach ($nodes as $node)
                            {
                                if ($node = trim($node))
                                {
                                    $sql = sprintf("INSERT INTO Qcomments (number, cat_num, year, term, comment) VALUES('%s', '%s', '%s', '%s', '%s')",
                                     mysql_real_escape_string($number),
                                     mysql_real_escape_string($cat_num),
                                     mysql_real_escape_string($year),
                                     mysql_real_escape_string($term),
                                     mysql_real_escape_string($node));
                                    if (!mysql_query($sql))
                                        echo "ERROR: $sql\n";
                                }
                            }
                        }
                    }
                    else
                    {
                        echo "DON'T TELL: $number, $cat_num, $year, $term\n";
                    }

                    // save Instructor Eval. Summary
                    $path = "inst-tf_summary.html?course_id={$course_id}";

                    // TEMP
                    if (!file_exists("$pages/$path"))
                        continue;

                    echo "  $pages/$path...\n";
                    $contents = file_get_contents("$pages/$path");
                    $contents = preg_replace('{xmlns="http://www.w3.org/1999/xhtml"}', "", $contents);
                    $dom = simplexml_load_string($contents);
                    if ($values = $dom->xpath("//select[@name='current_instructor_or_tf_huid_param']/option/@value"))
                    {
                        foreach ($values as $current_instructor_or_tf_huid_param)
                        {
                            // get faculty's ID
                            if (preg_match("/^(.+):/", $current_instructor_or_tf_huid_param, $matches))
                            {
                                $id = $matches[1];

                                // skip TFs
                                if (preg_match("/^tfs-/", $id))
                                    break;

                                // get evaluation
                                $path = "inst-tf_summary.html?current_instructor_or_tf_huid_param=" . urlencode($current_instructor_or_tf_huid_param) . "&course_id={$course_id}&current_tab=2&benchmark_type=no&sect_num=";

                                // TEMP
                                /*
                                if (!file_exists("$pages/$path"))
                                    continue;
                                */

                                echo "  $pages/$path...\n";
                                $contents = file_get_contents("$pages/$path");
                                $contents = preg_replace('{xmlns="http://www.w3.org/1999/xhtml"}', "", $contents);
                                $dom = simplexml_load_string($contents);

                                // get name
                                $name = trim(array_shift($dom->xpath("//h3[@class='instructor']")));
                                list($last, $first) = preg_split("/,/", $name, 2);
                                $last = trim($last);
                                $first = trim($first);

                                // get values
                                foreach (array("Instructor Overall", "Effective Lectures or Presentations", "Accessible Outside Class", "Generates Enthusiasm", "Facilitates Discussion & Encourages Participation", "Gives Useful Feedback", "Returns Assignments in Timely Fashion") as $field)
                                {
                                    $var = preg_replace("/[^\w]/", "", $field);
                                    if ($nodes = $dom->xpath("//table[@class='graphReport']/tr[td[1]/strong = '$field']/td[3]/div[1]/img/@alt"))
                                        $$var = $nodes[0];
                                    else if ($field == "Instructor Overall" && ($nodes = $dom->xpath("//table[@class='graphReport']/tr[td[1]/strong = 'Person Overall']/td[3]/div[1]/img/@alt")))
                                        $$var = $nodes[0];
                                    else
                                        $$var = FALSE;
                                    echo "$field: {$$var}\n";
                                }

                                // TEMP
                                if ($first == "Zachary" && $last == "Sifuentes")
                                    continue;

                                // INSERT
                                $sql = sprintf("INSERT INTO Qinstructors (number, cat_num, year, term, id, first, last, instructor_overall, effective_lectures_or_presentations, accessible_outside_class, generates_enthusiasm, facilitates_discussion_encourages_participation, gives_useful_feedback, returns_assignments_in_timely_fashion) " . 
                                 "VALUES('%s', '%s', '%s', '%s', '%s', '%s', '%s', %s, %s, %s, %s, %s, %s, %s)",
                                 mysql_real_escape_string($number),
                                 mysql_real_escape_string($cat_num),
                                 mysql_real_escape_string($year),
                                 mysql_real_escape_string($term),
                                 mysql_real_escape_string($id),
                                 mysql_real_escape_string($first),
                                 mysql_real_escape_string($last),
                                 ($InstructorOverall) ? "'" . mysql_real_escape_string($InstructorOverall) . "'" : "NULL",
                                 ($EffectiveLecturesorPresentations) ? "'" . mysql_real_escape_string($EffectiveLecturesorPresentations) . "'" : "NULL",
                                 ($AccessibleOutsideClass) ? "'" . mysql_real_escape_string($AccessibleOutsideClass) . "'" : "NULL",
                                 ($GeneratesEnthusiasm) ? "'" . mysql_real_escape_string($GeneratesEnthusiasm) . "'" : "NULL",
                                 ($FacilitatesDiscussionEncouragesParticipation) ? "'" . mysql_real_escape_string($FacilitatesDiscussionEncouragesParticipation) . "'" : "NULL",
                                 ($GivesUsefulFeedback) ? "'" . mysql_real_escape_string($GivesUsefulFeedback) . "'" : "NULL",
                                 ($ReturnsAssignmentsinTimelyFashion) ? "'" . mysql_real_escape_string($ReturnsAssignmentsinTimelyFashion) . "'" : "NULL");
                                if (!mysql_query($sql))
                                    echo "ERROR: " . mysql_error();
                            }
                        }
                    }
                }
            }
        }
    }

?>

DROP TABLE IF EXISTS `courses`;

CREATE TABLE `courses` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `cat_num` varchar(255) DEFAULT NULL,
  `term` varchar(255) DEFAULT NULL,
  `bracketed` tinyint(1) DEFAULT NULL,
  `field` varchar(255) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `faculty` varchar(255) DEFAULT NULL,
  `description` text,
  `prerequisites` text,
  `notes` text,
  `meetings` varchar(255) DEFAULT NULL,
  `building` varchar(255) DEFAULT NULL,
  `room` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table faculty
# ------------------------------------------------------------

DROP TABLE IF EXISTS `faculty`;

CREATE TABLE `faculty` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `first` varchar(255) DEFAULT NULL,
  `middle` varchar(255) DEFAULT NULL,
  `last` varchar(255) DEFAULT NULL,
  `suffix` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table fields
# ------------------------------------------------------------

DROP TABLE IF EXISTS `fields`;

CREATE TABLE `fields` (
  `id` varchar(255) NOT NULL DEFAULT '',
  `name` varchar(255) DEFAULT NULL,
  `unique_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`unique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Qcomments
# ------------------------------------------------------------

DROP TABLE IF EXISTS `Qcomments`;

CREATE TABLE `Qcomments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(255) DEFAULT NULL,
  `cat_num` varchar(255) DEFAULT NULL,
  `year` varchar(255) DEFAULT NULL,
  `term` varchar(255) DEFAULT NULL,
  `comment` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Qcourses
# ------------------------------------------------------------

DROP TABLE IF EXISTS `Qcourses`;

CREATE TABLE `Qcourses` (
  `course_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(255) DEFAULT NULL,
  `cat_num` varchar(255) DEFAULT NULL,
  `year` varchar(255) DEFAULT NULL,
  `term` varchar(255) DEFAULT NULL,
  `enrollment` varchar(255) DEFAULT NULL,
  `response_rate` varchar(255) DEFAULT NULL,
  `course_overall` varchar(255) DEFAULT NULL,
  `materials` varchar(255) DEFAULT NULL,
  `assignments` varchar(255) DEFAULT NULL,
  `feedback` varchar(255) DEFAULT NULL,
  `section` varchar(255) DEFAULT NULL,
  `workload` varchar(255) DEFAULT NULL,
  `difficulty` varchar(255) DEFAULT NULL,
  `would_you_recommend` varchar(255) DEFAULT NULL,
  `evaluations` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;



# Dump of table Qinstructors
# ------------------------------------------------------------

DROP TABLE IF EXISTS `Qinstructors`;

CREATE TABLE `Qinstructors` (
  `id_unique` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id` varchar(255) NOT NULL DEFAULT '',
  `cat_num` varchar(255) DEFAULT NULL,
  `year` varchar(255) DEFAULT NULL,
  `term` varchar(255) DEFAULT NULL,
  `first` varchar(255) DEFAULT NULL,
  `last` varchar(255) DEFAULT NULL,
  `instructor_overall` varchar(255) DEFAULT NULL,
  `effective_lectures_or_presentations` varchar(255) DEFAULT NULL,
  `accessible_outside_class` varchar(255) DEFAULT NULL,
  `generates_enthusiasm` varchar(255) DEFAULT NULL,
  `facilitates_discussion_encourages_participation` varchar(255) DEFAULT NULL,
  `gives_useful_feedback` varchar(255) DEFAULT NULL,
  `returns_assignments_in_timely_fashion` varchar(255) DEFAULT NULL,
  `number` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_unique`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
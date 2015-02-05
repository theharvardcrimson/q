# q guide scraper

### instructions

#### config
Set the following environment variables to point PHP to your MySQL database:

* `Q_SCRAPER_DATABASE_HOST`
* `Q_SCRAPER_DATABASE_USER`
* `Q_SCRAPER_DATABASE_PASSWORD`
* `Q_SCRAPER_DATABASE_NAME`

#### install tables

Execute `tables.sql` on your database. **Warning: this will `DROP` existing
tables**. Don't run this scraper on a production database.

    $ mysql -h HOSTNAME -u USER -p PASSWORD DB_NAME < tables.sql

If you've configured as per above, save yourself some typing:

    $ mysql -u"$Q_SCRAPER_DATABASE_USER" -p"$Q_SCRAPER_DATABASE_PASSWORD" -h"$Q_SCRAPER_DATABASE_HOST" "$Q_SCRAPER_DATABASE_NAME" < tables.sql

#### import courses

Run `import_courses.php` to import courses, faculty, and academic fields from
the [CS50 Courses API][courses api]. The Q scraper accesses these tables to link
Q guide IDs with course catalog IDs.

    $ php import_courses.php

#### scrape q

Run `scrape_q.php` to crawl the [Q website][q] and download all relevant HTML
into a `pages/` directory. The semesters to download are hardcoded in near the
top of the file.

Since the Q guide requires authentication, you'll need to login with your PIN to 
generate a session cookie. Using your browser's web inspector, obtain the value
of the `JSESSIONID` cookie after you've authenticated and pass it to the script:

    $ php scrape_q.php COOKIEVAL4F8D49AC

#### import q

Wait for a few billion hours, and the Q will have downloaded! Now run
`import_q.php` to parse the HTML in `pages/` and insert it into the database.

    $ php import_q.php

### todos

* Retry on timeout, rather than plowing through the rest of the script.
* Rewrite this in Python. Or any language other than PHP, really.
* Refactor so we can multithread. The Q guide is *slow*, and frequently breaks.
  A better architecture would use [gevent][gevent] or multithreading to pull
  links off a global Redis queue. Each coroutine would pull a URL off the queue,
  parse the HTML, insert any relevant information intto the database, and add
  any additional URLS to scrape to the queue.

### warning

There may be legal issues releasing Q data to non-Harvard affiliates.

### credits

Original PHP scraper written by David Malan.

[courses api]: https://manual.cs50.net/HarvardCourses_API
[q]: https://webapps.fas.harvard.edu/course_evaluation_reports/fas/
[gevent]: http://www.gevent.org/

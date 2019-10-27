# What is this?

Universal sitemap generator tool.

# Installation and configuration.

A config file is required for the script to function. It should contain a list of several static data 
files (plain/text or text/csv).

‘Production’ folder has an example list of required files:

- config.sitemap + db.example.ini – a config file example.
data.countries.txt – a list of pages for different countries (text file)
data.staticpages.txt – a list of static pages (text file)
sitemap_generator.php – a complete script for sitemap generation.

# Installation.

All the sources are located in /sources and are assembled into sitemap_generator.php using a build-script
/bin/bash ./build.sitemap_generator.sh
The assembled file can be placed anywhere and launched without specifying the path for interpreter.
chmod +x ./production/sitemap_generator.php
mv ./production/sitemap_generator.php /usr/local/bin
sitemap_generator.php –help

# Launching.

Now we can launch it from the console, with the path to config file as an argument:
 /usr/local/bin/sitemap_generator.php /path/fo/my_sitemap.ini
…Or, from current directory:
/usr/local/bin/sitemap_generator.php my_sitemap.ini

# Configuration.

DB access configs and the sitemap-building instructions are set in ini files. The config files 
are assumed to be inaccessible to the outsiders,  but I recommend creating a dedicated 
sitemap-generating user with ‘SELECT’ rights only, and use these values in config files.

```
CREATE USER ‘sitemapcreator’@’localhost’ IDENTIFIED BY ‘sitemappassword’;
GRANT SELECT ON database.* TO ‘sitemapcreator’@’localhost’;
FLUSH PRIVELEGES;
```

The config file contains at least three sections:
-	Global settings
-	DB connection settings
-	Sitemap-building instructions for specific type(s) of pages (one or several sections)

The details of the sections are as follows

## Global settings

This section is nessesary and without one it’s completely unclear what needs doing.
```
[___GLOBAL_SETTINGS___]
; [Required]
; URL of the site (including domain and final slash)
sitehref = 'http://www.example.com/'

; [Required]
; URL of the intermediate sitemap files (including domain and final slash)
sitemaps_href = 'http://www.example.com/sitemaps/'

; [Required]
; Sitemap file directory (write permissions required)
sitemaps_storage = '/var/www/example.com/sitemaps/'

; [Required]
; the path and the name for the primary (index) sitemap file. Setting just the name will cause the file to save to the current directory. (write permissions required)
sitemaps_mainindex = 'sitemap.xml'

; [Optional]
; URL number limit in the sitemap file. Default: 50.000.
limit_urls = 50000

; [Optional]
; maximum size for an uncompressed sitemap file. Default: 50.000.000 bites.
; the actual max size is 50mb according to the standard, but it’s recommended to use the value above due to specifics of the script
limit_bytes = 50000000

; [Optional]
; should gzip be used for compression? Default: TRUE.
; this is a global setting, can be overridden in a specific section.
use_gzip = 1

; [Optional]
; should the map file generation info be displayed? Default: TRUE.
logging = 1

; [Optional]
; Date format. Acceptable values:
; iso8601 - W3C Datetime format (2004-12-23T18:00:15+00:00)
; * YMD – date format of Y-m-d (2004-12-23), this is used by default (if this option isn’t set or has any other value)
date_format_type = 'iso8601'

; [Required]
; connection settings section suffix
; option can be skipped ONLY when all the other sections draw values from the files, and a warning will be displayed in this case.
db_section_suffix = 'DATABASE'
```

## Database connection settings section

The name of this section is ___GLOBAL_SETTINGS:<db_section_suffix>___. It can be skipped ONLY if all the other 
sections draw values from files.

```
[___GLOBAL_SETTINGS:DATABASE___]
; db driver type, currently only ‘mysql’ value is used.
driver   = 'mysql'

; hostname for db connection (typically localhost)
hostname = ''

; username for db connection (remember I suggested creating a dedicated user?)
username = ''

; password for db connection
password = ''

; db name
database = ''

; port (MySQL default is 3306, required regardless).
port     = 3306

•	Specific pages sitemap settings section(s)
The data used to build sitemap can be drawn from three sources:
-	Database
-	Text file
-	CSV-file (in development)
Depending on the source the details of the section will differ.
Section example: DB source.
; section name
[price]

; [Required]
; data source - sql, file, csv
source = 'sql'

; [Required]
; DB request for a count of elements used
sql_count_request = 'SELECT COUNT(id) AS cnt FROM price'

; [Required]
; the name of the request results field with the elements’ count
sql_count_value = 'cnt'

; [Required]
; DB request for all the nessesary elements.(LIMIT ... OFFSET ... aren’t set!)
sql_data_request = 'SELECT id, lastmod FROM price'

; [Required]
; the name of the request results field with the pages’ ID
sql_data_id = 'id'

; [Required]
; the name of the request results field with the date of last modification. Used for lastmod attribute in the sitemap-link.
; IMPORTANT: if there’s no such field available, use the value ‘NOW()’ to get the current timestamp.
sql_data_lastmod = 'lastmod'

; [Required]
; URL to the page from the site’s root (excluding domain)
; used as the mask for sprintf
url_location = 'price/%s.html'

; [Recommended]
; priority of pages in the section. Default: 0.5
url_priority = '0.5'

; [Recommended]	
; expected frequency of updates for pages in the section.
; allowed values: always, hourly, daily, weekly, monthly, yearly, never. Default: never
url_changefreq = 'daily'

; [Optional]
; root for the name of the file(s) with links to pages of the section 
; ex: for the root ‘price’ the files will have names like price-1, price-2 etc
; if the option is skipped or left empty, the files will use the name of the section
radical = 'price'

; [Optional]
; should gzip be used for compression in this section? Overrides the global value.
use_gzip = 0
```

Important case #1. Different requests.
The request can be different. For example.

`SELECT CONCAT('/page/xxx/', id) AS it, FORMAT_DATE(mask, lastdate) AS pld FROM price`

In this case the different  field names should be used (‘it’ and ‘pld’).
Important case #2. Different operators in the request.
While it’s possible to set the select parameters in options…
sql_count_request = 'SELECT COUNT(id) AS cnt FROM price WHERE price.actual = 1'
sql_data_request = 'SELECT id, lastmod FROM price WHERE price.actual = 1'

...a more efficient way would be to use VIEW. For example…

```
CREATE VIEW actual_price
AS SELECT id, lastmod FROM price WHERE price.actual = 1;
```

Accordingly, the parameters would be like this:

```
sql_count_request = 'SELECT COUNT(id) AS cnt FROM actual_price'
sql_data_request = 'SELECT id, lastmod FROM actual_price'
```

Important case #3. Sort order.

sql_data_request = 'SELECT id, lastmod FROM price ORDER BY lastmod DESC'
This is acceptable, but again VIEW usage is preferable.
	Section example: text file source. 
 [countries]
; [Required]
; data source
source = 'file'

; [Required]
; path to file with the data (stored row by row)
; Important: symbol '$' means the file is in the same directory the confid file is in.
; Otherwise the absolute path must is required (although, $/static/countries.txt is allowed)
filename = '$/countries.txt'

; [Required]
; URL to the page from the site root (excluding domain)
; used as mask for sprintf
url_location = 'countries/%s/'

; [Recommended]
; priority of pages in the section. Default: 0.5
url_priority = '0.5'

; [Recommended]
; expected frequency of updates for pages in the section.
; allowed values: always, hourly, daily, weekly, monthly, yearly, never. Default: never
url_changefreq = 'daily'

; the only currently allowed value. Using current timestamp for lastmod.
lastmod = 'NOW()'

; [Optional]
; root for the name of the file(s) with links to pages of the section 
; the file names will be countries-1, countries-2 etc (separator set in $GLOBAL_SETTINGS$)
radical = 'countries'

The config example has a [static] section, where the data source is defined as a file data.staticpages.txt. 
The empty row in the source file means the link for the root page needs to be generated.
	Section example: CSV source.
@todo: In development
	Appendix
It’s possible to restrict the processing of a specific section for debug purposes. For that the section body needs to have a line
enabled = 0

Any other value or the absence of the line is ignored.

•	TODO
-	CSV data source with named columns
-	Setting lastmod for pages/rows from file data source. Currently only NOW() is available.
-	Currently the root sitemap is stored in the same directory sitemap sections do. Is another behavior needed?
-	Currently if the wrapper SitemapFileSaver::push()does not recieve last modification date, it uses current timestamp. Does this behavior require changes?
-	Determine if individual  date_format_type for each section should be allowed.


•	Perfomance.
My home server (Gentoo/4.14.15 на GA-N3150N-D3V, CPU Celeron™ N3150 (1.6 GHz), PHP 7.1.13, HDD WD Blue 5400rpm in RAID1) with a test db with approx. 800k records takes about 45 seconds to fully process. 
Peak memory usage in this case is approx. 80mb.
•	Test data generation

There are two files in the /tests folder.

-	make_db_seed.ini.example – copy to make_db_seed.ini and set random db connection parameters.
-	make_seed.php – launch. Generate tables. Bear in mind that the old tables are deleted.

•	Links

-	Sitemap specification
-	XMLWriter library (Gentoo requires compilation with +xmlwriter flag)
-	W3c Datetime Specification

•	Compatibility

Requires PHP 7.0 and above.

•	License.

Use anywhere however you want, provided you notify the author (me). Forks, pull-requests and translations are welcome.

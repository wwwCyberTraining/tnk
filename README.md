Tanakh Navigation Kit
=====================

A set of tools for navigating in the TNK (Tora Neviim Ktuvim)

## Requirements
* Apache 2+
* PHP 5.2+
* MySQL 5+

## Installation

A. Clone the repository, e.g.:

	git clone https://github.com/erelsgl/tnk.git
	
B. In your Apache2 configuration, create an alias "/tnk" that points to the "web" folder. e.g.:

	sudo ln -s [full-path-to-repository]/web /var/www/tnk

C. Run the create script:

	php [full-path-to-repository]/admin/create.php

Enter your MySQL username and password, and a name of a database for creating required tables.

D. Verify that there are no errors in the output.

## Use

Go to http://localhost/tnk/findpsuq.php

## Code
* **script/findpsuq_lib.php** - functions for searching regular expressions in the Tanakh verses. 
* **script/niqud.php** - functions for adding dots ("niqud") to Tanakh verses.

## License
LGPL

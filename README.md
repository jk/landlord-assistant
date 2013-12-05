# Landlord Assistant
## Installation
* Create a stats.sqlite database file
* Setup two crojobs
```bash
23 */5 * * * * cd /var/www/landlord; /usr/local/bin/php -f crawler_contestants.php > /dev/null 2>&1
24 */7 * * * * cd /var/www/landlord; /usr/local/bin/php -f crawler.php > /dev/null 2>&1
```
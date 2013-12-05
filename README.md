# Landlord Assistant
## Attention
I observed that the heavy scraping I did on a fixes-IP server resulted in blocking my IP by the landlord folks. I'm not liable to anything what derives of using these scripts. Do what ever your want with it.

And yes, the landlord API is a frickin' security nightmare. They transfer a 3rd-party (Foursqare) OAuth token over an unsecure channel (i.e. HTTP).

## Installation
* Create a stats.sqlite database file
* Setup two crojobs

```sh
23 */5 * * * * cd /var/www/landlord; /usr/local/bin/php -f crawler_contestants.php > /dev/null 2>&1
24 */7 * * * * cd /var/www/landlord; /usr/local/bin/php -f crawler.php > /dev/null 2>&1
```

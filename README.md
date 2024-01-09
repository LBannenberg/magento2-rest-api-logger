# Corrivate_RestApiLogger

## Goal
Get logging visibility on the use of the Magento REST API:
* Which requests are made, by which IP & user agent
* Response code & status
* Request & Response payloads

At HYPR we have found this very useful for settling arguments with external integrators: "no, we can see that you sent *this* data".

## IMPORTANT!
Improper use of logging can expose security- and personally-sensitive data.

This module is a "power tool" for debugging API issues, it is not entirely possible to prevent this. 
Being careful what you log is YOUR responsibility. Setting this module to "always on" is not a good idea.

The module has several filters that allow you to narrow down the scope of what you're logging.

## Installation

```bash
composer require corrivate/magento2-rest-api-logger
bin/magento module:enable Corrivate_RestApiLogger
bin/magento setup:upgrade
```

## Configuration

You can configure the logger in Admin > Stores > Configuration > Services > REST API Logger.

## Security

The module will not log the body of incoming auth requests. If headers are logged, credentials will be hashed.


## Credits

![HYPR](src/docs/hypershop_b_v__logo.jpeg)

This module was originally developed at HYPR. With their permission it has been open-sourced.

The design of the module builds on previous loggers, in particular:

* http://www.wishusucess.com/magento-2-api-log-checker/
* https://blog.syncitgroup.com/advanced-logging-of-the-magento-2-services/
* https://github.com/vladflonta/magento2-webapi-log



## Corrivate
(en.wiktionary.org)

Etymology

From Latin *corrivatus*, past participle of *corrivare* ("to corrivate").

### Verb

**corrivate** (*third-person singular simple present* **corrivates**, *present participle* **corrivating**, *simple past and past participle* **corrivated**)

(*obsolete*) To cause to flow together, as water drawn from several streams. 


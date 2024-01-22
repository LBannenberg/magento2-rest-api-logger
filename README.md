# Corrivate_RestApiLogger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/corrivate/magento2-rest-api-logger?color=blue)](https://packagist.org/packages/corrivate/magento2-rest-api-logger)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)

## Goal
Get logging visibility on the use of the Magento REST API:
* Which requests are made, by which IP & user agent, to what endpoints?
* What is in those requests?
* What are they getting as responses?

At HYPR we have found this very useful for settling arguments with external integrators: "no, we can see that you sent *this* data". 

## IMPORTANT!
Improper use of logging can expose security- and personally-sensitive data.

This module is a "power tool" for debugging API issues, it is not entirely possible to prevent this. Being careful what you log is YOUR responsibility. Setting this module to "always on" is not a good idea.

The module has several filters that allow you to narrow down the scope of what you're logging.

## Installation

```bash
composer require corrivate/magento2-rest-api-logger
bin/magento module:enable Corrivate_RestApiLogger
bin/magento setup:upgrade
```

## Configuration

You can configure the logger in Admin > Stores > Configuration > Services > REST API Logger.

The following configurations are available:
* Enable/Disable the module
* Enable/Disable safer mode (which censors some privacy-sensitive payloads)
* Include request/response headers
* Which HTTP methods to log
* Which Magento API endpoints to log, or exclude from logging
* The ability to construct fine-grained custom filters

## Security

The module will not log the body of incoming auth requests. If headers are logged, credentials will be hashed.


## Credits

![HYPR](docs/hypershop_b_v__logo.jpeg)

This module was originally developed at HYPR. With their permission it has been open-sourced.

The design of the module builds on previous loggers, in particular  https://github.com/vladflonta/magento2-webapi-log ; however, that module appears to be no longer actively supported.



## Corrivate
(en.wiktionary.org)

Etymology

From Latin *corrivatus*, past participle of *corrivare* ("to corrivate").

### Verb

**corrivate** (*third-person singular simple present* **corrivates**, *present participle* **corrivating**, *simple past and past participle* **corrivated**)

(*obsolete*) To cause to flow together, as water drawn from several streams. 


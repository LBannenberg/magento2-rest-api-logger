# Corrivate_RestApiLogger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/corrivate/magento2-rest-api-logger?color=blue)](https://packagist.org/packages/corrivate/magento2-rest-api-logger)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)

## Goal

Get logging visibility on the use of the Magento REST API:
* Which requests are made, by which IP & user agent, to what endpoints?
* What is in those requests?
* What are they getting as responses?

At HYPR we've found this to be quite useful, because you often run into questions such as:
* Which external integration interacted with this product's data?
* The third party warehouse claims to have set product qty to X, but it's showing as Y. Have they really set it to X?
* Product image roles are configured strangely. Has someone used the wrong store code when updating them through the API?

## IMPORTANT!
Improper use of logging can expose security- and personally-sensitive data.

This module is a "power tool" for debugging API issues, it is not entirely possible to prevent this. Being careful what you log is YOUR responsibility. Setting this module to "always on" is not a good idea.

The module has several filters that allow you to narrow down the scope of what you're logging.

## Installation

```bash
composer require corrivate/magento2-rest-api-logger
bin/magento module:enable Corrivate_RestApiLogger
bin/magento setup:di:compile
```

## Configuration

Most of this module's power is in the configuration. That's where you decide what kind of requests you want to log.

You can configure the logger in Admin > Stores > Configuration > Services > REST API Logger.

The following configurations are available:
* Enable/Disable logging
* Enable/Disable safer mode (which censors some privacy-sensitive payloads)
* Include request/response headers
* Setting up filters

### Configure what to filter on

  * HTTP method (GET, POST, PUT, DELETE)
  * API endpoint (https://developer.adobe.com/commerce/webapi/rest/quick-reference/)
  * Route: can be used for query string arguments, or if you want all endpoints relating to products for example
  * Requester's IP address
  * Requester's user agent
  * Text in the request body
  * HTTP status of the response
  * Text in the response body

### Configure consequences when a filter matches

  * **Forbid** logging this request/response/both. If this filter matches it overrules all other filters.
  * **Require** that this specific filter matches, otherwise don't log the request/response/both. If "require" filters are specified, **all** of them need to match to log this.
  * **Allow** logging this if the filter matches. If any "allow" filters are configured, at least **one** of them has to match, but not all of them. 
  * **Censor** the body of the request/response/both, but log that it took place and information about who sent it, response codes etc.
  * Whenever a filter matches you can also add **tags**. This is useful for example to tag all requests coming from a particular IP address so that you know it came from company X. They could also be used for later post-processing of the logs.

## Security

The module will not log the body of incoming auth requests. If headers are logged, credentials will be hashed.

When "safer mode" is active in the configuration, the following additional filters are applied to reduce the risk of logging sensitive data:

* Header logging is disabled.
* request body contains "street" => censor both
* response body contains "street" => censor response
* Request URLs containing these parts => censor both
  * /V1/applepay
  * /V1/braintree
  * /V1/carts
  * /V1/creditmemo
  * /V1/customers
  * /V1/guest-carts
  * /V1/inventory/get-latlng-from-address
  * /V1/inventory/get-latslngs-from-address
  * /V1/invoices
  * /V1/orders
  * /V1/shipment
  * /V1/tfa



## Credits

![HYPR](docs/hypershop_b_v__logo.jpeg)

This module was originally created at HYPR. With their permission it has been open-sourced and been developed further.

The design of the module builds on previous loggers, in particular  https://github.com/vladflonta/magento2-webapi-log ; however, that module appears to be no longer actively supported.



## Corrivate
(en.wiktionary.org)

Etymology

From Latin *corrivatus*, past participle of *corrivare* ("to corrivate").

### Verb

**corrivate** (*third-person singular simple present* **corrivates**, *present participle* **corrivating**, *simple past and past participle* **corrivated**)

(*obsolete*) To cause to flow together, as water drawn from several streams. 


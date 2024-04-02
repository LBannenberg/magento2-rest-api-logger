# Changelog

## 0.7.1
- Fix type error in reading accept header

## 0.7.0
- Configuration revised
  - Should now be in stable form for the long term
  - Splits the filters into Request and Response tab
  - Removes the separate endpoint tab
  - Removes chosen.js which was flaky
  - Splits filters into separate tables that can take advantage of more specific source models
- Endpoint filters improved
  - Handle confusion when there's overlap between routes for endpoints, such as `GET /cmsPage/search` and `GET /cmsPage/:pageId`
  - Some efficiencies for handling larger amounts of endpoint filters, needed for safer mode
- Support for logging XML content type

## 0.6.1
- Fixed a TypeError

## 0.6.0
Endpoint improvements
- Make them work
- Also distinguish by HTTP method

## 0.5.0
Revision of filters
- Treat filter configs as objects, to make it easier to merge different configurations in a way the filter processor doesn't need to know about.  
- Move method filters into dynamic rows to enable easier and more granular use
- Make endpoint filters more customizable
- Enable adding tags to log entries (useful for annotating IP addresses for example)

## 0.4.0
Focus on code cleanup
- Moved assisting classes into more descriptive namespaces than "helper"
- Renamed filter classes
- Add tentative php8.3 support
- Use semantic Magento composer module versions
- List all dependencies in module.xml / composer.json
- Apply Magento2 coding standard (within reason) 

## 0.3.0
- improve README
- improve configuration UI
- add service include/exclude filters

## 0.2.0
- add unit tests to the filter

## 0.1.4
- simplify filter implementation
- fix an error in the required filter

## 0.1.3
- fix autoload path

## 0.1.2
- move to git-only versioning, drop version from composer.json

## 0.1.1
- Fix di.xml illegal attribute

## 0.1.0
Initial release

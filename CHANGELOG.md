# Changelog

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

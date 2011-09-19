CHANGELOG for 2.1.x
===================

This changelog references the relevant changes (bug and security fixes) done
in 2.1 minor versions.

To get the diff for a specific change, go to https://github.com/symfony/symfony/commit/XXX where XXX is the change hash
To get the diff between two versions, go to https://github.com/symfony/symfony/compare/v2.1.0...v2.1.1

2.1.0
-----

### FrameworkBundle

 * changed the default profiler storage to use the filesystem instead of SQLite
 * added support for placeholders in route defaults and requirements (replaced by the value set in the service container)

### ClassLoader

 * added support for loading globally-installed PEAR packages

### Finder

 * Finder::exclude() now supports an array of directories as an argument

### HttpFoundation

 * made FileBinaryMimeTypeGuesser command configurable
 * added Request::getUser() and Request::getPassword()
 * added support for the PATCH method in Request
 * removed the ContentTypeMimeTypeGuesser class as it is deprecated and never used on PHP 5.3
 * added ResponseHeaderBag::makeDisposition() (implements RFC 6266)
 * made mimetype to extension conversion configurable

### HttpKernel

 * added a File-based profiler storage
 * added a MongoDB-based profiler storage

### Translation

 * added support for more than one fallback locale
 * added support for translations in ResourceBundles
 * added support for extracting translation messages from templates (Twig and PHP)
 * added dumpers for translation catalogs
 * added support for QT translations

### Validator

 * improved the ImageValidator with min width, max width, min height, and max height constraints
 * added support for MIME with wildcard in FileValidator

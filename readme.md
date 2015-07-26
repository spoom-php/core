Framework
======
The framework is a simple class with some constants, internal log/reporting level handling and an extension search feature. The main function is the library
importer with autoload support.

In your bootstrap file you must call the `Framework::setup()` to initialize the framework (autoloader, default levels and some checks). For the method you can
specify a main function that runs after the initialization. The default *index.php* has an example for this. 

# Importer
The library import is accessible with the `\Framework::import()` method. This method use only one parameter that MUST be the fully qualified name of the class
that needs to be imported. This method's result is true if the given class is exists after the import. The importer main purpose is to load extension's library
classes but it also supports custom namespace sources that is compatible with PSR-4. Example for extensions:

  `\<Package>(\<Name>(\<Feature>)?)?(\<Namespaces>)*\<Class(Fragment)*>`

This "defines" the following directory structure and filename: 

  */extension/\<package\>(-\<name\>)(-\<feature\>)?)?/library/(\<namespaces\>/)\*\<class\>(\<fragment\>)\*.php*
  
Directory structure (including the filename) SHOULD be lowercase, and the namespaces for that MAY have ucfirst. The importer also supports the mixed lettercase
structures as well (in case-sensitive environment), but not recommended. Examples:

 The `\SamplE\FoO\bAr\ClassName` class can be in:
  - */extension/sample/library/foo/bar/classname.php*
  - */extension/sample/library/foo/bar/ClassName.php*
  - */extension/sample/library/FoO/bAr/ClassName.php*
  - */custom/sample/foo/bar/classname.php*
  - */custom/sample/foo/bar/ClassName.php*
  - */custom/samplE/FoO/bAr/ClassName.php*

## Class nesting
The fragment parts can be ignored in the file name, so you could put more class in one file. For example: Define a trait
and an interface for it may be in the same file, or custom exception classes used by only one class... etc.
This nested classes' name MUST be started with the file name that contains the class, and the remain part MUST be separated with
an uppercase letter. For example the `\Sample\Foo\ClassNameException` class' files can be:
 
  - */extension/sample/library/foo/class.php*
  - */extension/sample/library/foo/classname.php*
  - */extension/sample/library/foo/classnameexception.php*
  
## Custom roots
The importer supports custom roots for namespaces. Custom root can be added with the `Framework::connect()` method where you MUST specify the namespace and this
namespace's root path. For example:

`````php
 \Framework::connect('\Sample\Foo\', '/custom/location');
 // after that the '\Sample\Foo\Bar\Class' will be loaded from '/custom/location/bar/class.php'
`````

The nesting and letter case supports also applied to the custom roots. **The custom root lookup is executed before the standard extension library lookup, so be
careful of the namespace you connect.**

# Standards
**This will apply to the framework and all extensions either\!**
The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD", "SHOULD NOT", "RECOMMENDED", "MAY", and
"OPTIONAL" in this document are to be interpreted as described in [RFC 2119](http://tools.ietf.org/html/rfc2119).

## Coding standard
Code MUST follow [PSR-1](http://www.php-fig.org/psr/psr-1/ "PSR-1") guide with the following modifications: 

 - Autoloading has support for multiple classes in a file
 - Property names MUST be in $under\_score format

## Exception handling
 
 - The exceptions in the code SHOULD never be returned by a method or function. You MUST throw an exception, or store it in 
   a class `->exception` or `->collector` property\!

## Coding style
The code MUST follow [PSR-2](http://www.php-fig.org/psr/psr-2/ "PSR-2") guide with the following modifications and extras:

 - Code MUST use 2 spaces for indenting instead of tabs
 - Opening braces for classes MUST go on the same line, and closing braces MUST go on the next line after the body
 - Opening braces for methods MUST go on the same line, and closing braces MUST go on the next line after the body
 - The abstract, final and static MUST be declared after the visibility
 - Control structure keywords, method and function calls MUST NOT have one space after them
 - Opening parentheses for control structures MUST have a space after them, and closing parentheses for control structures MUST have a space before
 - Lists of implements SHOULD NOT be split across multiple lines
 - Argument lists SHOULD NOT be split across multiple lines
 - When making a method or function call, there MUST be a space after the opening parenthesis, and there MUST be a space before the closing parenthesis
 
 - FIXME and TODO for class or method MUST be placed after the description in the PHPDoc

## Language
All elements in the code (variables-, class-, method names, comments...etc) and readme files MUST be written in
English (US) language. The documentation MAY be written in other languages, but an English version of the documentation
MUST exists.  

## Versioning
Version numbers MUST follow the [Semantic Versioning](http://semver.org/) rules. 

## Git
One commit MUST contains only one extension's changes, but one repository MAY contain more extensions. The branching
model MUST follow [this](http://nvie.com/posts/a-successful-git-branching-model/) "rules", with a little differences:

 - The 'develop' branch name MUST be 'development'
 - The commits of unfinished features (in any 'feature-*' branch) SHALL NOT have title. In the main branches ('development'
   or 'master') all commit MUST have a title (including merge commits)
 - Avoid unnecessary merge commits from remote update in the main branches
 - The commits in the 'master' branch MUST be tagged with the version name, with 'v0.0.0' format 

### Commit message format
`title`  
  
`content`  
`type`\!:`description`  

#### Types
An **\!** after the type indicates the compatibility broker changes.

 - fix *(F in legacy message)*
 - refactor
 - deprecated
 - remove
 - update *(U in legacy message)*
 - new *(N in legacy message)*
 - other *(O, C in legacy message)*

### Example
This is the commit title  
  
This is a longer description for the commit changes, and MUST be in one line  
fix: This is one change description that was a fix  
update: This is an other change description that was an update

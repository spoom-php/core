Framework
======

# For Contributors
**This will apply to the framework and all extensions either\!**
The key words "MUST", "MUST NOT", "REQUIRED", "SHALL", "SHALL NOT", "SHOULD", "SHOULD NOT", "RECOMMENDED", "MAY", and "OPTIONAL" in this document are to be interpreted as described in [RFC 2119](http://tools.ietf.org/html/rfc2119).

# Coding standard
Code MUST follow [PSR-1](http://www.php-fig.org/psr/psr-1/ "PSR-1") guide.

# Coding style
...

 - FIXME and TODO for class or method MUST be placed after the description in the PHPDoc.

## Language
The code (variables, class names, method, comments...etc) and the documentation (this readme file) MUST be written in
English language.

## Versioning
Version numbers MUST follow the [Semantic Versioning](http://semver.org/) rules. 

## Git
One commit MUST contains only one extension changes, but one repository MAY contains more extensions. The branching
model MUST follow [this](http://nvie.com/posts/a-successful-git-branching-model/) "rules", with little differences:

 - The 'develop' branch name MUST be 'development'
 - ...

### Commit message format
v`version` `title`  
`content`  
`type`\!:`description`  

#### Types
An **\!** after the type indicates the compatibility broker changes.

 - fix *(F in legacy message)*
 - format *(C in legacy message)*
 - refactor
 - deprecated
 - remove
 - update *(U in legacy message)*
 - new *(N in legacy message)*
 - other *(O in legacy message)*

### Example
v0.1.2 This is the commit title  
This is a longer description for the commit changes, and MUST be in one line  
fix: This is one change description that was a fix  
update: This is an other change description that was an update

# Documentation
...
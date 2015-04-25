The framework extension
======

## Page manager (`\Framework\Page` class)
All of the data related to the page and the code that can generate the page are stored and handled by the `\Framework\Page`
static class. It stores a basic log(`\Framework\Page::getLog()`), exception collector (`\Framework\Page::getCollector()`),
as well as the default localization (`\Framework\Page::getLocalization()`). 

It's enough to call the `\Framework\Page::execute()` method to run the generation of the page. It basically calls three
methods `\Framework\Page::start()`, `\Framework\Page::run()`, `\Framework\Page::stop()` in this order, but it handles errors as
well as, and has other functions too. If it's needed you are able to call them separately (for example on pages where
there is no output, but keeping the order is highly recommended (and most of the times the `::start()`
essential).

The `::start()` initializes the settings of the PHP and the attributes of the static class, and then triggers the
`\Framework\Page::EVENT_PAGE_START` event to make different kinds of registered handlers to be able to initialize their
own code. The `::run()` is basically a method responsible for triggering the `\Framework\Page::EVENT_PAGE_RUN` event, where
the registered handlers are able to generate the page's content and to give it (or them) back as the result of the
event. This return value (or values) will be the `::run()`'s result (or results), in array format. The `::stop()`
method is to make the `::run()`'s result displayable with the trigger of the `\Framework\Page::EVENT_PAGE_STOP` event. In this
event the registered operators are able to send the final content to the output based on the event's arguments.
These three sections are completely independent from format and application, and in themselves don't generate any kind
of output. It's just a frame, which defines the content generation extensions (for example the 'mvc'), general process. 

Use the `\Framework\Page::redirect()` for redirect to a new URL. 

## Event management
The event's function is to make other codes (so called handlers) to be able to react to particular extensions' operations,
and through the event result even be able to modify its result. The generation of the page is also based on this method,
with the `\Framework\Page::EVENT_*` event.  To run an event, `\Framework\Extension\Event` event has to be instantiated, giving
the namespace, the name of the event and it's parameters. After this the event is runable with the `->execute()` method
(mostly only once, but running it more times is also available). For the extension-based call, use the 
`\Framework\Extension->trigger()`, which gives the extension's identifier as the event's namespace, the other parameters
are transmissible to the method. *using this is recommended against the instantiated version*

The registered operators' result is available in the `->result` 's attribution after running, in associative array format.
The array's key is the handler's identifier  *\<extension\>:\<library\>* (for example: *framework:namespace1.namespace2.classname*),
and the value is the result. The errors coming up while the handlers are running are collected in the `->collector` 's
attribution. 

### Configuration
The handlers for the events are needed to be defined in a configuration file. The configuration files have to be put
into the *framework* extension configurations, where the file's name will be the event's namespace (with *'event-'* prefix),
the file's content will be an object with the event names, which value is a handler-defining array. For example the
*'foo.sample'* event's with *'test-namespace'* namespace definition is: **/extension/framework/configuration/event-test-namespace.json**

`````javascript
{
  "foo": {
    "sample": [{
      "extension": "extension-id",                  // extension identifier 
      "library": "namespace1.namespace2.classname", // the class of the extension, the handler (separated with dot)
      "enabled": true,                              // enabled or not
      "data": {}                                    // second parameter on handler execution as an object
    }, ... ]                                        // more definiton
  },
  ...                                               // more event
} 
`````

### Handlers
The handler classes need a `->execute( eventname, arguments )` method, which is called at the running of the event. The
'arguments' parameter gets an array, whose first element is the `\Framework\Extension\Event` instance, the second is the 'data'
object defined in the configuration file.

Switching the `\Framework\Extension\Event` instance `->stopped` attribution to true, the call of other handlers can be prevented,
and the default operation is also can be prevented (if there is a default operation, it depends on the caller of the event)
by switching the `->prevent` attribution to true. The method's result will be the handler's result, and exceptions can be
thrown in it, or they can be saved in the `\Framework\Extension\Event` instance `->collector` attribution. 

## Exception handling
The `\Framework\Exception\*` classes extend the PHP default exception-handling. The exceptions still can be trown, or can be
collected with an `\Framework\Exception\Collector` instance. There are three kinds of predefined exception (and most of the
time more isn't needed):

 - `\Framework\Exception\Runtime`: Public exceptions. They are displayable for the users, or on other frontends (eg: invalid registration inputs)
 - `\Framework\Exception\Strict`: Exceptions which can be corrected with programming. These are for developers (eg: wrong parameter type)
 - `\Framework\Exception\System`: Exceptions created by external effects. They have to be handled, but are not published to the user (eg: cannot connect to database)

All of these types are extended from the `\Framework\Exception` class. This class defines the basic fuctions and operations.
When doing instantiation the exception's id identifier is needed, which consists of 3 parts: '\<extension\>#\<code\>\<type\>'
The *extension* gives the extension's identifier of the exception's source, the *code* is for unique identification within
the extension, the *type* gives the exception's weight. Available types:

 - critical
 - error
 - warning
 - notice

While doing instantiation beside the identifier, a data array also can be given, along with the "parent" of the exception.

The exception's message can be defined in a special localization file. The file has to be named as *framework-exception*, and
the content is:
 
`````javascript
{
  "#<code><type>": "The localised message with {insertion}",
  ...
}
`````

For example:

*/extension/sample/localization/hu/framework-exception.json*
`````javascript
{
 "#245E": "This is a {name} exception's message"
}
`````

Somewhere in the code:
`````php

// ...

$exception = new Runtime( 'sample#245E', [ 'name' => 'wonderful' ] );
echo $exception->getMessage() // 'this is a wonderful exception's message'

// ...
 
`````

Every exception can be written in a log with the `->log()` method, where a log manager can be added trough the first parameter,
but the default is the `\Framework\Page::getLog()`. The `\Framework\Exception\Strict` and `\Framework\Exception\System` get into 
the log at the construct by default. 

## Extensions
The function of the framework can be extended by creating extensions. All of the extensions have an identifier, which
consists of three parts, in order: package, name, feature. These parts are separated by a hyphen, and they contains only
alphanumeric characters. The package part is required, but the other two is optional and can be used to create a custom
structure within the package. Eg: database, database-sql, database-sql-mysql, database-oracle
Within the package it's worth to split the fuction into more smaller parts, into cooperative extensions, in order to get
only the needed functions installed. 

The extensions' files has to be put into the */extension/* folder corresponding to its (extension's) identifier. An
extension's file structure is the following:

  - */extension/\<id\>/asset/*: Image, css, js and other files, optional 
  - */extension/\<id\>/configuration/*: Configuration files. It must to contain a **manifest** file
  - */extension/\<id\>/library/*: PHP classes, the extension's namespace is loaded from this directory (the part after it's identifier)
  - */extension/\<id\>/localization/*: Localization files, in language identifier folder, optional 
    
For the extensions' basic operations (like accessing files, configurations, language elements, ...) the `\Framework\Extension`
class is used. When creating an extension's object, the identifier is needed to give in the first parameter, otherwise
it will be taken from the current namespace (it's only important in the case of inheritance, but that is not really recommended).
  
### Configuration
The extensions' configurations has to be created in the */extension/\<id\>/configuration/* folder. The only required
configuration is the *manifest*, in which the identifier of the extension's parts (**package**, **name**, **feature**),
version (**version**), default localization (**localization**, and the selected one have to exists) and the author
(**author.name**,**author.email**, **author.website**, **author.company** ) have to be defined. For example:

**/extension/framework/configuration/manifest.json**
`````javascript
{
  "package": "framework",
  "version": "0.5.0",
  "localization": "en",
  "author": {
    "name": "Nagy Samu",
    "email": "nagy.samu222@gmail.com"
  }
}
`````

Creating other configuration is up to the creator of the extension, and it's available as `\Framework\Extension\Configuration`
(which is a `\Framework\Storage\Directory` class), or through the `\Framework\Extension->configuration` attribution.
The files can be: json, ini, xml and php.

General convention is that the extension's configuration in other extension's configuration gets defined by the extension's
identifier as the file name. For example if an extension supports the 'admin-install' extension as an installer and wants
to define some installation configuration (eg: dependencies), than the configuration file name should be **admin-install**
in the extensions configuration.

### Localization
In the */extension/\<id\>/localization/* directory, you can define localization files under directory named as the language
code. The language code can be anything, but the standard is the two or three letter code of the language. For example the
english files SHOULD be defined in */extension/\<id\>/localization/en/* directory. The system localization code determined
from the `Page::getLocalization()` method, or (if this directory doesn't exist) from the **manifest:localization** configuration
of the extension. The files can be in json, ini, xml and php format. 

The localization files content is available through the `\Framework\Extension\Localization` class instance or the
`\Framework\Extension->localization` attribution. Thes class is based on `\Framework\Storage\Directory`. There is a helper method
(`\Framework\Extension->text()`) which is a proxy for the `\Framework\Extension->localization->getPattern()` method.

To localize the exceptions, there MUST be a **framework-exception** localization file, where the keys are the exceptions'
codes without the extension id. For example: the 'mvc-api#24E' exception code's message will be defined from the 
*/extension/mvc-api/localization/\<language\>/framework-exception* file, in the '#24E' key. **framework-exception:#24E**

## Storages
With the storages you can handle complex (or not too complex) data structures, with simple string syntax and extended
features. You can pass data to the storage on construct or with storage methods. To access a storage content you MUST use
special syntax indexes: 

In the `{ elem1: { elem2: [ 8, 'elem4', ... ], ... }, ... }` structure the 'elem1.elem2.1' index access the 'elem4' value

One of the advanced features is the type forcing with the index (with the '...\!típus' postfix) or specialized
get methods. In the previuos example the 'elem1.elem2.0\!array' index doesn't return the value 8, but the 'elem1\!array'
returns an associative array and the 'elem1\!object' returns the same as an object.

### Single (`\Framework\Storage\Single`)
Single container storage. The initial data can be added on construct. This is the basic storage and has all the needed data handler
function. Implements the `\JsonSerializable` and `\ArrayAccess` interface, for easy json convertion and to
simplify the data access with array operator (proxy for `->get()` and `->set()` methods).

### Multi (`\Framework\Storage\Multi`)
Extends the `\Framework\Storage\Single` class with multiple container: the namespaces. There is a
default namespace for the indexes without namespace definition, but you can access other namespaces with 'namespacename:'
index prefix. For example:

In the `{ namespace1: { element1: [ 8, 'element2', ... ], ... }, namespace2: { ... }, ... }` structure, the 'namespace1:element1.0' index access the 8 value

### Directory (`\Framework\Storage\Directory`)
This is a `\Framework\Storage\Multi` storage variation where the namespaces data is loaded from files and the modifications can
be saved back to it. The source directory MUST be added on construct. The file's name (in this source directory) will be
the namespaces in the storage, and the new namespaces can be saved as a file with the namespace name. Native supported formats:

 - xml: Parsed and stringified with the `\Framework\Helper\Enumerable::*Xml()` methods
 - json: Parsed and stringified with the `\Framework\Helper\Enumerable::*Json()` methods
 - ini: Parsed and stringified with the `\Framework\Helper\Enumerable::*Ini()` methods
 - php: Parsed and stringified with the `\serializable` and `\unserializable` functions
 
Other formats can be supported with `\Framework\Storage\Directory::EVENT_CONVERT` event handlers.

The namespaces (files) content is loaded automatically when needed, but only once per run. This means: two instances of storage with
the same directory, works with the same content regardless of which one does the modifications. The content saving is not
automatic, so you MUST call the `->write()` method to do that.

### Request (`\Framework\Storage\Request`)
This special `\Framework\Storage\Multi` storage gives access to the superglobals with storage syntax. The namespaces are the
superglobals' names without underscore and lowercase: `$_GET[*][*]` index is 'get:\*.\*'. All namespaces are stored as
references so like the `\Framework\Storage\Directory` the contents of the instances are always equal, and the modification
also modifies the superglobals too.

The `$_COOKIE` access is available too, but for the modification use the `\Framework\Storage\Request->setCookie()` method.

## Helper classes

### Basic operations
The `\Framework\Helper\Enumerable` static class contains methods for object and class handling. Contains JSON, XML and
ini parse and stringify, and some array operations.
The `\Framework\Helper\File` static class contains file and directory operations. For now it's pretty small, but will
extend when needed.
The `\Framework\Helper\String` static class contains useful methods for string operations. There is object insertion to string,
link to convert and safe unique id generation.

### Log
The `\Framework\Helper\Log` class is used for runtime logging. The entries has six levels of importance, like the exception 
handling:

 - `\Framework\Helper\Log::TYPE_CRITICAL`
 - `\Framework\Helper\Log::TYPE_ERROR`
 - `\Framework\Helper\Log::TYPE_WARNING`
 - `\Framework\Helper\Log::TYPE_NOTICE`
 - `\Framework\Helper\Log::TYPE_INFO`
 - `\Framework\Helper\Log::TYPE_DEBUG`
 
Every level has a dedicated method in the class.

With the `_LOGGING` constant in the **define.php** you can ignore all log below a level. The constant value is the lowest
level that is NOT ignored. In the list above the critical is the highest and the debug is the lowest level, the rest is
in the right order. For example: if the `_LOGGING` value is 4, then the`\Framework\Helper\Log::TYPE_NOTICE` ...
`\Framework\Helper\Log::TYPE_CRITICAL` intervall will be logged, the entries with `\Framework\Helper\Log::TYPE_INFO` and
`\Framework\Helper\Log::TYPE_DEBUG` level will be ignored. The 6 value means ALL, the 0 is the NONE.
 
In the `\Framework\Helper\Log` construct, you can pass the log name, which is the log file name by default (but every day
starts a new file). When creating the entries, you can pass a message (`$message` argument), whicg can contain object
insertion, which values can be passed with the `$data` argument. The last argument on creation is a custom namespace for
that entry which can be helpful in the log processing later.

The default storage is file based (the logs goes to the */tmp/* directory), but an extension can override this behavior with
a custom handler for the `\Framework\Helper\Log::EVENT_CREATE` event. In the event handler you can set the `->prevented` flag
for the event object to prevent the file based storing. The default file is a csv, and the format is defined in the
`\Framework\Helper\Log::PATTERN_MESSAGE` constant. 

### Other
The `\Framework\Helper\Library` trait and the `\Framework\Helper\LibraryInterface` interface purpose is to create a common base
to every library in the framework and extensions. There is no special feature yet, but for the future update compatibility the
usage of these bases are strongly RECOMMENDED\!
The `\Framework\Helper\Feasible` trait and the `\Framework\Helper\FeasibleInterface` interface is a support for the classes
that have dinamically called methods and the method invocation is based on strings. 

Every trait MUST have an interface that defines the trait methods. This interface MUST be implemented on classes that uses
the trait\! 

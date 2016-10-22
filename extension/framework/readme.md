The framework extension
======

# Application management
Store basic data for the request and can generate the request's response. It stores a basic log(`\Framework\Application::getLog()`), exception collector 
(`\Framework\Application::getCollector()`), as well as the default localization (`\Framework\Application::getLocalization()`). It's enough to call the
`\Framework\Application::execute()` method to generate the response. It basically calls three methods `\Framework\Application::start()`, `\Framework\Application::run()`,
`\Framework\Application::stop()` in this order.

The `::start()` initializes some settings and the attributes of the static class, and then triggers the `\Framework\Application::EVENT_REQUEST_START` event to make
different kinds of registered handlers to be able to initialize their own code. The `::run()` is basically a method responsible for triggering the
`\Framework\Application::EVENT_REQUEST_RUN` event, where the registered handlers are able to generate the response and to give it (or them) back as the result of
the event. This return value (or values) will be the `::run()`'s result (or results), in array format. The `::stop()` method is to make the `::run()`'s result
displayable with the trigger of the `\Framework\Application::EVENT_REQUEST_STOP` event. In this event the registered handlers are able to send the final content to
the output based on the event's arguments. These three sections are completely independent from format and application, and in themselves don't generate any
kind of output. It's just a frame, which defines the content generation extensions (for example the 'http'), general process. 

# Event management
The event's function is to make other codes (so called handlers) to be able to react to particular extensions' operations, and through the event result even be
able to modify its result. The generation of the request is also based on this, with the `\Framework\Application::EVENT_*` events. To run an event,
`\Framework\Extension\Event` instance has to be created. After this the event can execute with the `->execute()` method (mostly only once, but running it more
times is also available). For the extension-based call, use the `\Framework\Extension->trigger()`, which gives the extension's identifier as the event's
namespace.

**Triggering event with the `\Framework\Extension->trigger()` is highly recommended**

The registered handlers' result is available in the `->result` property after running, in associative array format. The array's key is the handler's identifier
*\<extension\>:\<library\>* (for example: *framework:namespace1.namespace2.classname*), and the value is the handler's result. All exceptions that throwed in
the handler are collected into the `->collector`'s property. 

## Configuration
The handlers for the events are needed to be defined in a configuration file. The configuration files have to be put into the *'framework'* extension
configurations, where the file's name will be the event's namespace (with *'event-'* prefix), the file's content will be an object with the event names, which
value is a handler-defining array. For example the *'foo.sample'* event's with *'test-namespace'* namespace definition is:

**/extension/framework/configuration/event-test-namespace.json**
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

## Handler
The handler classes needs to implement the `\Framework\Helper\FeasibleInterface` interface. The executed method first parameter is the
`\Framework\Extension\Event` instance, the second is the 'data' object defined in the configuration file.

Switching the `\Framework\Extension\Event` instance `->stopped` property to true, the call of other handlers can be prevented, and the default operation is also
can be prevented (if there is a default operation, it depends on the caller of the event) by switching the `->prevent` property to true. The method's result
will be the handler's result, and exceptions can be thrown in it, or they can be saved in the `\Framework\Extension\Event` instance `->collector` property. 

# Exception
The `\Framework\Exception` class extend the PHP default exception-handling. The exceptions still can be thrown, or can be collected with an 
`\Framework\Exception\Collector` instance. There are three kinds of predefined exception (and most of the time more isn't needed):

 - `\Framework\Exception\Runtime`: Public exceptions. They are displayable for the users, or on other frontends (eg: invalid registration inputs)
 - `\Framework\Exception\Strict`: Exceptions which can be corrected with programming. These are for developers (eg: wrong parameter type)
 - `\Framework\Exception\System`: Exceptions created by external effects. They have to be handled, but are not published to the user (eg: cannot connect to database)

All of these types are extended from the `\Framework\Exception` class. This class defines the basic fuctions and operations. On the exception constructing you
must specify the exception's id identifier, which consists of 3 parts: '\<extension\>#\<code\>\<type\>'
The *extension* gives the extension's identifier of the exception's source, the *code* is for unique identification within the extension, the *type* gives the
exception's weight. Available types (and level):

 - C (critical)
 - E (error)
 - W (warning)
 - N (notice)

In the constructor you can pass a data array, along with the "parent" of the exception. The exception's message must be defined in a special localization file.
The file has to be named as *framework-exception*, and the content is (the type is optional):
 
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

Every exception can be written in a log with the `->log()` method, where a log manager can be added trough the first parameter, but the default is the
`\Framework\Application::getLog()`.

# Extension
The function of the framework can be extended by creating extensions. All of the extensions have an identifier, which consists of three parts,
in order: package, name, feature. These parts are separated by a hyphen, and they contains only alphanumeric lowercase characters. The package part is required,
but the other two is optional and can be used to create a custom structure within the package. Eg: database, database-sql, database-sql-mysql, database-oracle.
Within the package it's worth to split the function into smaller parts, with cooperative extensions, in order to get only the needed functions installed. 

The extensions' files has to be put into the */extension/* folder in a subfolder named as the extension's identifier. In this folder the directory structure is
the following:

  - */extension/\<id\>/asset/*: Image, css, js and other files (optional) 
  - */extension/\<id\>/configuration/*: Configuration files (optional)
  - */extension/\<id\>/library/*: PHP classes, the extension's namespace is loaded from this directory (the part after it's identifier)
  - */extension/\<id\>/localization/*: Localization files, in language identifier folder (optional) 
    
Every extension MUST define a manifest file, in the directory root. This is a special configuration file which is available through the `\Framework\Extension`
class `->manifest` property. The manifest MUST contains the extension id (**id**), version (**version**), default localization (**localization**, and this one
must exists) and the author data (**author.name**,**author.email**, **author.website**, **author.company** ). For example:
                                               
**/extension/framework/manifest.json**
`````javascript
{
 "id": "framework",
 "version": "0.6.0",
 "localization": "en",
 "author": {
   "name": "Nagy Samu",
   "email": "nagy.samu222@gmail.com"
 }
}
`````
 
For the extensions' basic operations (like accessing files, configurations, language elements, ...) the `\Framework\Extension` class is used.
  
## Configuration
The extensions' configurations has to be created in the */extension/\<id\>/configuration/* folder. Creating more configuration is up to the creator of the
extension, and available through the `\Framework\Extension->configuration` property (it's a `\Framework\Extension\ConfigurationInterface`). By default the
`->configuration` property is a `\Framework\Extension\Configuration` object which is a `\Framework\Storage\File` storage. This can be altered through the
manifest file **storage.configuration** option. It MUST be (empty or) a valid `\Framework\Extension\ConfigurationInterface` library definition.

General convention is that the extension's configuration in other extension's configuration gets defined by the extension's identifier as the file name. For
example if an extension supports the 'admin-install' extension as an installer and wants to define some installation configuration (eg: dependencies), than the
configuration file name should be *'admin-install'* in the extensions' configuration directory.

## Localization
In the */extension/\<id\>/localization/* directory, you can define localization files under directory named as the language code. The language code can be
anything, but the standard is the two or three letter code of the language. For example the english files SHOULD be defined in
*/extension/\<id\>/localization/en/* directory (for the default localization handler class). The system localization code determined from the
`Application::getLocalization()` method, or (if this is not supported by the extension) the manifest **localization** index of the extension.

The localization files content is available through the `\Framework\Extension->localization` property (it's a `\Framework\Extension\LocalizationInterface`).
There is a helper method in the `\Framework\Extension` class (`->text()`) which is a proxy for the `\Framework\Extension->localization->getPattern()` method.
By default the `->localization` property is a `\Framework\Extension\Localization` object which is a `\Framework\Storage\File` storage. This can be altered
through the manifest file **storage.localization** option. It can be a valid `\Framework\Extension\LocalizationInterface` library definition.

To localize the extensions' exceptions, there MUST be a **framework-exception** localization file, where the keys are the exceptions' codes without the
extension id. For example: the 'mvc-api#24E' exception code's message will be defined from the
*/extension/mvc-api/localization/\<language\>/framework-exception* file, in the '#24E' key (**framework-exception:#24E**).

# Storage (`\Framework\Storage`)
With the storages you can handle complex data structures, with simple string syntax and extended features. You can pass data to the storage on construct or with
storage methods. The class implements the `\JsonSerializable` and `\ArrayAccess` interface, for easy json convertion and to simplify the data access with array
operator (proxy for `->get()` and `->set()` methods). 

To access a storage content you MUST use special syntax indexes:
  
  In the `{ elem1: { elem2: [ 8, 'elem4', ... ], ... }, ... }` structure the 'elem1.elem2.1' index access the 'elem4' value
  
You can use namespace in the indexes. If no namespace in the index, there is a default namespace (which can be null), but you can access other namespaces with
'namespacename:' index prefix. For example: 

  In the `{ namespace1: { element1: [ 8, 'element2', ... ], ... }, namespace2: { ... }, ... }` structure, the 'namespace1:element1.0' index access the 8 value

One of the advanced features is the type forcing with the index (with the '...\!type' postfix) or specialized get methods. In the previous example the
'elem1.elem2.0\!array' index doesn't return the value 8, but the 'elem1\!array' returns an associative array and the 'elem1\!object' returns the same as an
object.

## Permanent (`\Framework\Storage\Permanent`)
This is a storage variation where the data can be saved and loaded to/from permanent storages (hard drive, database, etc..). Before the save and after the load
the data is serialized/unserialized into/from various formats. Native supported formats:

 - xml: Parsed and stringified with the `\Framework\Helper\Enumerable::*Xml()` methods
 - json: Parsed and stringified with the `\Framework\Helper\Enumerable::*Json()` methods
 - ini: Parsed and stringified with the `\Framework\Helper\Enumerable::*Ini()` methods
 - php: Parsed and stringified with the `\serializable` and `\unserializable` functions
 - Other formats can be supported with `\Framework\Storage\Permanent::EVENT_CONVERT` event handlers

The data is loaded automatically when needed. The data saving is not automatic, so you MUST call the `->save()` method to do that.

## File (`\Framework\Storage\File`)
This is an implementation of the `\Framework\Storage\Permanent` storage. It gives access to a file or files from a directory. With the directory mode, the
namespaces will be the file names in the directory (for single file the namespace has no special meaning).

# Helper classes
The `\Framework\Helper\Enumerable` static class contains methods for object and class handling. Contains JSON, XML and ini parse and stringify, and some array
operations. The `\Framework\Helper\File` static class contains file and directory operations. For now it's pretty small, but will extend when needed. The
`\Framework\Helper\Text` static class contains useful methods for string operations. There is object insertion to string, link to convert and safe unique id
generation.

## Log
The `\Framework\Log` class is used for runtime logging. The entries has level of importance, and the values is from the `\Framework::LEVEL_*` constants:

 - `\Framework::LEVEL_NONE`
 - `\Framework::LEVEL_CRITICAL`
 - `\Framework::LEVEL_ERROR`
 - `\Framework::LEVEL_WARNING`
 - `\Framework::LEVEL_NOTICE`
 - `\Framework::LEVEL_INFO`
 - `\Framework::LEVEL_DEBUG`
 
Every level has a dedicated method in the class.

With the `\Framework::logLevel()` method you can setup a level to ignore all log below that level. The value is the lowest level that is NOT ignored. In the
list above the none is the highest (0) and the debug is the lowest (6) level, the rest is in the right order. For example: if the `\Framework::logLevel()` value
is 4, then the`\Framework::LEVEL_NOTICE` ... `\Framework::LEVEL_CRITICAL` interval will be logged, the entries with `\Framework::LEVEL_INFO` and
`\Framework::LEVEL_DEBUG` level will be ignored. The `\Framework::LEVEL_DEBUG` value means all, the `\Framework::LEVEL_NONE` is the none.
 
In the `\Framework\Log` construct, you can pass the log name, which is the log file name by default (but every day starts a new file). When creating the
entries, you can pass a message (`$message` argument), whicg can contain object insertion, which values can be passed with the `$data` argument. The last
argument on creation is a custom namespace for that entry which can be helpful in the log processing later.

The default storage is file based (the logs goes to the */tmp/* directory), but an extension can override this behavior with a custom handler for the 
`\Framework\Log::EVENT_CREATE` event. In the event handler you can set the `->prevented` flag for the event object to prevent the file based storing. The
default file is a csv, and the format is defined in the `\Framework\Log::PATTERN_MESSAGE` constant. 

## Other
The `\Framework\Helper\Library` trait and the `\Framework\Helper\LibraryInterface` interface purpose is to create a common base to every library in the
framework and extensions. There is no special feature yet in the interface, but for the future update compatibility the usage of these bases are strongly
RECOMMENDED\! The class provide a default getter and setter to magic property convertion.
 
The `\Framework\Helper\Feasible` trait and the `\Framework\Helper\FeasibleInterface` interface is a support for the classes that have dinamically called methods
and the method invocation is based on strings. 

Every trait MUST have an interface that defines the trait methods. This interface MUST be implemented on classes that uses
the trait\! 

# The libsso library

This is the repository for the libsso library intended to communicate with the SSO system of the czech school SPŠ Ostrov. The library is intended mainly for
the students of the school and their projects but it may be used by anybody who would like to create some system communicating with the school's SSO interface. 

## Installation

### Manually adding the library into your project

1. Copy the content of the repository (or at least the `lib/` subdirectory) into the structure of your project.
2. Include the file `lib/libsso.php` in each php script.
3. Alternatively it is sufficient to include `lib/libsso.php` only in scripts handling the SSO login, but this variant is not recommended and has its limitations.

### Adding via composer

```
composer require spsostrov/libsso
```


## Specification

The specification of the SSO protocol is also available as a [standalone document](specification.md).


## Documentation


The library provides a couple of classes in the namespace `SPSOstrov\SSO`. The main class provided by the library is the class `SSO` having the fully
qualified name `SPSOstrov\SSO\SSO`. However, if loaded using the `lib/libsso.php` include file, the library also provides class aliases:

```
SPSOstrov\SSO\SSO -> SSO
SPSOstrov\SSO\SSOUser -> SSOUser
```

into the root namespace, so the classes may be then be used also in the root (empty) namespace.
In case the library is loaded using composer, the class aliases into the root namespace are disabled by default, but they may be enabled by calling
the command:

```php
SPSOstrov\SSO\SSO::enableAliases();
```

If you want to load the library using `lib/libsso.php` and you don't want to use the aliases to the root namespace, you may just define the constant
`SPSOSTROV_SSO_NO_ALIASES` before including the file:

```php
define("SPSOSTROV_SSO_NO_ALIASES", true);
require_once $ssoLibDir . "/lib/libsso.php";
```

But of course the recommended usage is by using the `use` directive, according to the standard rules of the PHP language:

```php
use SPSOstrov\SSO\SSO;
use SPSOstrov\SSO\SSOUser;
```

### Basic usage

Basic usage of the library is represented by a testing application in the directory [testapp](testapp/).

The following code invokes the SSO procedure as whole:

```php
$sso = new SSO();
$user = $sso->doLogin();
```

Function `$sso->doLogin()` returns an object of the class `SSOUser` representing the logged-in user or `null` if the logged in user couldn't be determined.

**Attention:** The function `$sso->doLogin()` should be called somewhere at the beginning of the whole script, since all the code will be called **twice**.
First before redirecting to the SSO server and then again after the redirection back from the SSO server to the application. The code being invoked **before**
the `$sso->doLogin()` call should therefore have no side effects and shouldn't output anything to standard output (for example using the `echo` command).


### Objectless interface

This method works the same as `$sso->doLogin()`, but the returned use is not returned as an instance of the class `SSOUser`, but as an associative array:

```php
$sso->doLoginAsArray();
```

### Access to parts of the login process:

The right function of invoking `$sso->doLogin()` depends on many conditions, which may not be satisfied in all cases. Therefore there are also methods available
invoking only partial phases of the SSO login process:


The following method invokes only the redirection to the SSO server (1st phase of the authorization). You may also provide the `$backUrl`, which says
on which URL the SSO server will redirect back to the application:

```php
$sso->doRedirect($backUrl);
```

The following options are available for setting the `$backUrl`:
```php
$backUrl = null; //the url of the current script will be used
$backUrl = "https://moje.aplikace.cz/sso"; //full url is provided
$backUrl = "/cesta/k/sso"; //absolute path on the current webserver
$backUrl = "sso.php"; //relative path according to the current script
```

The following method invokes the 2nd phase of the authorization process:
```php
$sso->getLoginCredentials($token, $backUrl);
```

where `$token` may be either `null` (and then it is read from the GET parameters) or it may be a specific string. `$backUrl` is necessary to be set **identically** as in the phase 1,
otherwise the second phase will fail.

The following method just returns the URL for redirecting as the first phase, if the application wants to make the redirection by its own:
```php
$redirectUrl = $sso->getRedirectUrl($backUrl);
```

### The interface of the class SSOUser

* Login: `$user->getLogin()`
* Full name: `$user->getName()`
* E-mail: `$user->getEmail()`
* Group name: `$user->getGroupName()`
* Groups: `$user->getGroups()`
* Test if the user has a group: `$user->hasGroup("ucitele")`
* Auth by: `$user->getAuthBy()`
* OU Simple: `$user->getOUSimple()`
* OU Name: `$user->getOUName()`
* Test if the user is a teacher: `$user->isTeacher()`
* Test if the user is a student: `$user->isStudent()`
* Class: `$user->getClass()` (only for students, `null` otherwise)
* Field of study: `$user->getFieldOfStudy()` (only for students, `null` otherwise)
* Study entry year: `$user->getStudyEntryYear()` (only for students, `null` otherwise)
* Unix timestamp when the user logged in: `$user->getLoginTimestamp()`
* Print the user as html: `$user->prettyPrint()`
* Convert the user to an associative array: `$user->asArray()`

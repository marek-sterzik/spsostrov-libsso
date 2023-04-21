# SPŠ Ostrov SSO Protocol Specification

## Introduction

The SSO protocol (single sign-on) for the SPŠ Ostrov technical school is a protocol used for 3rd party applications to allow to authenticate users using the school user database.

## Terms

* **Requesting Application** - refers to the application which wants to authenticate the user
* **SSO Application** - refers to the central sign on application being responsible to provide central authentication

## General flow

If the Requesting Application wants to authenticate users against the SPŠ Ostrov user database, it needs to follow this flow:

1. The Requesting Application needs to know its own callback URL, which will process the authentication request.
2. The Requesting Application redirects the user to the central SSO Application, where the callback URL is passed as a parameter.
3. The SSO Application authenticates the user and stores its data in its internal database. A referring token is generated, which is later used by the Requesting Application to catch the user data.
4. The SSO Application redirects the user back to the callback url and passes the token as a parameter to it.
5. The Requesting Application is making a request in the background to the SSO Application passing the token and getting user data of the authenticated user.
6. The Requesting Application is responsible for storing the user data into the user's session storage, where the data becomes available for all following http requests.
7. The application should keep the user data valid for a limited period of time and reauthenticate from time to time.

## The SSO Application

The SSO application consists of two URLs:

1. `https://titan.spsostrov.cz/ssogw/` which is the **main page**, where the user should be redirected to.
2. `https://titan.spsostrov.cz/ssogw/service-check.php` which is the **user data requesting url** for doing the background request requesting user data.

Both URLs are using query parameters to pass data.

The *main page* has just a single query parameter `service`, where the callback url should be stored. The callback url is base64 encoded and should be properly encoded as an URI component.

The *user data requesting url* has two parameters:

* `service` which has the same meaning as in the main page (warning: **this parameter needs to be exactly the same as in the main page**)
* `ticket` which is the token being passed to the Requesting Application by the SSO Application.

When the *main page* is passing the token back to the Requesting application, it is passing it in the query parameter `ticket`.

##Example

Lets have an service `www.example.com`, which has implemented the callback url `https://www.example.com/sso-login`. The SSO authentication process will look like this:

1. The example application needs to determine the URL of the callback url. I.e. `https://www.example.com/sso-login`
2. The application needs to encode the callback url as base64, which looks like: `aHR0cHM6Ly93d3cuZXhhbXBsZS5jb20vc3NvLWxvZ2lu`
3. The application creates the url of the *main page* which will be: `https://titan.spsostrov.cz/ssogw/?service=aHR0cHM6Ly93d3cuZXhhbXBsZS5jb20vc3NvLWxvZ2lu`
4. The application redirects the user to this main page url.
5. The SSO application does its job and then redirects the user back to the callback url with the token as a parameter. The callback url will look like: `https://www.example.com/sso-login?ticket=7a089f789c7d0750aa16bd37783fb598`
5. When processing the /sso-login controller the example application should request the user data by obtaining the url: `https://titan.spsostrov.cz/ssogw/service-check.php?service=aHR0cHM6Ly93d3cuZXhhbXBsZS5jb20vc3NvLWxvZ2lu&ticket=7a089f789c7d0750aa16bd37783fb598`

## Example SSO authentication in PHP

```php
// set the base url for the SSO application
$ssoUrlBase = "https://titan.spsostrov.cz/ssogw/";

#compose the callback URL (may not work in all circumstances)
$protocol = $_SERVER['HTTPS'] ? 'https' : 'http';
$callbackUrl = $protocol . "://" . $_SERVER['SERVER_NAME'] . $_SERVER['SCRIPT_NAME'];

#create the service token
$service = base64_encode($callbackUrl);

if (isset($_GET['ticket'])) {
    //if the ticket parameter is set, it means that SSO Application already
    //redirected back

    $token = $_GET['ticket'];

    $ssoUserDataRequestingUrl = $ssoUrlBase . 
                                "service-check.php?service=" .
                                urlencode($service) .
                                "&ticket=" .
                                urlencode($token);

    //get the user data
    $data = file_get_contents($ssoUserDataRequestingUrl);

    //output user data
    header('Content-Type: text/plain');
    echo $data;
} else {
    //if the ticket parameter is not set, it means we are starting and we should
    //redirect to the SSO Application
    $ssoUrl = $ssoUrlBase . "?service=" . urlencode($service);

    //redirect to the SSO Application
    header("Location: ".$ssoUrl);
}
```

## The user data format

The *user data requesting url* outputs the data in a simple text format, which may be easily parsed. It consists of multiple records (one per line) each record starting with an identifier of the record type follwed by a comma (`:`) and then followed by the data for the given record type. For example the user data may look like this:
```
login:john-doe
name:John Doe
group:users
group:bakalari
group:xpu-bakalari
group:ucitele
group_name:
mail:doe@spsostrov.cz
auth_by:tgc
ou_simple:ucitele
```
Some records types are always unique, other may contain multiple values. The meaning of the record types is as follows:

* `login` - the username of the given user
* `name` - the full name of the given user
* `group` - multiple records one per each user group
* `group_name` - ???
* `mail` - the e-mail address of the user
* `auth_by` - ???
* `ou_simple` - ???

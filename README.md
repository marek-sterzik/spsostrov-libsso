# Knihovna libsso

Toto je repozitář knihovny pro komunikaci s SSO systémem ve škole SPŠ Ostrov.

## Instalace

### Ruční přidání do projektu

1. Zkopírujte obsah tohoto repozitáře do adresářové struktury vašeho projektu.
2. Zařiďte, aby se v rámci každého běhu php skriptu načítal soubor `lib/libsso.php`.
3. Alternativně postačuje, že `lib/libsso.php` se bude načítat jenom v rámci skriptů pracujících s SSO, ale tato varianta není doporučená.

### Přidání přes composer

```
composer require spsostrov/libsso
```


## Specifikace

Je k dispozici taktéž [specifikace](specification.md) celého SSO protokolu v angličtině.


## Dokumentace

### Základní použití

Základní použití je reprezentováno testovací aplikací v adresáři [testapp](testapp/).

Následující kód spustí celou SSO proceduru:

```php
$sso = new SSO();
$user = $sso->doLogin();
```

Funkce `$sso->doLogin()` vrátí buď objekt třídy `SSOUser` reprezentující příslušného přihlášeného uživatele, nebo `null` pokud se přihlášeného uživatele nepodařilo zjistit.


### Bezobjektový přístup

Tato metoda funguje stejně jako `$sso->doLogin()` ale přihlášeného uživatele nevrací jako instanci třídy `SSOUser`, ale jako asociativní pole:

```php
$sso->doLoginAsArray();
```

### Přístup k dílčím částem login procesu

Správná funkce volání `$sso->doLogin()` v tomto případě ovšem závisí na mnoha podmínkách, které nemusí být za všech okolností splněny. Proto jsou také k dispozici metody, které spouští vždy jenom část procesu:


Následující metoda provede pouze přesměrování na SSO server s tím, že přesměrování zpět proběhne na adresu `$backUrl`:
```php
$sso->doLogin($backUrl);
```

Lze přitom použít následující možnosti pro `$backUrl`:
```php
$backUrl = null; //bude použito url skriptu, který se právě provádí
$backUrl = "https://moje.aplikace.cz/sso"; //plné url
$backUrl = "/cesta/k/sso"; //absolutní cesta na aktuálním serveru
$backUrl = "sso.php"; //relativní cesta vůči právě prováděnému skriptu
```

Následující volání provede druhou část autorizace:
```php
$sso->getLoginCredentials($token, $backUrl);
```

kde `$token` může být buď `null` (a pak je přečten z GET parametrů) nebo může být zcela konkrétní řetězec. `$backUrl` je potřeba zadat **identické** jako při první fázi (přesměrování na SSO).

Následující metoda vrací url pro přesměrování v prnví fázi, pokud si aplikace chce realizovat přesměrování ve vlasntí režii:
```php
$redirectUrl = $sso->getRedirectUrl($backUrl);
```

### Rozhraní třídy SSOUser

* Login: `$user->getLogin()`
* Plné jméno: `$user->getName()`
* E-mail: `$user->getEmail()`
* Název skupiny: `$user->getGroupName()`
* Skupiny: `$user->getGroups()`
* Test na přítomnost uživatele ve skupině: `$user->hasGroup("ucitele")`
* Auth by: `$user->getAuthBy()`
* OU Simple: `$user->getOUSimple()`
* Vypsání celého uživatele jako html: `$user->prettyPrint()`
* Převod uživatele na asociativní pole: `$user->asArray()`

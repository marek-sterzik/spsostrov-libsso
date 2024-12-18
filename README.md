# Knihovna libsso

[English documentation](README.en.md) is also available.

Toto je repozitář knihovny pro komunikaci s SSO systémem ve škole SPŠ Ostrov. Knihovna je určena zejména studentům SPŠ Ostrov a jejich projektům,
ale lze jí využít v libovolném projektu, který by měl s daným SSO systémem spolupracovat.

## Instalace

### Ruční přidání do projektu

1. Zkopírujte obsah tohoto repozitáře (nebo přinejmenším jeho podadresáře `lib/`) do adresářové struktury vašeho projektu.
2. Zařiďte, aby se v rámci každého běhu php skriptu načítal soubor `lib/libsso.php`.
3. Alternativně postačuje, že `lib/libsso.php` se bude načítat jenom v rámci skriptů pracujících s SSO, ale tato varianta není doporučená.

### Přidání přes composer

```
composer require spsostrov/libsso
```


## Specifikace

Je k dispozici taktéž [specifikace](specification.md) celého SSO protokolu v angličtině.


## Dokumentace

Knihovna poskytuje několik tříd v rámci jmenného prostoru `SPSOstrov\SSO`. Hlavní třídou, která poskytuje funkcionalitu knihovny je třída
`SSO`. Tj. její plně kvalifikované jméno je `SPSOstrov\SSO\SSO`. Nicméně, pokud je knihovna načtena přes `lib/libsso.php`, jsou zároveň vytvořeny aliasy:

```
SPSOstrov\SSO\SSO -> SSO
SPSOstrov\SSO\SSOUser -> SSOUser
```

do kořenového jmenného prostoru, takže je možné potom příslušné třídy používat i v kořenovém (žádném) jmenném prostoru.
V případě načtení knihovny přes composer nejsou třídní aliasy automaticky zapnuty, ale lze je zapnout příkazem:

```php
SPSOstrov\SSO\SSO::enableAliases();
```

Pokud chcete načíst knihovnu `lib/libsso.php` a zároveň nechcete používat aliasy v kořenovém jmenném prostoru, prostě definujte konstantu
`SPSOSTROV_SSO_NO_ALIASES` před načtením příslušného souboru:

```php
define("SPSOSTROV_SSO_NO_ALIASES", true);
require_once $ssoLibDir . "/lib/libsso.php";
```

Lze také pracovat bez aliasů jednoduše použitím direktivy `use`, podle standardních pravidel práce s jmennými prostory v jazyce PHP:

```php
use SPSOstrov\SSO\SSO;
use SPSOstrov\SSO\SSOUser;
```

### Základní použití

Základní použití je reprezentováno testovací aplikací v adresáři [testapp](testapp/).

Následující kód spustí celou SSO proceduru:

```php
$sso = new SSO();
$user = $sso->doLogin();
```

Funkce `$sso->doLogin()` vrátí buď objekt třídy `SSOUser` reprezentující příslušného přihlášeného uživatele, nebo `null` pokud se přihlášeného uživatele nepodařilo zjistit.

**Pozor:** Funkci `$sso->doLogin()` je potřeba volat někde na samém začátku provádění skriptu, protože veškerý kód před voláním této metody se provede **dvakrát**. Nejprve,
před přesměrováním na SSO server a poté znova po přesměrování z SSO serveru zpět do aplikace. Kód zavolaný **před** zavoláním funkce `$sso->doLogin()` by proto neměl mít
žádné vedlejší efekty a neměl by nic vypisovat na standardní výstup (třeba pomocí příkazu `echo`).


### Bezobjektový přístup

Tato metoda funguje stejně jako `$sso->doLogin()`, ale přihlášeného uživatele nevrací jako instanci třídy `SSOUser`, ale jako asociativní pole:

```php
$sso->doLoginAsArray();
```

### Přístup k dílčím částem login procesu

Správná funkce volání `$sso->doLogin()` v tomto případě ovšem závisí na mnoha podmínkách, které nemusí být za všech okolností splněny. Proto jsou také k dispozici metody, které spouští vždy jenom část procesu:


Následující metoda provede pouze přesměrování na SSO server (1. fázi autorizaci) s tím, že přesměrování zpět proběhne na adresu `$backUrl`:
```php
$sso->doRedirect($backUrl);
```

Lze přitom použít následující možnosti pro `$backUrl`:
```php
$backUrl = null; //bude použito url skriptu, který se právě provádí
$backUrl = "https://moje.aplikace.cz/sso"; //plné url
$backUrl = "/cesta/k/sso"; //absolutní cesta na aktuálním serveru
$backUrl = "sso.php"; //relativní cesta vůči právě prováděnému skriptu
```

Následující volání provede druhou fázi autorizace:
```php
$sso->getLoginCredentials($token, $backUrl);
```

kde `$token` může být buď `null` (a pak je přečten z GET parametrů) nebo může být zcela konkrétní řetězec. `$backUrl` je potřeba zadat **identické** jako při první fázi (přesměrování na SSO),
jinak druhá fáze selže.

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
* OU Name: `$user->getOUName()`
* Test zda je uživatel učitel: `$user->isTeacher()`
* Test zda je uživatel student: `$user->isStudent()`
* Třída: `$user->getClass()` (pouze pro studenty, pro ne-studenty je hodnota vždy `null`)
* Obor studia: `$user->getFieldOfStudy()` (pouze pro studenty, pro ne-studenty je hodnota vždy `null`)
* Rok počátku studia: `$user->getStudyEntryYear()` (pouze pro studenty, pro ne-studenty je hodnota vždy `null`)
* Časová značka (unix timestamp) přihlášení uživatele: `$user->getLoginTimestamp()`
* Vypsání celého uživatele jako html: `$user->prettyPrint()`
* Převod uživatele na asociativní pole: `$user->asArray()`

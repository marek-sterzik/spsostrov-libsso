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
* Test zda je uživatel reálný nebo dummy (vysvětleno dále): `$user->isDummy()`


### Testovací rozhraní

Pro účely testování je možné změnit URL SSO aplikace. Existuje dokonce již připravené testovací SSO rozhraní, na kterém je možné
odladit aplikaci a mít přístup k vícero účtům. Testovací SSO rozhraní poskytuje navenek stejné API jako produkční SSO rozhraní,
ale poskytuje navíc funkci přihlášení se pomocí testovacích účtů.

Změnit rozhraní lze skrze nepovinné parametry konstruktoru třídy `SSO`. Ve skutečnosti má konstruktor třídy `SSO` tři nepovinné
parametry:

1. `$ssoGatewayUrl` - hlavní URL SSO gatewaye
2. `$ssoGatewayCheckUrl` - URL pro přenos dat přihlášeného uživatele (nepovinné, odvozeno z `$ssoGatewayUrl` pokud není explicitně nastaveno)
3. `$ssoUserClass` - třída pro objekty typu uživatel. Musí to být podřída třídy `SPSOstrov\SSO\SSOUser`, pokud je nastaveno.
   Používá se jenom, pokud chcete rozšířit základní funkce třídy `SSOUser` ve vaší aplikaci. Konstruktor podtřídy pak musí mít stejné rozhraní
   jako konstruktor třídy `SSOUser`.

Obě URL (`$ssoGatewayUrl` a `$ssoGatewayCheckUrl`) mohou mít tyto hodnoty:

- `null` - implicitní nastavení - produkční SSO gateway a její check URL
- `production` - explicitně nastavená hodnota URL produkční SSO gatewaye
- `testing` - explicitně nastavená hodnota URL testovací SSO gatewaye
- cokoliv jiného - libovolné URL, `$ssoGatewayCheckUrl` přitom může být zadáno i relativně vůči `$ssoGatewayUrl`

Příklady:

```php
$sso = new SSO();             // implicitně produkční SSO gateway
$sso = new SSO("production"); // explicitně produkční SSO gateway
$sso = new SSO("testing");    // testovací SSO gateway
$sso = new SSO("https://www.exapmle.com/ssogw/", "https://www.example.com/ssogw/check.php"); // vlastní nastavení URL
$sso = new SSO("production", "testing"); // produkční SSO gateway s testovacím check-url (nedává smysl to takto nastavovat, ale možné to je)
```

### Dummy uživatelé

Dummy uživatel je ve skutečnosti nereálný uživatel, který ale formálně má stejné rozhraní jako reálný uživatel. SSO knihovna sama nikdy negeneruje dummy uživatele.
Dummy uživatel slouží jenom k tomu, aby si jej mohl vytvořit kód aplikace používající knihovnu, aniž by došlo k reálné autorizaci. Dummy uživatel je tak formálně
instancí třídy `SSOUser`, ale taková instance nevznikla reálně proběhlou SSO autorizací, ale explicitním vytvořením.

V současné době jsou podporovány pouze omezené možnosti nesení informací u dummy uživatelů. V obecnosti lze u dummy přistoupit jenom k uživatelskému jménu a k
času vytvoření. Pokus o přístup k ostatním údajům skončí vyvoláním výjimky.

Vytvoření dummy uživatele:

```php
    $user = SSOUser::createDummy();        // vytvoří dummy uživatele bez uživatelského jména. (ani k uživatelskému jménu pak nelze přistupovat)
    $user = SSOUser::createDummy(null);    // stejné jako výše, vytvoří dummy uživatele bez uživatelského jména

    $user = SSOUser::createDummy("dummy"); // vytvoří dummy uživatele s uživatelským jménem "dummy"
```

Test, zda je uživatel reálným nebo dummy uživatelem:
```php
    if ($user->isDummy()) {
        echo "Toto je dummy uživatel!";
    } else {
        echo "Toto je reálný uživate!";
    }
```

Rozdíly mezi reálným a dummy uživatelem:

* U dummy uživatele lze přistupovat pouze k těmto údajům:
   * uživatelskému jménu (není-li to dummy uživatel bez uživatelského jména): `$user->getLogin()`
   * informaci, zda je uživatel dummy uživatelem nebo reálným uživatelem: `$user->isDummy()`
   * informaci o času posledního přihlášení (u dummy uživatele je časem vytvoření): `$user->getLoginTimestamp()`
* Pokus o přístup k jakýmkoliv jiným údajům, než výše zmíněným, vyvolá u dummy uživatele výjimku.
* Pole všech údajů o uživateli (`$user->asArray()`) nevrací u dummy uživatelů nedostupné údaje.
* Příkaz `$user->prettyPrint()` vypisuje u dummy uživatelů pouze dostupné údaje.

Smyslem dummy uživatelů není řešit otázku přímé autorizace, ale rozšířit možnosti autorizace u složitějších projektů o další možnosti kde standardní autorizace
není dostačující. Dummy uživatele lze používat společně s rozšířením třídy `SSOUser` (vizte dále).


### Rozšíření třídy SSOUser

Pokud není řečeno jinak, instance třídy `SSO` vytváří instance třídy `SSOUser`. Za určitých okolností lze ale toto standardní chování změnit a knihovna může
generovat i instance potomků této třídy. Toho se dosáhne využitím třetího parametru konstruktoru třídy `SSO`, kterým se označuje třída, která se má použít
k vytváření instancí uživatelů. To umožňuje efektivně uživatelům knihovny rozšiřovat knihovní funkce třídy `SSOUser`. Aby to bylo možné, musí být u
rozšiřující třídy splněny tyto podmínky:

* rozšiřující třída dědí od třídy `SSOUser`
* rozšiřující třída používá stejnou signaturu konstruktoru jako třída `SSOUser` (a konstruktor rozšiřující třídy interně volá konstruktor `SSOUser`)

Příklad:
```php
class SSOUserExtended extends SSOUser
{
    public function isInvalid(): bool
    {
        return !$this->isTeacher() && !$this->isStudent();
    }
}

$sso = new SSO(null, null, SSOUserExtended::class);

$user = $user->doLogin(); // $user je instance třídy SSOUserExtended
```

Tento příklad rozšiřuje funkce třídy `SSOUser` o novou metodu `isInvalid()` která je schopná u uživatele testovat zda je validní
(musí být buď učitel nebo student, což pravidla SSO protokolu nezaručují, aby byl aspoň jedním nebo druhým).

Odesílač upomínek pro AbraFlexi
==============================

![Package Logo](abraflexi-reminder.svg?raw=true "Project Logo")

* PDF a ISDOC přílohy
* QR Platby (volitelně)
* Adresář pro vaše moduly např. odeslání SMS nebo odpojení neplatiče
* Definovatelná maximální velikost emailu
* Blacklist pro možnost ignorování některých druhů dokladů
* Řízeno štítky (např. štítek NEUPOMINKOVAT)
* Podpora cizích měn
* Logo vaší firmy
* Česká a anglická lokalizace. (gettext překladový systém)
* balíčky pro debian/ubuntu ale může běžet i na windows

Příkaz **abraflexi-show-debts** pouze vypíše pohledávky dle jednotlivých dlužníků (export do json).

Příkaz **abraflexi-reminder** Po spuštění (vytvoří potřebné štítky a)
zkontroluje v přednastavené firmě pohledávky. Při odeslání upomínky
Pokud nemá zákazník nastaven štítek NEUPOMINKOVAT, je mu odeslána upomínka.
příkaz je určen k automatickému spouštění každý den.

![Upomínka](reminder-screenshot.png?raw=true "ukázka upomínky")

Příkaz **abraflexi-inventarize** zašle klientům přehled jejich závazků.
Předpokládá se jeho automatické spouštění jednou za měsíc.

Prohledávají se evidence "vydané faktury" a "pohledávky"

Funkce štítků
-------------

Štítky mají jak informativní tak řídící funkci. Po spuštění upomínkovače se nejprve se projdou všichni klienti a těm kteří nemají žádní neuhrazené pohledávky jsou odstraněny štítky  UPOMINKA1,UPOMINKA2,UPOMINKA3 a NEPLATIC.
Datum odeslání upomínky je zapisováno do jednotlivé faktury do sloupců `datUp1`,`datUp2` a `datSmir` - [více sloupců ve faktuře abraflexi na to není](https://demo.flexibee.eu/c/demo/faktura-vydana/properties).
Avšak upomínaný je klient ne faktura a tuto skutečnost je třeba nějakým způsobem poznamenat. To se děje právě prostřednictvím štítku.
Tzn. pokud má klient nastavený štítek `UPOMINKA1` a `UPOMINKA2` znamená to, že klientovi byly již odeslány dvě upomínky. Pro program to znamená že další odeslaná upomínka již bude pokus o smír.
Současně je také informace o tom že upomínka byla opravdu odeslána. tzn. nenastaví se v případě že na zákazníka není znám email, nebo že poštovní server zrovna někdo rebootoval.
Další týden po odeslání třetí upomínky se klientovi nastaví informativní štítek `NEPLATIC`

Upomínka Mailem
---------------

Texty upomínek se mění ve abraflexi evidenci **sablona-upominky**
A je poznamenán datum jejího odeslání a současně je zákazníkovi přiřazen štítek `UPOMINKA1-3`
Odeslaná upomínka obsahuje přehled všech položek po splatnosti a k nim patřičné přílohy ve formátech pdf a isdocx

Upomínka SMS
------------

Upomínky je v současné době možné zasílat jako SMS prostřednictvím těchto metod:

* **místní gnokii** - na stejném stroji kde běží upomínkovač je nainstalována aplikace gnokii.
* **vzdálené gnokii** - gnokii je nainstalována na jiném stroji. Příkaz na něm je spouštěn prostřednictvím SSH s klíčem
* **Axfone SMS brána** - Vaše přihlašovací údaje zadejte do konfiguráku pod klíči **AXFONE_USERNAME** a **AXFONE_PASSWORD**
* **Huawei E5180 API** - Nastavte **MODEM_PASSWORD** (případně **MODEM_IP** pokud se liší od 192.168.8.1)

![SMS Upomínka](reminder-sms-screenshot.png?raw=true "ukázka SMS upomínky")

jiná akce při upomínání
-----------------------

Do složky  **src/AbraFlexi/Reminder/Notifier** ( /usr/lib/abraflexi-reminder/Reminder/Notifier/ v případě instalace z debianího balíčku )
je možné přidat další moduly vykonávající akci. Například odpojení neplatiče od služby atd.
Jak takové doplňky psát by mělo být zřejmé z [ByEmail.php](src/AbraFlexi/Reminder/Notifier/ByEmail.php)

Debian/Ubuntu
-------------

Pro Linux jsou k dispozici .deb balíčky. Prosím použijte repo:

```shell
    sudo apt install lsb-release wget
    echo "deb http://repo.vitexsoftware.com $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
    sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
    apt update
    apt install abraflexi-reminder
```

Po instalaci balíku jsou v systému k dispozici tyto nové příkazy:

* **abraflexi-show-debts**       - vypíše nalezené pohledávky
* **abraflexi-reminder**         - obešle dlužníky
* **abraflexi-notify-customers** - odešle klientovi seznam jeho závazků
* **abraflexi-reminder-init**    - připraví předvolenou firmu na použití s upomínačem

Konfigurace
-----------

se nastavuje v souboru  /etc/abraflexi/**reminder.json**  nebo do proměnných prostředí

```json
    "EASE_MAILTO": "info@yourdomain.net",
    "REMIND_FROM": "noreply@yourdomain.net",
    "EASE_LOGGER": "syslog|mail",
    "QR_PAYMENTS": true,
    "MAX_MAIL_SIZE": 1250000
    "SKIPLIST": "DOBROPIS,ZDD",
    "MUTE": false,
    "SMS_SENDER": "+420739778202",
    "SMS_ENGINE": "gnokii"
```

* **EASE_MAILTO**   - kam zasílat protokol v případě že je povoleno logování do mailu
* **MAIL_CC**       - zasílat kopii kazdé odeslané zprávy také na tento email
* **REMIND_FROM**   - adresa odesilatele v upomínkách
* **EASE_LOGGER**   - Jak logovat ? (dostupné metody jsou: memory,console,file,syslog,email,std,eventlog)
* **MAX_MAIL_SIZE** - maximální velikost vysledného mailu v Bytech. (1250000 = 10Mb) Pokud je tato velikost překročena, nejsou již přikládány žádné další přílohy.
* **QR_PAYMENTS**   - zda vložit do upomínky QR kódy pro [QR Platby](http://qr-platba.cz/)
* **ADD_LOGO**      - zda vložit do upomínky logo upomínající firmy
* **REMINDER_SKIPDOCTYPE**  - nebrat doklady těchto typů v potaz
* **MUTE**          - neodesílá klientům notifikace. Maily se pro kontrolu odesílají na **EASE_MAILTO**
* **SMS_SENDER**    - Telefoní číslo odesilatele sms. Např.: +420739778202
* **SMS_ENGINE**    - Metoda odeslání SMS. Možné hodnoty: **none**: neodesílat SMS, **gnokii**: místní Gnokii, **sshgnokii**: [Gnokii](https://www.gnokii.org/) na vzdáleném serveru (GNOKII_HOST) , **axfone** [Axfone](https://www.axfone.eu/) API
* **SMS_SIGNATURE** - podpis připojovaný na konec odesílaných SMS
* **GNOKII_HOST**   - specifikace serveru kde je modem. Může být i ve formátu login@host
* **AXFONE_USERNAME** - Login pro AXFONE api
* **AXFONE_PASSWORD** - Heslo pro AXFONE api
* **MODEM_PASSWORD** - Heslo webového rozhraní Huawei E5180
* **MODEM_IP**      - ip adresa modemu (nepovinné)
* **JSON_REPORT_FILE** - Uloží přehled dlužníků do Json souboru
* **NO_REMIND_LABEL** - Není li zadáno používá se se **NEUPOMINAT**
* **OVERDUE_PATIENCE** - trpělivost ve dnech před odesíláním inventarizace

V případě že nepoužíváte debianí balíček ale pouze klonujete repozitář, je potřeba před prvním použitím spustit [skript Init.php](src/Init.php) který vytvoří štítky 'UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC', 'NEUPOMINKOVAT'

ISDS Datové schránky
--------------------

* **DATOVKA_LOGIN** - Přihlašovací jméno do datovky
* **DATOVKA_PASSWORD** - Login do datovky

Upomínky a inventarizace jsou odesílány i do datovky pokud je tato nastavena v konfiguraci a dlužík má v adresáři nastavený štítek `DATA_BOX`

Závislosti
----------

Tento nástroj ke svojí funkci využívá následující knihovny:

* [**EasePHP Framework core**](https://github.com/VitexSoftware/php-ease-core)      - pomocné funkce např. logování
* [**PHP AbraFlexi**](https://github.com/Spoje-NET/php-abraflexi)                   - komunikace s [AbraFlexi](https://abraflexi.eu/)
* [**PHP AbraFlexi Bricks**](https://github.com/VitexSoftware/php-abraflexi-bricks) - používá se třída Zákazníka
* [**CzechDataBox**](https://github.com/dfridrich/CzechDataBox)                     - Komunikace s datovými schránkami

Mohlo by vás zajímat
--------------------

* https://github.com/VitexSoftware/php-abraflexi-matcher - Párovač faktur 
* https://github.com/VitexSoftware/AbraFlexi-Digest      - Pravidelný souhrn vašeho AbraFlexi

Poděkování
----------

Tento projekt by nevznikl bez podpory společnosti [Spoje.Net s.r.o.](http://spoje.net/)

![Spoje.Net](https://raw.githubusercontent.com/VitexSoftware/php-abraflexi-reminder/master/logo-spojenet.png "Spoje.Net s.r.o.")

Za HTML verzi upomínek a zahrnutí ostatních pohledávek, jež bylo hrazeno společností [Medinet .s.r.o.](http://medinetsro.cz/)

![Medinet](https://raw.githubusercontent.com/VitexSoftware/php-abraflexi-reminder/master/mendinet-logo.png "Medinet s.r.o.")

MultiFlexi
----------

AbraFlexi Reminder is ready for run as [MultiFlexi](https://multiflexi.eu) application.

<img src="abraflexi-inventarize.svg?raw=true" width="100" height="100"><img src="abraflexi-reminder-clean-labels.svg?raw=true" width="100" height="100"><img src="abraflexi-reminder.svg?raw=true" width="100" height="100"><img src="abraflexi-show-debts.svg?raw=true" width="100" height="100">

See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/apps.php)

## Exit Codes

Applications in this package use the following exit codes:

- `0`: Success

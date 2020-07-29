![Package Logo](https://raw.githubusercontent.com/VitexSoftware/php-flexibee-reminder/master/package_logo.png "Project Logo")

Odesílač upomínek pro FlexiBee
==============================

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

Příkaz **flexibee-debts** pouze vypíše pohledávky dle jednotlivých dlužníků.

Příkaz **flexibee-reminder** Po spuštění (vytvoří potřebné štítky a) 
zkontroluje v přednastavené firmě pohledávky. Při odeslání upomínky 
Pokud nemá zákazník nastaven štítek NEUPOMINKOVAT, je mu odeslána upomínka.
příkaz je určen k automatickému spouštění každý den.

![Upomínka](https://raw.githubusercontent.com/VitexSoftware/php-flexibee-reminder/master/reminder-screenshot.png "ukázka upomínky")

Příkaz **flexibee-inventarize** zašle klientům přehled jejich závazků. 
Předpokládá se jeho automatické spouštění jednou za měsíc.

Prohledávají se evidence "vydané faktury" a "pohledávky"


Funkce štítků
-------------

Štítky mají jak informativní tak řídící funkci. Po spuštění upomínkovače se nejprve se projdou všichni klienti a těm kteří nemají žádní neuhrazené pohledávky jsou odstraněny štítky  UPOMINKA1,UPOMINKA2,UPOMINKA3 a NEPLATIC.
Datum odeslání upomínky je zapisováno do jednotlivé faktury do sloupců datUp1,datUp2 a datSmir - více sloupců ve faktuře flexibee na  to není. viz: https://demo.flexibee.eu/c/demo/faktura-vydana/properties .
Avšak upomínaný je klient ne faktura a tuto skutečnost je třeba nějakým způsobem poznamenat. To se děje právě prostřednictvím štítku.
Tzn. pokud má klient nastavený štítek UPOMINKA1 a UPOMINKA2 znamená to, že klientovi byly již odeslány dvě upomínky. Pro program to znamená že další odeslaná upomínka již bude pokus o smír.
Současně je také informace o tom že upomínka byla opravdu odeslána. tzn. nenastaví se v případě že na zákazníka není znám email, nebo že poštovní server zrovna někdo rebootoval.
Další týden po odeslání třetí upomínky se klientovi nastaví informativní štítek NEPLATIC

Upomínka Mailem
---------------

Texty upomínek se mění ve flexibee evidenci **sablona-upominky**
A je poznamenán datum jejího odeslání a současně je zákazníkovi přiřazen štítek UPOMINKA1-3
Odeslaná upomínka obsahuje přehled všech položek po splatnosti a k nim patřičné přílohy ve formátech pdf a isdocx

Upomínka SMS 
------------

Upomínky je v současné době možné zasílat jako SMS prostřednictvím těchto metod:

 * **místní gnokii** - na stejném stroji kde běží upomínkovač je nainstalována aplikace gnokii.
 * **vzdálené gnokii** - gnokii je nainstalována na jiném stroji. Příkaz na něm je spouštěn prostřednictvím SSH s klíčem
 * **Axfone SMS brána** -  Vaše přihlašovací údaje zadejte do konfiguráku pod klíči **AXFONE_USERNAME** a **AXFONE_PASSWORD**

![SMS Upomínka](https://raw.githubusercontent.com/VitexSoftware/php-flexibee-reminder/master/reminder-sms-screenshot.png "ukázka SMS upomínky")

Papírová Upomínka
-----------------

Je odesílána pomocí služby: [Listonoška](https://www.listonoska.cz/) Pro použití služby potřebujete přístupové údaje které jsou k tarifu [PROFI](https://www.listonoska.cz/posta-pro-firmy)
Do konfiguračního dialogu je třeba doplnit 

    "LISTONOSKA_ID": "vas@registracni.mail",
    "LISTONOSKA_KEY": "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"



jiná akce při upomínání
-----------------------

Do složky  **src/FlexiPeeHP/Reminder/Notifier** ( /usr/lib/php-flexibee-reminder/Reminder/Notifier/ v případě instalace z debianího balíčku )
je možné přidat další moduly vykonávající akci. Například odpojení neplatiče od služby atd.
Jak takové doplňky psát by mělo být zřejmé z [ByEmail.php](src/FlexiPeeHP/Reminder/Notifier/ByEmail.php)


Debian/Ubuntu
-------------

Pro Linux jsou k dispozici .deb balíčky. Prosím použijte repo:

```shell
    sudo apt install lsb-release wget
    echo "deb http://repo.vitexsoftware.cz $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
    sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
    apt update
    apt install flexibee-reminder
```

Po instalaci balíku jsou v systému k dispozici tyto nové příkazy:

  * **flexibee-debts**            - vypíše nalezené pohledávky
  * **flexibee-reminder**         - obešle dlužníky
  * **flexibee-nontify-customers** - odešle klientovi seznam jeho závazků
  * **flexibee-reminder-init**    - připraví předvolenou firmu na použití s upomínačem 

Konfigurace
-----------

se nastavuje v souboru  /etc/flexibee/**reminder.json** 

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
  * **SKIPLIST**      - nebrat doklady těchto typů v potaz
  * **MUTE**          - neodesílá klientům notifikace. Maily se pro kontrolu odesílají na **EASE_MAILTO**
  * **SMS_SENDER**    - Telefoní číslo odesilatele sms. Např.: +420739778202
  * **SMS_ENGINE**    - Metoda odeslání SMS. Možné hodnoty: **none**: neodesílat SMS, **gnokii**: místní Gnokii, **sshgnokii**: [Gnokii](https://www.gnokii.org/) na vzdáleném serveru (GNOKII_HOST) , **axfone** [Axfone](https://www.axfone.eu/) API
  * **GNOKII_HOST**   - specifikace serveru kde je modem. Může být i ve formátu login@host 
  * **AXFONE_USERNAME** - Login pro AXFONE api
  * **AXFONE_PASSWORD** - Heslo pro AXFONE api


V případě že nepoužíváte debianí balíček ale pouze klonujete repozitář, je potřeba před prvním použitím spustit [skript Init.php](src/Init.php) který vytvoří štítky 'UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC', 'NEUPOMINKOVAT'


# Třídy v FlexiPeeHP/Reminder/:

| Soubor                                                        | Popis                                 |
| ------------------------------------------------------------- | --------------------------------------|
| [Mailer.php](src/FlexiPeeHP/Reminder/Mailer.php)              | Třída pro HTML email
| [Upominac.php](src/FlexiPeeHP/Reminder/Upominac.php)          | Třída upomínající neplatiče
| [Upominka.php](src/FlexiPeeHP/Reminder/Upominka.php)          | Třída upomínky pro neplatiče

Závislosti
----------

Tento nástroj ke svojí funkci využívá následující knihovny:

 * [**EasePHP Framework core**](https://github.com/VitexSoftware/php-ease-core) - pomocné funkce např. logování
 * [**PHP FlexiBee**](https://github.com/Spoje-NET/php-flexibee)                - komunikace s [FlexiBee](https://flexibee.eu/)
 * [**PHP FlexiBee Bricks**](https://github.com/VitexSoftware/php-flexibee-bricks) - používá se třída Zákazníka

Mohlo by vás zajímat
--------------------

 * https://github.com/VitexSoftware/php-flexibee-matcher - Párovač faktur
 * https://github.com/VitexSoftware/FlexiBee-Digest      - Pravidelný souhrn

Poděkování
----------

Tento projekt by nevznikl bez podpory společnosti [Spoje.Net s.r.o.](http://spoje.net/)

![Spoje.Net](https://raw.githubusercontent.com/VitexSoftware/php-flexibee-reminder/master/logo-spojenet.png "Spoje.Net s.r.o.")

Za HTML verzi upomínek a zahrnutí ostatních pohledávek bylo hrazeno společností [Medinet .s.r.o.](http://medinetsro.cz/)

![Medinet](https://raw.githubusercontent.com/VitexSoftware/php-flexibee-reminder/master/mendinet-logo.png "Medinet s.r.o.")

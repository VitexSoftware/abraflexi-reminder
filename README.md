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

Příkaz **flexibee-notify-customer** zašle klientovi přehled jeho závazků. 
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


Upomínka SMS nebo jiná akce při upomínání
-----------------------------------------

Do složky  **notifiers** ( /usr/lib/php-flexibee-reminder/notifiers/ v případě instalace z debianího balíčku )
je možné přidat další moduly vykonávající akci. Například odpojení neplatiče od služby atd.
Jak takové doplňky psát by mělo být zřejmé z [ByEmail.php](src/notifiers/ByEmail.php)


Debian/Ubuntu
-------------

Pro Linux jsou k dispozici .deb balíčky. Prosím použijte repo:

    wget -O - http://v.s.cz/info@vitexsoftware.cz.gpg.key|sudo apt-key add -
    echo deb http://v.s.cz/ stable main > /etc/apt/sources.list.d/ease.list
    apt update
    apt install php-flexibee-reminder

Po instalaci balíku jsou v systému k dispozici tyto nové příkazy:

  * **flexibee-debts**            - vypíše nalezené pohledávky
  * **flexibee-reminder**         - obešle dlužníky
  * **flexibee-nontify-customers** - odešle klientovi seznam jeho závazků 

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
    "SMS_ENGINE": "sshgnokii"
```

  * **EASE_MAILTO** kam zasílat protokol v případě že je povoleno logování do mailu
  * **EASE_LOGGER** Jak logovat ? (dostupné metody jsou: memory,console,file,syslog,email,std,eventlog)
  * **MAX_MAIL_SIZE** - maximální velikost vysledného mailu v Bytech. (1250000 = 10Mb) Pokud je tato velikost překročena, nejsou již přikládány žádné další přílohy.
  * **QR_PAYMENTS**   - zda vložit do upomínky QR kódy pro [QR Platby](http://qr-platba.cz/)
  * **ADD_LOGO**      - zda vložit do upomínky logo upomínající firmy
  * **SKIPLIST**      - nebrat doklady těchto typů v potaz
  * **MUTE**          - neodesílá klientům notifikace. Maily se pro kontrolu odesílají na **EASE_MAILTO**
  * **SMS_SENDER**    - Telefoní číslo odesilatele sms. Např.: +420739778202
  * **SMS_ENGINE**    - Metoda odeslání SMS. Možné hodnoty: **none**: neodesílat SMS, **gnokii**: místní Gnokii, **sshgnokii**: [Gnokii](https://www.gnokii.org/) na vzdáleném serveru , **axfone** [Axfone](https://www.axfone.eu/) API


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

 * [**EasePHP Framework**](https://github.com/VitexSoftware/EaseFramework) - pomocné funkce např. logování
 * [**FlexiPeeHP**](https://github.com/Spoje-NET/FlexiPeeHP)        - komunikace s [FlexiBee](https://flexibee.eu/)
 * [**FlexiPeeHP Bricks**](https://github.com/VitexSoftware/FlexiPeeHP-Bricks) - používá se třída Zákazníka

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

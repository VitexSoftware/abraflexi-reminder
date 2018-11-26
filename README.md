![Package Logo](https://raw.githubusercontent.com/VitexSoftware/php-flexibee-reminder/master/package_logo.png "Project Logo")

Odesílač upomínek pro FlexiBee
==============================


Příkaz **flexibee-debts** pouze vypíše pohledávky dle jednotlivých dlužníků.

Příkaz **flexibee-reminder** Po spuštění (vytvoří potřebné štítky a) 
zkontroluje v přednastavené firmě pohledávky. Pokud nemá zákazník nastaven 
štítek NEUPOMINKOVAT, je mu odeslána upomínka.

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

Po instalaci balíku jsou v systému k dispozici dva nové příkazy:

  * **flexibee-debts**    - vypíše nalezené pohledávky
  * **flexibee-reminder** - obešle dlužníky

Konfigurace
-----------

se nastavuje v souboru  /etc/flexibee/**reminder.json**

```json
    "EASE_MAILTO": "info@yourdomain.net",
    "EASE_LOGGER": "syslog|mail",
    "PATIENCE_DAYS": 0
```

  * **EASE_MAILTO** kam zasílat protokol v případě že je povoleno logování do mailu
  * **EASE_LOGGER** Jak logovat ? (dostupné metody jsou: memory,console,file,syslog,email,std,eventlog)
  * **PATIENCE_DAYS** - pokud je 0 budou do upomínky přikládány i faktury které jsou již vystavené ale ještě nejsou po splatnosti


V případě že nepoužíváte debianí balíček ale pouze klonujete repozitář, je potřeba před prvním použitím spustit [skript Init.php](src/Init.php) který vytvoří štítky 'UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC', 'NEUPOMINKOVAT'


# Třídy v FlexiPeeHP/Reminder/:

| Soubor                                                        | Popis                                 |
| ------------------------------------------------------------- | --------------------------------------|
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

![Package Logo](https://raw.githubusercontent.com/VitexSoftware/php-flexibee-reminder/master/package_logo.png "Project Logo")

Odesílač upomínek pro FlexiBee
==============================

Příkaz **php-flexibee-reminder** Po spuštění (vytvoří potřebné štítky a) 
zkontroluje v přednastavené firmě pohledávky. Pokud nemá zákazník nastaven 
štítek NEUPOMINKOVAT, je mu odeslána upomínka.
A je poznamenán datum jejího odeslání a současně je zákazníkovi přiřazen štítek 
UPOMINKA1-3

Texty upomínek se mění ve flexibee evidenci **sablona-upominky**

Odeslaná upomínka obsahuje přehled všech položek po splatnosti a k nim patřičné přílohy ve formátech pdf a isdocx

Debian/Ubuntu
-------------

Pro Linux jsou k dispozici .deb balíčky. Prosím použijte repo:

    wget -O - http://v.s.cz/info@vitexsoftware.cz.gpg.key|sudo apt-key add -
    echo deb http://v.s.cz/ stable main > /etc/apt/sources.list.d/ease.list
    apt update
    apt install php-flexibee-reminder

Po instalaci balíku jsou v systému k dispozici dva nové příkazy:

  * **php-flexibee-debts**    - vypíše nalezené pohledávky
  * **php-flexibee-reminder** - obešle dlužníky


Závislosti
----------

Tento nástroj ke svojí funkci využívá následující knihovny:

 * [**EasePHP Framework**](https://github.com/VitexSoftware/EaseFramework) - pomocné funkce např. logování
 * [**FlexiPeeHP**](https://github.com/Spoje-NET/FlexiPeeHP)        - komunikace s [FlexiBee](https://flexibee.eu/)
 * [**FlexiPeeHP Bricks**](https://github.com/VitexSoftware/FlexiPeeHP-Bricks) - používají se třídy Zákazníka, Upomínky a Upomínače


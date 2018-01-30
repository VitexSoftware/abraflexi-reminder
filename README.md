![Package Logo](https://raw.githubusercontent.com/VitexSoftware/php-flexibee-reminder/master/package_logo.png "Project Logo")

Odesílač upomínek pro FlexiBee
==============================

Po spuštění zkontroluje v přednastavené firmě pohledávky. Pokud nemá zákazník nastaven štítek NEUPOMINKOVAT, je mu odeslána upomínka.
A je poznamenán datum jejího odeslání a současně je zákazníkovi přiřazen štítek UPOMINKAX

Texty upomínek se mění ve flexibee evidenci **sablona-upominky**

Debian/Ubuntu
-------------

Pro Linux jsou k dispozici .deb balíčky. Prosím použijte repo:

    wget -O - http://v.s.cz/info@vitexsoftware.cz.gpg.key|sudo apt-key add -
    echo deb http://v.s.cz/ stable main > /etc/apt/sources.list.d/ease.list
    aptitude update
    aptitude install php-flexibee-reminder

Po instalaci balíku jsou v systému k dispozici dva nové příkazy:

  * **php-flexibee-debts**    - vypíše nalezené pohledávky
  * **php-flexibee-reminder** - obešle dlužníky

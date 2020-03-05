FROM debian:buster-slim
env DEBIAN_FRONTEND=noninteractive

RUN apt update ; apt install -y wget; echo "deb http://repo.vitexsoftware.cz buster main" | tee /etc/apt/sources.list.d/vitexsoftware.list ; wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
RUN apt install -y gdebi-core apt-utils ; apt update
ADD dist/flexibee-reminder*.deb /tmp/
RUN gdebi -n /tmp/flexibee-reminder_*_all.deb
RUN gdebi -n /tmp/flexibee-reminder-papermail_*_all.deb  
RUN gdebi -n /tmp/flexibee-reminder-sms_0.19_all.deb  

RUN apt-get update && apt-get install -y locales locales-all && rm -rf /var/lib/apt/lists/* \
    && localedef -i cs_CZ -c -f UTF-8 -A /usr/share/locale/locale.alias cs_CZ.UTF-8
ENV LANG cs_CZ.utf8



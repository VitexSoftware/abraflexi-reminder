all: fresh build install

composer:
	composer update

fresh:
	echo fresh

install: 
	echo install
	
build:
	echo build

clean:
	rm -rf debian/php-flexibee-reminder 
	rm -rf debian/php-flexibee-reminder-sms 
	rm -rf debian/php-flexibee-reminder-papermail
	rm -rf debian/*.substvars debian/*.log debian/*.debhelper debian/files debian/debhelper-build-stamp

deb:
	dpkg-buildpackage -A -us -uc

.PHONY : install
	
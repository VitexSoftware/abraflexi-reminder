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
	rm -rf debian/flexibee-reminder 
	rm -rf debian/flexibee-reminder-sms 
	rm -rf debian/flexibee-reminder-gnokii 
	rm -rf debian/flexibee-reminder-papermail
	rm -rf debian/*.substvars debian/*.log debian/*.debhelper debian/files debian/debhelper-build-stamp
	rm -rf dist

debts:
	cd src ; php -f flexibee-debts.php ; cd ..

deb:
	dpkg-buildpackage -A -us -uc

dimage: deb
	mkdir  -p dist
	cp ../flexibee-reminder*.deb dist
	docker build .

.PHONY : install
	
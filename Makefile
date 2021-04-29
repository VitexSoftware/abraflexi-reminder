repoversion=$(shell LANG=C aptitude show flexibee-reminder | grep Version: | awk '{print $$2}')
nextversion=$(shell echo $(repoversion) | perl -ne 'chomp; print join(".", splice(@{[split/\./,$$_]}, 0, -1), map {++$$_} pop @{[split/\./,$$_]}), "\n";')



all:

composer:
	composer update

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

release:
	echo Release v$(nextversion)
	dch -v $(nextversion) `git log -1 --pretty=%B | head -n 1`
	debuild -i -us -uc -b
	git commit -a -m "Release v$(nextversion)"
	git tag -a $(nextversion) -m "version $(nextversion)"


.PHONY : install
	
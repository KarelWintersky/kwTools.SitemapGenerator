#!/usr/bin/make
SHELL	 = /bin/bash

DIR_SOURCES="$(PWD)/sources"

FILE_SOURCE="kw_sitemapgenerator.php"

DIR_PRODUCTION=$(PWD)/production

FILE_PRODUCTION="$(DIR_PRODUCTION)/kwsitemapgenerator"

PROJECT=kwsitemapgenerator
VAR_ROOT=$(DESTDIR)/usr/local/bin

help:
	@perl -e '$(HELP_ACTION)' $(MAKEFILE_LIST)

update:		##@build Update project from GIT
	@echo Updating project from GIT
	git pull

build_local:	##@build Cook v2 version
	@echo 'Generating all-in-one php-file'
	@echo '#!/usr/bin/php' > $(FILE_PRODUCTION)
	@cat $(DIR_SOURCES)/$(FILE_SOURCE) >> $(FILE_PRODUCTION)

	@echo 'Incapsulating class.SitemapSystem.php'
	@cp $(DIR_SOURCES)/class.SitemapSystem.php __tmp__.php
	@sed -i "1s/.*/\/\*\*\//" __tmp__.php
	@sed -i -e "/class\.SitemapSystem\.php/{r __tmp__.php"  -e 'd}' $(FILE_PRODUCTION)

	@echo 'Incapsulating class.SitemapFileSaver.php'
	@cp $(DIR_SOURCES)/class.SitemapFileSaver.php __tmp__.php
	@sed -i "1s/.*/\/\*\*\//" __tmp__.php
	@sed -i -e "/class\.SitemapFileSaver\.php/{r __tmp__.php"  -e 'd}' $(FILE_PRODUCTION)

	@rm -f __tmp__.php

	@echo 'Ok.'
	@echo "$(FILE_PRODUCTION) Generated"
	@echo

install: build_local	##system Install sitemap generator to /usr/local/bin, required sudo
	install -d $(VAR_ROOT)
	cp production/kwsitemapgenerator $(VAR_ROOT)/sitemapgenerator
	chmod +x $(VAR_ROOT)/sitemapgenerator

build:		##@build Build project to DEB Package
	@echo 'Building project to DEB-package'
	export DEBFULLNAME="Karel Wintersky" && export DEBEMAIL="karel.wintersky@gmail.com" && dpkg-buildpackage -rfakeroot --build=binary --sign-key=5B880AAEA75CA9F4AC7FB42281C5D6EECDF77864

build_unsigned: ##@build_unsigned Build unsigned DEB Package (for AJUR Repository)
	@echo 'Building DEB-package for AJUR Repository'
	dpkg-buildpackage -rfakeroot --build=binary --no-sign

build_seed:
	@echo Building SEED SQL file for tests

dch:
	dch -M -i

dchr:
	dch -M --release --distribution stable

# ------------------------------------------------
# Add the following 'help' target to your makefile, add help text after each target name starting with '\#\#'
# A category can be added with @category
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)
HELP_ACTION = \
	%help; while(<>) { push @{$$help{$$2 // 'options'}}, [$$1, $$3] if /^([a-zA-Z\-_]+)\s*:.*\#\#(?:@([a-zA-Z\-]+))?\s(.*)$$/ }; \
	print "usage: make [target]\n\n"; for (sort keys %help) { print "${WHITE}$$_:${RESET}\n"; \
	for (@{$$help{$$_}}) { $$sep = " " x (32 - length $$_->[0]); print "  ${YELLOW}$$_->[0]${RESET}$$sep${GREEN}$$_->[1]${RESET}\n"; }; \
	print "\n"; }

# -eof-

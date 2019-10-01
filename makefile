#!/usr/bin/make
SHELL	 = /bin/bash

DIR_SOURCES="$(PWD)/sources"

FILE_SOURCE_V1="generator.php"
FILE_SOURCE_V2="kw_sitemapgenerator.php"

DIR_PRODUCTION="$(PWD)/production"

FILE_PRODUCTION_V1="$(DIR_PRODUCTION)/sitemap_generator_v1.php"
FILE_PRODUCTION_V2="$(DIR_PRODUCTION)/sitemap_generator.php"

PROJECT=kwsitemapgenerator
VAR_ROOT=$(DESTDIR)

help:
	@perl -e '$(HELP_ACTION)' $(MAKEFILE_LIST)

update:		##@build Update project from GIT
	@echo Updating project from GIT
	git pull

build_generator_legacy:	##@build cook legacy version
	@echo 'Generating all-in-one V1 php-file'
	@echo
	
	@echo '#!/usr/bin/php' > $(FILE_PRODUCTION_V1)
	@cat $(DIR_SOURCES)/$(FILE_SOURCE_V1) >> $(FILE_PRODUCTION_V1)
	
	@echo 'Incapsulating class.CLIConsole.php'
	@cp $(DIR_SOURCES)/class.CLIConsole.php __tmp__.php
	@sed -i "1s/.*/\/\*\*\//" __tmp__.php
	@sed -i -e "/class\.CLIConsole\.php/{r __tmp__.php"  -e ' d}' $(FILE_PRODUCTION_V1)
	
	@echo 'Incapsulating class.INI_Config.php'
	@cp $(DIR_SOURCES)/class.INI_Config.php __tmp__.php
	@sed -i "1s/.*/\/\*\*\//" __tmp__.php
	@sed -i -e "/class\.INI_Config\.php/{r __tmp__.php"  -e 'd}' $(FILE_PRODUCTION_V1)
	
	@echo 'Incapsulating class.DBConnectionLite.php'
	@cp $(DIR_SOURCES)/class.DBConnectionLite.php __tmp__.php
	@sed -i "1s/.*/\/\*\*\//" __tmp__.php
	@sed -i -e "/class\.DBConnectionLite\.php/{r __tmp__.php"  -e 'd}' $(FILE_PRODUCTION_V1)
	
	@echo 'Incapsulating class.SitemapMessages.php'
	@cp $(DIR_SOURCES)/class.SitemapMessages.php __tmp__.php
	@sed -i "1s/.*/\/\*\*\//" __tmp__.php
	@sed -i -e "/class\.SitemapMessages\.php/{r __tmp__.php"  -e 'd}' $(FILE_PRODUCTION_V1)
	
	@echo 'Incapsulating class.SitemapFileSaver.php'
	@cp $(DIR_SOURCES)/class.SitemapFileSaver.php __tmp__.php
	@sed -i "1s/.*/\/\*\*\//" __tmp__.php
	@sed -i -e "/class\.SitemapFileSaver\.php/{r __tmp__.php"  -e 'd}' $(FILE_PRODUCTION_V1)
	
	@rm -f __tmp__.php
	
	@echo 'Ok.'
	@echo $(FILE_PRODUCTION_V1) GENERATED
	@echo

build_generator:	##@build Cook v2 version
	@echo 'Generating all-in-one php-file'
	@echo '#!/usr/bin/php' > $(FILE_PRODUCTION_V2)
	@cat $(DIR_SOURCES)/$(FILE_SOURCE_V2) >> $(FILE_PRODUCTION_V2)

	@echo 'Incapsulating class.SitemapSystem.php'
	@cp $(DIR_SOURCES)/class.SitemapSystem.php __tmp__.php
	@sed -i "1s/.*/\/\*\*\//" __tmp__.php
	@sed -i -e "/class\.SitemapSystem\.php/{r __tmp__.php"  -e 'd}' $(FILE_PRODUCTION_V2)

	@echo 'Incapsulating class.SitemapFileSaver.php'
	@cp $(DIR_SOURCES)/class.SitemapFileSaver.php __tmp__.php
	@sed -i "1s/.*/\/\*\*\//" __tmp__.php
	@sed -i -e "/class\.SitemapFileSaver\.php/{r __tmp__.php"  -e 'd}' $(FILE_PRODUCTION_V2)

	@rm -f __tmp__.php

	@echo 'Ok.'
	@echo "$(FILE_PRODUCTION_V2) Generated"
	@echo

install: build_generator	##system Install sitemap generator to /usr/local/bin, required sudo
	@echo Placing file to /usr/local/bin/
	@sudo cp --force ./production/sitemap_generator.php /usr/local/bin/sitemap_generator
	@echo Setting executable attribute
	@sudo chmod +x /usr/local/bin/sitemap_generator
	@echo '---------------------------------------------------------------'
	@sitemap_generator --help

build_seed:
	@echo Building SEED SQL file for tests

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

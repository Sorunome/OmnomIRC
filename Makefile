.PHONY: debug mini all info
WORKDIR=/home/sorunome/public_html/oirc

OIRCHTML=html/omnomirc_www
UGLIFYOPTIONS="-m --comments -v"

debug: mini
	for f in $$(find $(OIRCHTML)); do							\
		f=$${f:18};									\
		dir=$$(dirname "$${f}");							\
		if [[ $$dir != smileys ]] && [[ $$dir != smileys* ]] &&				\
			[[ $$f != config.json.php ]] && [[ $$f != config.backup.php ]] &&	\
			[[ $$f != updater.php ]] && [[ $$f != omnomirc_curid ]] &&		\
			[[ $$f != *.sql ]] && [[ ! -d "$(WORKDIR)/$$f" ]]; then						\
			dir=$$(dirname "$${f}");						\
			echo $$f;								\
			mkdir -p "$(WORKDIR)/$$dir";						\
			cp "$(OIRCHTML)/$$f" "$(WORKDIR)/$$f";					\
		fi										\
	done
	chmod go+w $(WORKDIR)/*
mini:
	for f in $$(find src -name '*.js'); do						\
		f=$${f:4};								\
		dir=$$(dirname "$${f}");						\
		f="$${f%.js}";								\
		mkdir -p "$(OIRCHTML)/$$dir";						\
		uglifyjs $(UGLIFYOPTIONS) "src/$$f.js" -o "$(OIRCHTML)/$$f.min.js";	\
		git add "$(OIRCHTML)/$$f.min.js";					\
	done
info:
	find . \( -name '*.php' -o -name '*.xml' -o -name '*.css' -o -name '*.html' -o -name '*.py' -o -name '*.sh' -o -name '*.js' -o -name '*.sql' \) \! \( -name '*.min.*' -o -name '\.*' \) -exec wc {} \+ | awk {'print $$4" Lines:"$$1" Bytes:"$$3'} | grep total

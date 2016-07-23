.PHONY: all

all:
	mkdir -p OmnomIRC/checkLogin
	cp *.php OmnomIRC
	cp plugin.settings OmnomIRC

	cp ../../html/checkLogin/index.php OmnomIRC/checkLogin
	sed 's/generic/abxd/' ../../html/checkLogin/config.json.php > OmnomIRC/checkLogin/config.json.php
	cp ../../html/checkLogin/hook-abxd.php OmnomIRC/checkLogin

	tar -zcvf omnomirc_abxd.tar.gz OmnomIRC/* > /dev/null
	zip -r omnomirc_abxd.zip OmnomIRC/* > /dev/null
	rm -r OmnomIRC

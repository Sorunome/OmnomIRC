#!/bin/bash
read -p "Old OmnomIRC version:" oldversion
read -p "New OmnomIRC version:" newversion

tmp=$(mktemp)

echo "building sourcefiles / updating..."
make mini
sed "s/$oldversion/$newversion/" html/omnomirc_www/config.json.php > $tmp
cp $tmp html/omnomirc_www/config.json.php
sed "s/$oldversion/$newversion/" html/omnomirc_www/updater.php > $tmp
cp $tmp html/omnomirc_www/updater.php
echo "done"
echo "committing git..."
git commit -am "version bump to $newversion"
echo "done"

echo "Creating remote directories...."
ssh sorunome.de "mkdir -p /var/www/omnomirc.omnimaga.org/$newversion/bot" > /dev/null
ssh sorunome.de "mkdir -p /var/www/omnomirc.omnimaga.org/$newversion/html" > /dev/null
ssh sorunome.de "mkdir -p /var/www/omnomirc.omnimaga.org/$newversion/checkLogin" > /dev/null
echo "done"

for f in $(git diff --name-only master dev); do
	base=$(dirname $f)
	file=$(basename $f)
	ext="${file##*.}"
	case $base in
		bot)
			if [ "$file" != ".gitignore" ] &&
					[ "$file" != "documentroot.cfg" ]; then
				scp $f "sorunome.de:/var/www/omnomirc.omnimaga.org/$newversion/bot/$file.s"
			fi
			;;
		html/checkLogin)
			if [ "$file" != "config.json.php" ]; then
				scp $f "sorunome.de:/var/www/omnomirc.omnimaga.org/$newversion/checkLogin/$file.s"
			fi
			;;
		html/omnomirc_www)
			if [ "$file" != "config.json.php" ] &&
					[ "$file" != "updater.php" ] &&
					[ "$file" != "config.backup.php" ] &&
					[ "$file" != "omnomirc_curid" ] &&
					[ "$file" != ".gitignore" ] &&
					[ "$ext" != "sql" ]; then
				scp $f "sorunome.de:/var/www/omnomirc.omnimaga.org/$newversion/html/$file.s"
			fi
			;;
	esac
done
echo "Uploading updater..."
./make_updater.sh "$oldversion" "$newversion" > $tmp
scp $tmp "sorunome.de:/var/www/omnomirc.omnimaga.org/$newversion/updater.php.s"
ssh sorunome.de "chmod go+r /var/www/omnomirc.omnimaga.org/$newversion/updater.php.s"
rm $tmp

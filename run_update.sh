#!/bin/bash
read -p "Old OmnomIRC version:" oldversion
read -p "New OmnomIRC version:" newversion

tmp=$(mktemp)
remoteServer="sorunome.de"
remotePath="/var/www/omnomirc.omnimaga.org/core"

ssh_sock=$(mktemp -u)
ssh -M -o "ControlPersist=yes" -S $ssh_sock $remoteServer ":"

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
ssh -S $ssh_sock $remoteServer "mkdir -p $remotePath/$newversion/bot" > /dev/null
ssh -S $ssh_sock $remoteServer "mkdir -p $remotePath/$newversion/html" > /dev/null
ssh -S $ssh_sock $remoteServer "mkdir -p $remotePath/$newversion/checkLogin" > /dev/null
echo "done"

subpath(){
	echo "$1" | cut -d"/" -f$(expr $2 + 1)-999999
}



for f in $(git diff --name-only master dev); do
	base=$(dirname $f)
	file=$(basename $f)
	ext="${file##*.}"
	case $base in
		bot)
			if [ "$file" != ".gitignore" ] &&
					[ "$file" != "documentroot.cfg" ]; then
				scp -o "ControlPath=$ssh_sock" $f "$remoteServer:$remotePath/$newversion/bot/$file.s"
			fi
			;;
		html/checkLogin)
			if [ "$file" != "config.json.php" ]; then
				scp -o "ControlPath=$ssh_sock" $f "$remoteServer:$remotePath/$newversion/checkLogin/$file.s"
			fi
			;;
		html/omnomirc_www*)
			if [ "$file" != "config.json.php" ] &&
					[ "$file" != "updater.php" ] &&
					[ "$file" != "config.backup.php" ] &&
					[ "$file" != "omnomirc_curid" ] &&
					[ "$file" != ".gitignore" ] &&
					[ "$ext" != "sql" ]; then
				ssh -S $ssh_sock $remoteServer "mkdir -p $remotePath/$newversion/html/$(subpath $base 2)" > /dev/null
				scp -o "ControlPath=$ssh_sock" $f "$remoteServer:$remotePath/$newversion/html/$(subpath $base 2)/$file.s"
			fi
			;;
	esac
done
echo "Uploading updater..."
./make_updater.sh "$oldversion" "$newversion" > $tmp
scp -o "ControlPath=$ssh_sock" $tmp "$remoteServer:$remotePath/$newversion/updater.php.s"
ssh -S $ssh_sock $remoteServer "chmod go+r $remotePath/$newversion/updater.php.s"
rm $tmp
ssh -O stop -S $ssh_sock $remoteServer

#!/bin/bash

function join { local IFS="$1"; shift; echo "$*"; }

declare -a bot_files
declare -a cl_files
declare -a normal_files
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
				bot_files=("${bot_files[@]}" "'$file'")
			fi
			;;
		html/checkLogin)
			if [ "$file" != "config.json.php" ]; then
				cl_files=("${cl_files[@]}" "'$file'")
			fi
			;;
		html/omnomirc_www*)
			if [ "$file" != "config.json.php" ] &&
					[ "$file" != "updater.php" ] &&
					[ "$file" != "config.backup.php" ] &&
					[ "$file" != "omnomirc_curid" ] &&
					[ "$file" != ".gitignore" ] &&
					[ "$ext" != "sql" ]; then
				if [ "$(subpath $base 2)" != "" ]; then
					normal_files=("${normal_files[@]}" "'$(subpath $base 2)/$file'")
				else
					normal_files=("${normal_files[@]}" "'$file'")
				fi
			fi
			;;
	esac
done
echo ${normal_files[@]}
exit
tmp1=$(mktemp)
tmp2=$(mktemp)
cp generic_updater.php $tmp1

sed "s/SED_INSERT_FILES/$(join , ${normal_files[@]})/" $tmp1 > $tmp2
sed "s/SED_INSERT_CLFILES/$(join , ${cl_files[@]})/" $tmp2 > $tmp1
sed "s/SED_INSERT_BOTFILES/$(join , ${bot_files[@]})/" $tmp1 > $tmp2
sed "s/SED_INSERT_FROMVERSION/$1/" $tmp2 > $tmp1
sed "s/SED_INSERT_NEWVERSION/$2/" $tmp1 > $tmp2

cat $tmp2

rm $tmp1
rm $tmp2

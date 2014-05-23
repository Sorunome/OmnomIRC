#!/bin/bash
######## Config ########
user="sorunome"
group=$user
home=/home/$user/omnomirc
pid=/run/omnomirc_curid
####### Init ########
echo "0" > $pid
chown $user:$group $pid
chmod 777 $pid
cd $home
su -c "./MrOmnomIRC &> /dev/null &" $user
su -c "./MrTopicBot &> /dev/null &" $user

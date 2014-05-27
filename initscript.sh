#!/bin/bash
######## Config ########
user="sorunome"
group=$user
home=/home/$user/omnomirc
curid=/run/omnomirc_curid
####### Init ########
echo "0" > $curid
chown $user:$group $curid
chmod 777 $curid
cd $home
su -c "./MrOmnomIRC &> /dev/null &" $user
su -c "./MrTopicBot &> /dev/null &" $user

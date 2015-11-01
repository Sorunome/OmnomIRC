<?php

$title = __("IRC");

$crumbs = new PipeMenu();
$crumbs->add(new PipeMenuLinkEntry(__("IRC"), "irc"));
makeBreadcrumbs($crumbs);

if(isset($_GET['name']))
{
	$user = Fetch(Query("select * from {users} where displayname={0} or name={0}", $_GET['name']));
	if($user)
	{
		Alert(UserLink($user, false),__('User redirect'));
		redirectAction("profile", $user['id']);
	}
	else
		Alert(__('User not found'),__('User redirect'));
}
else
{
	echo "<style>#ircbox{height: 600px !important;}</style>";
}

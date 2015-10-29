<?php
/*
    OmnomIRC COPYRIGHT 2010,2011 Netham45
                       2012-2015 Sorunome

    This file is part of OmnomIRC.

    OmnomIRC is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OmnomIRC is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with OmnomIRC.  If not, see <http://www.gnu.org/licenses/>.
*/
if (!defined('SMF'))
	die('Hacking attempt...');

function generateOircSigURL()
{
	global $config,$only_include_oirc,$boarddir;
	$only_include_oirc = true;
	include_once($boarddir.'/checkLogin/index.php');
	$nick = '*';
	$time = (string)(time() - 60*60*24 + 60); // the sig key is only valid for one min!
	$uid = rand();
	$network = 0;
	$signature = $time.'|'.hash_hmac('sha512',$nick.$uid,$network.$config['sigKey'].$time);
	return 'nick='.base64_url_encode($nick).'&signature='.base64_url_encode($signature).'&time='.$time.'&network='.$network.'&id='.$uid.'&noLoginErrors';
}

function sendToOmnomIRC($message,$channel)
{
	global $config;
	$sigurl = generateOircSigURL();
	file_get_contents($config['oircUrl'].'/message.php?message='.base64_url_encode($message).'&channel='.$channel.'&serverident&'.$sigurl);
}

function getTopicName($id)
{
	global $smcFunc;
	$topicName = '';
	$request = $smcFunc['db_query']('',"SELECT id_first_msg FROM {db_prefix}topics WHERE id_topic = {int:id_topic} LIMIT 1",array('id_topic' => (int)$id));
	$temp = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	if(isset($temp['id_first_msg']))
	{
		$request = $smcFunc['db_query']('',"SELECT subject FROM {db_prefix}messages WHERE id_msg = {int:id_msg} LIMIT 1",array('id_msg' => (int)$temp['id_first_msg']));
		$temp = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);
		if(isset($temp['subject']))
		{
			$topicName = $temp['subject'];
		}
	}
	return $topicName;
}

function getOircSendToChan($id_board)
{
	global $smcFunc;
	
	$request = $smcFunc['db_query']('',"SELECT id_profile FROM {db_prefix}boards WHERE id_board = {int:id_board} LIMIT 1",array('id_board' => $id_board));
	$temp = $smcFunc['db_fetch_assoc']($request);
	$smcFunc['db_free_result']($request);
	if(!empty($temp['id_profile']))
	{
		$request = $smcFunc['db_query']('',"SELECT channel FROM {db_prefix}oirc_postchans WHERE id_profile = {int:id_profile} LIMIT 1",array('id_profile' => $temp['id_profile']));
		$temp = $smcFunc['db_fetch_assoc']($request);
		$smcFunc['db_free_result']($request);
		if(isset($temp['channel']) && $temp['channel']!='' && $temp['channel']!=-1)
		{
			return $temp['channel'];
		}
	}
	return false;
}

function loadOircActions($s = 'oirc_actions',$who = NULL,$theme = NULL)
{
	global $context,$modSettings,$settings,$smcFunc,$options;
	$context[$s] = array();
	$themeActions = array();
	if($who === NULL)
	{
		if(!empty($options['oirc_actions']))
			$themeActions = unserialize($options['oirc_actions']);
		elseif(!empty($settings['oirc_actions']))
			$themeActions = unserialize($settings['oirc_actions']);
	}
	else
	{
		$request = $smcFunc['db_query']('', '
			SELECT value
			FROM {db_prefix}themes
			WHERE id_theme IN (1, {int:current_theme})
				AND id_member = {int:guest_member}
				AND variable = "oirc_actions"
			LIMIT 1',
			array(
				'current_theme' => $theme,
				'guest_member' => $who,
			)
		);
		$tmp = $smcFunc['db_fetch_assoc']($request);
		if(!empty($tmp['value']))
			$themeActions = unserialize($tmp['value']);
		$smcFunc['db_free_result']($request);
	}
	
	foreach(unserialize($modSettings['oirc_actionarray']) as $action)
		$context[$s][$action] = !isset($themeActions[$action]) || $themeActions[$action];
	$context['oirc_curaction'] = $context['current_action'];
	if($context['oirc_curaction'] == '')
	{
		if(!empty($context['current_topic']))
			$context['oirc_curaction'] = 'topic';
		elseif(!empty($context['current_board']))
			$context['oirc_curaction'] = 'board';
		else
			$context['oirc_curaction'] = 'index';
	}
	$context['oirc_show'] = allowedTo('oirc_can_view') && !empty($context['oirc_actions'][$context['oirc_curaction']]) && $context['oirc_actions'][$context['oirc_curaction']];
}

function loadOircPermissions(&$permissionGroups,&$permissionList,&$leftPermissionGroups,&$hiddenPermissions,&$relabelPermissions)
{
	$permissionList['membergroup']['oirc_can_view'] = array(false, 'omnomirc', 'omnomirc');
	$permissionList['membergroup']['oirc_is_op'] = array(false, 'omnomirc', 'omnomirc');
}

function OircMaintenance()
{
	global $boarddir;
	$indexphp = file_get_contents($boarddir . '/index.php');
	preg_match('~actionArray\\s*=\\s*array[^;]+~', $indexphp, $actionArrayText);
	preg_match_all('~\'([^\']+)\'\\s*=>~', $actionArrayText[0], $actionArray, PREG_PATTERN_ORDER);
	$actionArray = $actionArray[1];

	$dummy = array();
	call_integration_hook('integrate_actions', array(&$dummy));
	$actionArray += array_keys($dummy);
	array_unshift($actionArray,'index','board','topic');
	
	updateSettings(array(
		'oirc_actionarray' => serialize($actionArray)
	));
}
?>
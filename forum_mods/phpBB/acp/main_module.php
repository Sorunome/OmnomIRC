<?php
namespace omnimaga\OmnomIRC\acp;

class main_module
{
	var $u_action;
	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $cache, $request;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		//$user->add_lang('acp/common');
		//$user->add_lang_ext('omnimaga/OmnomIRC','common');
		
		
		$this->tpl_name = $mode;
		$this->page_title = $user->lang('ACP_OIRC_TITLE');
		add_form_key('omnimaga/OmnomIRC');
		$var_lookup = array(
			'general' => array(
				'oirc_title' => '',
				'oirc_height' => 0,
			),
			'notifications' => array(
				'oirc_topics' => false,
				'oirc_posts' => false,
				'oirc_edits' => false,
				'oirc_topicnotification' => '',
				'oirc_postnotification' => '',
				'oirc_editnotification' => '',
			),
			'checklogin' => array(
				'oirc_config_installed' => false,
				'oirc_config_sigKey' => '',
				'oirc_config_network' => 0,
				'oirc_config_oircUrl' => '',
			),
		);
		global $oirc_config,$only_include_oirc;
		$only_include_oirc = true;
		include_once(realpath(dirname(__FILE__)).'/../checkLogin/index.php');
		
		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('omnimaga/OmnomIRC'))
			{
				trigger_error('FORM_INVALID');
			}
			if(isset($var_lookup[$mode])){
				$updateFrameUrl = false;
				foreach($var_lookup[$mode] as $var => $def){
					$val = $request->variable($var,$def);
					$config->set($var,$val);
					if(substr($var,0,strlen('oirc_config_')) === 'oirc_config_')
					{
						$updateFrameUrl = true;
						$oirc_config[substr($var,strlen('oirc_config_'))] = $val;
					}
				}
				if($updateFrameUrl)
				{
					$config->set('oirc_frameurl',$oirc_config['oircUrl'].'/index.php?network='.$oirc_config['network']);
				}
				writeConfig();
			}
			trigger_error($user->lang('ACP_OIRC_SETTING_SAVED') . adm_back_link($this->u_action));
		}
		if(isset($var_lookup[$mode])){
			$addVars = array();
			foreach($var_lookup[$mode] as $var => $def){
				if(substr($var,0,strlen('oirc_config_')) === 'oirc_config_')
				{
					$addVars[strtoupper($var)] = $oirc_config[substr($var,strlen('oirc_config_'))];
				}
				else
				{
					$addVars[strtoupper($var)] = $config[$var];
				}
			}
			$template->assign_vars($addVars);
		}
		$template->assign_vars(array(
			'U_ACTION' => $this->u_action,
			'ACME_DEMO_GOODBYE' => $config['acme_demo_goodbye'],
		));
	}
}

?>
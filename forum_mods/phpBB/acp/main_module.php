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
				'oirc_topics' => 0,
				'oirc_posts' => 0,
				'oirc_edits' => 0,
				'oirc_topicnotification' => '',
				'oirc_postnotification' => '',
				'oirc_editnotification' => '',
			),
			'checklogin' => array(),
		);
		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('omnimaga/OmnomIRC'))
			{
				trigger_error('FORM_INVALID');
			}
			if(isset($var_lookup[$mode])){
				foreach($var_lookup[$mode] as $var => $def){
					$config->set($var, $request->variable($var,$def));
				}
			}
			trigger_error($user->lang('ACP_OIRC_SETTING_SAVED') . adm_back_link($this->u_action));
		}
		if(isset($var_lookup[$mode])){
			$addVars = array();
			foreach($var_lookup[$mode] as $var => $def){
				$addVars[strtoupper($var)] = $config[$var];
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
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
		
		
		$this->tpl_name = 'oirc_body';
		$this->page_title = $user->lang('ACP_OIRC_TITLE');
		add_form_key('omnimaga/OmnomIRC');
		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('omnimaga/OmnomIRC'))
			{
				trigger_error('FORM_INVALID');
			}
			$config->set('acme_demo_goodbye', $request->variable('acme_demo_goodbye', 0));
			trigger_error($user->lang('ACP_DEMO_SETTING_SAVED') . adm_back_link($this->u_action));
		}
		$template->assign_vars(array(
			'U_ACTION' => $this->u_action,
			'ACME_DEMO_GOODBYE' => $config['acme_demo_goodbye'],
		));
	}
}

?>
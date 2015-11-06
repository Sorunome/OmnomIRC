<?php
namespace omnimaga\OmnomIRC\ucp;

class main_module
{
	var $u_action;
	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $cache, $request, $phpbb_container;
		global $config, $phpbb_root_path, $phpbb_admin_path, $phpEx;
		//$user->add_lang('acp/common');
		//$user->add_lang_ext('omnimaga/OmnomIRC','common');
		
		
		$this->tpl_name = 'ucp_oirc';
		$this->page_title = $user->lang('UCP_OIRC_TITLE');
		
		add_form_key('omnimaga/OmnomIRC');
		
		include_once(realpath(dirname(__FILE__)).'/../OmnomIRC.php');
		
		if ($request->is_set_post('submit'))
		{
			if (!check_form_key('omnimaga/OmnomIRC'))
			{
				trigger_error('FORM_INVALID');
			}
			
			$data = serialize(getOircPostPages());
			if($data)
			{
				$db->sql_query('UPDATE '.USERS_TABLE.' SET '.
					$db->sql_build_array('UPDATE',array('oirc_pages' => $data)).
					'WHERE user_id = '.(int) $user->data['user_id']
				);
			}
			trigger_error($user->lang('UCP_OIRC_SETTING_SAVED') . '<br /><br />' . sprintf($user->lang['RETURN_UCP'], '<a href="' . $this->u_action . '">', '</a>'));
		}
		$data = unserialize($user->data['oirc_pages']);
		if(empty($data))
		{
			$data = unserialize($phpbb_container->get('config_text')->get('oirc_pages'));
		}
		$template->assign_vars(array(
			'U_ACTION' => $this->u_action,
			'OIRC_PAGEPICKER' => getOircPagePicker($data),
		));
	}
}

?>
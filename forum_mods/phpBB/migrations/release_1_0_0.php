<?php

namespace omnimaga\OmnomIRC\migrations;

class release_1_0_0 extends \phpbb\db\migration\migration
{
	public function effectively_installed()
	{
		return isset($this->config['oirc_height']);
	}
	static public function depends_on()
	{
		return array('\phpbb\db\migration\data\v310\alpha2');
	}
	public function update_data()
	{
		return array(
			array('config.add', array('oirc_height', 280)),
			array('config.add', array('oirc_title', 'OmnomIRC Chat')),
			array('config.add', array('oirc_topics', true)),
			array('config.add', array('oirc_posts', true)),
			array('config.add', array('oirc_edits', true)),
			array('config.add', array('oirc_topicnotification', '{COLOR}10New topic by {COLOR}03{NAME} {COLOR}04{TOPIC} {COLOR}12/index.php?topic={TOPICID}')),
			array('config.add', array('oirc_postnotification', '{COLOR}10New post by {COLOR}03{NAME} {COLOR}10in {COLOR}04{TOPIC} {COLOR}12/index.php?topic={TOPICID}.msg{POSTID}#msg{POSTID}')),
			array('config.add', array('oirc_editnotification', '{COLOR}10Edit by {COLOR}03{NAME} {COLOR}10on {COLOR}04{TOPIC} {COLOR}12/index.php?topic={TOPICID}.msg{POSTID}#msg{POSTID}')),
			array('config.add', array('oirc_frameurl', '')),
			array('module.add', array(
				'acp',
				'ACP_CAT_DOT_MODS',
				'ACP_OIRC_TITLE'
			)),
			array('module.add', array(
				'acp',
				'ACP_OIRC_TITLE',
				array(
					'module_basename' => '\omnimaga\OmnomIRC\acp\main_module',
					'modes' => array('general','notifications','checklogin'),
				),
			)),
		);
	}
}
?>
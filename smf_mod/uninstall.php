<?php
if (!defined('SMF'))
	require_once('SSI.php');

remove_integration_function('integrate_pre_include','$sourcedir/OmnomIRC.php');
remove_integration_function('integrate_menu_buttons','loadOircActions');


?>
<?php
/* withgusto 404 Error Page */
function issetor(&$variable, $or = NULL) {
	return $variable === NULL ? $or : $variable;
}

echo('<b>Oops! You got lost in the woods! :/</b><br /><br />');
if(issetor($_GET['q'])) {
	echo('<i>(You tried to go to <b>'.$_GET['q'].'</b> but it does not exist!)</i><br />');
} else {
	echo('<i>(You tried to go somewhere that does not exist!)</i><br />');
}
echo('Try going to the <a href="/">home page</a> instead? ;)');
?>

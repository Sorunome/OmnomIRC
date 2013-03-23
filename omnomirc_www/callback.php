<?PHP
header('Content-type: text/javascript');
echo "parent.signCallback('".base64_url_decode($_GET['signature'])."','".base64_url_decode($_GET['nick'])."');"
?>
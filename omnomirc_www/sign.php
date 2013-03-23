<?php
include_once("Source/sign.php");
echo base64_url_encode(sign($_GET['usr']));

?> 

<?php
// removable dir auto cleaner
/*
$files = glob('../--del_x/*'); // get all file names
foreach($files as $file){ // iterate files
  if(is_file($file))
    unlink($file); // delete file
} */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <title>Dir & file reader</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<style>
body {margin:0;}
a {text-decoration:none;}
</style>
</head>
<body>
<ul style="margin-left:0; list-style-type:none;">
<div style="margin: 5px 0 0 -40px;">
<?php
	$file = 20; 
	$aFaili = array ();

	if ($saturs = opendir ('./')) {
		while (false !== ($file = readdir($saturs)))
			if ($file != "." && $file != "Thumbs.db" && $file != ".htaccess" && $file != "index.php" && $file != "_wp"&& $file != "_info") {
					$aFaili [] = "<li style='border-bottom:1px solid #999;'><a href='./$file' target='_blank'><font style='color:#333;'><b>&nbsp;$file</b></font></a></li>\n";
			}
		closedir($saturs);
	}
	list($a[0], $a[1], $a[2]) = $content;
	sort ($aFiles);
	foreach (array_reverse ($aFiles) as $sFile) {
	       echo $sFile;
	}
?>
</div>
</ul>
</body>
</html>
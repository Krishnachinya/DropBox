<?php
global  $dropbox;
require_once("DropboxClient.php");
// you have to create an app at https://www.dropbox.com/developers/apps and enter details below:
$dropbox = new DropboxClient(array(
	'app_key' => "qtkwi214mmvybjp",      // Put your Dropbox API key here
	'app_secret' => "7rmwtczg78v11va",   // Put your Dropbox API secret here
	'app_full_access' => false,
),'en');
//static $img_data;
//static $downloadLink;
// display all errors on the browser
error_reporting(E_ALL);
ini_set('display_errors','On');

// if there are many files in your Dropbox it can take some time, so disable the max. execution time
set_time_limit(0);




// first try to load existing access token
$access_token = load_token("access");
if(!empty($access_token)) {
	$dropbox->SetAccessToken($access_token);
	//echo "loaded access token:";
	//print_r($access_token);
}
elseif(!empty($_GET['auth_callback'])) // are we coming from dropbox's auth page?
{
	// then load our previosly created request token
	$request_token = load_token($_GET['oauth_token']);
	if(empty($request_token)) die('Request token not found!');
	
	// get & store access token, the request token is not needed anymore
	$access_token = $dropbox->GetAccessToken($request_token);	
	store_token($access_token, "access");
	delete_token($_GET['oauth_token']);
}


// checks if access token is required
if(!$dropbox->IsAuthorized())
{
	// redirect user to dropbox auth page
	$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
	$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
	$request_token = $dropbox->GetRequestToken();
	store_token($request_token, $request_token['t']);
	die("Authentication required. <a href='$auth_url'>Click here.</a>");
}

function store_token($token, $name)
{
	if(!file_put_contents("tokens/$name.token", serialize($token)))
		die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
}

function load_token($name)
{
	if(!file_exists("tokens/$name.token")) return null;
	return @unserialize(@file_get_contents("tokens/$name.token"));
}

function delete_token($name)
{
	@unlink("tokens/$name.token");
}


function downloadImage()
{
        global $dropbox;
    //print_r($dropbox);
    $files = $dropbox->GetFiles("",false);
    $file = $dropbox->GetMetadata($_GET['download']);
    $dir = "/Applications/XAMPP/xamppfiles/htdocs/project5/";
    $target_file = $dir . basename($_GET['download']);
    //print_r($file);
    //print_r($_GET['download']);
    $dropbox->DownloadFile($_GET['download'], $target_file); 
    header('Location: album.php');
}

function deleteImage()
{
    global $dropbox;
    $dropbox->Delete($_GET['delete']);
	header('Location: album.php');
}

if(isset($_GET['download']))
{
    try{
	downloadImage();
	}
	catch(Exception $e)
	{
		echo 'Message '. $e->getMessage();
	}
    
}

if(isset($_GET['delete'])){
    try{
	deleteImage();
	}
	catch(Exception $e)
	{
		echo 'Message '. $e->getMessage();
	}
    
}

?>


<html>
<head><title> Photo Album </title>
<link rel="stylesheet" href="style.css"/>
<script type="text/javascript">
function display_image(imgpath, name)
{
   // alert(img);
    //alert(path);

    //var picture = document.getElementById('picture');
    //picture.src = img;
    //picture.title = path;
    document.getElementById("picture").innerHTML = "<br><br><br><img src='" + imgpath + "' width=300 height=300 alt='Image loading... Please wait...' title='"+name+"' />" ;
}
</script>
</head>
<body>


<form action="album.php" method="post" enctype="multipart/form-data" name="imageupload" id="imgupload">
    Select image to upload:
    <input type="file" name="files" id="files">
    <input type="submit" value="Upload Image" name="submit">
</form>

<?php 
if(isset($_FILES["files"]["name"]))
{
$dir = "/Applications/XAMPP/xamppfiles/htdocs/project5/";
$target_file = $dir . basename($_FILES["files"]["name"]);
$upload = 1;
$extension = pathinfo($target_file,PATHINFO_EXTENSION);

// Allow certain file formats
if($extension != "jpg") {
    echo "Sorry, only JPG files are allowed.";
    $upload = 0;
}


if ($upload == 0) {
    echo "Sorry, your file was not uploaded.";
// if everything is ok, try to upload file
} else {
    if (move_uploaded_file($_FILES["files"]["tmp_name"], $target_file)) {
        $dropbox->UploadFile($target_file);
        echo "The file ". basename( $_FILES["files"]["name"]). " has been uploaded.";
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
}
}

$files = $dropbox->GetFiles("",false);
if(!empty($files))
{?> <br>
<table class="tab-prop">
<tr><td>
<h3> List of All Images in your Drop box is </h3>
<li class="filenames"> <?
   foreach($files as $file){
       $img_data = base64_encode($dropbox->GetThumbnail($file->path));
       //echo $img_data;
       $downloadLink = $dropbox->GetLink($file,false);
       //echo $downloadLink;
       $name = basename($file->path);
       //echo $filename;
    ?> <ul> Name: <a href="#" onclick="display_image('<?echo $downloadLink?>','<?echo $name?>')"> <? echo $name."\n"; ?> </a>
    <br> Download: <a href="album.php?download=<? echo $name ?>"> Click Here </a>
    &nbsp;&nbsp;&nbsp;&nbsp; Delete: <a href="album.php?delete=<? echo $name ?>"> Click Here </a>
    </ul><?
}
?></li></td>
<td>
    <div id="picture"></div>
</td></tr>
</table><?
}
 


?>
</body>
</html>

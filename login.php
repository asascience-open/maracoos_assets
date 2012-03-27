<?php

ob_start();
session_start();

require_once ("auth.php");

$returnurl = urlencode(isset($_GET["returnurl"])?$_GET["returnurl"]:"");
if($returnurl == "")
    $returnurl = urlencode(isset($_POST["returnurl"])?$_POST["returnurl"]:"");

$do = isset($_GET["do"])?$_GET["do"]:"";

$do = strtolower($do);

switch($do)
{
case "":
    if (checkLoggedin())
    {
        echo "<H1>You are already logged in - <A href = \"login.php?do=logout\">logout</A></h1>";
    }
    else
    {
        ?>
<html>
  <head>
    <title>ECOP Explorer</title>
    <link href="http://horizonmarine.com/style.css" rel="stylesheet" type="text/css" />
  </head>
  <body onload="document.getElementById('username').focus()">
    <div id="mainwrap">
      <div id="header">
        <div id="horizonlogo"  onclick="location.href='http://www.horizonmarine.com';"></div>
        <div id="nav">
          <div id="bottomNav" style="bottom:4px">
            <form NAME="login1" ACTION="login.php?do=login" METHOD="POST">
              <input TYPE="hidden" name="returnurl" value="<?php $returnurl?>">
              <table><tr>
                <td><font color="#a0a0a0">Username&nbsp;&nbsp;</font></td>
                <td><input TYPE="TEXT" id="username" NAME="username"><td>
                <td>&nbsp;&nbsp;</td>
                <td><font color="#a0a0a0">Password&nbsp;&nbsp;</font></td><td><input TYPE="PASSWORD" NAME="password"></td>
                <td>&nbsp;&nbsp;</td>
                <td><input style="padding:1px;border: 1px solid #343747;" TYPE="SUBMIT" name="submit" value="Login"></td>
              </tr></table>
            </form>
          </div>
        </div>
      </div>
    </div>
  </body>
</html>
    <?
    }
    break;
case "login":
    $username = isset($_POST["username"])?$_POST["username"]:"";
    $password = isset($_POST["password"])?$_POST["password"]:"";

    if ($username=="" or $password=="" )
    {
        echo "<h1>Username or password is blank</h1>";
        clearsessionscookies();
        header("location: login.php?returnurl=$returnurl");
    }
    else
    {
        if(confirmuser($username,md5($password))) // As pointed out by asgard2005
        {
            createsessions($username,$password);
            if ($returnurl<>"")
                header("location: $returnurl");
            else
            {
                header("Location: ./?config=ecop");
            }
        }
        else
        {
            echo "<h1>Invalid Username and/Or password</h1>";
            clearsessionscookies();
            header("location: login.php?returnurl=$returnurl");
        }
    }
    break;
case "logout":
    clearsessionscookies();
    header("location: .");
    break;
}
?>

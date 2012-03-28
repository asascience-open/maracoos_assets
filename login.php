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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
  <head>
    <title>ECOP Explorer</title>
    <link media="all" type="text/css" rel="stylesheet" href="http://asascience.com/css/all.css" />
    <style>
      #login {
        position    : absolute;
        top         : 3px;
        right       : 1px;
      }
      #login td {
        font-size   : 14px;
        font-weight : bold;
        color       : #EBF1F6;
      }
      A:link {text-decoration: underline; color:#AEBB9B}
      A:visited {text-decoration: underline; color:#AEBB9B}
      A:active {text-decoration: underline; color:#AEBB9B}
      A:hover {text-decoration: underline; color: #68BD45;}
    </style>
  </head>
  <body onload="document.getElementById('username').focus()">
    <div class="page">
      <div id="main" class="home-page">
        <div class="bg-holder">
                    <div id="header">
                        <div class="opacity-holder">
                            <!-- map box -->
                            <div class="map-box">
                                <!-- map -->
                                <div class="map">&nbsp;</div>
                                <!-- city nav -->
                            </div>
                            <br />
                            <a href="#" class="logo" id="logo-us" style="display:block;"><span>ASA | science. services. solutions.</span></a>
                            <div class="menu-line">
                              <div id="login">
            <form NAME="login1" ACTION="login.php?do=login" METHOD="POST">
              <input TYPE="hidden" name="returnurl" value="<?php $returnurl?>">
              <table><tr>
                <td>Username&nbsp;&nbsp;</td>
                <td><input TYPE="TEXT" id="username" NAME="username"><td>
                <td>&nbsp;&nbsp;</td>
                <td>Password&nbsp;&nbsp;</td><td><input TYPE="PASSWORD" NAME="password"></td>
                <td>&nbsp;&nbsp;</td>
                <td><input style="padding:1px;border: 1px solid #343747;" TYPE="SUBMIT" name="submit" value="Login"></td>
              </tr></table>
            </form>
                              </div>
                            </div>
                         </div>
                    </div>
        </div>
      </div>
    </div>
  </body>
</html>
    <?php
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

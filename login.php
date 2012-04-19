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
    <title>CoastMap</title>
    <style>
            body{margin: 0 0;padding: 0 0;font-family: Arial, Sans-Serif;font-weight: bold;background-color: #b5c0ca;background-image: url("_images/bg.png");background-repeat: no-repeat;background-position: top left;}
            #main{width: 1020px;height: 582px;margin: 25px auto 0 auto;background-image: url("_images/bkgd_image.png");background-size: 1020px 582px;}
            #content{width: 452px;margin-left: 56px;padding-top: 140px;color: #00374C;font-size: 14px;}a{color: #00374C;}
            #login{background-image: url("_images/gradient.png");background-repeat: repeat-x;margin: 14px auto 0 auto;color: #3c4d57;border: 1px solid #3c4d57;font-size: 16px;line-height: 43px;width: 1018px;}
            input[type="text"], input[type="password"]{background-image: url("_images/gradient.png");width: 226px;}
<?php
  if ($_COOKIE['failedLogin']) {
?>
            input[type="submit"]{margin-left: 59px;background-image: url("_images/button_gradient.png");width: 80px;font-weight: bold;color: #3c4d57;font-size: 14px;}
<?php
  }
  else {
?>
            input[type="submit"]{margin-left: 175px;background-image: url("_images/button_gradient.png");width: 80px;font-weight: bold;color: #3c4d57;font-size: 14px;}
<?php
  }
?>
    </style>
  </head>
        <div id="main">
            <div id="content">
                <p>CoastMap Explorer provides live access to ASA.s Environmental Data Server (EDS).</p>
                <p>EDS is a data management infrastructure that manages vast amounts of ocean and meteorological observation and model data. The data is derived from government agencies as well as custom data from ASA.s modeling teams.</p>
                <p>The data spans global to regional domains and this explorer web site provides a view into the data with maps and time series data.</p>
                <p>Other applications such as OILMAP and SAROPS can access the server to obtain data for scientific analysis and marine emergency response.</p>
                <p>For a demo user account, or subscription information, please contact <a href="mailto:&#097;&#115;&#097;&#099;&#111;&#110;&#116;&#097;&#099;&#116;&#064;&#097;&#115;&#097;&#115;&#099;&#105;&#101;&#110;&#099;&#101;&#046;&#099;&#111;&#109;">&#097;&#115;&#097;&#099;&#111;&#110;&#116;&#097;&#099;&#116;&#064;&#097;&#115;&#097;&#115;&#099;&#105;&#101;&#110;&#099;&#101;&#046;&#099;&#111;&#109;</a></p>
            </div>
        </div>
        <div id="login">
            <form id="form" NAME="login1" ACTION="login.php?do=login" METHOD="POST">
                <input TYPE="hidden" name="returnurl" value="<?php $returnurl?>">
<?php
  $failed = '';
  if ($_COOKIE['failedLogin']) {
    $failed = '&nbsp;&nbsp;&nbsp;&nbsp;<font style="color:#276BA6">Invalid login.</font>';
    setcookie("failedLogin");
  }
?>
                <span style="padding-left: 56px;"> Username <input type="text" name="username" id="username" /></span> <span style="padding-left: 34px;">Password <input type="password" name="password" id="password" /></span> <span><?php echo $failed?></span><input type="submit" value="Login" />
            </form>
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
        if(confirmuser($username,$password)) // As pointed out by asgard2005
        {
            createsessions($username,$password);
            if ($returnurl<>"")
                header("location: $returnurl");
            else
            {
                header("Location: .");
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

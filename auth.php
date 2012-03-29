<?php

function createsessions($username,$password)
{
    //Add additional member to Session array as per requirement
    session_register();

    $_SESSION["gdusername"] = $username;
    $_SESSION["gdpassword"] = $password; // md5($password);
    
    if(isset($_POST['remme']))
    {
        //Add additional member to cookie array as per requirement
        setcookie("gdusername", $_SESSION['gdusername'], time()+60*60*24*100, "/");
        setcookie("gdpassword", $_SESSION['gdpassword'], time()+60*60*24*100, "/");
        return;
    }
}

function clearsessionscookies()
{
    unset($_SESSION['gdusername']);
    unset($_SESSION['gdpassword']);
    
    session_unset();    
    session_destroy(); 

    setcookie ("gdusername", "",time()-60*60*24*100, "/");
    setcookie ("gdpassword", "",time()-60*60*24*100, "/");
}

function confirmUser($username,$password)
{
    // $md5pass = md5($password); // Not needed any more as pointed by ted_chou12
    /* Validate from the database but as for now just demo username and password */

    $xml = simplexml_load_file("http://coastmap.com/ecop/wms.aspx?&request=GetUserInfo&version=1.1.1&username=$username&pw=$password");
    if (array_key_exists('Error',$xml)) {
      return false;
    }
    else {
      setcookie("clientKey",$xml->{'clientKey'});
      setcookie("bounds"   ,$xml->{'bounds'});
      return true;
    }
}

function checkLoggedin()
{
    if(isset($_SESSION['gdusername']) AND isset($_SESSION['gdpassword']))
        return true;
    elseif(isset($_COOKIE['gdusername']) && isset($_COOKIE['gdpassword']))
    {
        if(confirmUser($_COOKIE['gdusername'],$_COOKIE['gdpassword']))
        {
            createsessions($_COOKIE['gdusername'],$_COOKIE['gdpassword']);
            return true;
        }
        else
        {
            clearsessionscookies();
            return false;
        }
    }
    else
        return false;
}
?> 

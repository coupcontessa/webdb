<?php

namespace webdb\users;

#####################################################################################################

function auth_dispatch()
{
  global $settings;
  if (isset($_GET["logout"])==true)
  {
    \webdb\users\logout();
  }
  if (isset($_POST["send_reset_password"])==true)
  {
    \webdb\users\send_reset_password_message();
  }
  if (isset($_GET["reset_password"])==true)
  {
    \webdb\users\reset_password();
  }
  if (isset($_GET["change_password"])==true)
  {
    \webdb\users\change_password();
  }
  \webdb\users\login();
  $settings["logged_in_username"]=$settings["user_record"]["username"];
  $user_id=$settings["user_record"]["user_id"];
  $settings["logged_in_user_groups"]=\webdb\users\get_user_groups($user_id);
}

#####################################################################################################

function generate_csrf_token()
{
  global $settings;
  if (\webdb\cli\is_cli_mode()==true)
  {
    return;
  }
  $crypto_strong=true;
  $settings["csrf_token"]=base64_encode(openssl_random_pseudo_bytes(30,$crypto_strong));
  $options=array();
  $options["cost"]=$settings["password_bcrypt_cost"];
  $hash=password_hash($settings["csrf_token"],PASSWORD_BCRYPT,$options);
  setcookie("csrf_token_hash",$hash,0,"/",$_SERVER["HTTP_HOST"],false,true);
  \webdb\users\auth_log(false,"GENERATE_CSRF_TOKEN","generated csrf token for ".$_SERVER["REMOTE_ADDR"]);
}

#####################################################################################################

function check_csrf()
{
  global $settings;
  # note: user not authenticated at this point
  $csrf_ok=false;
  if (count($_POST)==0)
  {
    $csrf_ok=true;
  }
  if (\webdb\cli\is_cli_mode()==true)
  {
    $csrf_ok=true;
  }
  $referer="";
  if (isset($_SERVER["HTTP_REFERER"])==true)
  {
    $referer=$_SERVER["HTTP_REFERER"];
  }
  if ((isset($_POST["csrf_token"])==true) and (isset($_COOKIE["csrf_token_hash"])==true))
  {
    if (password_verify($_POST["csrf_token"],$_COOKIE["csrf_token_hash"])==true)
    {
      \webdb\users\auth_log(false,"VALID_CSRF_TOKEN","valid csrf token from ".$_SERVER["REMOTE_ADDR"]);
      $csrf_ok=true;
    }
    else
    {
      \webdb\users\auth_log(false,"INVALID_CSRF_TOKEN_1","invalid csrf token from ".$_SERVER["REMOTE_ADDR"]." [referer=".$referer."]");
    }
  }
  if ($csrf_ok==false)
  {
    \webdb\users\auth_log(false,"INVALID_CSRF_TOKEN_2","invalid csrf token from ".$_SERVER["REMOTE_ADDR"]." [referer=".$referer."]");
    \webdb\utils\system_message("csrf error");
  }
}

#####################################################################################################

function obfuscate_hashes(&$data)
{
  $obfuscation_value="(obfuscated)";
  if (isset($data["login_cookie"])==true)
  {
    $data["login_cookie"]=$obfuscation_value;
  }
  if (isset($data["pw_hash"])==true)
  {
    $data["pw_hash"]=$obfuscation_value;
  }
  if (isset($data["pw_reset_key"])==true)
  {
    $data["pw_reset_key"]=$obfuscation_value;
  }
}

#####################################################################################################

function get_user_record($username)
{
  global $settings;
  $sql_params=array();
  $sql_params["username"]=trim(strtolower($username));
  $records=\webdb\sql\file_fetch_prepare("user_get_by_username",$sql_params);
  if (count($records)<>1)
  {
    \webdb\users\webdb_unsetcookie("login_cookie");
    \webdb\utils\show_message("error: username not found: ".htmlspecialchars($username));
  }
  return $records[0];
}

#####################################################################################################

function get_user_groups($user_id)
{
  $sql_params=array();
  $sql_params["user_id"]=$user_id;
  $records=\webdb\sql\file_fetch_prepare("user_groups",$sql_params);
  return $records;
}

#####################################################################################################

function cancel_password_reset($user_record)
{
  global $settings;
  $where_items=array();
  $where_items["user_id"]=$user_record["user_id"];
  $value_items=array();
  $value_items["pw_reset_key"]="*";
  $value_items["pw_reset_time"]=0;
  $settings["sql_check_post_params_override"]=true;
  \webdb\sql\sql_update($value_items,$where_items,"users","webdb",true);
  $settings["sql_check_post_params_override"]=false;
}

#####################################################################################################

function login_failure($user_record,$message)
{
  global $settings;
  if ($user_record["pw_reset_key"]<>"*")
  {
    \webdb\users\cancel_password_reset($user_record);
  }
  if (empty($user_record["failed_login_count"])==true)
  {
    $user_record["failed_login_count"]=0;
  }
  $where_items=array();
  $where_items["user_id"]=$user_record["user_id"];
  $value_items=array();
  $value_items["failed_login_count"]=$user_record["failed_login_count"]+1;
  $value_items["failed_login_time"]=microtime(true);
  $value_items["login_cookie"]="*"; # disable login with cookie
  $settings["sql_check_post_params_override"]=true;
  \webdb\sql\sql_update($value_items,$where_items,"users","webdb",true);
  $settings["sql_check_post_params_override"]=false;
  \webdb\users\webdb_unsetcookie("login_cookie");
  \webdb\users\auth_log($user_record,"FAILED",$message);
  if ($message!==false)
  {
    \webdb\utils\show_message($message);
  }
}

#####################################################################################################

function auth_log($user_record,$status,$message)
{
  global $settings;
  $user_id=null;
  if ($user_record===false)
  {
    if (isset($settings["user_record"])==true)
    {
      $user_id=$settings["user_record"]["user_id"];
    }
  }
  else
  {
    $user_id=$user_record["user_id"];
  }
  $items=array();
  $items["user_id"]=$user_id;
  $items["auth_status"]=$status;
  $items["auth_message"]=$message;
  $settings["sql_check_post_params_override"]=true;
  \webdb\sql\sql_insert($items,"auth_log","webdb",true);
  $settings["sql_check_post_params_override"]=false;
}

#####################################################################################################

function login_lockout($user_record)
{
  global $settings;
  $where_items=array();
  $where_items["user_id"]=$user_record["user_id"];
  $value_items=array();
  $value_items["pw_hash"]="*"; # disable login with password
  $value_items["login_cookie"]="*"; # disable login with cookie
  \webdb\sql\sql_update($value_items,$where_items,"users","webdb",true);
  \webdb\users\webdb_unsetcookie("login_cookie");
  \webdb\users\auth_log($user_record,"LOCKOUT","");
  \webdb\utils\show_message(\webdb\utils\template_fill("lockout_error"));
}

#####################################################################################################

function check_admin($user_record)
{
  global $settings;
  if ($user_record["username"]=="admin")
  {
    if (in_array($_SERVER["REMOTE_ADDR"],$settings["admin_remote_address_whitelist"])==false)
    {
      $params=array();
      $params["remote_address"]=$_SERVER["REMOTE_ADDR"];
      $msg=\webdb\utils\template_fill("admin_address_whitelist_error",$params);
      \webdb\users\login_failure($user_record,$msg);
    }
  }
}

#####################################################################################################

function login()
{
  global $settings;
  $login_form_params=array();
  $login_form_params["welcome_tip"]="";
  $login_form_params["default_username"]="";
  if (isset($_COOKIE[$settings["username_cookie"]])==true)
  {
    $login_form_params["default_username"]=$_COOKIE[$settings["username_cookie"]];
  }
  else
  {
    $login_form_params["welcome_tip"]=\webdb\utils\template_fill("welcome_tip");
  }
  $login_form_params["default_remember_me"]=\webdb\utils\template_fill("checkbox_checked");
  $login_form_params["login_script_modified"]=\webdb\utils\resource_modified_timestamp("login.js");
  $login_form_params["login_styles_modified"]=\webdb\utils\resource_modified_timestamp("login.css");
  if ((isset($_POST["login_username"])==true) and (isset($_POST["login_password"])==true))
  {
    $login_username=trim(strtolower($_POST["login_username"]));
    \webdb\users\webdb_setcookie("username_cookie",$login_username);
    $login_form_params["default_username"]=$login_username;
    $user_record=\webdb\users\get_user_record($login_username);
    if ($user_record["pw_reset_key"]<>"*")
    {
      \webdb\users\cancel_password_reset($user_record);
    }
    if ($user_record["failed_login_count"]>$settings["max_login_attempts"])
    {
      \webdb\users\login_lockout($user_record);
    }
    if (strlen($_POST["login_password"])>$settings["max_password_length"])
    {
      \webdb\users\login_failure($user_record,"error: password is too long");
    }
    \webdb\users\check_admin($user_record);
    if ($user_record["pw_hash"]<>"*")
    {
      if (password_verify($_POST["login_password"],$user_record["pw_hash"])==false)
      {
        \webdb\users\login_failure($user_record,"error: incorrect password");
      }
      \webdb\users\initialise_login_cookie($user_record);
      $settings["user_record"]=$user_record;
      \webdb\users\auth_log($user_record,"PASSWORD_LOGIN","");
      \webdb\utils\redirect($settings["app_web_index"]);
    }
    else
    {
      \webdb\utils\show_message(\webdb\utils\template_fill("lockout_first_time_message"));
    }
  }
  elseif ((isset($_COOKIE[$settings["login_cookie"]])==true) and (isset($_COOKIE[$settings["username_cookie"]])==true))
  {
    $user_record=\webdb\users\get_user_record($_COOKIE[$settings["username_cookie"]]);
    if ($user_record["pw_reset_key"]<>"*")
    {
      \webdb\users\cancel_password_reset($user_record);
    }
    if ((\webdb\users\remote_address_changed($user_record)==false) and ($settings["user_agent"]==$user_record["user_agent"]))
    {
      if ($user_record["failed_login_count"]>$settings["max_login_attempts"])
      {
        \webdb\users\login_lockout($user_record);
      }
      \webdb\users\check_admin($user_record);
      if ($user_record["login_cookie"]<>"*")
      {
        if (password_verify($_COOKIE[$settings["login_cookie"]],$user_record["login_cookie"])==true)
        {
          $where_items=array();
          $where_items["user_id"]=$user_record["user_id"];
          $value_items=array();
          $value_items["cookie_login_time"]=microtime(true);
          $value_items["user_agent"]=$settings["user_agent"];
          $value_items["remote_address"]=$_SERVER["REMOTE_ADDR"];
          $value_items["failed_login_count"]=0;
          $settings["sql_check_post_params_override"]=true;
          \webdb\sql\sql_update($value_items,$where_items,"users","webdb",true);
          $settings["sql_check_post_params_override"]=false;
          $settings["user_record"]=$user_record;
          \webdb\users\auth_log($user_record,"COOKIE_LOGIN","");
          if ($user_record["pw_change"]==1)
          {
            \webdb\users\change_password();
          }
          return;
        }
        else
        {
          login_failure($user_record,"invalid login cookie");
        }
      }
    }
  }
  \webdb\users\webdb_unsetcookie("login_cookie");
  $content=\webdb\utils\template_fill("login_form",$login_form_params);
  $buf=ob_get_contents();
  if (strlen($buf)<>0)
  {
    ob_end_clean(); # discard buffer
  }
  $settings["unauthenticated_content"]=true;
  \webdb\users\auth_log(false,"LOGIN_FORM","");
  \webdb\utils\output_page($content,"Login");
}

#####################################################################################################

function initialise_login_cookie($user_record)
{
  global $settings;
  $where_items=array();
  $where_items["user_id"]=$user_record["user_id"];
  $value_items=array();
  $value_items["pw_login_time"]=microtime(true);
  $value_items["user_agent"]=$settings["user_agent"];
  $value_items["remote_address"]=$_SERVER["REMOTE_ADDR"];
  $value_items["failed_login_count"]=0;
  $crypto_strong=true;
  $key=base64_encode(openssl_random_pseudo_bytes(30,$crypto_strong));
  $options=array();
  $options["cost"]=$settings["password_bcrypt_cost"];
  if ($user_record["user_id"]==1) # admin
  {
    $options["cost"]=$settings["admin_password_bcrypt_cost"];
  }
  $cookie=password_hash($key,PASSWORD_BCRYPT,$options);
  $value_items["login_cookie"]=$cookie;
  \webdb\users\webdb_setcookie("login_cookie",$key);
  \webdb\users\auth_log($user_record,"LOGIN_COOKIE_INIT","");
  \webdb\sql\sql_update($value_items,$where_items,"users","webdb",true);
}

#####################################################################################################

function logout()
{
  global $settings;
  \webdb\users\auth_log(false,"LOGOUT","");
  \webdb\users\webdb_unsetcookie("login_cookie");
  $url=$settings["app_web_index"];
  \webdb\utils\redirect($url,false);
}

#####################################################################################################

function insert_user_stub($form_config,$value_items)
{
  $value_items["username"]=trim(strtolower($value_items["username"]));
  return false;
}

#####################################################################################################

function update_user_stub($form_config,$id,$where_items,$value_items)
{
  $value_items["username"]=trim(strtolower($value_items["username"]));
  return false;
}

#####################################################################################################

function remote_address_listed($remote_address,$type) # $type = black|white
{
  global $settings;
  $fn=$settings["ip_".$type."list_file"];
  if (file_exists($fn)==false)
  {
    return false;
  }
  $data=trim(file_get_contents($fn));
  $lines=explode(PHP_EOL,$data);
  for ($i=0;$i<count($lines);$i++)
  {
    $line=trim($lines[$i]);
    if ($line=="")
    {
      continue;
    }
    if ($remote_address==$line)
    {
      return true;
    }
    $line=escapeshellarg($line);
    $address=escapeshellarg($remote_address);
    $cmd=$settings["webdb_root_path"]."sh".DIRECTORY_SEPARATOR."test_ip.sh ".$line." ".$address;
    $result=trim(shell_exec($cmd));
    if ($result==$remote_address)
    {
      return true;
    }
  }
  return false;
}

#####################################################################################################

function remote_address_changed($user_record) # allow right/lowest octet to change (ipv4 dhcp subnet); no ipv6 subnet changes allowed for
{
  if ($user_record["remote_address"]=="")
  {
    return true;
  }
  if ($_SERVER["REMOTE_ADDR"]=="")
  {
    return true;
  }
  if ($_SERVER["REMOTE_ADDR"]==$user_record["remote_address"])
  {
    return false;
  }
  $octets_d=explode(".",$user_record["remote_address"]);
  $octets_r=explode(".",$_SERVER["REMOTE_ADDR"]);
  if (count($octets_d)<>4)
  {
    return true;
  }
  if (count($octets_r)<>4)
  {
    return true;
  }
  if (($octets_d[0]==$octets_r[0]) and ($octets_d[1]==$octets_r[1]) and ($octets_d[2]==$octets_r[2]))
  {
    return false;
  }
  return true;
}

#####################################################################################################

function reset_password()
{
  global $settings;
  $records=\webdb\sql\file_fetch_prepare("user_get_all_enabled");
  $validated=false;
  for ($i=0;$i<count($records);$i++)
  {
    $record=$records[$i];
    if ($record["pw_reset_key"]=="")
    {
      continue;
    }
    $time_delta=microtime(true)-$record["pw_reset_time"];
    if ($time_delta>$settings["password_reset_timeout"])
    {
      continue;
    }
    if (password_verify($_GET["reset_password"],$record["pw_reset_key"])==true)
    {
      $validated=$record;
      break;
    }
  }
  if ($validated===false)
  {
    \webdb\users\webdb_unsetcookie("login_cookie");
    \webdb\users\auth_log(false,"INVALID_RESET_KEY","");
    \webdb\utils\show_message("error: invalid password reset key");
  }
  \webdb\users\cancel_password_reset($validated);
  \webdb\users\auth_log($validated,"RESET_PASSWORD","");
  \webdb\users\change_password($validated);
}

#####################################################################################################

function send_reset_password_message()
{
  global $settings;
  if (isset($_POST["login_username"])==false)
  {
    \webdb\utils\show_message("error: missing username");
  }
  $user_record=\webdb\users\get_user_record($_POST["login_username"]);
  $value_items=array();
  $crypto_strong=true;
  $key=base64_encode(openssl_random_pseudo_bytes(30,$crypto_strong));
  $options=array();
  $options["cost"]=$settings["password_bcrypt_cost"];
  if ($user_record["username"]=="admin")
  {
    $options["cost"]=$settings["admin_password_bcrypt_cost"];
  }
  $value_items["pw_reset_key"]=password_hash($key,PASSWORD_BCRYPT,$options);
  $value_items["pw_reset_time"]=microtime(true);
  $value_items["pw_hash"]="*"; # disable login with password
  $value_items["login_cookie"]="*"; # disable login with cookie
  $value_items["pw_change"]=1; # force password change on user clicking link from email
  $where_items=array();
  $where_items["user_id"]=$user_record["user_id"];
  \webdb\sql\sql_update($value_items,$where_items,"users","webdb",true);
  $t=$value_items["pw_reset_time"]+$settings["password_reset_timeout"];
  $msg_params=array();
  $msg_params["key"]=urlencode($key);
  $msg_params["valid_to_time"]=date("g:i a",$t);
  $msg_params["valid_to_date"]=date("l, j F Y (T)",$t);
  $message=\webdb\utils\template_fill("password_reset_message",$msg_params);
  # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  \webdb\utils\show_message($message); # TESTING (REMOVE/COMMENT OUT FOR PROD)
  # ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
  \webdb\utils\send_email($user_record["email"],"",$settings["app_name"]." password reset",$message,$settings["server_email_from"],$settings["server_email_reply_to"],$settings["server_email_bounce_to"]);
  \webdb\users\webdb_unsetcookie("login_cookie");
  $message=\webdb\utils\template_fill("password_reset_valid_to_message",$msg_params);
  \webdb\users\auth_log($user_record,"RESET_PASSWORD_EMAIL","");
  \webdb\utils\show_message($message);
}

#####################################################################################################

function webdb_setcookie($setting_key,$value,$max_age=false)
{
  global $settings;
  if ($max_age===false)
  {
    $max_age=$settings["max_cookie_age"];
  }
  $expiry=time()+$max_age;
  setcookie($settings[$setting_key],$value,$expiry,"/",$_SERVER["HTTP_HOST"],false,true);
}

#####################################################################################################

function webdb_unsetcookie($setting_key)
{
  global $settings;
  setcookie($settings[$setting_key],null,-1,"/");
}

#####################################################################################################

function change_password($password_reset_user=false)
{
  global $settings;
  if (isset($_POST["change_password"])==true)
  {
    $pw_old=$_POST["change_password_old"];
    $pw_new=trim($_POST["change_password_new"]);
    $pw_new_conf=$_POST["change_password_new_confirm"];
    if ($pw_new<>$pw_new_conf)
    {
      \webdb\users\webdb_unsetcookie("login_cookie");
      \webdb\utils\show_message("error: new passwords do not match");
    }
    if (in_array($pw_new,$settings["prohibited_passwords"])==true)
    {
      \webdb\users\webdb_unsetcookie("login_cookie");
      \webdb\utils\show_message("error: cannot use any of the following for your new password: ".htmlspecialchars(implode(" ",$settings["prohibited_passwords"])));
    }
    if (strlen($pw_new)<$settings["min_password_length"])
    {
      \webdb\users\webdb_unsetcookie("login_cookie");
      \webdb\utils\show_message("error: new password must be at least ".$settings["min_password_length"]." characters");
    }
    if (strlen($pw_new)>$settings["max_password_length"])
    {
      \webdb\users\webdb_unsetcookie("login_cookie");
      \webdb\utils\show_message("error: a password of more than ".$settings["max_password_length"]." characters, while commendable, is considered a bit much. please try something shorter");
    }
    if ($pw_new==$pw_old)
    {
      \webdb\users\webdb_unsetcookie("login_cookie");
      \webdb\utils\show_message("error: new password cannot be the same as your old password");
    }
    $user_record=\webdb\users\get_user_record($_POST["login_username"]);
    if (password_verify($pw_old,$user_record["pw_hash"])==false)
    {
      \webdb\users\webdb_unsetcookie("login_cookie");
      \webdb\utils\show_message("error: old password is incorrect");
    }
    $options=array();
    $options["cost"]=$settings["password_bcrypt_cost"];
    if ($user_record["username"]=="admin")
    {
      $options["cost"]=$settings["admin_password_bcrypt_cost"];
    }
    $value_items=array();
    $value_items["pw_hash"]=password_hash($pw_new,PASSWORD_BCRYPT,$options);
    $value_items["pw_change"]=0;
    $where_items=array();
    $where_items["user_id"]=$user_record["user_id"];
    \webdb\sql\sql_update($value_items,$where_items,"users","webdb",true);
    \webdb\users\cancel_password_reset($user_record);
    \webdb\users\initialise_login_cookie($user_record);
    $settings["user_record"]=$user_record;
    \webdb\users\auth_log($user_record,"CHANGED_PASSWORD","");
    \webdb\utils\redirect($settings["app_web_index"]);
  }
  $change_password_params=array();
  $change_password_params["login_script_modified"]=\webdb\utils\resource_modified_timestamp("login.js");
  $change_password_params["login_styles_modified"]=\webdb\utils\resource_modified_timestamp("login.css");
  $change_password_params["home_link_display"]="none";
  if ($password_reset_user===false)
  {
    $change_password_params["old_password_default"]="";
    $change_password_params["old_password_display"]="table-row";
    if (isset($settings["user_record"])==false)
    {
      \webdb\users\login();
    }
    if ($settings["user_record"]["pw_change"]==0)
    {
      $change_password_params["home_link_display"]="block";
    }
    $change_password_params["login_username"]=$settings["user_record"]["username"];
  }
  else
  {
    # from password reset
    $value_items=array();
    $crypto_strong=true;
    $temp_password=base64_encode(openssl_random_pseudo_bytes(30,$crypto_strong));
    $options=array();
    $options["cost"]=$settings["password_bcrypt_cost"];
    if ($password_reset_user["username"]=="admin")
    {
      $options["cost"]=$settings["admin_password_bcrypt_cost"];
    }
    $value_items["pw_hash"]=password_hash($temp_password,PASSWORD_BCRYPT,$options);
    $value_items["pw_login_time"]=microtime(true);
    $value_items["user_agent"]=$settings["user_agent"];
    $value_items["remote_address"]=$_SERVER["REMOTE_ADDR"];
    $value_items["failed_login_count"]=0;
    $where_items=array();
    $where_items["user_id"]=$password_reset_user["user_id"];
    $settings["sql_check_post_params_override"]=true;
    \webdb\sql\sql_update($value_items,$where_items,"users","webdb",true);
    $settings["sql_check_post_params_override"]=false;
    $change_password_params["old_password_default"]=$temp_password;
    $change_password_params["old_password_display"]="none";
    $change_password_params["login_username"]=$password_reset_user["username"];
    $settings["user_record"]=$password_reset_user;
    \webdb\users\auth_log($password_reset_user,"RESET_PASSWORD_CHANGE","");
  }
  $content=\webdb\utils\template_fill("change_password",$change_password_params);
  \webdb\utils\output_page($content,"Change Password");
}

#####################################################################################################

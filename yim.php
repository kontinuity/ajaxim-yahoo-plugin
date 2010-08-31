<?php
require('globals.php');
require('oauth_helper.php');
require('yim_helper.php');

function yim_get_buddy_list() {
  
}

function yim_send_message($to, $message) {
  
  $oauth_data = $_SESSION['oauth_data'];
  $oauth_token = $oauth_data['oauth_token'];
  $access_token_secret = $oauth_data['oauth_token_secret'];
  
  $session_data = $_SESSION['session_data'];
  
  $url = 'http://' . $session_data->server . '/v1/message/yahoo/' . $to . '?sid=' . $session_data->sessionId;
  
  $params = yim_get_basic_oauth_params();
  $params['format'] = 'json';
  $params['oauth_token'] = $oauth_token;
  $params['oauth_signature'] = oauth_compute_plaintext_sig(OAUTH_CONSUMER_SECRET, $access_token_secret);

  $query_param_string = oauth_http_build_query($params);
  $url = $url . '&' . $query_param_string;
  
  $headers = array();
  $headers[] = 'Content-Type: application/json;charset=utf-8';
  
  $post_body = '{"message" : "' . $message . '"}';
  $response = do_post($url, $post_body, 80, $headers);
  
  return yim_is_successful_response($response);
}

function yim_checkinfo($username, $password, $return=array()) {
  // Add to refresh token here
  //Should check if session is valid
  $oauth_data = array_key_exists('oauth_data', $_SESSION) ? $_SESSION['oauth_data'] : array();
  $session_data = array_key_exists('session_data', $_SESSION) ? $_SESSION['session_data'] : array();
  
  if (count($oauth_data) <= 0 || count($session_data) <= 0 || !yim_session_is_valid($oauth_data, $session_data)) {
    $oauth_data = yim_login($username, $password);
    $session_data = yim_create_session();
    logit("PHP session active (" . session_id() . "). Created new Yahoo session with id: " . $session_data->sessionId . "\n\n");
  } else {
    logit("PHP session active (" . session_id() . "). Using Yahoo! session id: " . $session_data->sessionId . "\n\n");
  }
  return $session_data ? $session_data : false;
}

function yim_session_is_valid($oauth_data, $session_data) {
  $oauth_data = $_SESSION['oauth_data'];
  $oauth_token = $oauth_data['oauth_token'];
  $access_token_secret = $oauth_data['oauth_token_secret'];
  
  $session_data = $_SESSION['session_data'];
  
  $url = 'http://developer.messenger.yahooapis.com/v1/session?sid=' . $session_data->sessionId;
  
  $params = yim_get_basic_oauth_params();
  $params['oauth_token'] = $oauth_token;
  $params['oauth_signature'] = oauth_compute_plaintext_sig(OAUTH_CONSUMER_SECRET, $access_token_secret);
  
  $query_param_string = oauth_http_build_query($params);
  $url = $url . '&' . $query_param_string;
  $response = do_get($url);
  return yim_is_successful_response($response);
}

function yim_create_session() {
  
  $oauth_data = $_SESSION['oauth_data'];
  $oauth_token = $oauth_data['oauth_token'];
  $access_token_secret = $oauth_data['oauth_token_secret'];
  
  $url = 'http://developer.messenger.yahooapis.com/v1/session';
  
  $params = yim_get_basic_oauth_params();
  $params['fieldsBuddyList'] = '+groups';
  $params['oauth_token'] = $oauth_token;
  $params['oauth_signature'] = oauth_compute_plaintext_sig(OAUTH_CONSUMER_SECRET, $access_token_secret);
  
  $query_param_string = oauth_http_build_query($params);
  $url = $url . '?' . $query_param_string;
  $headers = array();
  $headers[] = 'Content-Type: application/json;charset=utf-8';
    
  $response = do_post($url, '{}', 80, $headers);
  yim_fail_if_not_ok($response, 'Could not create session');
  $json_session_data = $response[2];
  $json_handler = new JSON_obj();
  $data = $json_handler->decode($json_session_data);
  $_SESSION['session_data'] = $data;
  return $data;
}

function yim_get_session_crumb() {
  
  $oauth_data = $_SESSION['oauth_data'];
  $oauth_token = $oauth_data['oauth_token'];
  $access_token_secret = $oauth_data['oauth_token_secret'];
  
  $url = 'http://developer.messenger.yahooapis.com/v1/session';
  
  $params = yim_get_basic_oauth_params();  
  $params['oauth_token'] = $oauth_token;
  $params['oauth_signature'] = oauth_compute_plaintext_sig(OAUTH_CONSUMER_SECRET, $access_token_secret);
  
  $query_param_string = oauth_http_build_query($params);
  $url = $url . '?' . $query_param_string;
  $response = do_get($url);
  yim_fail_if_not_ok($response, 'Could not get session crumb');
  
  $json_session_crumb = $response[2];
  print($json_session_crumb);
}

function yim_logout() {
  $oauth_data = $_SESSION['oauth_data'];
  $oauth_token = $oauth_data['oauth_token'];
  $access_token_secret = $oauth_data['oauth_token_secret'];
  
  $session_data = $_SESSION['session_data'];
  
  $url = 'http://developer.messenger.yahooapis.com/v1/session?sid=' . $session_data->sessionId;
  
  $params = yim_get_basic_oauth_params();
  $params['oauth_token'] = $oauth_token;
  $params['oauth_signature'] = oauth_compute_plaintext_sig(OAUTH_CONSUMER_SECRET, $access_token_secret);
  
  $query_param_string = oauth_http_build_query($params);
  $url = $url . '&' . $query_param_string;
  $response = do_delete($url);
  return yim_is_successful_response($response);
}

function yim_login($username, $password) {
  $request_token = yim_auth_get_request_token($username, $password);
  $oauth_data = yim_auth_get_access_token($request_token);
  $_SESSION['oauth_data'] = $oauth_data;
  return $oauth_data;
}

/*
  https://api.login.yahoo.com/oauth/v2/get_token?
  oauth_consumer_key=dj0yJmk9RWsxQjk4cDBickdxJmQ9WVdrOVlXMW1lRE5ETjJNbWNHbzlOamt5TkRJME9EWXkmcz1jb25zdW1lcnNlY3JldCZ4PTJj
  &oauth_signature_method=PLAINTEXT
  &oauth_nonce=12345
  &oauth_timestamp=1234567890
  &oauth_signature=de75359591a2481eca60dd934ce28bdb80f8404a%26
  &oauth_version=1.0
  &oauth_token=4lYAVIdu3AbFfB2eqfPTQIS.UUYT8cfzZPF2KflaDc592gSZ6lgB5XSXSrivtjszFV2VjNtQ2cuJdgQQMUr_zstmBtXzjVStcZ.ZglhVvHanAVtBiGUyMoaCA7Gsd7AvIQhJzzw_hbAoL8XogPuj4oEKotdjQRFj9_JzC9TNYpIgf8RO49CDoofnVL4rOl8gLng_0Rt46hsdBOaD2w9YfGhd
*/

function yim_auth_get_access_token($request_token, $oauth_session_handle=NULL, $oauth_token_secret=NULL) {
  
  $url = 'https://api.login.yahoo.com/oauth/v2/get_token';
  
  $params = yim_get_basic_oauth_params();  
  $params['oauth_signature'] = oauth_compute_plaintext_sig(OAUTH_CONSUMER_SECRET, $oauth_token_secret);
  $params['oauth_token'] = $request_token;
  
  if ($oauth_session_handle != NULL) {
    $params['oauth_session_handle'] = $oauth_session_handle;
  }

  $query_param_string = oauth_http_build_query($params);
  $url = $url . '?' . $query_param_string;  
  $response = do_get($url, 443);
  yim_fail_if_not_ok($response, 'Could not get access token');
  
  $access_token_response = $response[2];
  $access_tokens = split("&", $access_token_response);
  
  $oauth_data = array();
  foreach ($access_tokens as $param) {
    $d = split("=", $param);
    $oauth_data[$d[0]] = $d[1];
  }

  $oauth_data['oauth_token'] = rfc3986_decode($oauth_data['oauth_token']);
  
  return $oauth_data;
}

/*
  https://login.yahoo.com/WSLogin/V1/get_auth_token?
  &login=directi_developer@yahoo.com
  &passwd=qwedsa
  &oauth_consumer_key=dj0yJmk9RWsxQjk4cDBickdxJmQ9WVdrOVlXMW1lRE5ETjJNbWNHbzlOamt5TkRJME9EWXkmcz1jb25zdW1lcnNlY3JldCZ4PTJj
*/
function yim_auth_get_request_token($username, $password) {
  $url = 'https://login.yahoo.com/WSLogin/V1/get_auth_token';
  $params['oauth_consumer_key'] = OAUTH_CONSUMER_KEY;
  $params['login'] = $username;
  $params['passwd'] = $password;
  
  $query_param_string = oauth_http_build_query($params);
  $url = $url . '?' . $query_param_string;
  
  $response = do_get($url, 443);
  yim_fail_if_not_ok($response, 'Invalid credentials');
  
  $request_token_response = $response[2];
  $request_token_arr = split("=", $request_token_response);
  return $request_token_arr[1];
}

?>
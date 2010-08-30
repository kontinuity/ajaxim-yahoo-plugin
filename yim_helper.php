<?php

function yim_get_basic_oauth_params() {
  
  $params['oauth_version'] = '1.0';
  $params['oauth_nonce'] = mt_rand();
  $params['oauth_timestamp'] = time();
  $params['oauth_consumer_key'] = OAUTH_CONSUMER_KEY;
  $params['oauth_signature_method'] = 'PLAINTEXT';  
  return $params;
}

function yim_is_successful_response($response) {
  $code = $response[0]['http_code'];
  return ($code == '200');
}

function yim_fail_if_not_ok($response, $message='An unknown error has occurred') {
  $code = $response[0]['http_code'];
  
  if ($code != '200') {
    print_r($response);
    throw new Exception($message);
  }
  
}

?>
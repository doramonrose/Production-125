<?php
ob_start();
session_start();
// MS Entra ID
// $client_id = 'YOUR_CLIENT_ID';    // The client ID assigned to you by the provider
// $client_secret = 'YOUR_CLIENT_SECRET';  // The client secret assigned to you by the provider

$client_id = 'YOUR_CLIENT_ID';    // The client ID assigned to you by the provider
$client_secret = 'YOUR_CLIENT_SECRET';  // The client secret assigned to you by the provider


$redirect_uri = 'YOUR_SECRET_VALUE';
$oauth_scope = "api://cmu/Mis.Account.Read.Me.Basicinfo offline_access";
$oauth_auth_url = "https://login.microsoftonline.com/cf81f1df-de59-4c29-91da-a2dfd04aa751/oauth2/v2.0/authorize";
$oauth_token_url = 'YOUR_SECRET_VALUE';
$wsapi_get_basicinfo_url = "https://api.cmu.ac.th/mis/cmuaccount/prod/v3/me/basicinfo";



if (isset($_GET['error']) != null) {
  echo "error: " . $_GET['error'];
  echo "<br>";
  echo "error_description: " . $_GET['error_description'];
} else {
  if (isset($_GET['code'])) {
    $accessToken = get_oauth_token($_GET['code'], $oauth_token_url);

    //Show Debug Code / Access Token
    echo "code: " . $_GET['code'] . "<br>";
    echo 'YOUR_SECRET_VALUE' . $accessToken . "<br>";
    $_SESSION['access'] = $accessToken;

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => $wsapi_get_basicinfo_url,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => "",
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 30,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => "GET",
      CURLOPT_HTTPHEADER => array(
        "Authorization: Bearer " . $accessToken,
        "Cache-Control: no-cache"
      )
    ));

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);

    if ($err) {
      $json = json_decode($response, true);
      echo "cURL Error #:" . $err;
    } else {
      $json = json_decode($response, true);

      // Response model in Json format  

      // $_SESSION['code_std'] = $json['student_id'];
      $_SESSION['prefix'] = $json['prename_TH'];
      $_SESSION['firstname'] = $json['firstname_TH'];
      $_SESSION['lastname'] = $json['lastname_TH'];
      $_SESSION['cmuitaccount'] = $json['cmuitaccount'];
      $_SESSION['emailCMU'] = $data_json['emailCMU'];

      // echo '<pre>';
      // print_r($json);
      // echo '</pre>';
      header('Location:page_oauth.php');
    }
  } else {
    header("Location:" . $oauth_auth_url . "?response_type=code&client_id=$client_id&redirect_uri=$redirect_uri&scope=$oauth_scope");
    exit;
  }
}


// Get access_token to call webservices api
function get_oauth_token($code, $oauth_url)
{
  global $client_id;
  global $client_secret;
  global $redirect_uri;

  // $client_id = 'YOUR_CLIENT_ID';    // The client ID assigned to you by the provider
  // $client_secret = 'YOUR_CLIENT_SECRET';  // The client secret assigned to you by the provider
  // $redirect_uri = 'https://mis.pharmacy.cmu.ac.th/mis_new/oauth.php';       // redirect_uri (This file url)

  $client_post = array(
    "code" => $code,
    "client_id" => $client_id,
    "client_secret" => $client_secret,
    "redirect_uri" => $redirect_uri,
    "grant_type" => "authorization_code"
  );

  $curl = curl_init();

  curl_setopt($curl, CURLOPT_URL, $oauth_url);
  curl_setopt($curl, CURLOPT_HTTPHEADER, array('application/x-www-form-urlencoded'));
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $client_post);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);


  $json_response = curl_exec($curl);
  $err = curl_error($curl);
  curl_close($curl);
  if ($err) {
    echo "cURL Error Token #:" . $err;
  }

  $authObj = json_decode($json_response);

  if (isset($authObj->refresh_token)) {
    global $refreshToken;
    $refreshToken = $authObj->refresh_token;
  }

  $accessToken = $authObj->access_token;
  return $accessToken;
}

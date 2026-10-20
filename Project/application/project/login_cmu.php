<?php
session_start();

$side = isset($_GET['side']) ? trim((string) $_GET['side']) : '';
if ($side !== 'user' && $side !== 'plan') {
    header('Location: index.php');
    exit;
}

$_SESSION['pbm_project_side'] = $side;

$clientId = 'c6b982e3-412c-430c-b505-0eab62431a4d';
$redirectUri = 'https://pbm.pharmacy.cmu.ac.th/application/KPI/oauth-EN-ID-account.php';
$scope = 'api://cmu/Mis.Account.Read.Me.Basicinfo offline_access';
$authUrl = 'https://login.microsoftonline.com/cf81f1df-de59-4c29-91da-a2dfd04aa751/oauth2/v2.0/authorize';

header(
    'Location: ' . $authUrl
    . '?response_type=code'
    . '&client_id=' . rawurlencode($clientId)
    . '&redirect_uri=' . rawurlencode($redirectUri)
    . '&scope=' . rawurlencode($scope)
);
exit;

<?php

/*if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $access_token = get_access_token($code);
    if (!$access_token) {
        echo "<p class='alert alert-danger'>Erreur lors de la récupération du token</p>";
    } else {
        echo "<p class='alert alert-info'>Token récupéré : $access_token </p>";
    }
}*/

/**
 * Récupère un token d'accès à partir d'un code d'autorisation OAuth2.
 *
 * @param string $code Le code d'autorisation OAuth2.
 *
 * @return string Le token d'accès si la requête a réussi.
 */
function get_access_token(string $code): string
{
    $env = parse_ini_file('.env');

    $client_id = $env['CLIENT_ID'];
    $client_secret = $env['CLIENT_SECRET'];
    $redirect_uri = $env['REDIRECT_URI'];

    $url = 'https://wbsapi.withings.net/v2/oauth2';

    // Données à envoyer
    $data = [
        'action' => 'requesttoken',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri
    ];

    $cSession = curl_init();

    curl_setopt_array($cSession, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false

    ]);

    $result = curl_exec($cSession);
    curl_close($cSession);

    if (!$result) {
        return false;
    } else {
        $response = json_decode($result);
        return $response->body->access_token ?? false;
    }
}

/**
 * @param string $accessToken
 * @return false|mixed
 */
function get_data_user(string $accessToken)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://wbsapi.withings.net/measure");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'action' => 'getmeas',
        'meastype' => 1,
        'meastypes' => 1,
        'category' => 1,
        'offset' => 0
    ]));

    $response = curl_exec($ch);
    if (curl_error($ch)) {
        echo 'Erreur curl : ' . curl_error($ch);
        return false;
    }

    curl_close($ch);

    if (!$response) {
        return false;
    } else {
        return json_decode($response, true);
    }
}

$access_token = "";
if (isset($_GET['code'])) {
    $access_token = get_access_token($_GET['code']);
}
$user_data = [];
if (!empty($access_token)) {
    $user_data = get_data_user($access_token);
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css"
          integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65"
          crossorigin="anonymous">
    <title>Withings Oauth2</title>
</head>
<body>
<main class="container">
    <h1>Demande d'autorisation Withings</h1>
    <p>Pour autoriser cette application à accéder à vos données Withings, veuillez cliquer sur le bouton ci-dessous
        :</p>

    <form method="get" action="https://account.withings.com/oauth2_user/authorize2">
        <input type="hidden" name="response_type" value="code">
        <input type="hidden" name="client_id" value="a16837aaa8f536b229ce20fa8e90a2739885b640ff67de7b84562b6fe0e27513">
        <input type="hidden" name="redirect_uri" value="http://localhost:7070">
        <input type="hidden" name="state" value="withings_test">
        <input type="hidden" name="scope" value="user.metrics">
        <input type="hidden" name="mode" value="demo">
        <input class="btn btn-sm btn-primary" type="submit" value="Autoriser">
    </form>

    <?php
    $measure = 0;
    $dateMeasure = "";
    echo '<div class="container m-5">';
    if (!empty($user_data) && isset($user_data['body']['measuregrps'])) {
        foreach ($user_data['body']['measuregrps'] as $measuregrp) {
            $dateMeasure = date('d/m/Y à H\hi', $measuregrp['date']);
            foreach ($measuregrp['measures'] as $measure) {
                $measure = $measure['value'] / 1000;
            }
        }
        echo '<p>Date de la dernière pesée : le ' . $dateMeasure . '</p>';
        echo '<p>Poids : '. $measure . ' kg</p>';
    } else {
        echo '<p class="alert alert-info m-5">Aucune donnée Withings à afficher</p>';
    }
    echo '</div>';
    ?>

</main>
</body>
</html>


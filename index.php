<?php

/**
 * Récupère un token d'accès à partir d'un code d'autorisation OAuth2.
 *
 * @param string $code Le code d'autorisation OAuth2.
 *
 * @return string Le token d'accès si la requête a réussi.
 */
function get_access_token(string $code): string
{
    // Récupération des variables d'environnement (fichier .env)
    $env = parse_ini_file('.env');

    $client_id = $env['CLIENT_ID'];
    $client_secret = $env['CLIENT_SECRET'];
    $redirect_uri = $env['REDIRECT_URI'];

    $url = 'https://account.withings.com/oauth2/token';

    // Données à envoyer
    $data = [
        'grant_type' => 'authorization_code',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
        'code' => $code,
        'redirect_uri' => $redirect_uri
    ];


    $httpBuildQuery = http_build_query($data);

    $cSession = curl_init();
    curl_setopt($cSession, CURLOPT_URL, $url);
    curl_setopt($cSession, CURLOPT_POST, true);
    curl_setopt($cSession, CURLOPT_POSTFIELDS, $httpBuildQuery);
    curl_setopt($cSession, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($cSession, CURLOPT_HEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);

    $result = curl_exec($cSession);
    curl_close($cSession);

    if (!$result) {
        return false;
    } else {
        $response = json_decode($result, true);
        return $response['access_token'] ?? false;
    }
}

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $access_token = get_access_token($code);
    if (!$access_token) {
        echo "<p class='alert alert-danger'>Erreur lors de la récupération du token</p>";
    } else {
        echo "<p class='alert alert-info'>Token récupéré : $access_token</p>";
    }
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
</main>
</body>
</html>
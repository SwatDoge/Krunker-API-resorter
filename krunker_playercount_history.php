<?php
	header("Access-Control-Allow-Origin: *");
    header("content-type: application/json");

function read_file($file_name){
    $store_json = fopen($file_name, "r");
    $x = fread($store_json, filesize($file_name));
    fclose($store_json);
    return $x;
}

if ((int)$_GET["count"] <= 0) $_GET["count"] = 87600;

$database_info = json_decode(read_file("/home/u52521p49827/domains/swatdoge.nl/public_html/s/krunkerbrowser/db_connection.json"), true);
$connection = mysqli_connect($database_info["host"], $database_info["username"], $database_info["password"], $database_info["database"]);
$game_types = ["custom", "public"];

if (!$connection) die("{\"error\"}");

foreach ($game_types as $type){
    $result = mysqli_query($connection, "SELECT * FROM krunker_playerbase_{$type} ORDER BY `krunker_playerbase_{$type}`.`id` ASC LIMIT {$_GET['count']}");
    while($row = mysqli_fetch_assoc($result)) $playercounts[$type][] = $row;
}
print(json_encode($playercounts));

mysqli_close($connection);

/*
print("
<title>Krunker playerbase history</title>
<meta property=\"og:type\" content=\"website\">
<meta property=\"og:title\" content=\"SwatDoge's krunker playerbase history api\"/>
<meta property=\"og:description\" content=\"An api which stores krunker playercounts every 30 minutes. Get playercounts per region and gametype, since 15/11/2020. \"/>
<meta property=\"og:url\" content=\"https://swatdoge.nl/s/krunkerbrowser/krunker_playercount_history.php\"/>

<meta name=\"title\" content=\"SwatDoge's krunker playerbase history api\">
<meta name=\"description\" content=\"An api which stores krunker playercounts every 30 minutes. Get playercounts per region and gametype, since 15/11/2020.\">

<meta property=\"twitter:url\" content=\"https://swatdoge.nl/s/krunkerbrowser/krunker_playercount_history.php\">
<meta property=\"twitter:title\" content=\"SwatDoge's krunker playerbase history api\">
<meta property=\"twitter:description\" content=\"An api which stores krunker playercounts every 30 minutes. Get playercounts per region and gametype, since 15/11/2020.\">
");
?>
*/
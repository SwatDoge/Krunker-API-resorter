<?php
    $converted_contents = json_decode(file_get_contents('https://matchmaker.krunker.io/game-list?hostname=krunker.io'), true);

    function read_file($file_name){
        $store_json = fopen($file_name, "r");
        $x = fread($store_json, filesize($file_name));
        fclose($store_json);
        return $x;
    }

    function write_to_file($text, $file_name){
        $x = fopen($file_name, "w");
        fwrite($x, $text);
        fclose($x);
    }

    function remove_dupes_array($input_array){
        $clone_array = array();
            foreach($input_array as $input_array_item){
                $var_exists = false;
                foreach($clone_array as $clone_array_item) {
                    if ($input_array_item == $clone_array_item) $var_exists = true;
                }
                if (!$var_exists) $clone_array[] = $input_array_item;
            }
        return $clone_array;
    }

    $all_regions = [];
    $complete_json = [];
    $known_regions_names = [];
    $unsorted_playercounts = [];
    $region_info_collection = [];
    $game_types = ["custom", "public"];

    $region_json_registered = json_decode(read_file(__DIR__ . "/krunker_regions_registered.json"), true);    
    $player_counts["total"] = $converted_contents["totalPlayerCount"];
    
    foreach ($game_types as $type){
        foreach ($converted_contents["games"] as $game){
            switch ($type){
                case $game_types[0]:
                    if ($game[4]["cs"])
                        $all_regions[$game_types[0]][$game[1]][] = $game;
                break;
                case $game_types[1]:
                    if (!$game[4]["cs"])
                        $all_regions[$game_types[1]][$game[1]][] = $game;
                break;
            }
        }

        foreach ($region_json_registered["server_regions"] as $region_shortcut => $x) $known_regions_names[] = $region_shortcut;

        foreach ($all_regions[$type] as $region => $x){
            $region_info_collection[$region] = [];
            $region_data = $region_info_collection[$region];
    
            if (in_array($region, $known_regions_names)) $region_data = $region_json_registered["server_regions"][$region];
            else $region_data = array("region_name" => "?", "country_name" => "?", "server_location" => $region);
            
            $player_counts["regions"][$type][$region_data["region_name"]][$region_data["country_name"]][$region_data["server_location"]] = 0;
            $unsorted_playercounts[$type][$region] = 0;
            foreach($all_regions[$type][$region] as $game => $x){
                $complete_json[$type][$region_data["region_name"]][$region_data["country_name"]][$region_data["server_location"]][] = array(
                    $all_regions[$type][$region][$game][0],
                    $all_regions[$type][$region][$game][2],
                    $all_regions[$type][$region][$game][3],
                    $all_regions[$type][$region][$game][4]["v"],
                    explode("_", $all_regions[$type][$region][$game][4]["i"], 2)[0],
                    explode("_", $all_regions[$type][$region][$game][4]["i"], 2)[1]
                );
                $player_counts["regions"][$type][$region_data["region_name"]][$region_data["country_name"]][$region_data["server_location"]] += $all_regions[$type][$region][$game][2];
                $unsorted_playercounts[$type][$region] += $all_regions[$type][$region][$game][2];
            }
        }
    }

    write_to_file(json_encode($complete_json), "/home/u52521p49827/domains/swatdoge.nl/public_html/s/krunkerbrowser/krunker_data_sorted.json");
    write_to_file(json_encode($region_info_collection), "/home/u52521p49827/domains/swatdoge.nl/public_html/s/krunkerbrowser/krunker_regions_generated.json");
    write_to_file(json_encode($player_counts), "/home/u52521p49827/domains/swatdoge.nl/public_html/s/krunkerbrowser/krunker_total_playercount.json");

    $database_info = json_decode(read_file("/home/u52521p49827/domains/swatdoge.nl/public_html/s/krunkerbrowser/db_connection.json"), true);
    $connection = mysqli_connect($database_info["host"], $database_info["username"], $database_info["password"], $database_info["database"]);

    if (!$connection) die("Connection failed: ". mysqli_connect_error());
    else {
        $db_tables = [];
        $db_columns = [];
        $date_now = time();
        $result = mysqli_query($connection, "SHOW TABLES");

        foreach ($game_types as $key => $gamemode){
            $insert_data[$gamemode] = "";
            $insert_names[$gamemode] = "";
        }

        if ($result){
            if (mysqli_num_rows($result) > 0) 
                while($row = mysqli_fetch_assoc($result)) 
                    $db_tables[] = $row["Tables_in_{$database_info['database']}"];
        } else die(mysqli_error($connection));

        foreach ($game_types as $key => $gamemode){
            //add not yet existing gamemodes
            if (!in_array("krunker_playerbase_" . $gamemode, $db_tables))
                if (!mysqli_query($connection, "CREATE TABLE `krunker_playerbase_{$gamemode}` ( `id` INT NOT NULL AUTO_INCREMENT , `date` TEXT NOT NULL , PRIMARY KEY (`id`));"))
                    die("Failed to add new gamemode as database");

            $result = mysqli_query($connection, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='krunker_playerbase_{$gamemode}';");
            
            if (mysqli_num_rows($result) > 0) 
                while($row = mysqli_fetch_assoc($result)) 
                    $db_columns[] = $row["COLUMN_NAME"];
            else 
                die("It looks like the entire database got wiped, thats going to be a hard one to recover from.");

            //add not yet existing regions
            foreach ($unsorted_playercounts[$gamemode] as $region_shortcut => $values){
                if (!in_array($region_shortcut, $db_columns))
                    if (!mysqli_query($connection, "ALTER TABLE `krunker_playerbase_{$gamemode}` ADD `{$region_shortcut}` INT NULL DEFAULT NULL, ADD INDEX (`{$region_shortcut}`);"))
                        die("there was an issue with adding a new region");

                $insert_names[$gamemode] .= "`" . $region_shortcut . "`, ";
                $insert_data[$gamemode] .= "'" . $values . "', ";
            }
        }

        foreach ($game_types as $key => $gamemode){
            $names = rtrim($insert_names[$gamemode], ", ");
            $data = rtrim($insert_data[$gamemode], ", ");
            
            if (!mysqli_query($connection, "INSERT INTO `krunker_playerbase_{$gamemode}` (`date`, {$names}) VALUES ('{$date_now}', {$data});"))
                die("There was an issue with adding the record to the database. ". "INSERT INTO `krunker_playerbase_{$gamemode}` (`date`, {$names}) VALUES ('{$date_now}', {$data}); </br>");
        }
    }

    mysqli_close($connection);
?>
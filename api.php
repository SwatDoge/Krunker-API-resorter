<?php
	header("Access-Control-Allow-Origin: *");
	header("content-type: application/json");
	
    function read_file($file_name){
        $store_json = fopen($file_name, "r");
        $x = fread($store_json, filesize($file_name));
        fclose($store_json);
        return $x;
    }

    $json_rebuild = [];
    $all_contents = json_decode(read_file("krunker_data_sorted.json"), true);
    $all_values = ["gametype" => true, "return_location_only" => "", "url" => true, "players" => true, "max-players" => true, "version" => true, "gamemode" => true, "map" => true, "merge_map_and_gamemode" => false, "show_games" => true];

    foreach ($all_values as $key => $value)
        if (isset($_GET[$key]))
            if (gettype($_GET[$key]) == "string")
                if ($_GET[$key] == "false") $all_values[$key] = false;
                else if ($_GET[$key] == "true") $all_values[$key] = true;
                else if ($key == "return_location_only" && preg_match('/[a-z]/', $_GET[$key])) $all_values[$key] = $_GET[$key];

    if (!$all_values["show_games"]){
        $all_values["url"] = false;
        $all_values["players"] = false;
        $all_values["max-players"] = false;
        $all_values["version"] = false;
        $all_values["gamemode"] = false;
        $all_values["map"] = false;
    }

    foreach($all_contents as $gametype_key => $gametype_value){
        foreach($all_contents[$gametype_key] as $region_key => $region_value){
            foreach($all_contents[$gametype_key][$region_key] as $country_key => $country_value){
                foreach($all_contents[$gametype_key][$region_key][$country_key] as $location_key => $location_value){
                    $json_rebuild[$gametype_key][$region_key][$country_key][$location_key] = array();
                    foreach($all_contents[$gametype_key][$region_key][$country_key][$location_key] as $gamelist => $gamelist_value){
                        if ($all_values["return_location_only"] == "" || ($all_values["return_location_only"] != "" && $location_key == $all_values["return_location_only"])){
                            if ($all_values["url"])             $json_rebuild[$gametype_key][$region_key][$country_key][$location_key][$gamelist][] = $all_contents[$gametype_key][$region_key][$country_key][$location_key][$gamelist][0];
                            if ($all_values["players"])         $json_rebuild[$gametype_key][$region_key][$country_key][$location_key][$gamelist][] = $all_contents[$gametype_key][$region_key][$country_key][$location_key][$gamelist][1];
                            if ($all_values["max-players"])     $json_rebuild[$gametype_key][$region_key][$country_key][$location_key][$gamelist][] = $all_contents[$gametype_key][$region_key][$country_key][$location_key][$gamelist][2];
                            if ($all_values["version"])         $json_rebuild[$gametype_key][$region_key][$country_key][$location_key][$gamelist][] = $all_contents[$gametype_key][$region_key][$country_key][$location_key][$gamelist][3];
                            if ($all_values["merge_map_and_gamemode"])   
                                $json_rebuild[$gametype_key][$region_key][$country_key][$location_key][$gamelist][] = $all_contents[$gametype_key][$region_key][$country_key][$location_key][$gamelist][4] . "_" .  $all_contents[$gametype_key][$region_key][$country_key][$location_key][$gamelist][5];
                            else{
                                if ($all_values["gamemode"])    $json_rebuild[$gametype_key][$region_key][$country_key][$location_key][$gamelist][] = $all_contents[$gametype_key][$region_key][$country_key][$location_key][$gamelist][4];
                                if ($all_values["map"])         $json_rebuild[$gametype_key][$region_key][$country_key][$location_key][$gamelist][] = $all_contents[$gametype_key][$region_key][$country_key][$location_key][$gamelist][5];
                            }
                        }
                    }
                }
            }
        }
    }
    
    print_r(json_encode($json_rebuild));
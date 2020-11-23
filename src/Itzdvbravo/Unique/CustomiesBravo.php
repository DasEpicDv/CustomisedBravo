<?php

namespace Itzdvbravo\Unique;

use pocketmine\network\mcpe\convert\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use const pocketmine\RESOURCE_PATH;

class CustomiesBravo{
    public const GEM = 899;
    /** @var ItemTypeEntry[] */
    public static $entries = [];

    public static $simpleNetToCoreMapping = [];
    public static $simpleCoreToNetMapping = [];

    public static function init(Main $plugin){
        $file = json_decode(file_get_contents($plugin->getDataFolder()."config.json"), true);
        $data = file_get_contents(RESOURCE_PATH . '/vanilla/r16_to_current_item_map.json');
        if($data === false) throw new AssumptionFailedError("Missing required resource file");
        $json = json_decode($data, true);
        $add = $file["r16_to_current_item_map"];
        //Merged custom items here with minecraft vanilla items
        $json["simple"] = array_merge($json["simple"], $add["simple"]);
        if(!is_array($json) or !isset($json["simple"], $json["complex"]) || !is_array($json["simple"]) || !is_array($json["complex"])){
            throw new AssumptionFailedError("Invalid item table format");
        }

        $legacyStringToIntMapRaw = file_get_contents(RESOURCE_PATH . '/vanilla/item_id_map.json');
        $add = $file["item_id_map"];
        if($legacyStringToIntMapRaw === false){
            throw new AssumptionFailedError("Missing required resource file");
        }
        //Merged custom items here with minecraft vanilla items
        $legacyStringToIntMap = json_decode($legacyStringToIntMapRaw, true);
        $legacyStringToIntMap = array_merge($add, $legacyStringToIntMap);

        if(!is_array($legacyStringToIntMap)){
            throw new AssumptionFailedError("Invalid mapping table format");
        }

        /** @phpstan-var array<string, int> $simpleMappings */
        $simpleMappings = [];
        foreach($json["simple"] as $oldId => $newId){
            if(!is_string($oldId) || !is_string($newId)){
                throw new AssumptionFailedError("Invalid item table format");
            }
            $simpleMappings[$newId] = $legacyStringToIntMap[$oldId];
        }
        foreach($legacyStringToIntMap as $stringId => $intId){
            if(isset($simpleMappings[$stringId])){
                throw new \UnexpectedValueException("Old ID $stringId collides with new ID");
            }
            $simpleMappings[$stringId] = $intId;
        }

        /** @phpstan-var array<string, array{int, int}> $complexMappings */
        $complexMappings = [];
        foreach($json["complex"] as $oldId => $map){
            if(!is_string($oldId) || !is_array($map)){
                throw new AssumptionFailedError("Invalid item table format");
            }
            foreach($map as $meta => $newId){
                if(!is_numeric($meta) || !is_string($newId)){
                    throw new AssumptionFailedError("Invalid item table format");
                }
                $complexMappings[$newId] = [$legacyStringToIntMap[$oldId], (int) $meta];
            }
        }
        //Merged custom items here with minecraft vanilla items
        $old = json_decode(file_get_contents(RESOURCE_PATH  . '/vanilla/required_item_list.json'), true);
        $add = $file["required_item_list"];
        $table = array_merge($old, $add);
        $params = [];
        foreach($table as $name => $entry){
            $params[] = new ItemTypeEntry($name, $entry["runtime_id"], $entry["component_based"]);
        }
        self::$entries = $entries = (new ItemTypeDictionary($params))->getEntries();
        foreach($entries as $entry){
            $stringId = $entry->getStringId();
            $netId = $entry->getNumericId();
            if (isset($complexMappings[$stringId])){
                // Uh.. XD not using this for now
            }elseif(isset($simpleMappings[$stringId])){
                self::$simpleCoreToNetMapping[$simpleMappings[$stringId]] = $netId;
                self::$simpleNetToCoreMapping[$netId] = $simpleMappings[$stringId];
            }elseif($stringId !== "minecraft:unknown"){
                throw new \InvalidArgumentException("Unmapped entry " . $stringId);
            }
        }
    }

    //To be honest idk what these "r16_to_current_item_map" or "required_item_list" really means, I have just Copied the style items are there and then merged the files
}

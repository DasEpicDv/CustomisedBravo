<?php

namespace Itzdvbravo\Unique;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\Config;
use const pocketmine\RESOURCE_PATH;

class Main extends PluginBase implements Listener {

	public function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveResource("config.json");
        	ItemFactory::registerItem(new Item(CustomiesBravo::GEM, 0, "Gem"));
	}

    public function onPacketReceve(DataPacketSendEvent $event){
	    $packet = $event->getPacket();
	    if ($packet instanceof StartGamePacket){
            $old = json_decode(file_get_contents(RESOURCE_PATH . '/vanilla/item_id_map.json'), true);
            $add = json_decode(file_get_contents(Server::getInstance()->getDataPath()."plugin_data/Unique/config.json"), true);
            $packet->itemTable =  array_merge($old, $add);
        }
    }
}

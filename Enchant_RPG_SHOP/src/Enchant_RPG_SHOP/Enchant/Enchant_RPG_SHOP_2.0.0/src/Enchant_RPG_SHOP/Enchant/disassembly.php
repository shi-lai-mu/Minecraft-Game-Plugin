<?php
namespace Enchant_RPG_SHOP\Enchant;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginLoader;
use pocketmine\plugin\PluginBase;

use Enchant_RPG_SHOP\Enchant_RPG_SHOP as Main;

/*
	by slm47888
	qq 478889187
	in disassembly.php
*/

class disassembly
{
	private $Main;
	public function __construct(Main $Main)
	{
		$this->Main = $Main;
		$dir = $this->Main->getDataFolder();
	}
	

}
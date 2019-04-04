<?php
/**
 * User: slm47888
 * Date: 2017/12/05
 * Time: 14:03
 * Version: 2.4.0
 */
namespace Enchant_RPG_SHOP\Command;

class setName
{
	public function __construct($sender,$args,$Main){
		if(!isset($args[1])) return $sender->sendMessage($Main->Message->get('Command.setName.001'));
		$Main->Main->Command['setName'][$sender->getName()] = $args[1];
		$Main->Inventory->AddPock(
			$sender,$Main->Main->EnchantEvent->Select_Upgrade_Item($sender),
			'§c§l请双击一个需命名的装备',
			'setName'
		);
	}
}
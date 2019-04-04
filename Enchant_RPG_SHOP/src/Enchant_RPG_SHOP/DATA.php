<?php
namespace Enchant_RPG_SHOP;
use pocketmine\utils\Config;
use Enchant_RPG_SHOP\Enchant_RPG_SHOP as Main;

class DATA
{
	public function __construct(Main $Main,$file_name)
	{
		$dir = $Main->getDataFolder();
		$file = new Config($dir.'ID/'.$file_name.'.yml',Config::YAML,[]);
		$this->name = $file->get('名字');
		$this->id = $file->get('ID');
		$this->pvp = $file->get('pvp生效');
		$this->independent = $file->get('独立');
		$this->destroyed = $file->get('销毁');
		$this->discarded = $file->get('可丢弃');
		$this->setname = $file->get('设名字');
		$this->Food = $file->get('为食物');
		$this->passive = $file->get('为被动');
		$this->protect = $file->get('为护甲');
		$this->shooting = $file->get('为射击');
		$this->hand = $file->get('为手持');
		$this->info = $file->get('详细');
		$this->gamemode = $file->get('模式');
		$this->damage = $file->get('能量');
		$this->burning = $file->get('燃烧');
		$this->CD = $file->get('冷却');
		$this->combo = $file->get('连击');
		$this->scope = $file->get('伤害范围');
		$this->Effect = $file->get('药水范围');
		$this->vampire = $file->get('攻击吸血');
		$this->superposition = $file->get('连击伤害');
		$this->rebound_D = $file->get('反弹伤害');
		$this->vampire_d = $file->get('药水范围');
		$this->CDtip = $file->get('冷却提示');
		$this->ai = $file->get('覆盖AI');
		$this->Tip = $file->get('连击提示');
		$this->swim = $file->get('眩晕');
		$this->note = $file->get('增强');
		$this->getdefense = $file->get('防御');
		$this->getScores = $file->get('分数');
		$this->getEnchantLevel = $file->get('等级');
		$this->getItemId = $file->get('物品');
		$this->getDamage = $file->get('攻击');
	}
}
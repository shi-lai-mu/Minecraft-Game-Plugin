<?php
namespace Enchant_RPG_SHOP;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginLoader;
use pocketmine\plugin\PluginBase;

use Enchant_RPG_SHOP\Enchant_RPG_SHOP as Main;

class api extends PluginBase implements Listener
{
	private $Main;
	public static $api = Null;
	public function __construct(Main $Main)
	{
		$this->Main = $Main;
		self::$api = $this;
		$this->Main->getLogger()->info('§e[接口] 附魔静态PRG_API加载成功!');
		$dir = $this->Main->getDataFolder();
		$this->money = new Config($dir . 'Money.json',Config::YAML,[]);
		$this->set = new Config($dir . 'set.yml',Config::YAML,array());
	}
	
	public static function api()
	{
		return self::$api;
	}

	public function getVersion()//获取附魔插件版本
	{
		return $this->Main->getDescription()->getVersion();
	}
	
	public function getMoney(String $name)//获取玩家附魔券
	{
		if(!$this->money->exists($name))
		{
			return Null;
		}
		return $this->money->get($name);
	}
	
	public function setMoney(String $name,$int)//设置玩家附魔券
	{
		$this->money->set($name,$int);
		$this->money->save();
		return True;
	}
	
	public function addMoney(String $name,$int)//给玩家附魔券
	{
		$old_money = $this->getMoney();
		if($old_money === null) return $old_money;
		$this->money->set($name,$old_money + $int);
		$this->money->save();
		return True;
	}
	
	public function delMoney(String $name,$int)//扣玩家附魔券
	{
		$old_money = $this->getMoney();
		if($old_money === null) return $old_money;
		if($int > $old_money) $int = $old_money;
		$this->money->set($name,$old_money - $int);
		$this->money->save();
		return True;
	}
	
	public function MoneyList($int = 'ALL')//获取附魔券富豪榜
	{
		$ALL = $this->Money->getAll();
		arsort($ALL);
		$c = 0;
		$Array = [];
		if($int == 'ALL') $int = count($ALL);
		foreach($ALL as $key => $value)
		{
			if($c < $ALL)
			{
				$c += 1;
				$Array[] = Array($key,$value);
			}
		}
		return $Array;
	}

	public function getAdmin()//获取附魔管理员列表
	{
		return $this->set->get('设置')['白名单'];
	}

	public function setAdmin(String $name)//设置为附魔管理员
	{
		$set = $this->set->get('设置');
		if(in_array($name,$set['白名单'])) return False;
		$set['白名单'][] = $name;
		$this->set->set('设置',$set);
		$this->set->save();
		return True;
	}

	public function delAdmin(String $name)//夺取附魔管理员
	{
		$set = $this->set->get('设置');
		if(!in_array($name,$set['白名单'])) return False;
		for($a = 0; $a < count($set['白名单']); $a ++)
		{
			if($set['白名单'][$a] == $name)
			{
				unset($set['白名单'][$a]);
			}
		}
		var_dump($set['白名单']);
		$set['白名单'] = $this->unArray($set['白名单']);
		$this->set->set('设置',$set);
		$this->set->save();
		return True;
	}

	public function unArray($Array)
	{
		$New_Array = [];
		foreach ($Array as $value)
		{
			$New_Array[] = $value;
		}
		return $New_Array;
	}
	
	public function getinfo($name)//获取底部全部
	{
		if(!isset($this->Main->info[$name])) return Null;
		return $this->Main->info[$name];
	}

	public function getMaxMagic($name)//获取生命上限
	{
		if($this->getinfo($name) === Null) return Null;
		return getinfo($name)['生命上限'];
	}

	public function getMaxHealth($name)//获取魔法上限
	{
		if($this->getinfo($name) === Null) return Null;
		return getinfo($name)['魔法上限'];
	}

	public function getHealth($name)//获取魔法
	{
		if($this->getinfo($name) === Null) return Null;
		return getinfo($name)['魔法'];
	}

	public function getContent($name)//获取物攻
	{
		if($this->getinfo($name) === Null) return Null;
		return getinfo($name)['物攻'];
	}

	public function getAuthors($name)//获取物防
	{
		if($this->getinfo($name) === Null) return Null;
		return getinfo($name)['物防'];
	}

	public function getCrit($name)//获取暴击
	{
		if($this->getinfo($name) === Null) return Null;
		return getinfo($name)['暴击'];
	}

	public function getPhysical($name)//获取抗暴
	{
		if($this->getinfo($name) === Null) return Null;
		return getinfo($name)['抗暴'];
	}

	public function getMagainst($name)//获取魔防
	{
		if($this->getinfo($name) === Null) return Null;
		return getinfo($name)['魔防'];
	}

	public function getMattack($name)//获取魔攻
	{
		if($this->getinfo($name) === Null) return Null;
		return getinfo($name)['魔攻'];
	}
}
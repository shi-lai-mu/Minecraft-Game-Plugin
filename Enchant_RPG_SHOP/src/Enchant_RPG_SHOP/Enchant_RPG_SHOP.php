<?php
namespace Enchant_RPG_SHOP;

	use pocketmine\Server;
	use pocketmine\Player;
	use pocketmine\event\Listener;
	use pocketmine\plugin\PluginBase;
	use pocketmine\event\player\PlayerInteractEvent;
	use pocketmine\event\player\PlayerJoinEvent;
	use pocketmine\event\player\PlayerQuitEvent;
	use pocketmine\event\player\PlayerItemConsumeEvent;
	use pocketmine\event\player\PlayerChatEvent;
	use pocketmine\event\block\SignChangeEvent;
	use pocketmine\event\block\BlockBreakEvent;
	use pocketmine\event\entity\EntityDamageByEntityEvent;
	use pocketmine\event\entity\EntityDamageEvent;
	use pocketmine\event\entity\ItemSpawnEvent;
	use pocketmine\event\entity\EntityShootBowEvent;
	use pocketmine\event\entity\ProjectileHitEvent;
	use pocketmine\event\player\PlayerDropItemEvent;
	use pocketmine\tile\Sign;
	use pocketmine\item\Item;
	use pocketmine\item\enchantment\Enchantment;
	use pocketmine\utils\Config;
	use pocketmine\entity\Effect;
	use pocketmine\entity\Entity;
	use pocketmine\command\Command;
	use pocketmine\command\CommandSender;
	use pocketmine\nbt\NBT;
	use pocketmine\nbt\tag\CompoundTag;
	use pocketmine\nbt\tag\StringTag;
	use pocketmine\nbt\tag\NamedTag;
	use pocketmine\math\Vector3;
	use pocketmine\inventory\Inventory;
	use pocketmine\scheduler\CallbackTask;
	use pocketmine\network\protocol\ExplodePacket;
	use pocketmine\level\Level;
	use pocketmine\level\particle\HeartParticle;

	use Enchant_RPG_SHOP\api;
	use Enchant_RPG_SHOP\Enchant\TXT;
	use onebone\economyapi\EconomyAPI;
	use Enchant_RPG_SHOP\PRO\Protect;
	use Enchant_RPG_SHOP\PRO\Fire_protection;
	use Enchant_RPG_SHOP\PRO\Feather_Falling;
	use Enchant_RPG_SHOP\PRO\Blast_Protection;
	use Enchant_RPG_SHOP\PRO\Projectile_protection;
	use Enchant_RPG_SHOP\PRO\Thorns;
	use Enchant_RPG_SHOP\PRO\Respiration;
	use Enchant_RPG_SHOP\PRO\Depth_Strider;
	use Enchant_RPG_SHOP\PRO\Aqua_Affinity;
	use Enchant_RPG_SHOP\PRO\Sharpness;
	use Enchant_RPG_SHOP\PRO\Smite;
	use Enchant_RPG_SHOP\PRO\Bane_Arthropods;
	use Enchant_RPG_SHOP\PRO\Knock_Back;
	use Enchant_RPG_SHOP\PRO\Fire_Aspect;
	use Enchant_RPG_SHOP\PRO\Looting;
	use Enchant_RPG_SHOP\PRO\Efficiency;
	use Enchant_RPG_SHOP\PRO\Silk_Touch;
	use Enchant_RPG_SHOP\PRO\Unbreaking;
	use Enchant_RPG_SHOP\PRO\Fortune;
	use Enchant_RPG_SHOP\PRO\Power;
	use Enchant_RPG_SHOP\PRO\Punch;
	use Enchant_RPG_SHOP\PRO\Flame;
	use Enchant_RPG_SHOP\PRO\Infinity;
	use Enchant_RPG_SHOP\PRO\Care_sea;
	use Enchant_RPG_SHOP\PRO\Bait;
	use \ZXDAConnector\Main as ZXDAConnector;

class Enchant_RPG_SHOP extends PluginBase implements Listener
{
	public $info = Array();
	private $click = Array();
	private $SHOP_SET = [];
	private $gj = Array();
	private $Tip = 0;
	private $CD = [];//CD冷却
	private $combo = [];//连击
	private static $instance;
 
	public static function getInstance()
	{
		return self::$instance;
	}
	
	public function onLoad()
	{
		ZXDA::init(584,$this);
		ZXDA::requestCheck();
	}
	
	public function onEnable()
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		self::$instance = $this;
		$this->getLogger()->info('§e ------------------------------------- ');
		$this->getLogger()->info('§6授权成功!§3感谢支持本插件...');
		$this->getLogger()->info('§2Enchant-RPG-SHOP §d'.$this->getDescription()->getVersion().'§3 加载中... ');
		$this->ZXDA_load();
		$config = new TXT($this);
		$dir = $this->getDataFolder();
		@mkdir($dir.'ID/');
		$this->Enchant = new Config($dir . 'Enchant_NBT.yml',Config::YAML,[]);//NBT
		$this->set = new Config($dir . 'Enchant_Config.yml',Config::YAML,[]);//配置
		$this->b = new Config($dir . 'Config.yml',Config::YAML,[]);//木牌
		$this->Money = new Config($dir . 'Money.json',Config::YAML,[]);//附魔券
		$this->item = new Config($dir . 'item.yml',Config::YAML,[]);//物品名称
		$this->TS = new Config($dir . 'Tip.yml',Config::YAML,[]);//提示库
		$this->DATA = new Config($dir . 'Enchant.yml',Config::YAML,[]);//附魔数据
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,'timer']),20);
		$this->getLogger()->info('§e ------------------------------------- ');
		for($a=0;$a<=24;$a++)
		{
			var_dump($a);
			$dir = $this->getDataFolder();
			$this->DATA = new Config($dir . 'ID/'.$a.'.yml',Config::YAML,[]);//附魔数据
			$f = $this->Get_Enchant_Class($a);
			$arr = [
				'名字' => $f->name,
				'ID' => $f->id,
				'物品' => $f->getItemId(),
				'攻击' => $f->getDamage(1),
				'防御' => $f->getdefense(1),
				'分数' => $f->getScores(1),
				'等级' => $f->getEnchantLevel(),
				'详细' => $f->info,
				'pvp生效' => $f->pvp,
				'独立' => $f->independent,
				'销毁' => $f->destroyed,
				'可丢弃' => $f->discarded,
				'设名字' => $f->setname,
				'为食物' => $f->Food,
				'为被动' => $f->passive,
				'为护甲' => $f->protect,
				'为射击' => $f->shooting,
				'为手持' => $f->hand,
				'模式' => $f->gamemode,
				'能量' => $f->damage,
				'燃烧' => $f->burning,
				'冷却' => $f->CD,
				'连击' => $f->combo,
				'伤害范围' => $f->scope,
				'药水范围' => $f->Effect,
				'攻击吸血' => $f->vampire,
				'连击伤害' => $f->superposition,
				'反弹伤害' => $f->rebound_D,
				'冷却提示' => $f->CDtip,
				'吸血伤害' => $f->vampire_d,
				'覆盖AI' => $f->ai,
				'连击提示' => $f->Tip,
				'眩晕' => $f->swim,
				'增强' => $f->note
			];
			$this->DATA->setAll($arr);
			$this->DATA->save();
		}
	}

	public function onDisable()
	{
		$this->getLogger()->info('§a 正在保存关键的RPG数据...');
	}
	
	public function timer()
	{
		foreach($this->getServer()->getOnlinePlayers() as $player)
		{
			$name = $player->getName();
			$level = $player->getLevel();
			$set = $this->set->get('设置');
			if(isset($this->info[$name]))
			{
				$name_info = $this->info[$name];
				if($set['底部'] == '开')
				{
					$tips = "§1■■§2■■§3■■§5■■§6■■§8■■§a■■§b■■§c■■§e■■§4■■";
					if($set['底部动态框'] == '开')
					{
						switch ($this->Tip)
						{
							case 1:
								$tips = "§4■■§1■■§2■■§3■■§5■■§6■■§8■■§a■■§b■■§c■■§e■■";
								break;
							case 2:
								$tips = "§e■■§4■■§1■■§2■■§3■■§5■■§6■■§8■■§a■■§b■■§c■■";
								break;
							case 3:
								$tips = "§c■■§e■■§4■■§1■■§2■■§3■■§5■■§6■■§8■■§a■■§b■■";
								break;
							case 4:
								$tips = "§b■■§c■■§e■■§4■■§1■■§2■■§3■■§5■■§6■■§8■■§a■■";
								break;
							case 5:
								$tips = "§a■■§b■■§c■■§e■■§4■■§1■■§2■■§3■■§5■■§6■■§8■■";
								break;
							case 6:
								$tips = "§8■■§a■■§b■■§c■■§e■■§4■■§1■■§2■■§3■■§5■■§6■■";
								break;
							case 7:
								$tips = "§6■■§8■■§a■■§b■■§c■■§e■■§4■■§1■■§2■■§3■■§5■■";
								break;
							case 8:
								$tips = "§5■■§6■■§8■■§a■■§b■■§c■■§e■■§4■■§1■■§2■■§3■■";
								break;
							case 9:
								$tips = "§3■■§5■■§6■■§8■■§a■■§b■■§c■■§e■■§4■■§1■■§2■■";
								break;
							case 10:
								$tips = "§2■■§3■■§5■■§6■■§8■■§a■■§b■■§c■■§e■■§4■■§1■■";
								$this->Tip = -1;
								break;
						}
						$this->Tip += 1;
					}
					$Tip_x = $set['底部显示'];
					$Tip_c = Array();
					$Tip_c[] = Array('{生命上限}' => $name_info['生命上限']);//生命上限
					$Tip_c[] = Array('{生命}' => $player->getHealth());//生命
					$Tip_c[] = Array('{物攻}' => $name_info['物攻']);//物攻
					$Tip_c[] = Array('{物防}' => $name_info['物防']);//物防
					$Tip_c[] = Array('{暴击}' => $name_info['暴击']);//暴击
					$Tip_c[] = Array('{抗暴}' => $name_info['抗暴']);//抗暴
					$Tip_c[] = Array('{动态线}' => $tips);//动态线
					for($a = 0; $a < count($Tip_c); $a ++)
					{
						foreach($Tip_c[$a] as $k => $v)
						{
							if(strstr($Tip_x,$k))
							{
								$Tip_x = strtr($Tip_x,$Tip_c[$a]);
							}
						}
					}
					if($set['在指定世界开启底部'] == '开')
					{
						if(in_array($player->level->getName(),$this->set->get('设置')['底部世界']))
						{
							$set['底部方式'] == 'Tip' ? $player->sendTip("$Tip_x") : $player->sendPopup("$Tip_x");
						}
					}
					else
					{
						$set['底部方式'] == 'Tip' ? $player->sendTip("$Tip_x") : $player->sendPopup("$Tip_x");
					}
				}
				$Enchant = $this->getAllEnction($player);
				$Health = 0;//生命
				$Content_Attack = 0;//物攻
				$Authors = 0;//物防
				$Crit = 0;//暴击
				$Physical = 0;//抗暴
				foreach($Enchant as $a => $b)
				{
					if(is_array($b))
					{
						if($a == '头附魔' || $a == '胸附魔' || $a == '裤附魔' || $a == '鞋附魔')
						{
							foreach($b as $c => $d)
							{
								if(isset($b[1]))
								{
									if($player->isOnFire())
									{
										$Time = $player->fireTicks - ($this->get_Enchant_Class(1)->CODE($b[1]) * 20);
										if($Time < 20) $Time = 10;
										$player->fireTicks = $Time;
									}
								}
								if(isset($d[32]) && isset($d['镶嵌']))
								{
									if(isset($d['镶嵌'][35])) $Health += $d['镶嵌'][35] * 0.5;
									if(isset($d['镶嵌'][33])) $Content_Attack += $d['镶嵌'][33] * 0.6;
									if(isset($d['镶嵌'][34])) $Authors += $d['镶嵌'][34] * 0.5;
									if(isset($d['镶嵌'][36])) $Crit += $d['镶嵌'][36] * 1;
									if(isset($d['镶嵌'][39])) $Physical += $d['镶嵌'][39] * 0.8;
								}
							}
						}
					}
				}
				$lv = $this->getLV($player);
				if($set['等级上限'] < $lv) $this->setLV($player,$set['等级上限']);
				if($set['等级影响属性'] == '关') $lv = 1;
				$Health += $set['每级加血量上限'] * $lv;
				$Content_Attack += $set['每级加物攻'] * $lv;
				$this->info[$name]['生命上限'] = $player->getMaxHealth() + $Health;
				$this->info[$name]['物攻'] = round((1 + ($lv / 5)) + $Content_Attack + 0.1,2);
				$this->info[$name]['物防'] = round(($lv / 5) + $Authors + 0.1,2);
				$this->info[$name]['暴击'] = round(($lv / 5) + $Crit + 0.4,2);
				$this->info[$name]['抗暴'] = round(($lv / 6) + $Physical + 0.1,2);
				if($set['只开启血量和攻击属性'] == '开')
				{
					$this->info[$name]['物防'] = 0;
					$this->info[$name]['暴击'] = 0;
					$this->info[$name]['抗暴'] = 0;
				}
				if($this->info[$name]['生命上限'] > $this->set->get('设置')['最大可扩展血量上限']) $this->info[$name]['生命上限'] = 100;
				if($player->getMaxHealth() != $this->info[$name]['生命上限']) $player->setMaxHealth($this->info[$name]['生命上限']);
				if($player->getHealth() > $this->info[$name]['生命上限']) $player->setHealth($this->info[$name]['生命上限']);
			}
		}
	}

	public function JoinEvent(PlayerJoinEvent $event)
	{
		$player = $event->getPlayer();
		$name = $player->getName();
		if(!isset($this->info[$name]))
		{
			$txt = Array(
				'生命上限' => $player->getMaxHealth(),
				'物攻' => '加载中...',
				'物防' => '加载中...',
				'暴击' => '加载中...',
				'抗暴' => '加载中...',
				'Note' => Array()
			);
			$this->info[$name] = $txt;
		}
		if(!$this->Money->exists($name))
		{
			$this->Money->set($name,0);
			$this->Money->save();
		}
		$this->updateInt($player);
	}

	public function shop_code($player,$enchant,$puy = True)
	{
		######################################
		$name = $player->getName();
		$Hand = $player->getInventory()->getItemInHand();
		$Money = EconomyAPI::getInstance()->myMoney($name);
		$lv = $player->getXpLevel();
		$enchant_money = $this->Money->get($name);
		$ID = $Hand->getID();
		$Enchant = [];
		$ai = [];
		######################################
		$this->item = new Config($this->getDataFolder() . 'item.yml',Config::YAML,[]);
		$this->item->exists($ID) ? $item_name = $this->item->get($ID) : $item_name = '未知';
		$new = $this->get_Enchant_Class($enchant['ID'],$enchant['LV']);
		$nbt_id = -1;
		if(isset($Hand->getNamedTag()['display']['strings']))
		{
			$nbt_id = $Hand->getNamedTag()['display']['strings'];
			if($this->Enchant->exists($nbt_id))
			{
				$texts = $this->Enchant->get($nbt_id);
				foreach($texts as $key => $value)
				{
					$Enchant[$key] = $value;
				}
			}
		}
		if(!empty($Hand->getEnchantments()))
		{
			foreach($Hand->getEnchantments() as $enchantment)
			{
				$Enchant[$enchantment->getId()] = $enchantment->getLevel();
				$ai[$enchantment->getId()] = $enchantment->getLevel();
			}
		}
		switch ($enchant['类'])
		{
			case '回收':
				foreach($Enchant as $id => $lv)
				{
					if($id == $enchant['ID'])
					{
						$news = $this->get_Enchant_Class($id);
						if($lv < $enchant['LV']) return $player->sendMessage('§4-> §c'.$item_name.'所附魔的'.$news->name.'等级过低!');
						$player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 1));
						$player->sendMessage('§4-> §c已回收手中的'.$item_name.'!');
						$this->setMoneys($player,$this->getMoneyName($enchant['货币']),$enchant['价格'],'+');
						return True;
					}
				}
				$player->sendMessage('§4-> §c'.$item_name.'未附魔'.$this->get_Enchant_Class($enchant['ID'])->name.'!');
				return false;
			break;
			case '附魔':
				foreach($Enchant as $id => $lv)
				{
					$news = $this->get_Enchant_Class($id);
					if($news !== NULL and $news->independent == True and $id != $enchant['ID']) return $player->sendMessage('§4-> §c'.$item_name.'已附魔独立属性!');
				}
				if(isset($Enchant[$enchant['ID']]) and $Enchant[$enchant['ID']] >= $enchant['LV'])
				{
					return $player->sendMessage('§4-> §c'.$item_name.'已附魔高于'.$enchant['LV'].'级的'.$new->name);
				}
				$news = $this->get_Enchant_Class($enchant['ID']);
				if(!in_array($ID,$new->getItemId()))
				{
					return $player->sendMessage('§4-> §c'.$item_name.'不支持'.$enchant['类'].$new->name);
				}
				$puy = $this->setMoneys($player,$this->getMoneyName($enchant['货币']),$enchant['价格'],'-');
				if(mt_rand(1,100) > $enchant['几率'])
				{
					return $player->sendMessage('§4-> §c'.$item_name.'附魔'.$new->name.'失败!');
				}
				if($puy !== True) return $player->sendMessage('§4-> §c'.$puy);
				if($nbt_id != -1)
				{
					$Enchant[$enchant['ID']] = $enchant['LV'];
					$this->Enchant->set($nbt_id,$Enchant);
					$this->Enchant->save();
				}
				else
				{
					$nbt_id = $this->Enchant->get('Enchant') + 1;
					$Enchant[$enchant['ID']] = $enchant['LV'];
					$Enchant['Name'] = $item_name;
					if($new->damage > 0) $Enchant['Damage'] = $new->damage;
					$this->Enchant->set('Enchant',$nbt_id);
					$this->Enchant->set($nbt_id,$Enchant);
					$this->Enchant->save();
				}
				$nbt = new CompoundTag("", [
					"display" => new CompoundTag("display", [
						"Name" => new StringTag("Name",'初始化失败![#100]'),
						"strings" => new StringTag("strings",$nbt_id)
					])
				]);
				$Hand->setNamedTag($nbt);
				foreach($Enchant as $k => $v)
				{
					$news = $this->get_Enchant_Class($k,$v);
					if($news !== Null)
					{
						if(!$news->ai)
						{
							$enchantment = Enchantment::getEnchantment($k);
							$enchantment->setLevel($v);
							$Hand->addEnchantment($enchantment);
						}
					}
				}
				$enchantment = Enchantment::getEnchantment(-1);
				$enchantment->setLevel(1);
				$Hand->addEnchantment($enchantment);
				$player->getInventory()->setItemInHand($Hand);
				$this->updateInt($player);
				$player->sendMessage('§4-> §c'.$item_name.'已成功附魔'.$enchant['LV'].'级的'.$new->name);
				break;
		}
	}
	
	public function PlayerInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		//var_dump($event->getFace());
		$name = $player->getName();
		$level = $player->getLevel()->getFolderName();
		$block = $event->getBlock();
		$ItemInHand = $player->getInventory()->getItemInHand();
		$this->set = new Config($this->getDataFolder().'Enchant_Config.yml',Config::YAML,[]);//配置
		$ID = $block->getID();
		if($ID == 323 || $ID == 63 || $ID == 68)
		{
			$sign = $event->getPlayer()->getLevel()->getTile($block);
			if(!$sign instanceof Sign) return;
			$X = $block->x;
			$Y = $block->y;
			$Z = $block->z;
			$XYZ = $X.':'.$Y.':'.$Z;
			$this->Sign_Conten($XYZ,True);
			if(isset($this->SHOP_SET[$name])) return $player->sendMessage('§b＊ '.$this->AddShop($XYZ,$this->SHOP_SET[$name],$name));
			if(!$this->b->exists($XYZ)) return;
			$shop = $this->b->get($XYZ);
			if(!isset($shop['类'])) return;
			$event->setCancelled();
			$this->shop_code($player,$shop);
		}
	}

	function AddShop(String $XYZ , Array $Sign_Info , String $Name)
	{
		if(!is_array($Sign_Info)) return '错误的变量[Sign_Info]';
		if($this->b->exists($XYZ)) return '已存在的商店!';
		if($Sign_Info[1] == '附魔')
		{
			if(!isset($Sign_Info[7])) return '错误的数组数量';
			$this->b->set($XYZ , [
				'类' => $Sign_Info[1],
				'ID' => $Sign_Info[2],
				'LV' => $Sign_Info[3],
				'货币' => $Sign_Info[4],
				'价格' => $Sign_Info[5],
				'耐久' => $Sign_Info[6],
				'几率' => $Sign_Info[7],
				'世界' => $Sign_Info[8]
			]);
			unset($this->SHOP_SET[$Name]);
			$this->Sign_Conten($XYZ,True);
			$this->b->save();
			return '已添加一个商店!';
		}
	}

	function Sign_Conten(String $SHOP_XYZ,$SET = False)
	{
		if(!$this->b->exists($SHOP_XYZ)) return;
		$SHOP_INFO = $this->b->get($SHOP_XYZ);
		$set = $this->set->get('设置')[$SHOP_INFO['类']];
		$Enchant = $this->Get_Enchant_Class($SHOP_INFO['ID']);
		if($Enchant === Null) return;
		$String = [
			'{魔}' => $Enchant->name,
			'{级}' => $SHOP_INFO['LV'],
			'{货}' => $this->getMoneyName($SHOP_INFO['货币']),
			'{需}' => $SHOP_INFO['价格'],
			'{耐}' => $SHOP_INFO['耐久'],
			'{率}' => $SHOP_INFO['几率'],
			'{功}' => $Enchant->info
		];
		foreach($String as $key => $value)
		{
			foreach($set as $soc => $txt)
			{
				$set[$soc] = str_replace($key,$value,$txt);
			}
		}
		if($SET)
		{
			$xyz = explode(':',$SHOP_XYZ);
			$level = $this->getServer()->getLevelByName($SHOP_INFO['世界']);
			if(!$level instanceof Level) return '不存在的世界'.$SHOP_INFO['世界'];
			$Sign = $level->getTile(new Vector3($xyz[0],$xyz[1],$xyz[2]));
			if(!$Sign instanceof Sign) return '失败的木牌'.$SHOP_XYZ;
			$Sign->setText($set[0],$set[1],$set[2],$set[3]);
		}
		return $set;
	}

	public function ItemConsumeEvent(PlayerItemConsumeEvent $event)
	{
		$item = $event->getItem();
		$id = $item->getId();
		$player = $event->getPlayer();
		$name = $player->getName();
		$Enchant = Array();
		if(!empty($item->getEnchantments()))
		{
			foreach($item->getEnchantments() as $enchantment)
			{
				$Enchant[$enchantment->getId()] = $enchantment->getLevel();
			}
		}
		if(count($Enchant) > 0)
		{
			$enchant_names = "";
			if(isset($item->getNamedTag()['display']['strings']))
			{
				$nbt_id = $item->getNamedTag()['display']['strings'];
				$texts = $this->Enchant->get($nbt_id);
				if(isset($texts[30]))
				{
					if($this->info[$name]['魔法'] == $this->info[$name]['魔法上限'] * (@$player->getXpLevel() / 2))
					{
						$player->sendMessage("§b＊§6魔法苹果§b＊ §e你的魔法值处于巅峰,无需回复!");
						$event->setCancelled();
					}
					else
					{
						$this->info[$name]['魔法'] += ($texts[30] * 10);
						if($this->info[$name]['魔法'] > $this->info[$name]['魔法上限'] * (@$player->getXpLevel() / 2))
						{
							$this->info[$name]['魔法'] = $this->info[$name]['魔法上限'] * (@$player->getXpLevel() / 2);
						}
					}
				}
				if(isset($texts[31]))
				{
					$timess = ($texts[31] * 3) / ($texts[31] * 2);
					$this->info[$name]['Note']['治疗汤'] = Array('时间' => (Time() + ($texts[31] * 2)),'已回' => 0,'间隔' => 0,'等级' => $texts[31]);
					$player->sendMessage("§b＊§6治疗汤§b＊ §e在§5".$this->untime($texts[31] * 2,true)."§e内你的生命将持续恢复,最高§5$texts[31]点§e!");
					$player->sendMessage('§b※§6RPG§b※ 你得到一个新的属性"恢复血量 1d/2s"!');
				}
			}
		}
	}
	
	public function ItemSpawn(ItemSpawnEvent $event)
	{
		$entity = $event->getEntity();
		$item = $entity->getItem();
		if($item->getNamedTag()['display']['Name'])
		{
			$entity->setNameTag($item->getNamedTag()['display']['Name']);
			$entity->setNameTagVisible(true);
			$entity->setNameTagAlwaysVisible(true);
		}
	}
  

	public function EntityShootBow(EntityShootBowEvent $event)
	{
		$Player = $event->getEntity();
		if(!$event->isCancelled())
		{
			if($Player instanceof Player)
			{
				$ItemInHand = $event->getEntity()->getInventory()->getItemInHand();
				$Id = $ItemInHand->getId();
				$Enchant = [];
				if(!empty($ItemInHand->getEnchantments()))
				{
					foreach($ItemInHand->getEnchantments() as $enchantment)
					{
						$Enchant[$enchantment->getId()] = $enchantment->getLevel();
					}
				}
				if(isset($ItemInHand->getNamedTag()['display']['strings']))
				{
					$nbt_id = $ItemInHand->getNamedTag()['display']['strings'];
					if($this->Enchant->exists($nbt_id))
					{
						$texts = $this->Enchant->get($nbt_id);
						foreach($texts as $key => $value)
						{
							$Enchant[$key] = $value;
						}
					}
				}
				if($ItemInHand->getId() == 261)
				{
					$this->gj['弓箭']['射出'] = array($event->getEntity()->getName());
				}
				if(isset($Enchant[21]))
				{
					$event->getProjectile()->setOnFire(100);
					$this->gj['弓箭']['火焰附加'] = array($event->getEntity()->getName() => $Enchant[21]);
				}
				if(isset($Enchant[19]))
				{
					$this->gj['弓箭']['力量'] = array($event->getEntity()->getName() => $Enchant[19]);
				}
				if(isset($Enchant[22]))
				{
					if($this->set->get('设置')['无限在未射中目标情况下也生效'] == '开')
					{
						$event->getEntity()->getInventory()->addItem(new Item(262,0,1));
					}
					else
					{
						$this->gj['弓箭']['无限'] = array($event->getEntity()->getName() => $Enchant[22]);
					}
				}
			}
		}
	}

	public function EntityDamageEvent(EntityDamageEvent $event)
	{
		if($event instanceof EntityDamageByEntityEvent)
		{
			if(!$event->isCancelled())
			{
				$Damager = $event->getDamager();
				$Entity = $event->getEntity();
				$DamagerX = $Damager->x;
				$DamagerY = $Damager->y;
				$DamagerZ = $Damager->z;
				$EntityX = $Entity->x;
				$EntityY = $Entity->y;
				$EntityZ = $Entity->z;
				$Note = Array();
				$set = $this->set->get('设置');
				$Enchant = Array();
				$Damage_bf = $event->getDamage() / 100;
				$Damager_Enchant = [];
				$Entity_Enchant = [];
				$D_Gamemode = 0;
				$E_Gamemode = 0;
				$D_id = 0;
				$E_id = 0;
				$D_Player = False;
				$E_Player = False;
				$Damager_name = Null;
				$Entity_name = Null;
				$Damager_info = Null;
				$Entity_info = Null;
				$PVP = False;
				$this->log('§c-------------------------------'."\n来自实体 ".$Damager->getName()." 的攻击 初始值".$event->getDamage());
				if($Damager instanceof Player)
				{
					$Damager_name = $Damager->getName();
					$Damager_info = $this->info[$Damager_name];
					$Damager_Hand = $Damager->getInventory()->getItemInHand();
					$event->setDamage($event->getDamage() + $Damager_info['物攻']);
					$Damager_Enchant = $this->get_Enchant($Damager_Hand);
					$D_Player = True;
					$D_Gamemode = $Damager->getGamemode();
					if(isset($Damager_Hand->getNamedTag()['display']['strings']))
					{
						$D_id = $Damager_Hand->getNamedTag()['display']['strings'];
					}
				}
				if($Entity instanceof Player)
				{
					$Entity_name = $Entity->getName();
					$Entity_info = $this->info[$Entity_name];
					$Entity_Hand = $Entity->getInventory()->getItemInHand();
					$Entity_Enchant = $this->get_Enchant($Entity_Hand);
					$E_Gamemode = $Entity->getGamemode();
					$E_Player = True;
					if(isset($Entity_Hand->getNamedTag()['display']['strings']))
					{
						$E_id = $Entity_Hand->getNamedTag()['display']['strings'];
					}
				}
				if($D_Player === True && $E_Player === True) $PVP = True;
				if(count($Damager_Enchant) > 0)
				{
					$this->log('-> 攻击实体手持物发现 '.count($Damager_Enchant).' 个附魔,正在处理中...');
					foreach($Damager_Enchant as $id => $lv)
					{
						$new = $this->get_Enchant_Class($id,$lv);
						if($new === Null) continue;
						$this->item->exists($Damager_Hand->getID()) ? $item_name = $this->item->get($Damager_Hand->getID()) : $item_name = '未知';
						$this->log('-> 正在处理 '.$item_name .' 中的 '.$new->name.' 附魔...');
						if($PVP === True &&  $new->pvp === True || $PVP === False &&  $new->pvp === False || $new->pvp === NULL)
						{
							if($new->gamemode === -1 || $D_Gamemode === $new->gamemode && $new->hand === True)
							{
								$Smite = ['Zombie','PigZombie','ZombieVillager'];
								if(in_Array($Entity->getName(),$Smite) && $new->id == 10)
								{
									$event->setDamage($event->getDamage() + $new->CODE($lv,True));
									$this->log('判 亡灵类生物 攻击增加 '.$new->CODE($lv,True));
								}
								$Bane = ['Skeleton','Spider'];
								if(in_Array($Entity->getName(),$Bane) && $new->id == 11)
								{
									$event->setDamage($event->getDamage() + $new->CODE($lv,True));
									$this->log('判 节肢类生物 攻击增加 '.$new->CODE($lv,True));
								}
								if($new->CD - ($lv * 0.1) > 0)
								{
									if(isset($this->CD[$Damager_name]))
									{
										if(!isset($this->CD[$Damager_name][$D_id]))
										{
											$this->CD[$Damager_name][$D_id] = Time() + $new->CD - ($lv * 0.1);
										}
										else
										{
											if($this->CD[$Damager_name][$D_id] > Time())
											{
												if($new->CDtip == True) $Damager->sendMessage('§a[RPG] §c此装备的属性正在冷却...剩余['.$this->untime($this->CD[$Damager_name][$D_id]).'S]');
													$event->setCancelled();
											}
											else
											{
												$this->CD[$Damager_name][$D_id] = Time() + $new->CD - ($lv * 0.1);
											}
										}
									}
									else
									{
										$this->CD[$Damager_name][$D_id] = Time() + $new->CD - ($lv * 0.1);
									}
								}
								if($new->combo > 0)
								{
									if(isset($this->combo[$Damager_name]))
									{
										if($this->combo[$Damager_name]['Time'] < Time())
										{
											$this->combo[$Damager_name]['Time'] = Time() + $new->combo + ($lv * 0.01);
											$this->combo[$Damager_name]['combo'] = 1;
										}
										else
										{
											$this->combo[$Damager_name]['combo'] += 1;
											$this->combo[$Damager_name]['Time'] = Time() + $new->combo + ($lv * 0.01);
											$Damager->sendMessage('§e'.$this->combo[$Damager_name]['combo'].'连击!');
											if($new->superposition > 0)
											{
												$event->setDamage($event->getDamage() + ($this->combo[$Damager_name]['combo'] * $new->superposition));
											}
										}
									}
									else
									{
										$this->combo[$Damager_name]['Time'] = Time() + $new->combo + ($lv * 0.01);
										$this->combo[$Damager_name]['combo'] = 1;
									}
								}
								if($new->burning > 0)
								{
									$Entity->setOnFire($new->burning * $lv);
								}
								if($new->destroyed == True)
								{
									$Damager->getInventory()->setItemInHand(new Item(0,0,0));
									$Damager->sendMessage('§a[RPG] §c服务器回收了此物品!');
								}
								if($new->vampire > 0)
								{
									if($new->vampire + ($lv * 0.1) > 100) $new->vampire = 100;
									$Damager->setHealth($Damager->getHealth() + $new->vampire + ($lv * 0.1) * $Damage_bf);
									if($new->vampire_d  === True)
									{
										$ev = new EntityDamageEvent($sw, EntityDamageEvent::CAUSE_ENTITY_ATTACK, ($new->vampire * $Damage_bf) * 25);
										$sw->attack($ev->getFinalDamage(), $ev);
									}
								}
								$event->setDamage($event->getDamage() + $new->CODE($lv));
								if($new->scope > 0)
								{
									foreach($Damager->getLevel()->getEntities() as $sw)
									{
										if($sw instanceof Player)
										{
											if($new->pvp !== False)
											{
												if($sw->distance(new Vector3($Damager->x,$Damager->y,$Damager->z)) <= $new->scope + ($lv * 0.1))
												{
													$ev = new EntityDamageEvent($sw, EntityDamageEvent::CAUSE_ENTITY_ATTACK, ($new->vampire * $Damage_bf) * 25);
													$sw->attack($ev->getFinalDamage(), $ev);
												}
											}
										}
										else
										{
											if($sw->distance(new Vector3($Damager->x,$Damager->y,$Damager->z)) <= $new->scope + ($lv * 0.1))
											{
												$ev = new EntityDamageEvent($sw, EntityDamageEvent::CAUSE_ENTITY_ATTACK, ($new->vampire * $Damage_bf) * 25);
												$sw->attack($ev->getFinalDamage(), $ev);
											}
										}
									}
								}
								if($new->Effect > 0)
								{
									foreach($Damager->getLevel()->getEntities() as $sw)
									{
										if($sw instanceof Player)
										{
											if($new->pvp !== False)
											{
												if($sw->distance(new Vector3($Damager->x,$Damager->y,$Damager->z)) <= $new->Effect + ($lv * 0.1))
												{
													$this->Effect($new->addEEffect(),$sw);
												}
											}
										}
										else
										{
											if($sw->distance(new Vector3($Damager->x,$Damager->y,$Damager->z)) <= $new->Effect)
											{
												$this->Effect($new->addEEffect(),$sw,$lv * 0.1);
											}
										}
									}
								}
								if($new->note > 0)
								{
									$new->NOTE();
								}
							}
						}
					}
				}
				$this->log('判 攻击为 '.$event->getDamage());
				if($Entity instanceof Player)
				{
					$Enchant_Entity = $this->getEnction($Entity);
					$protect = 0;
					if(count($Enchant_Entity) > 0)
					{
						$this->log('-> '.$Entity->getName().' 实体装备栏内发现 '.count($Damager_Enchant).' 个附魔,正在处理中...');
						foreach($Enchant_Entity as $uid => $txt)
						{
							if(is_array($txt))
							{
								foreach($txt as $id => $lv)
								{
									$new = $this->get_Enchant_Class($id,$lv);
									if($new === Null) continue;
									if($PVP === True &&  $new->pvp === True || $PVP === False &&  $new->pvp === False || $new->pvp === NULL)
									{
										if($new->gamemode === -1 || $E_Gamemode === $new->gamemode)
										{
											if($new->passive == False && $new->protect == True)
											{
												$this->log('-> 正在处理 '.$new->name.' 附魔...');
												if(isset($this->gj['弓箭']) and $new->id == 4)
												{
													if(isset($this->gj['弓箭']['射出']) and isset($this->gj['弓箭']['射出'][$Enchant->getName()]))
													{
														$event->setDamage($event->getDamage() - $new->getdefense($lv));
													}
												}
												if($new->destroyed == True) $Entity->getInventory()->setItemInHand(new Item(0,0,0));
												$event->setDamage($event->getDamage() - $new->getdefense($lv));
												$this->log('判 防御[内置]为 '.$new->getdefense($lv));
												$protect += $new->getdefense($lv);
												if($new->note > 0) $new->NOTE();
												if($new->rebound_D > 0)
												{
													if($event->getDamage() < 0) $event->setDamage(0);
													$attack = ($event->getDamage() / 100) * ($lv * $new->rebound_D);
													$ev = new EntityDamageEvent($Damager, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $attack);
													$this->log('判 反弹 '.$attack.' 伤害 攻击为 '.$event->getDamage());
													$Damager->attack($ev->getFinalDamage(), $ev);
												}
												if($new->swim == True)
												{
													$Damager->addEffect(Effect::getEffect(9)->setAmplifier($lv / 5)->setDuration($lv)->setVisible(True));
												}
											}
										}
									}
								}
							}
						}
					}
					$Enchant_Entity = $this->getAllEnction($Entity);
					if(count($Enchant_Entity) > 0)
					{
						$this->log('-> '.$Entity->getName().' 实体背包内发现 '.count($Damager_Enchant).' 个附魔,正在处理中...');
						foreach($Enchant_Entity as $uid => $txt)
						{
							if(is_array($txt))
							{
								foreach($txt as $id => $lv)
								{
									$new = $this->get_Enchant_Class($id,$lv);
									if($new === Null) continue;
									if($PVP === True && $new->pvp === True || $PVP === False &&  $new->pvp === False)
									{
										if($new->gamemode === -1 || $E_Gamemode === $new->gamemode)
										{
											if($new->passive == True && $new->protect == False)
											{
												$this->log('-> 正在处理 '.$new->name.' 附魔...');
												if($new->destroyed == True)
												{
													$Entity->getInventory()->setItemInHand(new Item(0,0,0));
												}
												if($new->note > 0)
												{
													$new->NOTE();
												}
												if($new->rebound_D > 0)
												{
													$ev = new EntityDamageEvent($Damager, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $lv * $new->rebound_D);
													$Damager->attack($ev->getFinalDamage(), $ev);
												}
												if($new->swim == True)
												{
													$Damager->addEffect(Effect::getEffect(9)->setAmplifier($lv / 5)->setDuration($lv)->setVisible(True));
												}
											}
										}
									}
								}
							}
						}
					}
					$this->log('判 防御为 '.$protect);
				}
				if(class_exists('\PVE_Pro\\PVE_Pro',false))
				{
					if(\PVE_Pro\PVE_Pro::getInstance()->isRPGPointEntity($Entity))
					{
						$point = \PVE_Pro\PVE_Pro::getInstance()->getRPGPoint($Entity);
						$event->setDamage($event->getDamage() - \PVE_Pro\PVE_Pro::getInstance()->list[$point]['defense']);
						$this->log('判 PVE_PRO防御为 '.\PVE_Pro\PVE_Pro::getInstance()->list[$point]['defense']);
					}
				}
				if($event->getDamage() < 0) $event->setDamage(0);
				$this->log('经 插件处理后 攻击实际输出 '.$event->getDamage());
			}
		}
	}

	public function DropItemEvent(PlayerDropItemEvent $event)
	{
		$player = $event->getPlayer();
		$Item = $event->getItem();
		$Enchant = $this->get_Enchant($Item);
		if(count($Enchant) > 0)
		{
			foreach($Enchant as $id => $lv)
			{
				$new = $this->get_Enchant_Class($id);
				if($new === False) return true;
				if($new->discarded === False)
				{
					$player->sendMessage('§a[RPG] §c此物品包含禁止丢弃的属性!');
					$event->setCancelled();
				}
			}
		}
	}

	public function Effect($Effect,$entity,$lv = 0)
	{
		foreach ($Effect as $key => $value)
		{
			$entity->addEffect(Effect::getEffect($key)->setAmplifier($lv)->setDuration($value * 60)->setVisible(True));
		}
	}
	
	public function Get_Enchant_Class($id = 0,$lv = 1)
	{
		$new = Null;
		if($id == 0) $new = new Protect($lv);
		if($id == 1) $new = new Fire_protection($lv);
		if($id == 2) $new = new Feather_Falling($lv);
		if($id == 3) $new = new Blast_Protection($lv);
		if($id == 4) $new = new Projectile_protection($lv);
		if($id == 5) $new = new Thorns($lv);
		if($id == 6) $new = new Respiration($lv);
		if($id == 7) $new = new Depth_Strider($lv);
		if($id == 8) $new = new Aqua_Affinity($lv);
		if($id == 9) $new = new Sharpness($lv);
		if($id == 10) $new = new Smite($lv);
		if($id == 11) $new = new Bane_Arthropods($lv);
		if($id == 12) $new = new Knock_Back($lv);
		if($id == 13) $new = new Fire_Aspect($lv);
		if($id == 14) $new = new Looting($lv);
		if($id == 15) $new = new Efficiency($lv);
		if($id == 16) $new = new Silk_Touch($lv);
		if($id == 17) $new = new Unbreaking($lv);
		if($id == 18) $new = new Fortune($lv);
		if($id == 19) $new = new Power($lv);
		if($id == 20) $new = new Punch($lv);
		if($id == 21) $new = new Flame($lv);
		if($id == 22) $new = new Infinity($lv);
		if($id == 23) $new = new Care_sea($lv);
		if($id == 24) $new = new Bait($lv);
			
		return $new;
	}

	public function get_Enchant($item)
	{
		$Enchant = Array();
		if(!empty($item->getEnchantments()))
		{
			foreach($item->getEnchantments() as $enchantment)
			{
				if($enchantment->getId() !== -1) $Enchant[$enchantment->getId()] = $enchantment->getLevel();
			}
		}
		if(isset($item->getNamedTag()['display']['strings']))
		{
			$nbt_id = $item->getNamedTag()['display']['strings'];
			if($this->Enchant->exists($nbt_id))
			{
				$texts = $this->Enchant->get($nbt_id);
				foreach($texts as $key => $value)
				{
					$Enchant[$key] = $value;
				}
			}
		}
		return $Enchant;
	}

	public function onPlayerBreakBlock(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		if(!$event->isCancelled())
		{
			$block = $event->getBlock();
			$ItemInHand = $player->getInventory()->getItemInHand();
			$Enchant = $this->get_Enchant($ItemInHand);
			$nbt_id = -1;
			if(isset($ItemInHand->getNamedTag()['display']['strings']))
			{
				$nbt_id = $ItemInHand->getNamedTag()['display']['strings'];
			}
			if(count($Enchant) > 0)
			{
				$this->log('-> 破坏方块实体手持物发现 '.count($Enchant).' 个附魔,正在处理中...');
				foreach($Enchant as $id => $lv)
				{
					$new = $this->get_Enchant_Class($id,$lv);
					if($new === Null) continue;
					$this->item->exists($ItemInHand->getID()) ? $item_name = $this->item->get($ItemInHand->getID()) : $item_name = '未知';
					$this->log('-> 正在处理 '.$item_name .' 中的 '.$new->name.' 附魔...');
					if(in_array(17,$Enchant))
					{
						if(mt_rand(1,100) < $Enchant[17])
						{
							if($ItemInHand->getDamage() > 0)
							{
								if(mt_rand(1,100) < 50)
								{
									$ItemInHand->setDamage($ItemInHand->getDamage() - 1);
									$player->getInventory()->setItemInHand($ItemInHand);
								}
							}
						}
					}
					if(in_array(18,$Enchant_Id))
					{
						if(in_array(16,$Enchant_Id))
						{
							return;
						}
						$Block = $event->getBlock();
						$X = $Block->getX();
						$Y = $Block->getY();
						$Z = $Block->getZ();
						$Level = $Block->getLevel();
						$ID = $Block->getID();
						$new = $this->get_Enchant_Class(18);
						$IDS = $new->NOTE();
						if(!isset($IDS[$ID])) return;
						$numbers = $Enchant[18];
						$numbers = $numbers/2 - mt_rand(0,$numbers/2);
						$numbers < 0 ? $numbers = 0 : $Level->dropItem(new Vector3($X,$Y,$Z),new Item(explode(':',$IDS[$ID])[0],explode(':',$IDS[$ID])[1],$numbers));
					}
				}
			}
		}
		if($event->getBlock()->getID() == 323 || $event->getBlock()->getID() == 63 || $event->getBlock()->getID() == 68)
		{
			$sign = $player->getLevel()->getTile($event->getBlock());
			$sign = $sign->getText();
			$X = $event->getBlock()->getX();
			$Y = $event->getBlock()->getY();
			$Z = $event->getBlock()->getZ();
			if(!$this->b->exists($X . ':' . $Y . ':' . $Z))
			{
				return;
			}
			$sign = $this->b->get($X . ':' . $Y . ':' . $Z);
			if(isset($sign['一键修复']))
			{
				if(!$player->isOP())
					{
						$player->sendMessage('§4-> §c非管理员不能拆除附魔商店木牌!');
						$event->setCancelled();
						return;
					}
					$this->b->remove($X . ':' . $Y . ':' . $Z);
					$this->b->save();
					$player->sendMessage('§4-> §c已拆除§e 附魔商店!');
				}
			if(
			isset($sign['附魔商店']) Or
			isset($sign['强化商店']) Or
			isset($sign['回收商店']) Or
			isset($sign['修复商店']) Or
			isset($sign['出售商店'])
			)
			{
				if(!$player->isOP())
				{
					$player->sendMessage('§4-> §c非管理员不能拆除附魔商店木牌!');
					$event->setCancelled();
					return;
				}
				$this->b->remove($X . ':' . $Y . ':' . $Z);
				$this->b->save();
				$player->sendMessage('§4-> §c已拆除§e 附魔商店!');
			}
		}
	}

	public function getMoneyName($Money)
	{
		$Money = strtolower($Money);
		$txt = Array(
			'm' => '金币',
			'x' => '经验',
			'd' => '点券',
			'l' => '等级',
			'w' => '附魔券',
		);
		if(isset($txt[$Money])) return $txt[$Money];
		if(in_array($Money,$txt)) return $Money;
		return False;
	}
	
	public function updateInt($player)
 	{
		for($index = 0; $index < $player->getInventory()->getSize(); $index ++)
		{
			$ItemInHand = $player->getInventory()->getItem($index);
			if($ItemInHand->getID() != 0 and isset($ItemInHand->getNamedTag()['display']['strings']))
			{
				$nbt_id = $ItemInHand->getNamedTag()['display']['strings'];
				if(!$this->Enchant->exists($nbt_id)) continue;
				$texts = $this->Enchant->get($nbt_id);
				$ID = $ItemInHand->getID();
				$Enchant = $texts;
				$ai = [];
				$item_name = $this->item->exists($ID) ? $this->item->get($ID) : '未知';
				if($texts['Name'] !== $item_name) $item_name = $texts['Name'];
				if(!empty($ItemInHand->getEnchantments()))
				{
					foreach($ItemInHand->getEnchantments() as $enchantment)
					{
						$Enchant[$enchantment->getId()] = $enchantment->getLevel();
						$ai[$enchantment->getId()] = $enchantment->getLevel();
					}
				}
				$fs = 0;
				$text_d = "\n";
				if(count($Enchant) > 0)
				{
					$lv = [0,'Ⅰ','ⅠⅠ','ⅠⅠⅠ','ⅠⅤ','Ⅴ','ⅤⅠ','ⅤⅠⅠ','ⅤⅠⅠⅠ','ⅠⅩ','Ⅹ'];
					foreach($texts as $key => $value)
					{
						if(is_numeric($key))
						{
							$new = $this->get_Enchant_Class($key);
							if($new !== Null and $new->ai)
							{
								$fs += $new->getScores($value);
								if(isset($lv[$value])) $value = $lv[$value];
								$text_d .= "§7$new->name $value\n";
							}
						}
					}
				}
				$enchantment = Enchantment::getEnchantment(-1);
				$enchantment->setLevel(1);
				$max = count($Enchant) > 0 ? "§f§9+".$fs." 属性评价" : "";
				$text = "§b".$item_name." §4[§6 $nbt_id §4]".$text_d. $max;
				if($ItemInHand->getNamedTag()['display']['Name'] != $text)
				{
					$nbt = new CompoundTag("",[
						"display" => new CompoundTag("display",[
						"Name" => new StringTag("Name",$text),
						"strings" => new StringTag("strings",$nbt_id)
						]),
					]);
					$ItemInHand->setNamedTag($nbt);
					foreach($ai as $id => $lv)
					{
						$enchantment = Enchantment::getEnchantment($id);
						$enchantment->setLevel($lv);
						$ItemInHand->addEnchantment($enchantment);
					}
					$player->getInventory()->setItem($index,$ItemInHand);
				}
			}
		}			
	}
	
	public function ZXDA_load()
	{
		$data=ZXDA::getInfo();
		ZXDA::tokenCheck('MTMzNjg4NzkwMDgzMTI3NTA5NTY1OTQ4MjYzMjc1NTk5MjkyMDI0MTY3MDc3MzA3NTYxMTg2NjY0MjA1MzAxMjY1MjQzMDQ5MTQzNDk1Mjg2NzcwMjg0MjE2OTQ4MzIzMDY2MjE3NTQ0NjU4ODg4OTc2MDQ5ODk4MTA2OTk4NTk4Njc0OTIzOTIxNzU0MTk3OTA3MzM0ODAzOTEwNDM0NjE4MDk3MTc1NDg1OTIzNTUxNTU0ODQ5MjE2NTczMzAwOTA5NjcwMzE1NTE5MjA3MzI4MzMwMjc4MDMwMTU4NDkyMTcwMzYzOTQ1MjczNTY4OTM4ODcwMDQ2NjMxNDk5NzQ2ODQ5MTIyNzAzMDkzNjA0ODYzODQwMTc5NTMxMDk0NDIyNjI1MDQ3OTA4NTg3NjczNTk5');
	}
	
	public function onCommand(CommandSender $sender,Command $command,$label,array $args)
 	{
		if($command->getName() == '附魔')
		{
			$set = $this->set->get('设置');
			$name = $sender->getName();
			if(!isset($args[0]) || $args[0] == '帮助')
			{
				$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
				$sender->sendMessage('§e #§6 /附魔 白名单 帮助');
				$sender->sendMessage('§e #§6 /附魔 附魔券 帮助');
				$sender->sendMessage('§e #§6 /附魔 商店 帮助');
				$sender->sendMessage('§e #§6 /附魔 镶嵌 [宝石编号] [被镶嵌装备编号]');
				$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
				return true;
			}
			if($args[0] == '商店')
			{
				if(!isset($args[1]) || $args[1] == '帮助' || !isset($args[5]))
				{
					$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
					$sender->sendMessage('§6 ["魔"] = 附魔ID §d|§6 ["级"] = 附魔等级 §d|§6 ["货"] = 货币类型 §d|§6 ["需"] = 需货币量 §d|§6 ["围"] = 等级范围 §d|§6 ["高"] = 最高等级 §d|§6 ["耐"] = 耐久变化 §d|§6 ["率"] = 成功几率');
					$sender->sendMessage('§6 灰色为选填,默认值为白色!');
					$sender->sendMessage('§e #§6 /附魔 商店 附魔 [魔] [级] [货] [需] §7[耐§f无变化§7] [率§f100§7] §b<添加附魔商店>');
					$sender->sendMessage('§e #§6 /附魔 商店 回收 [魔] [级] [货] [需] §b<添加回收商店>');
					$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
					return true;
				}
				if(!is_numeric($args[2])) return $sender->sendMessage('§e #§6 [魔] 必须为数字!');
				if(!is_numeric($args[3])) return $sender->sendMessage('§e #§6 [级] 必须为数字!');
				if(!$this->getMoneyName($args[4])) return $sender->sendMessage('§e #§6 [货] 是不存在的!');
				if(!is_numeric($args[5])) return $sender->sendMessage('§e #§6 [需] 必须为数字!');
				if($args[1] == '附魔')
				{
					if(!isset($args[6])) $args[6] = '无变化';
					if(!isset($args[7])) $args[7] = 100;
					$args[8] = $sender->level->getFolderName();
					$this->SHOP_SET[$name] = $args;
					$sender->sendMessage('§e 现在点击一个木牌完成添加商店!');
					return true;
 				}
			}
			if($args[0] == '镶嵌')
			{
				if(!isset($args[2]))
				{
					$sender->sendMessage('§e #§6 /附魔 镶嵌 [宝石编号] [被镶嵌装备编号]');
					return true;
				}
				if(!$this->Enchant->exists($args[1]))
				{
					$sender->sendMessage('§e ID错误-> 在服务器内未发现此[宝石ID],他可能不存在或被使用...');
					return true;
				}
				if(!$this->Enchant->exists($args[2]))
				{
					$sender->sendMessage('§e ID错误-> 在服务器内未发现此[装备ID],他可能不存在或消失...');
					return true;
				}
				$arg1 = False;
				$arg2 = False;
				for($index = 0; $index < $sender->getInventory()->getSize(); $index ++)
				{
					$ItemInHand = $sender->getInventory()->getItem($index);
					if(isset($ItemInHand->getNamedTag()['display']['strings']))
					{
						$string = $ItemInHand->getNamedTag()['display']['strings'];
						if($string == $args[1])
						{
							$arg1 = True;
						}
						if($string == $args[2])
						{
							$arg2 = True;
						}
					}
				}
				if(!$arg1)
				{
					$sender->sendMessage('§e 宝石错误-> 此宝石不在你身上,无法进行镶嵌!');
					return true;
				}
				if(!$arg2)
				{
					$sender->sendMessage('§e 装备警告-> 此装备不在你身上!已切换为离线玩家装备镶嵌模式');
					return true;
				}
				$en_1 = $this->Enchant->get($args[1]);
				$en_2 = $this->Enchant->get($args[2]);
				if(count($en_1) != 1)
				{
					$sender->sendMessage('§e 宝石错误-> 此宝石不止附带一种属性!是错误的物品!');
					return true;
				}
				if(!isset($en_2['镶嵌']) || !isset($en_2['镶嵌']['max']))
				{
					$sender->sendMessage('§e 装备错误-> 此装备未附带正确的RPG属性,无法完成镶嵌!');
					return true;
				}
				if((count($en_2['镶嵌']) - 1) >= $en_2['镶嵌']['max'])
				{
					$sender->sendMessage('§e 提示-> 此装备可镶嵌凹槽已用尽!');
					return true;
				}
				foreach($en_2['镶嵌'] as $m => $t)
				{
					foreach($en_1 as $k => $v)
					{
						if($k == $m&& $v == $t)
						{
							$sender->sendMessage('§e 提示-> 此装备已镶嵌'.$v.'级的"'.$this->getEnchantName($k).'"!');
							return true;
						}
					}
				}
				$k = 33;
				$v = 1;
				foreach($en_1 as $k => $v)
				{
					$en_2['镶嵌'][$k] = $v;
					$this->Enchant->set($args[2],$en_2);
					$this->Enchant->remove($args[1]);
					$this->Enchant->save();
				}
				for($index = 0; $index < $sender->getInventory()->getSize(); $index ++)
				{
					$ItemInHand = $sender->getInventory()->getItem($index);
					if(isset($ItemInHand->getNamedTag()['display']['strings']))
					{
						$string = $ItemInHand->getNamedTag()['display']['strings'];
						if($string == $args[1])
						{
							$sender->getInventory()->setItem($index,Item::get(Item::AIR, 0, 1));
						}
					}
				}
				$sender->sendMessage('§e 提示-> 此装备 镶嵌"'.$this->getEnchantName($k).'"成功!获得额外属性:'.$this->buff_txt($k,$v).'!');
				return true;
			}
			if($args[0] == '镶嵌')
			{
				if(!isset($args[2]))
				{
					$sender->sendMessage('§e #§6 /附魔 镶嵌 [宝石编号] [被镶嵌装备编号]');
					return true;
				}
				if(!$this->Enchant->exists($args[1]))
				{
					$sender->sendMessage('§e ID错误-> 在服务器内未发现此[宝石ID],他可能不存在或被使用...');
					return true;
				}
				if(!$this->Enchant->exists($args[2]))
				{
					$sender->sendMessage('§e ID错误-> 在服务器内未发现此[装备ID],他可能不存在或消失...');
					return true;
				}
				$arg1 = False;
				$arg2 = False;
				for($index = 0; $index < $sender->getInventory()->getSize(); $index ++)
				{
					$ItemInHand = $sender->getInventory()->getItem($index);
					if(isset($ItemInHand->getNamedTag()['display']['strings']))
					{
						$string = $ItemInHand->getNamedTag()['display']['strings'];
						if($string == $args[1])
						{
							$arg1 = True;
						}
						if($string == $args[2])
						{
							$arg2 = True;
						}
					}
				}
				if(!$arg1)
				{
					$sender->sendMessage('§e 宝石错误-> 此宝石不在你身上,无法进行镶嵌!');
					return true;
				}
				if(!$arg2)
				{
					$sender->sendMessage('§e 装备警告-> 此装备不在你身上!已切换为离线玩家装备镶嵌模式');
					return true;
				}
				$en_1 = $this->Enchant->get($args[1]);
				$en_2 = $this->Enchant->get($args[2]);
				if(count($en_1) != 1)
				{
					$sender->sendMessage('§e 宝石错误-> 此宝石不止附带一种属性!是错误的物品!');
					return true;
				}
				if(!isset($en_2['镶嵌']) || !isset($en_2['镶嵌']['max']))
				{
					$sender->sendMessage('§e 装备错误-> 此装备未附带正确的RPG属性,无法完成镶嵌!');
					return true;
				}
				if((count($en_2['镶嵌']) - 1) >= $en_2['镶嵌']['max'])
				{
					$sender->sendMessage('§e 提示-> 此装备可镶嵌凹槽已用尽!');
					return true;
				}
				foreach($en_2['镶嵌'] as $m => $t)
				{
					foreach($en_1 as $k => $v)
					{
						if($k == $m&& $v == $t)
						{
							$sender->sendMessage('§e 提示-> 此装备已镶嵌'.$v.'级的"'.$this->getEnchantName($k).'"!');
							return true;
						}
					}
				}
				$k = 33;
				$v = 1;
				foreach($en_1 as $k => $v)
				{
					$en_2['镶嵌'][$k] = $v;
					$this->Enchant->set($args[2],$en_2);
					$this->Enchant->remove($args[1]);
					$this->Enchant->save();
				}
				for($index = 0; $index < $sender->getInventory()->getSize(); $index ++)
				{
					$ItemInHand = $sender->getInventory()->getItem($index);
					if(isset($ItemInHand->getNamedTag()['display']['strings']))
					{
						$string = $ItemInHand->getNamedTag()['display']['strings'];
						if($string == $args[1])
						{
							$sender->getInventory()->setItem($index,Item::get(Item::AIR, 0, 1));
						}
					}
				}
				$sender->sendMessage('§e 提示-> 此装备 镶嵌"'.$this->getEnchantName($k).'"成功!获得额外属性:'.$this->buff_txt($k,$v).'!');
				return true;
			}
			if($args[0] == '白名单')
			{
				$White_list = $set['白名单'];
				if(!isset($args[1]) || $args[1] == '帮助')
				{
					$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
					$sender->sendMessage('§e #§6 /附魔 白名单 列表 §5<查看所有拥有创建商店权限的玩家>');
					$sender->sendMessage('§e #§6 /附魔 白名单 添加 [游戏名] §5<添加此玩家创建商店的权限>');
					$sender->sendMessage('§e #§6 /附魔 白名单 删除 [游戏名] §5<删除此玩家创建商店的权限>');
					$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
					return true;
				}
				if($args[1] == '添加')
				{
					if($sender instanceof Player)
					{
						$sender->sendMessage('§e -->§6 此指令只有后台才有权限执行!');
						return true;
					}
					if(in_Array($args[2],$White_list))
					{
						$sender->sendMessage('§e -->§6 此玩家已有创建商店的权限!');
						return true;
					}
					$set['白名单'][] = $args[2];
					$sender->sendMessage('§e -->§6 已添加[' . $args[2] . ']玩家创建附魔商店的权限!');
					$this->set->set('设置',$set);
					$this->set->save();
					return true;
				}
				if($args[1] == '删除')
				{
					if($sender instanceof Player)
					{
						$sender->sendMessage('§e -->§6 此指令只有后台才有权限执行!');
						return true;
					}
					if(!in_Array($args[2],$White_list))
					{
						$sender->sendMessage('§e -->§6 此玩家没有创建商店的权限!');
						return true;
					}
					$player_bumer = 0;
					for($a = 0; $a < count($set['白名单']); $a ++)
					{
						if($set['白名单'][$a] == $args[2])
						{
							$player_bumer += 1;
							unset($set['白名单'][$a]);
							$this->set->set('设置',$set);
							$this->set->save();
						}
					}
					if($player_bumer == 1)
					{
						$sender->sendMessage('§e -->§6 已移除[' . $args[2] . ']玩家创建附魔商店的权限!');
						return true;
					}
					else if($player_bumer > 1)
					{
						$sender->sendMessage('§e -->§6 移除时发生了错误! 代码[003] , 字符串[' . $player_bumer . ']');
						return true;
					}
					else
					{
						$sender->sendMessage('§e -->§6 移除时发生了错误! 代码[004] , 字符串[' . $player_bumer . ']');
						return true;
					}
				}
				if($args[1] == '列表')
				{
					$player_bumer = 0;
					$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
					for($a = 0; $a < count($set['白名单']); $a ++)
					{
						$player_bumer += 1;
						$sender->sendMessage('§e *-->§6 < ' . $set['白名单'][$a] . ' >');
					}
					$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
					$sender->sendMessage('§e -->§6 共扫描到' . $player_bumer . '个名字!');
					return true;
				}
			}
			if($args[0] == '附魔券')
			{
				$set = $this->set->get('设置');
				if(!isset($args[1]) || $args[1] == '帮助')
				{
					$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
					$sender->sendMessage('§e #§6 /附魔 附魔券 富豪榜 §5<查看附魔券前10名>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 所持 §5<查看自己所持有附魔券的数量>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 查看 [玩家] §5<查看其他玩家所持有附魔券数量>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 设置 [玩家] [数量] §5<设置玩家的附魔券数量>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 扣除 [玩家] [数量] §5<扣除玩家一定数量的附魔券>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 增加 [玩家] [数量] §5<为玩家增加一定数量的附魔券>');
					$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
					return true;
				}
				if($args[1] == '富豪榜')
				{
					$ALL = $this->Money->getAll();
					arsort($ALL);
					$c = 0;
					foreach($ALL as $name => $but)
					{
						$c += 1;
						if($c <= 10)
						{
							$sender->sendMessage('§e第' . $c . '名 : §a' . $name . ' §5 ====> §b ' . $but . '附魔券');
						}
					}
					return true;
				}
				if($args[1] == '所持')
				{
					$Money = $this->Money->get($name);
					$sender->sendMessage('§4-> §c您所持有的附魔券数量为§e[ ' . $Money . ' ]');
					return true;
				}
				if($args[1] == '查看')
				{
					if(!$sender->isOp())
					{
						$sender->sendMessage('§4-> §c非管理员不能进行附魔点券操作!');
						return true;
					}
					if($set['后台才能执行附魔券操作'] == '开')
					{
						if($sender instanceof Player)
						{
							$sender->sendMessage('§4-> §c后台才能进行附魔点券操作!');
							return true;
						}
					}
					if(!isset($args[2]))
					{
						$sender->sendMessage('§e #§6 /附魔 附魔券 查看 [玩家] §5<查看其他玩家所持有附魔券数量>');
						return true;
					}
					if(!$this->Money->exists($args[2]))
					{
						$sender->sendMessage('§4-> §c玩家§e[ ' . $args[2] . ' ]§c未加入过游戏!');
						return true;
					}
					$Money = $this->Money->get($args[2]);
					$sender->sendMessage('§4-> §c玩家§e[ ' . $args[2] . ' ]§c所持有的附魔券数量为§e[ ' . $Money . ' ]');
					return true;
				}
				if($args[1] == '设置')
				{
					if(!$sender->isOp())
					{
						$sender->sendMessage('§4-> §c非管理员不能进行附魔点券操作!');
						return true;
					}
					if($set['后台才能执行附魔券操作'] == '开')
					{
						if($sender instanceof Player)
						{
							$sender->sendMessage('§4-> §c后台才能进行附魔点券操作!');
							return true;
						}
					}
					if(!isset($args[3]) || !is_numeric($args[3]))
					{
						$sender->sendMessage('§e #§6 /附魔 附魔券 设置 [玩家] [数量] §5<设置玩家的附魔券数量>');
						return true;
					}
					if($args[3] <= 0 || $args[3] > 100000000)
					{
						$sender->sendMessage('§4-> §c不能设置附魔券数为§e[ ' . $args[3] . ' ]§c 他只能大于0和小于100000000!');
						return true;
					}
					$sender->sendMessage('§4-> §c已设置 玩家§e[ ' . $args[2] . ' ]§c 所持有的 附魔券为§e[ ' . $args[3] . ' ]§c!');
					$this->Money->set($args[2],$args[3]);
					$this->Money->save();
					return true;
				}
				if($args[1] == '扣除')
				{
					if(!$sender->isOp())
					{
						$sender->sendMessage('§4-> §c非管理员不能进行附魔点券操作!');
						return true;
					}
					if($set['后台才能执行附魔券操作'] == '开')
					{
						if($sender instanceof Player)
						{
							$sender->sendMessage('§4-> §c后台才能进行附魔点券操作!');
							return true;
						}
					}
					if(!isset($args[3]) || !is_numeric($args[3]))
					{
						$sender->sendMessage('§e #§6 /附魔 附魔券 扣除 [玩家] [数量] §5<扣除玩家一定数量的附魔券>');
						return true;
					}
					if(!$this->Money->exists($args[2]))
					{
						$sender->sendMessage('§4-> §c玩家§e[ ' . $args[2] . ' ]§c未加入过游戏!');
						return true;
					}
					$Money = $this->Money->get($args[2]);
					if($Money <= 0)
					{
						$sender->sendMessage('§4-> §c不能设置扣除附魔券数为§e[ ' . $args[3] . ' ]§c他只能大于0!');
						return true;
					}
					if($args[3] > $Money)
					{
						$sender->sendMessage('§4-> §c不能设置扣除附魔券数为§e[ ' . $args[3] . ' ]§c玩家§e[ ' . $args[2] . ' ]§c只持有§e[ ' . $Money . ' ]§c!');
						return true;
					}
					$Moneys = $Money - (Int)$args[3];
					$sender->sendMessage('§4-> §c已设置扣除 玩家§e[ ' . $args[2] . ' ]§c 所持有的 附魔券§e[ ' . $args[3] . ' ]§c!现持有§e[ ' . $Moneys . ' ]§c!');
					$this->Money->set($args[2],$Moneys);
					$this->Money->save();
					return true;
				}
				if($args[1] == '增加')
				{
					if(!$sender->isOp())
					{
						$sender->sendMessage('§4-> §c非管理员不能进行附魔点券操作!');
						return true;
					}
					if($set['后台才能执行附魔券操作'] == '开')
					{
						if($sender instanceof Player)
						{
							$sender->sendMessage('§4-> §c后台才能进行附魔点券操作!');
							return true;
						}
					}
					if(!isset($args[3]) || !is_numeric($args[3]))
					{
						$sender->sendMessage('§e #§6 /附魔 附魔券 增加 [玩家] [数量] §5<为玩家增加一定数量的附魔券>');
						return true;
					}
					if(!$this->Money->exists($args[2]))
					{
						$sender->sendMessage('§4-> §c玩家§e[ ' . $args[2] . ' ]§c未加入过游戏!');
						return true;
					}
					$Money = $this->Money->get($args[2]);
					if($args[3] <= 0)
					{
						$sender->sendMessage('§4-> §c不能设置增加附魔券数为§e[ ' . $args[3] . ' ]§c他只能大于0!');
						return true;
					}
					$Moneys = $Money + (Int)$args[3];
					$sender->sendMessage('§4-> §c已设置增加 玩家§e[ ' . $args[2] . ' ]§c 所持有的 附魔券§e[ ' . $args[3] . ' ]§c!现持有§e[ ' . $Moneys . ' ]§c!');
					$this->Money->set($args[2],$Moneys);
					$this->Money->save();
					return true;
				}
			}
		}
	}

	public function getEnction($player)
	{
		$H = $player->getInventory()->getHelmet();
		$C = $player->getInventory()->getChestplate();
		$L = $player->getInventory()->getLeggings();
		$B = $player->getInventory()->getBoots();
		$Enchant = Array();
		if($H->getId() > 0)
		{
			if(isset($H->getNamedTag()['display']['strings']))
			{
				$nbt_id = $H->getNamedTag()['display']['strings'];
				$texts = $this->Enchant->get($nbt_id);
				$Enchant[$nbt_id] = $texts;
			}
		}
		if($C->getId() > 0)
		{
			if(isset($C->getNamedTag()['display']['strings']))
			{
				$nbt_id = $C->getNamedTag()['display']['strings'];
				$texts = $this->Enchant->get($nbt_id);
				$Enchant[$nbt_id] = $texts;
			}
		}
		if($L->getId() > 0)
		{
			if(isset($L->getNamedTag()['display']['strings']))
			{
				$nbt_id = $L->getNamedTag()['display']['strings'];
				$texts = $this->Enchant->get($nbt_id);
				$Enchant[$nbt_id] = $texts;
			}
		}
		if($B->getId() > 0)
		{
			if(isset($B->getNamedTag()['display']['strings']))
			{
				$nbt_id = $B->getNamedTag()['display']['strings'];
				$texts = $this->Enchant->get($nbt_id);
				$Enchant[$nbt_id] = $texts;
			}
		}
		return $Enchant;
	}

	function log($txt = ' ')
	{
		$this->getLogger()->warning('[开发者模式] -> '.$txt);
	}
	
	public function getAllEnction($player)
	{
		$Enchant = Array();
		for($index = 0; $index < $player->getInventory()->getSize(); $index ++)
		{
			$item = $player->getInventory()->getItem($index);
			if(isset($item->getNamedTag()['display']['strings']))
			{
				$nbt_id = $item->getNamedTag()['display']['strings'];
				$texts = $this->Enchant->get($nbt_id);
				$Enchant[$nbt_id] = $texts;
			}
		}
		$Enchant2 = $this->getEnction($player);
		$Enchant = array_merge($Enchant2,$Enchant);
		return $Enchant;
	}
	
	public function load_shop($name,$shop_type)
 	{
		$player = $this->getServer()->getPlayer($name);
		if($player instanceof Player) return '警告:在执行商店时出现未知错误[#1008]!';
		$ItemInHand = $player->getInventory()->getItemInHand();
		$ID = $ItemInHand->getId();
		$Enchant = Array();
		$NBT = Array();
		$nbt_id = 0;
		if(!empty($ItemInHand->getEnchantments()))//收集自带附魔
		{
			foreach($ItemInHand->getEnchantments() as $enchantment)
			{
				$Enchant[$enchantment->getId()] = $enchantment->getLevel();
			}
		}
		if(isset($ItemInHand->getNamedTag()['display']['strings']))//收集自定义附魔
		{
			$nbt_id = $ItemInHand->getNamedTag()['display']['strings'];
			if($this->Enchant->exists($nbt_id))
			{
				$texts = $this->Enchant->get($nbt_id);
				$NBT = $texts;
				foreach($texts as $key => $value)
				{
					$Enchant[$key] = $value;
				}
			}
		}
		$enchant_name = $this->getEnchantName($shop_type['ID']);//获取所需附魔名字
		if($shop_type['类型'] == '附魔')
		{
			if(!$this->getTrue($shop_type['ID'],$ID)) return "此物品不支持附魔".$enchant_name."!";
			if($shop_type['ID'] == 32 && isset($NBT['镶嵌']) && $NBT['镶嵌']['max'] >= $shop_type['LV']) return "此物品镶嵌宝石功能已开启,且凹槽数相等或更高!";
			if(isset($Enchant[$shop_type['ID']]) && $Enchant[$shop_type['ID']] >= $shop_type['LV']) return "你手中的物品已附魔".$shop_type["LV"]."级的".$enchant_name.",甚至更高!";
			$Money = $this->setMoneys($player,$shop_type['交换物'],$shop_type['交换量'],'-');
			if($Money !== true)
			{
				return $Money;
			}
			if(count($NBT) == 0)
			{
				$nbt_id = $this->Enchant->get('Enchant') + 1;
				$this->Enchant->set('Enchant',$nbt_id);
			}
			$NBT[$shop_type['ID']] = $shop_type['LV'];
			$shop_type['ID'] == 29 ? $NBT['指数'] = 5 : [];
			if($shop_type['ID'] == 32)
			{
				$NBT['镶嵌']['max'] = $shop_type['LV'];
			}
			$this->Enchant->set($nbt_id,$NBT);
			$this->Enchant->save();
			$nbt = new CompoundTag("", [
				"display" => new CompoundTag("display", [
					"Name" => new StringTag("Name",'正在加载...'),
					"strings" => new StringTag("strings",$nbt_id)
				])
			]);
			$ItemInHand->setNamedTag($nbt);
			foreach($Enchant as $k => $v)
			{
				if(!is_Array($v)&& is_numeric($k)&& $k >= 0&& $k <= 24)
				{
					$enchantment = Enchantment::getEnchantment($k);
					$enchantment->setLevel($v);
					$ItemInHand->addEnchantment($enchantment);
				}
			}
			$enchantment = Enchantment::getEnchantment($shop_type['ID']);
			$enchantment->setLevel($shop_type['LV']);
			$ItemInHand->addEnchantment($enchantment);
			$player->getInventory()->setItemInHand($ItemInHand);
			return true;
		}
		if($shop_type['类型'] == '修复')
		{
			if(!isset($Enchant[$shop_type['ID']]))
			{
				return "你手中的物品未附魔".$enchant_name.",修复至少需要1级!";
			}
			if($Enchant[$shop_type['ID']] >= $shop_type['上限'])
			{
				return "你手中的物品已附魔".$shop_type["上限"]."级的".$enchant_name.",甚至更高!超出可修复上限...";
			}
			$Money = $this->setMoneys($player,$shop_type['交换物'],$shop_type['交换量'],'-');
			if($Money !== true)
			{
				return $Money;
			}
			$nbt = new CompoundTag("", [
				"display" => new CompoundTag("display", [
					"Name" => new StringTag("Name",'正在加载...'),
					"strings" => new StringTag("strings",$nbt_id)
				])
			]);
			$ItemInHand->setNamedTag($nbt);
			foreach($Enchant as $k => $v)
			{
				if(!is_Array($v)&& is_numeric($k)&& $k >= 0&& $k <= 24)
				{
					$enchantment = Enchantment::getEnchantment($k);
					$enchantment->setLevel($v);
					$ItemInHand->addEnchantment($enchantment);
				}
			}
			$da = $ItemInHand->getDamage() - $shop_type['耐久'];
			$da < 0 ? $da = 0 : [];
			$ItemInHand->setDamage($da);
			$player->getInventory()->setItemInHand($ItemInHand);
			return true;
		}
		if($shop_type['类型'] == '强化')
		{
			if(!$this->getTrue($shop_type['ID'],$ID))
			{
				return "此物品不支持强化附魔".$enchant_name."!";
			}
			if(!isset($Enchant[$shop_type['ID']]))
			{
				return "你手中的物品未附魔".$enchant_name.",强化至少需要1级!";
			}
			if($Enchant[$shop_type['ID']] >= $shop_type['上限'])
			{
				return "你手中的物品已附魔".$shop_type["上限"]."级的".$enchant_name.",甚至更高!";
			}
			$Money = $this->setMoneys($player,$shop_type['交换物'],$shop_type['交换量'],'-');
			if($Money !== true)
			{
				return $Money;
			}
			if(count($NBT) == 0)
			{
				return "404:在强化时NBT异常清空!请联系开发者 #Array(ID:$nbt_id) => $name";
			}
			if(isset($NBT[$shop_type['ID']]))
			{
				$shop_type['ID'] == 29 ? $NBT['指数'] = 5 : [];
				if($shop_type['ID'] == 32)
				{
					$NBT['镶嵌']['max'] = $Enchant[$shop_type['ID']];
				}
				$NBT[$shop_type['ID']] = 1 + $Enchant[$shop_type['ID']];
			}
			$this->Enchant->set($nbt_id,$NBT);
			$this->Enchant->save();
			$nbt = new CompoundTag("", [
				"display" => new CompoundTag("display", [
					"Name" => new StringTag("Name",'正在加载...'),
					"strings" => new StringTag("strings",$nbt_id)
				])
			]);
			$ItemInHand->setNamedTag($nbt);
			foreach($Enchant as $k => $v)
			{
				if(!is_Array($v)&& is_numeric($k)&& !is_Array($k))
				{
					$enchantment = Enchantment::getEnchantment($k);
					$enchantment->setLevel($v);
					$ItemInHand->addEnchantment($enchantment);
				}
			}
			$player->getInventory()->setItemInHand($ItemInHand);
			return true;
		}
		if($shop_type['类型'] == '回收')
		{
			if(isset($Enchant[$shop_type['ID']])&& $Enchant[$shop_type['ID']] < $shop_type['LV'])
			{
				return "你手中的物品已附魔".$Enchant[$shop_type['ID']]."级的".$enchant_name.",但等级低于最低要求[LV:".$shop_type['LV1']."]!";
			}
			$this->setMoneys($player,$shop_type['交换物'],$shop_type['交换量']);
			$player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 1));
			return true;
		}
		if($shop_type['类型'] == '出售')
		{
			$Money = $this->setMoneys($player,$shop_type['交换物'],$shop_type['交换量'],'-');
			if($Money !== true)
			{
				return $Money;
			}
			$ItemInHand = Item::get($shop_type['装备'],0,1);
			$enchantment = Enchantment::getEnchantment($shop_type['ID']);
			$enchantment->setLevel($shop_type['LV']);
			$ItemInHand->addEnchantment($enchantment);
			$player->getInventory()->addItem(clone $ItemInHand);
			return true;
		}
	}

	function getEXP($player)
	{
		if($this->getServer()->getName() == 'Tesseract') return $player->getTotalXp();
		return $player->getExp();
	}

	function delEXP($player,int $EXP)
	{
		if($this->getServer()->getName() == 'Tesseract') return $player->setTotalXp($this->getEXP($player) - $EXP);
		return $player->setExp($this->getEXP($player) - $EXP);
	}

	function addEXP($player,int $EXP)
	{
		if($this->getServer()->getName() == 'Tesseract') return $player->setTotalXp($this->getEXP($player) + $EXP);
		return $player->setExp($this->getEXP($player) + $EXP);
	}

	function getLV($player)
	{
		if($this->getServer()->getName() == 'Tesseract') return $player->getXpLevel();
		return $player->getExpLevel();
	}

	function delLV($player,int $LV)
	{
		if($this->getServer()->getName() == 'Tesseract') return $player->takeXpLevel(-$LV);
		return $player->setExpLevel($this->getEXP($player) - $LV);
	}

	function addLV($player,int $LV)
	{
		if($this->getServer()->getName() == 'Tesseract') return $player->addXpLevel($LV);
		return $player->setExpLevel($this->getEXP($player) + $LV);
	}

	public function setMoneys(Player $player,String $lis,int $numeric,String$type = '+',$return = False)
	{
		$name = $player->getName();
		$lis = strtolower($lis);
		if($numeric < 0) return '错误:此金额小于0 ['.$numeric.$lis.']';
		if($lis == '金币' or $lis == 'm')
		{
			$numeric_one = EconomyAPI::getInstance()->myMoney($name);
			if($return) return $numeric_one;
			if($type == '+')
			{
				EconomyAPI::getInstance()->setMoney($player, $numeric_one + $numeric);
				$player->sendMessage('你的账户余额增加了'.$numeric.'金币');
				return true;
			}
			else
			{
				if($numeric > $numeric_one) return '你的账户余额不足'.$numeric.$lis;
				EconomyAPI::getInstance()->setMoney($player, $numeric_one - $numeric);
				$player->sendMessage('你的账户余额支出了'.$numeric.'金币');
				return true;
			}
		}
		if($lis == '经验' or $lis == 'x')
		{
			$numeric_one = $this->getEXP($player);
			if($return) return $numeric_one;
			if($type == '+')
			{
				$this->addEXP($player,$numeric);
				$player->sendMessage('你的经验增长了'.$numeric);
				return true;
			}
			else
			{
				if($numeric > $numeric_one) return '你的经验不足'.$numeric;
				$this->delEXP($player,$numeric);
				$player->sendMessage('你的经验减少了'.$numeric);
				return true;
			}
		}
		if($lis == '等级' or $lis == 'l')
		{
			$numeric_one = $this->getEXP($player);
			if($return) return $numeric_one;
			if($type == '+')
			{
				$this->addLV($player,$numeric);
				$player->sendMessage('你的等级增加了'.$numeric);
				return true;
			}
			else
			{
				if($numeric > $numeric_one) return '你的等级不足'.$numeric;
				$this->delLV($player,$numeric);
				$player->sendMessage('你的等级减少了'.$numeric);
				return true;
			}
		}
		if($lis == '附魔券' or $lis == 'w')
		{
			$numeric_one = $this->Money->get($name);
			if($return) return $numeric_one;
			if($type == '+')
			{
				$this->Money->set($name,$numeric_one + $numeric);
				$this->Money->save();
				$player->sendMessage('你账户内的附魔券增加了'.$numeric);
				return true;
			}
			else
			{
				if($numeric > $numeric_one) return '你账户内的附魔券不足'.$numeric;
				$this->Money->set($name,$numeric_one - $numeric);
				$this->Money->save();
				$player->sendMessage('你账户内的附魔券支出'.$numeric);
				return true;
			}
		} 
		if($lis == '点券')
		{
			$numeric_one = (int)ZXDAConnector::getPlayerCoupons($name);
			if($return) return $numeric_one;
			if($type == '+')
			{
				ZXDAConnector::addPlayerCoupons($name,$numeric);
				$player->sendMessage('你的点券增加了'.$numeric);
				return true;
			}
			else
			{
				if($numeric > $numeric_one) return '你账户内的点券不足'.$numeric;
				ZXDAConnector::takePlayerCoupons($name,$numeric);
				$player->sendMessage('你账户内的点券增加了'.$numeric);
				return true;
			}
		} 
		$this->getLogger()->info("404:在执行支付时出现未知的情况,请联系[附魔]开发者 #Array($lis,$numeric,$type) => $name");
		return False;
	}

}

class ZXDA
{
		private static $_PID=false;
		private static $_TOKEN=false;
		private static $_PLUGIN=null;
		private static $_VERIFIED=false;
		private static $_API_VERSION=5012;

		public static function init($pid,$plugin)
		{
			if(!is_numeric($pid))
			{
				self::killit('参数错误,请传入正确的PID(0001)');
				exit();
			}
			self::$_PLUGIN=$plugin;
			if(self::$_PID!==false && self::$_PID!=$pid)
			{
				self::killit('非法访问(0002)');
				exit();
			}
			self::$_PID=$pid;
		}

		public static function checkKernelVersion()
		{
			if(self::$_PID===false)
			{
				self::killit('SDK尚未初始化(0003)');
				exit();
			}
			if(!class_exists('\\ZXDAKernel\\Main',false))
			{
				self::killit('请到 https://pl.zxda.net/ 下载安装最新版ZXDA Kernel后再使用此插件(0004)');
				exit();
			}
			$version=\ZXDAKernel\Main::getVersion();
			if($version<self::$_API_VERSION)
			{
				self::killit('当前ZXDA Kernel版本太旧,无法使用此插件,请到 https://pl.zxda.net/ 下载安装最新版后再使用此插件(0005)');
				exit();
			}
			return $version;
		}

		public static function isTrialVersion()
		{
			try
			{
				self::checkKernelVersion();
				return \ZXDAKernel\Main::isTrialVersion(self::$_PID);
			}
			catch(\Exception $err)
			{
				@file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',var_export($err,true));
				self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
			}
		}

		public static function requestCheck()
		{
			try
			{
				self::checkKernelVersion();
				self::$_VERIFIED=false;
				self::$_TOKEN=sha1(uniqid());
				if(!\ZXDAKernel\Main::requestAuthorization(self::$_PID,self::$_PLUGIN,self::$_TOKEN))
				{
					self::killit('请求授权失败,请检查PID是否已正确传入(0006)');
					exit();
				}
			}
			catch(\Exception $err)
			{
				@file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',var_export($err,true));
				self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
			}
		}

		public static function tokenCheck($key)
		{
			try
			{
				self::checkKernelVersion();
				self::$_VERIFIED=false;
				$manager=self::$_PLUGIN->getServer()->getPluginManager();
				if(!($plugin=$manager->getPlugin('ZXDAKernel')) instanceof \ZXDAKernel\Main)
				{
					self::killit('ZXDA Kernel加载失败,请检查插件是否已正常安装(0008)');
				}
				if(!$manager->isPluginEnabled($plugin))
				{
					$manager->enablePlugin($plugin);
				}
				$key=base64_decode($key);
				if(($token=\ZXDAKernel\Main::getResultToken(self::$_PID))===false)
				{
					self::killit('请勿进行非法破解(0009)');
				}
				if(self::rsa_decode(base64_decode($token),$key,768)!=sha1(strrev(self::$_TOKEN)))
				{
					self::killit('插件Key错误,请更新插件或联系作者(0010)');
				}
				self::$_VERIFIED=true;
			}
			catch(\Exception $err)
			{
				@file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',var_export($err,true));
				self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
			}
		}

		public static function isVerified()
		{
			return self::$_VERIFIED;
		}

		public static function getInfo()
		{
			try
			{
				self::checkKernelVersion();
				$manager=self::$_PLUGIN->getServer()->getPluginManager();
				if(!($plugin=$manager->getPlugin('ZXDAKernel')) instanceof \ZXDAKernel\Main)
				{
					self::killit('ZXDA Kernel加载失败,请检查插件是否已正常安装(0008)');
				}
				if(($data=\ZXDAKernel\Main::getPluginInfo(self::$_PID))===false)
				{
					self::killit('请勿进行非法破解(0009)');
				}
				if(count($data=explode(',',$data))!=2)
				{
					return array(
				'success'=>false,
				'message'=>'未知错误');
				}
				return array(
					'success'=>true,
					'version'=>base64_decode($data[0]),
					'update_info'=>base64_decode($data[1]));
			}
			catch(\Exception $err)
			{
				@file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',var_export($err,true));
				self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
			}
		}

		public static function killit($msg)
		{
			if(self::$_PLUGIN===null)
			{
				echo('抱歉,插件授权验证失败[SDK:'.self::$_API_VERSION.']\n附加信息:'.$msg);
			}
			else
			{
				@self::$_PLUGIN->getLogger()->warning('§e抱歉,插件授权验证失败[SDK:'.self::$_API_VERSION.']');
				@self::$_PLUGIN->getLogger()->warning('§e附加信息:'.$msg);
				@self::$_PLUGIN->getServer()->forceShutdown();
			}
			exit();
		}


		//RSA加密算法实现
		public static function rsa_encode($message,$modulus,$keylength=1024,$isPriv=true){$result=array();while(strlen($msg=substr($message,0,$keylength/8-5))>0){$message=substr($message,strlen($msg));$result[]=self::number_to_binary(self::pow_mod(self::binary_to_number(self::add_PKCS1_padding($msg,$isPriv,$keylength/8)),'65537',$modulus),$keylength/8);unset($msg);}return implode('***&&&***',$result);}
		public static function rsa_decode($message,$modulus,$keylength=1024){
			$result=array();foreach(explode('***&&&***',$message) as $message){$result[]=self::remove_PKCS1_padding(self::number_to_binary(self::pow_mod(self::binary_to_number($message),'65537',$modulus),$keylength/8),$keylength/8);unset($message);}return implode('',$result);}
		private static function pow_mod($p,$q,$r){$factors=array();$div=$q;$power_of_two=0;while(bccomp($div,'0')==1){$rem=bcmod($div,2);$div=bcdiv($div,2);if($rem){array_push($factors,$power_of_two);}$power_of_two++;}$partial_results=array();$part_res=$p;$idx=0;foreach($factors as $factor){while($idx<$factor){$part_res=bcpow($part_res,'2');$part_res=bcmod($part_res,$r);$idx++;}array_push($partial_results,$part_res);}$result='1';foreach($partial_results as $part_res){$result=bcmul($result,$part_res);$result=bcmod($result,$r);}return $result;}
		private static function add_PKCS1_padding($data,$isprivateKey,$blocksize){$pad_length=$blocksize-3-strlen($data);if($isprivateKey){$block_type="\x02";$padding='';for($i=0;$i<$pad_length;$i++){$rnd=mt_rand(1,255);$padding .= chr($rnd);}}else{$block_type="\x01";$padding=str_repeat("\xFF",$pad_length);}return "\x00".$block_type.$padding."\x00".$data;}
		private static function remove_PKCS1_padding($data,$blocksize){assert(strlen($data)==$blocksize);$data=substr($data,1);if($data{0}=='\0'){return '';}assert(($data{0}=="\x01") || ($data{0}=="\x02"));$offset=strpos($data,"\0",1);return substr($data,$offset+1);}
		private static function binary_to_number($data){$radix='1';$result='0';for($i=strlen($data)-1;$i>=0;$i--){$digit=ord($data{$i});$part_res=bcmul($digit,$radix);$result=bcadd($result,$part_res);$radix=bcmul($radix,'256');}return $result;}
		private static function number_to_binary($number,$blocksize){$result='';$div=$number;while($div>0){$mod=bcmod($div,'256');$div=bcdiv($div,'256');$result=chr($mod).$result;}return str_pad($result,$blocksize,"\x00",STR_PAD_LEFT);}
}
/*
	看到此代码说明您已经解密或通过其他手段得到本插件源码!
	申明:本插件为[史莱姆]开发!如您[发布/抄袭/转载]等任何侵权行为,我将追究到底!
	©2016 - 2017 注明 : 2017/1/22 20:39:48
	史莱姆:
		QQ:478889187
		ZXDA UID:8897
		ZXDA USER:slm47888
*/
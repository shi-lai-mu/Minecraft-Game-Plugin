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
	
	use pocketmine\tile\Sign;
	use pocketmine\item\Item;
	use pocketmine\item\enchantment\Enchantment;
	use pocketmine\utils\Config;
	use pocketmine\entity\Effect;
	
	use pocketmine\entity\Entity;
	use pocketmine\entity\Zombie;
	use pocketmine\entity\ZombieVillager;
	use pocketmine\entity\PigZombie;
	use pocketmine\entity\Spider;
	use pocketmine\entity\Silverfish;
	use pocketmine\entity\Skeleton;
	//use \ZXDAConnector\Main as ZXDAConnector;

	use pocketmine\command\Command;
	use pocketmine\command\CommandSender;
	use pocketmine\command\CommandExecutor;
	use pocketmine\command\ConsoleCommandSender;
	
	use pocketmine\nbt\NBT;
	use pocketmine\nbt\tag\CompoundTag;
	use pocketmine\nbt\tag\StringTag;
	use pocketmine\nbt\tag\NamedTag;
	
	use pocketmine\math\Vector3;
	use pocketmine\inventory\Inventory;
	use pocketmine\scheduler\CallbackTask;
	use pocketmine\network\protocol\ExplodePacket;
	
	use pocketmine\level\Level;
	use pocketmine\level\particle\MobSpawnParticle;//生物死亡
	use pocketmine\level\particle\BubbleParticle;
	use pocketmine\level\particle\CriticalParticle;//蘑菇
	use pocketmine\level\particle\GenericParticle;
	use pocketmine\level\particle\InkParticle;//黑色的烟
	use pocketmine\level\particle\EntityFlameParticle;//僵尸着火
	use pocketmine\level\particle\WaterParticle;
	use pocketmine\level\particle\TerrainParticle;
	use pocketmine\level\particle\SmokeParticle;
	use pocketmine\level\particle\LavaParticle;
	use pocketmine\level\particle\HeartParticle;
	use pocketmine\level\particle\ExplodeParticle;
	use pocketmine\level\particle\DustParticle;
	use pocketmine\level\particle\EnchantParticle;
	use pocketmine\level\particle\PortalParticle;
	use pocketmine\level\particle\RedstoneParticle;
	use pocketmine\level\particle\WaterDripParticle;
	use pocketmine\level\particle\LavaDripParticle;

	use Enchant_RPG_SHOP\api;
	use onebone\economyapi\EconomyAPI;

	use Enchant_RPG_SHOP\Enchant\TXT;
	use Enchant_RPG_SHOP\Enchant\disassembly;
	use Enchant_RPG_SHOP\PRO\sharp;

class Enchant_RPG_SHOP extends PluginBase implements Listener
{
	private $datas = False;
	private $datas_name = False;
	private $PLUGIN_BAY = False;
	private $Connector = False;
	public $info = Array();
	private $click = Array();
	private $SHOP_SET = Array();
	private $SHOP_BUY = Array();
	private $gj = Array();
	private $Tip = 0;

	private static $instance;
 
	public static function getInstance()
	{
		return self::$instance;
	}
	
	public function onLoad()
	{
		//ZXDA::init(584,$this);
		//ZXDA::requestCheck();
	}
	
	public function onEnable()
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		self::$instance = $this;
		$this->getLogger()->info('§e ------------------------------------- ');
		$this->getLogger()->info('§6授权成功!§3感谢支持本插件...');
		$this->getLogger()->info('§2Enchant-RPG-SHOP §d'.$this->getDescription()->getVersion().'§3 加载中... ');
		$txt = new TXT($this);
		$api = new api($this);
		//ZXDAConnector::isEnabled() ? $this->Connector = True : $this->Connector = False;
		//$this->ZXDA_load();
		$dir = $this->getDataFolder();
		$this->Prepaid_10 = new Config($dir . 'Prepaid/10.Prepaid',Config::YAML,[]);
		$this->Prepaid_30 = new Config($dir . 'Prepaid/30.Prepaid',Config::YAML,[]);
		$this->Prepaid_50 = new Config($dir . 'Prepaid/50.Prepaid',Config::YAML,[]);
		$this->Prepaid_75 = new Config($dir . 'Prepaid/75.Prepaid',Config::YAML,[]);
		$this->Prepaid_100 = new Config($dir . 'Prepaid/100.Prepaid',Config::YAML,[]);
		$this->Enchant = new Config($dir . 'Enchant_NBT.yml',Config::YAML,[]);
		$this->set = new Config($dir . 'set.yml',Config::YAML,[]);
		$this->b = new Config($dir . 'Config.yml',Config::YAML,[]);
		$this->signs = new Config($dir . 'signs.json',Config::YAML,[]);
		$this->Player = new Config($dir . 'Player.json',Config::YAML,[]);
		$this->info = $this->Player->get('attribute');
		$this->beibao = new Config($dir . 'beibao.json',Config::YAML,[]);
		$this->Money = new Config($dir . 'Money.json',Config::YAML,[]);
		$this->Command_Shop = new Config($dir . 'Command_Shop.yml',Config::YAML,[]);
		$this->Prepaid = new Config($dir . 'Prepaid.yml',Config::YAML,[]);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,"timer"]),20);
		$this->getLogger()->info('§e ------------------------------------- ');
	}

	public function onDisable()
	{
		$this->getLogger()->info('§a 正在保存关键的RPG数据...');
		$this->Player->set('attribute',$this->info);
		$this->Player->save();
		$this->getLogger()->info('§a Enchant_sign 插件卸载中...');
	}
	
	public function timer()
	{
		foreach($this->getServer()->getOnlinePlayers() as $player)
		{
			$name = $player->getName();
			$this->updateInt($player);
			$level = $player->getLevel();
			if(isset($this->info[$name]))
			{
				$name_info = $this->info[$name];
				if($this->set->get('设置')['底部'] == '开')
				{
					$tips = "§1■■§2■■§3■■§5■■§6■■§8■■§a■■§b■■§c■■§e■■§4■■";
					if($this->set->get('设置')['底部动态框'] == '开')
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
					$Tip_x = $this->set->get('设置')['底部显示'];
					$Tip_c = Array();
					$Tip_c[] = Array('{生命上限}' => $name_info['生命上限']);//生命上限
					$Tip_c[] = Array('{魔法上限}' => $name_info['魔法上限'] * (@$player->getXpLevel() / 2));//魔法上限
					$Tip_c[] = Array('{生命}' => $player->getHealth());//生命
					$Tip_c[] = Array('{魔法}' => $name_info['魔法']);//魔法
					$Tip_c[] = Array('{物攻}' => $name_info['物攻']);//物攻
					$Tip_c[] = Array('{物防}' => $name_info['物防']);//物防
					$Tip_c[] = Array('{暴击}' => $name_info['暴击']);//暴击
					$Tip_c[] = Array('{抗暴}' => $name_info['抗暴']);//抗暴
					$Tip_c[] = Array('{魔防}' => $name_info['魔防']);//魔防
					$Tip_c[] = Array('{魔攻}' => $name_info['魔攻']);//魔攻
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
					if($this->set->get('设置')['在指定世界开启底部'] == '开')
					{
						if(in_array($player->level->getName(),$this->set->get('设置')['底部世界']))
						{
							$this->set->get('设置')['底部方式'] == 'Tip' ? $player->sendTip("$Tip_x") : $player->sendPopup("$Tip_x");
						}
					}
					else
					{
						$this->set->get('设置')['底部方式'] == 'Tip' ? $player->sendTip("$Tip_x") : $player->sendPopup("$Tip_x");
					}
				}
				$Enchant = $this->getAllEnction($player);
				$Health = 0;//生命
				$Magic = 0;//魔法
				$Content_Attack = 0;//物攻
				$Authors = 0;//物防
				$Crit = 0;//暴击
				$Block = 0;//格挡
				$Dodge = 0;//闪避
				$Physical = 0;//抗暴
				$Hit = 0;//命中
				$Magic_against = 0;//魔防
				$Magic_attack = 0;//魔攻
				foreach($Enchant as $a => $b)
				{
					if(is_array($b))
					{
						if($a == '头附魔' OR $a == '胸附魔' OR $a == '裤附魔' OR $a == '鞋附魔')
						{
							foreach($b as $c => $d)
							{
								if(isset($d[32]) and isset($d['镶嵌']))
								{
									if(isset($d['镶嵌'][35]))//生命
									{
										$Health += $d['镶嵌'][35] * 0.5;
									}
									if(isset($d['镶嵌'][43]))//魔法
									{
										$Magic += $d['镶嵌'][43] * 5;
									}
									if(isset($d['镶嵌'][33]))//物攻
									{
										$Content_Attack += $d['镶嵌'][33] * 0.6;
									}
									if(isset($d['镶嵌'][34]))//物防
									{
										$Authors += $d['镶嵌'][34] * 0.5;
									}
									if(isset($d['镶嵌'][36]))//暴击
									{
										$Crit += $d['镶嵌'][36] * 1;
									}
									if(isset($d['镶嵌'][39]))//抗暴
									{
										$Physical += $d['镶嵌'][39] * 0.8;
									}
									if(isset($d['镶嵌'][41]))//魔防
									{
										$Magic_against += $d['镶嵌'][41] * 0.6;
									}
									if(isset($d['镶嵌'][42]))//魔攻
									{
										$Magic_attack += $d['镶嵌'][42] * 0.6;
									}
								}
							}
						}
					}
				}
				$lv = @$player->getXpLevel();
				if($this->set->get('设置')['等级上限'] < $lv)
				{
					@$player->setXpLevel($this->set->get('设置')['等级上限']);
				}
				if($this->set->get('设置')['等级影响属性'] == '关')
				{
					$lv = 1;
				}
				$Health += $this->set->get('设置')['每级加血量上限'] * $lv;
				$Content_Attack += $this->set->get('设置')['每级加物攻'] * $lv;
				$this->info[$name]['生命上限'] = 20 + $Health;
				$this->info[$name]['魔法上限'] = (2 * $lv) + $Magic;
				if($this->info[$name]['魔法上限'] > $this->info[$name]['魔法'])
				{
					$this->info[$name]['魔法'] += 0.1;
				}
				$this->info[$name]['物攻'] = round((1 + ($lv / 5)) + $Content_Attack + 0.1,2);
				$this->info[$name]['物防'] = round(($lv / 5) + $Authors + 0.1,2);
				$this->info[$name]['暴击'] = round(($lv / 5) + $Crit + 0.4,2);
				$this->info[$name]['抗暴'] = round(($lv / 6) + $Physical + 0.1,2);
				$this->info[$name]['魔防'] = round($lv + $Magic_against + 0.1,2);
				$this->info[$name]['魔攻'] = round(($lv / 10) + (1 + $lv) + $Magic_attack + 0.1,2);
				$this->info[$name]['魔法'] = ceil($this->info[$name]['魔法']);
				if($this->set->get('设置')['只开启血量和攻击属性'] == '开')
				{
					$this->info[$name]['魔法'] = 0;
					$this->info[$name]['魔法上限'] = 0;
					$this->info[$name]['物防'] = 0;
					$this->info[$name]['暴击'] = 0;
					$this->info[$name]['抗暴'] = 0;
					$this->info[$name]['魔防'] = 0;
					$this->info[$name]['魔攻'] = 0;
					$this->info[$name]['魔攻'] = 0;
				}
				if($this->info[$name]['生命上限'] > $this->set->get('设置')['最大可扩展血量上限'])
				{
					$this->info[$name]['生命上限'] = 100;
				}
				if($player->getMaxHealth() != $this->info[$name]['生命上限'])
				{
					$player->setMaxHealth($this->info[$name]['生命上限']);
				}
				if($player->getHealth() > $this->info[$name]['生命上限'])
				{
					$player->setHealth($this->info[$name]['生命上限']);
				}
				if(count($name_info['Note']) > 0)
				{
					if(isset($name_info['Note']['治疗汤']))
					{
						if($name_info['Note']['治疗汤']['时间'] > Time())
						{
							if($name_info['Note']['治疗汤']['已回'] < $name_info['Note']['治疗汤']['等级'])
							{
								if($player->getMaxHealth() > $player->getHealth())
								{
									$this->info[$name]['Note']['治疗汤']['间隔'] += 1;
									if($this->info[$name]['Note']['治疗汤']['间隔'] % 2 == 0)
									{
										$player->setHealth($player->getHealth() + 1);
										$this->info[$name]['Note']['治疗汤']['已回'] += 1;
										$r = 1.5;
										for($i=0;$i<20;$i++)
										{
											$xx = $player->x+$r*cos($i*3.1415926/10) ;
											$zz = $player->z+$r*sin($i*3.1415926/10) ;
											$level->addParticle(new HeartParticle(new Vector3($xx, $player->y+2, $zz)));
										}
									}
								}
							}
						}
						else
						{
							unset($this->info[$name]['Note']['治疗汤']);
						}
					}
				}
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
				'力量' => '加载中...',
				'魔法' => '加载中...',
				'魔法上限' => 10,
				'物攻' => '加载中...',
				'物防' => '加载中...',
				'暴击' => '加载中...',
				'格挡' => '加载中...',
				'闪避' => '加载中...',
				'抗暴' => '加载中...',
				'命中' => '加载中...',
				'魔防' => '加载中...',
				'魔攻' => '加载中...',
				'Note' => Array()
			);
			$this->info[$name] = $txt;
			$this->Player->set('attribute',$this->info);
			$this->Player->save();
		}
		if(!$this->Money->exists($name))
		{
			$this->Money->set($name,0);
			$this->Money->save();
		}
	}
	
	public function PlayerInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$player_name = $player->getName();
		$level = $player->getLevel();
		$block = $event->getBlock();
		$x1 = $block->getX();
		$y1 = $block->getY() + 2;
		$z1 = $block->getZ();
		$ItemInHand = $player->getInventory()->getItemInHand();
		$Enchant = Array();
		if(!empty($ItemInHand->getEnchantments())){
			foreach($ItemInHand->getEnchantments() as $enchantment)
			{
				$Enchant[$enchantment->getId()] = $enchantment->getLevel();
			}
		}
		if(isset($Enchant[-1]))
		{
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
			if(isset($Enchant[28]))
			{
				$ItemInHand->setCount($ItemInHand->getCount() - 1);
				@$player->setXpLevel($player->getXpLevel() + 1);
				$player->getInventory()->setItemInHand($ItemInHand); 
			}
			if(isset($Enchant[30]) or isset($Enchant[31]))
			{
				if(isset($Enchant[31]) or $player->getFood() == 20)
				{
					$player->setFood($player->getFood() - 1);
				}
			}
		}
		if($event->getBlock()->getID() == 323 or $event->getBlock()->getID() == 63 or $event->getBlock()->getID() == 68)
		{
			$sign = $event->getPlayer()->getLevel()->getTile($event->getBlock());
			if(!$sign instanceof Sign)
			{
				return;
			}
			$sign_text = $sign;
			if(!isset($this->click[$player_name]) OR $this->set->get('设置')['双击确认'] == '关')
			{
				$this->click[$player_name] = Time() + (int)$this->set->get('设置')['双击冷却秒数'];
			}
			$sign_s = $sign->getText();
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
				if($ItemInHand->getId() == 0)
				{
					return;
				}
				$Player_Money = EconomyAPI::getInstance()->myMoney($player_name);
				if($Player_Money < $sign['一键修复'])
				{
					$player->sendMessage('§4-> §c您所携带的金币§e[' . $Player_Money . ']§c不足以进行一键修复');
					return true;
				}
				if($this->click[$player_name] > Time())
				{
					$ItemInHand->setDamage(0);
					$player->getInventory()->setItemInHand($ItemInHand);
					EconomyAPI::getInstance()->setMoney($player, $Player_Money - $sign['一键修复']);
					$player->sendMessage('§4-> §6已成功修复手中装备,花费'.$sign['一键修复'].'金币');
					$this->click[$player_name] = 0;
				}
				else
				{
					$player->sendMessage('§4-> §6修复此装备需要花费'.$sign['一键修复'].'金币，再次点击确认!');
					$this->click[$player_name] = Time() + (int)$this->set->get('设置')['双击冷却秒数'];
				}
			}
			if(!isset($sign['附魔商店']) and !isset($sign['强化商店']) and !isset($sign['修复商店']) and !isset($sign['回收商店']) and !isset($sign['出售商店']))
			{
				return;
			}
			$MIXS = 1;
			$MAXS = 1;
			if(isset($sign['回收商店']))
			{
				$MIXS = array('@MIX' => $sign['最低等级']);
				$MAXS = array('@MAX' => $sign['最高等级']);
				$fc = '回收';
			}
			else if(isset($sign['修复商店']))
			{
				$MIXS = array('@MIX' => $sign['最低等级']);
				$MAXS = array('@MAX' => $sign['最高等级']);
				$fc = '修复';
			}
			else if(isset($sign['强化商店']))
			{
				$MIXS = array('@MIX' => $sign['强化等级']);
				$MAXS = array('@MAX' => $sign['强化上限']);
				$fc = '强化';
			}
			else if(isset($sign['附魔商店']))
			{
				$LVS = array('@LV' => $sign['等级']);
				$fc = '附魔';
			}
			else if(isset($sign['出售商店']))
			{
				$Item = array('@Item' => $this->getIDTrue($sign['出售商店'],$sign['装备']));
				$LVS = array('@LV' => $sign['等级']);
				$fc = '出售';
			}
			if($player->isOp())
			{
				if(isset($sign['加百分比耐久']))
				{
					$sign_TEXTS = '§3耐久加' . $sign['加百分比耐久'] . '%';
				}
				else if(isset($sign['减百分比耐久']))
				{
					$sign_TEXTS = '§3耐久减' . $sign['减百分比耐久'] . '%';
				}
				else if(isset($sign['百分比耐久']))
				{
					$sign_TEXTS = '§3' . $sign['百分比耐久'] . '%耐久';
				}
				else if(isset($sign['加耐久']))
				{
					$sign_TEXTS = '§3加' . $sign['加耐久'] . '点耐久';
				}
				else if(isset($sign['减耐久']))
				{
					$sign_TEXTS = '§3减' . $sign['减耐久'] . '耐久';
				}
				else
				{
					$sign_TEXTS = '';
				}
				$event->setCancelled();
				$TEST = $this->set->get('设置')[$fc];
				$IDS = array('@ID' => $this->getEnchantName($sign[$fc .'商店']));
				$MC = array('@MC' => $this->set->get('设置')[$sign['交换物'] . '名称']);
				$Amount = array('@Amount' => $sign['交换量']);
				$TSS = array('@TS' => $this->getEnchantXG($sign[$fc .'商店']));
				$DAS = array('@DA' => $sign_TEXTS);
				if(strstr($TEST[0] , '@ID')){$TEST[0] = strtr($TEST[0] , $IDS);}
				if(strstr($TEST[1] , '@ID')){$TEST[1] = strtr($TEST[1] , $IDS);}
				if(strstr($TEST[2] , '@ID')){$TEST[2] = strtr($TEST[2] , $IDS);}
				if(strstr($TEST[3] , '@ID')){$TEST[3] = strtr($TEST[3] , $IDS);}
				if(strstr($TEST[0] , '@LV')){$TEST[0] = strtr($TEST[0] , $LVS);}
				if(strstr($TEST[1] , '@LV')){$TEST[1] = strtr($TEST[1] , $LVS);}
				if(strstr($TEST[2] , '@LV')){$TEST[2] = strtr($TEST[2] , $LVS);}
				if(strstr($TEST[3] , '@LV')){$TEST[3] = strtr($TEST[3] , $LVS);}
				if(strstr($TEST[0] , '@MC')){$TEST[0] = strtr($TEST[0] , $MC);}
				if(strstr($TEST[1] , '@MC')){$TEST[1] = strtr($TEST[1] , $MC);}
				if(strstr($TEST[2] , '@MC')){$TEST[2] = strtr($TEST[2] , $MC);}
				if(strstr($TEST[3] , '@MC')){$TEST[3] = strtr($TEST[3] , $MC);}
				if(strstr($TEST[0] , '@Amount')){$TEST[0] = strtr($TEST[0] , $Amount);}
				if(strstr($TEST[1] , '@Amount')){$TEST[1] = strtr($TEST[1] , $Amount);}
				if(strstr($TEST[2] , '@Amount')){$TEST[2] = strtr($TEST[2] , $Amount);}
				if(strstr($TEST[3] , '@Amount')){$TEST[3] = strtr($TEST[3] , $Amount);}
				if(strstr($TEST[0] , '@TS')){$TEST[0] = strtr($TEST[0] , $TSS);}
				if(strstr($TEST[1] , '@TS')){$TEST[1] = strtr($TEST[1] , $TSS);}
				if(strstr($TEST[2] , '@TS')){$TEST[2] = strtr($TEST[2] , $TSS);}
				if(strstr($TEST[3] , '@TS')){$TEST[3] = strtr($TEST[3] , $TSS);}
				if(strstr($TEST[0] , '@DA')){$TEST[0] = strtr($TEST[0] , $DAS);}
				if(strstr($TEST[1] , '@DA')){$TEST[1] = strtr($TEST[1] , $DAS);}
				if(strstr($TEST[2] , '@DA')){$TEST[2] = strtr($TEST[2] , $DAS);}
				if(strstr($TEST[3] , '@DA')){$TEST[3] = strtr($TEST[3] , $DAS);}
				if(strstr($TEST[0] , '@MIX')){$TEST[0] = strtr($TEST[0] , $MIXS);}
				if(strstr($TEST[1] , '@MIX')){$TEST[1] = strtr($TEST[1] , $MIXS);}
				if(strstr($TEST[2] , '@MIX')){$TEST[2] = strtr($TEST[2] , $MIXS);}
				if(strstr($TEST[3] , '@MIX')){$TEST[3] = strtr($TEST[3] , $MIXS);}
				if(strstr($TEST[0] , '@MAX')){$TEST[0] = strtr($TEST[0] , $MAXS);}
				if(strstr($TEST[1] , '@MAX')){$TEST[1] = strtr($TEST[1] , $MAXS);}
				if(strstr($TEST[2] , '@MAX')){$TEST[2] = strtr($TEST[2] , $MAXS);}
				if(strstr($TEST[3] , '@MAX')){$TEST[3] = strtr($TEST[3] , $MAXS);}
				if($sign_s[0] != $TEST[0] or $sign_s[1] != $TEST[1] or $sign_s[2] != $TEST[2] or $sign_s[3] != $TEST[3])
				{
					$sign_text->setText($TEST[0] , $TEST[1] , $TEST[2] , $TEST[3]);
				}
			}
			$ID = $ItemInHand->getId();
			$Damage = $ItemInHand->getDamage();
			if(!isset($sign['出售商店']))
			{
				if($ID == 0)
				{
					return true;
				}
			}
			if(isset($sign['出售商店']))
			{
				$Enchant[$sign['出售商店']] = 0;
			}			
			if(isset($sign['加百分比耐久']))
			{
				$Damage = $Damage - (($this->itemDamage($ID) / 100) * $sign['加百分比耐久']);
				$Damage_TXT = '耐久恢复' . $sign['加百分比耐久'] . '%';
			}
			else if(isset($sign['减百分比耐久']))
			{
				$Damage = $Damage + (($this->itemDamage($ID) / 100) * $sign['减百分比耐久']);
				$Damage_TXT = '耐久减少' . $sign['减百分比耐久'] . '%';
			}
			else if(isset($sign['百分比耐久']))
			{
				$Damage = $this->itemDamage($ID) - (($this->itemDamage($ID) / 100) * $sign['百分比耐久']);
				$Damage_TXT = '耐久恢复至' . $sign['百分比耐久'] . '%';
			}
			else if(isset($sign['加耐久']))
			{
				$Damage = $Damage - $sign['加耐久'];
				$Damage_TXT = '耐久恢复' . $sign['加耐久'] . '点';
			}
			else if(isset($sign['减耐久']))
			{
				$Damage = $Damage + $sign['减耐久'];
				$Damage_TXT = '耐久减少' . $sign['减耐久'] . '点';
			}
			else if(isset($sign['耐久']))
			{
				$Damage = 0;
				$Damage_TXT = '耐久恢复至满';
			}
			else
			{
				$Damage = $Damage;
				$Damage_TXT = '耐久无变化!';
			}
			if($Damage > $this->itemDamage($ID))
			{
				$Damage = $this->itemDamage($ID);
			}
			else if($Damage < 0 and $Damage != 6000)
			{
				$Damage = 0;
			}
			$text = '未知';
			if($sign['交换物'] == '金币')
			{
				$text = $sign['交换量'] . '金币!';
			}
			else if($sign['交换物'] == '经验')
			{
				$text = $sign['交换量'] . '经验!';
			}
			else if($sign['交换物'] == '点券')
			{
				$text = $sign['交换量'] . '点券!';
			}
			else if($sign['交换物'] == '等级')
			{
				$text = $sign['交换量'] . '等级!';
			}
			else if($sign['交换物'] == '附魔券')
			{
				$text = $sign['交换量'] . '附魔券!';
			}
			$Player_Money = EconomyAPI::getInstance()->myMoney($player_name);
			if(!isset($sign['回收商店']))
			{
				if($sign['交换物'] == '金币')
				{
					$Enchant_Money = $sign['交换量'];
					if($Player_Money < $Enchant_Money)
					{
						$player->sendMessage('§4-> §c您所携带的金币§e[' . $Player_Money . ']§c不足以' . $fc . '§e' . $this->getEnchantName($sign[$fc . '商店']));
						return true;
					}
				}
				else if($sign['交换物'] == '经验')
				{
					$Experience = (int)@$player->getTotalXp();
					$Enchant_Experience = (int)$sign['交换量'];
					if($Experience < $Enchant_Experience)
					{
						$player->sendMessage('§4-> §c您所携带的经验§e[' . $Experience . ']§c不足以' . $fc . '§e' . $this->getEnchantName($sign[$fc . '商店']));
						return true;
					}
				}
				else if($sign['交换物'] == '等级')
				{
					$LVerience = @$player->getXpLevel();
					$Enchant_LVerience = (int)$sign['交换量'];
					if($LVerience < $Enchant_LVerience)
					{
						$player->sendMessage('§4-> §c您所携带的等级§e[' . $LVerience . ']§c不足以' . $fc . '§e' . $this->getEnchantName($sign[$fc . '商店']));
						return true;
					}
				}
				else if($sign['交换物'] == '附魔券')
				{
					$LVerience = (int)$this->Money->get($player_name);
					$Enchant_LVerience = (int)$sign['交换量'];
					if($LVerience < $Enchant_LVerience)
					{
						$player->sendMessage('§4-> §c您所携带的附魔券§e[' . $LVerience . ']§c不足以' . $fc . '§e' . $this->getEnchantName($sign[$fc . '商店']));
						return true;
					}
				}
				else if($sign['交换物'] == '点券')
				{
					$player_name = $player->getName();
					$Connector = ZXDAConnector::getPlayerCoupons($player_name);
					$Enchant_Connector = (int)$sign['交换量'];
					if($Connector < $Enchant_Connector)
					{
						$player->sendMessage('§4-> §c您所携带的点券§e[' . $Connector . ']§c不足以' . $fc . '§e' . $this->getEnchantName($sign[$fc . '商店']));
						return true;
					}
				}
			}
			//////////////////////////////
			/**********************
			修复商店
			**********************/
			//////////////////////////////
			if(isset($sign['修复商店']))
			{
				if(!isset($Enchant[$sign['修复商店']]))
				{
					$player->sendMessage('§4-> §c您手中所持的装备并非§e'. $this->getEnchantName($sign['修复商店']) . '§c附魔!无法修复!');
					return true;
				}
				if($Enchant[$sign['修复商店']] < $sign['最低等级'])
				{
					$player->sendMessage('§4-> §c此商店修复§e' . $this->getEnchantName($sign['修复商店']) . '§c至少要§e' . $sign['最低等级'] . '§c级!');
					return true;
				}
				if($Enchant[$sign['修复商店']] > $sign['最高等级'])
				{
					$player->sendMessage('§4-> §c此商店修复§e' . $this->getEnchantName($sign['修复商店']) . '§c只能§e' . $sign['最高等级'] . '§c级以下!');
					return true;
				}
				if($ItemInHand->getDamage() <= 0 OR $ItemInHand->getDamage() > $this->itemDamage($ID))
				{
					$player->sendMessage('§4-> §c您手中所持的装备并未损坏!无法修复!');
					return true;
				}
				if($this->click[$player_name] > Time())
				{
					if($sign['交换物'] == '金币')
					{
						$money = EconomyAPI::getInstance()->myMoney($player_name);
						EconomyAPI::getInstance()->setMoney($player, $money - $sign['交换量']);
					}
					else if($sign['交换物'] == '经验')
					{
						$Experience = @$player->getTotalXp();
						$setExperience = $Experience - $sign['交换量'];
						@$player->setTotalXp($player->getTotalXp() - $sign['交换量']);
					}
					else if($sign['交换物'] == '点券')
					{
						ZXDAConnector::takePlayerCoupons($player_name,$sign['交换量']);
					}
					else if($sign['交换物'] == '等级')
					{
						@$player->setXpLevel($player->getXpLevel() - $sign['交换量']);
					}
					else if($sign['交换物'] == '附魔券')
					{
						$fmq = $this->Money->get($player_name);
						$this->Money->set($player_name,$fmq - $sign['交换量']);
						$this->Money->save();
					}
					$ItemInHand->setDamage($Damage);
					$player->getInventory()->setItemInHand($ItemInHand);
					$player->sendMessage('§4-> §6此装备 §c' . $Damage_TXT . '§e 花费: ' . $text . '');
					$this->click[$player_name] = 1;
					return true;
				}
				else
				{
					$player->sendMessage('§4-> §6修复此装备 §c' . $Damage_TXT . '§e 需花费: ' . $text . '§6，再次点击确认!');
					$this->click[$player_name] = Time() + (int)$this->set->get('设置')['双击冷却秒数'];
				}
				return true;
			}
			//////////////////////////////
			/**********************
			回收商店
			**********************/
			//////////////////////////////
			if(isset($sign['回收商店']))
			{
				if(!isset($Enchant[$sign['回收商店']]))
				{
					$player->sendMessage('§4-> §c您手中所持的装备并非§e '. $this->getEnchantName($sign['回收商店']) . ' §c附魔!无法回收!');
					return true;
				}
				if($Enchant[$sign['回收商店']] < $sign['最低等级'])
				{
					$player->sendMessage('§4-> §c此商店回收§e' . $this->getEnchantName($sign['回收商店']) . '§c至少要§e' . $sign['最低等级'] . '§c级!');
					return true;
				}
				if($Enchant[$sign['回收商店']] > $sign['最高等级'])
				{
					$player->sendMessage('§4-> §c此商店回收§e' . $this->getEnchantName($sign['回收商店']) . '§c只能§e' . $sign['最高等级'] . '§c级以下!如果继续将以此价格回收!');
				}
				if($ItemInHand->getDamage() > $Damage)
				{
					$player->sendMessage('§4-> §c您手中所持的装备并耐久低于商店要求!无法回收!');
					return true;
				}
				if($this->click[$player_name] > Time())
				{
					if($sign['交换物'] == '金币')
					{
						$money = EconomyAPI::getInstance()->myMoney($player_name);
						EconomyAPI::getInstance()->setMoney($player, $money + $sign['交换量']);
					}
					else if($sign['交换物'] == '经验')
					{
						$Experience = @$player->getTotalXp();
						$setExperience = $Experience + $sign['交换量'];
						@$player->setTotalXp($player->getTotalXp() + $sign['交换量']);
					}
					else if($sign['交换物'] == '点券')
					{
						ZXDAConnector::addPlayerCoupons($player_name,$sign['交换量']);
					}
					else if($sign['交换物'] == '等级')
					{
						@$player->setXpLevel($player->getXpLevel() + $sign['交换量']);
					}
					else if($sign['交换物'] == '附魔券')
					{
						$fmq = $this->Money->get($player_name);
						$this->Money->set($player_name,$fmq + $sign['交换量']);
						$this->Money->save();
					}
					$player->getInventory()->setItemInHand(Item::get(Item::AIR, 0, 1));
					$player->sendMessage('§4-> §c已回收 附魔§b' . $Enchant[$sign['回收商店']] . '§c级§a' . $this->getEnchantName($sign['回收商店']) . ' §e获得: ' . $text . '');
					return true;
				}
				else
				{
					$player->sendMessage('§4-> §6回收附魔§b' . $Enchant[$sign['回收商店']] . '§c级§a' . $this->getEnchantName($sign['回收商店']) . ' §e装备将获得: ' . $text . '§6，再次点击确认!');
					$this->click[$player_name] = Time() + (int)$this->set->get('设置')['双击冷却秒数'];
				}
				return true;
			}
			//////////////////////////////
			/**********************
			强化商店
			**********************/
			//////////////////////////////
			if(isset($sign['强化商店']))
			{
				if(empty($Enchant))
				{
					$player->sendMessage('§4-> §c您手中所持的装备并未附魔!无法强化');
					return true;
				}
				if(!isset($Enchant[$sign['强化商店']]))
				{
					$player->sendMessage('§4-> §c您手中所持的装备并非§e '. $this->getEnchantName($sign['强化商店']) . ' §c附魔!无法强化');
					return true;
				}
				if($Enchant[$sign['强化商店']] + $sign['强化等级'] > $sign['强化上限'])
				{
					$player->sendMessage('§4-> §c您手中所持的装备强化后§e附魔等级§6超出此强化商店可强化上限!!');
					return true;
				}
				if($this->click[$player_name] > Time())
				{
					if($sign['交换物'] == '金币')
					{
						$money = EconomyAPI::getInstance()->myMoney($player_name);
						EconomyAPI::getInstance()->setMoney($player, $money - $sign['交换量']);
					}
					else if($sign['交换物'] == '经验')
					{
						$Experience = @$player->getTotalXp();
						$setExperience = $Experience - $sign['交换量'];
						@$player->setTotalXp($player->getTotalXp() - $sign['交换量']);
					}
					else if($sign['交换物'] == '点券')
					{
						ZXDAConnector::takePlayerCoupons($player_name,$sign['交换量']);
					}
					else if($sign['交换物'] == '等级')
					{
						@$player->setXpLevel($player->getXpLevel() - $sign['交换量']);
					}
					else if($sign['交换物'] == '附魔券')
					{
						$fmq = $this->Money->get($player_name);
						$this->Money->set($player_name,$fmq - $sign['交换量']);
						$this->Money->save();
					}
					if($sign['强化商店'] == 32)
					{
						if(isset($Enchant[32]) and isset($Enchant['镶嵌']))
						{
							$Enchant['镶嵌']['max'] += $sign['强化等级'];
							$Enchant[32] = $Enchant['镶嵌']['max'];
						}
					}
					$Enchant[$sign['强化商店']] += $sign['强化等级'];
					$nbt_id = $ItemInHand->getNamedTag()['display']['strings'];
					$this->Enchant->set($nbt_id,$Enchant);
					$this->Enchant->save();
					$ItemInHand->setDamage($Damage);
					$enchantment = Enchantment::getEnchantment((int)$sign['强化商店']);
					$enchantment->setLevel($Enchant[$sign['强化商店']]);
					$ItemInHand->addEnchantment($enchantment);
					$ItemInHand->setDamage($Damage);
					$player->getInventory()->setItemInHand($ItemInHand);
					$player->sendMessage('§4-> §c已强化 §b' . $sign['强化等级'] . '§c级§a' . $this->getEnchantName($sign['强化商店']) . '§a' . $Damage_TXT . '§e 花费: ' . $text . '');
					$this->click[$player_name] = 1;
					return true;
				}
				else
				{
					$player->sendMessage('§4-> §c强化 §b' . $sign['强化等级'] . '§c级§a' . $this->getEnchantName($sign['强化商店']) . '§a' . $Damage_TXT . '§e 花费: ' . $text . '§6，再次点击确认!');
					$this->click[$player_name] = Time() + (int)$this->set->get('设置')['双击冷却秒数'];
				}
				return;
			}
			//////////////////////////////
			/**********************
			出售商店
			**********************/
			//////////////////////////////
			if(isset($sign['出售商店']))
			{
				if($this->click[$player_name] > Time())
				{
					if($sign['交换物'] == '金币')
					{
						$money = EconomyAPI::getInstance()->myMoney($player_name);
						EconomyAPI::getInstance()->setMoney($player, $money - $sign['交换量']);
					}
					else if($sign['交换物'] == '经验')
					{
						$Experience = @$player->getTotalXp();
						$setExperience = $Experience - $sign['交换量'];
						@$player->setTotalXp($player->getTotalXp() - $sign['交换量']);
					}
					else if($sign['交换物'] == '点券')
					{
						ZXDAConnector::takePlayerCoupons($player_name,$sign['交换量']);
					}
					else if($sign['交换物'] == '等级')
					{
						@$player->setXpLevel($player->getXpLevel() - $sign['交换量']);
					}
					else if($sign['交换物'] == '附魔券')
					{
						$fmq = $this->Money->get($player_name);
						$this->Money->set($player_name,$fmq - $sign['交换量']);
						$this->Money->save();
					}
					$ItemInHand = Item::get($sign['装备'],0,1);
					$ItemInHand->setDamage($Damage);
					$enchantment = Enchantment::getEnchantment($sign['出售商店']);
					$enchantment->setLevel($sign['等级']);
					$ItemInHand->addEnchantment($enchantment);
					$player->getInventory()->addItem(clone $ItemInHand);
					$player->sendMessage('§4-> §c已购买附魔§b' . $sign['等级'] . '§c级§a' . $this->getEnchantName($sign['出售商店']) . '§a' . $Damage_TXT . '§e 花费: ' . $text . '');
					$this->click[$player_name] = 1;
					return true;
				}
				else
				{
					$player->sendMessage('§4-> §c购买附魔 §b' . $sign['等级'] . '§c级§a' . $this->getEnchantName($sign['出售商店']) . '§a' . $Damage_TXT . '§e 花费: ' . $text . '§6，再次点击确认!');
					$this->click[$player_name] = Time() + (int)$this->set->get('设置')['双击冷却秒数'];
				}
				return true;
			}
			if(isset($sign['附魔商店']))
			{
				if(!$this->getTrue($sign['附魔商店'],$ID))
				{
					$player->sendMessage('§b[§5Enchant_SIGN§b] §6您手中所持的装备§e不支持附魔'  . $this->getEnchantName($sign['附魔商店']));
					return true;
				}
			}
			if(isset($sign['限时商店']))
			{
				if(!$this->getTrue($sign['限时商店'],$ID))
				{
					$player->sendMessage('§b[§5Enchant_SIGN§b] §6您手中所持的装备§e不支持附魔' . $this->getEnchantName($sign['限时商店']));
					return true;
				}
			}
			//////////////////////////////
			/**********************
			附魔商店
			**********************/
			//////////////////////////////
			if(isset($sign['附魔商店']))
			{
				$texts ='';
				if(isset($ItemInHand->getNamedTag()['display']))
				{
					if(isset($ItemInHand->getNamedTag()['display']['strings']))
					{
						$nbt_id = $ItemInHand->getNamedTag()['display']['strings'];
						$texts = $this->Enchant->get($nbt_id);
						if(isset($texts[$sign['附魔商店']]))
						{
							if($texts[$sign['附魔商店']] == $sign['等级'])
							{
								$player->sendMessage('§4-> §c您手中所持的装备已附魔§6' . $sign['等级'] . '级§c的§6' . $this->getEnchantName($sign['附魔商店']) . '§c!');
								return true;
							}
							if(isset($texts['独立']) and $texts[$sign['附魔商店']] >= $sign['等级'])
							{
								$player->sendMessage('§4-> §c此物品已附魔的属性为独立属性!');
								return true;
							}
						}
					}
				}
				if(isset($Enchant[$sign['附魔商店']]) And $Enchant[$sign['附魔商店']] > $sign['等级'])
				{
					$player->sendMessage('§4-> §c您手中所持的装备§e附魔等级§6高于此附魔商店等级!!');
					return true;
				}
				if(isset($Enchant[$sign['附魔商店']]) And $Enchant[$sign['附魔商店']] == $sign['等级'])
				{
					$player->sendMessage('§4-> §c您手中所持的装备已附魔§6' . $Enchant[$sign['附魔商店']] . '级§c的§6' . $this->getEnchantName($sign['附魔商店']) . '§c!');
					return true;
				}
				if($sign['附魔商店'] >= 33 and $sign['附魔商店'] <= 43)
				{
					if($ItemInHand->getCount() > 1)
					{
						$player->sendMessage('§4-> §c只能一个宝石进行附魔!');
						return true;
					}
				}
				if($this->click[$player_name] > Time())
				{
					$ItemInHand->setDamage($Damage);
					$arrays = Array();
					$nbt_id = 0;
					if(isset($ItemInHand->getNamedTag()['display']['strings']))
					{
						$nbt_id = $ItemInHand->getNamedTag()['display']['strings'];
						if($this->Enchant->exists($nbt_id))
						{
							$arrays = $this->Enchant->get($nbt_id);
							$arrays[$sign['附魔商店']] = $sign['等级'];
						}
					}
					else
					{
						$nbt_id = $this->Enchant->get("Enchant") + 1;
						$arrays = Array($sign['附魔商店'] => $sign['等级']);
						$idarr = Array(28,29,30,31);
						if(in_array($sign['附魔商店'],$idarr))
						{
							$sign['附魔商店'] == 29 ? $arrays['指数'] = 5 : 1;
							$arrays['独立'] = True;
						}
						$this->Enchant->set('Enchant',$nbt_id);
					}
					if($sign['附魔商店'] == 32 and isset($arrays['镶嵌']))
					{
						$player->sendMessage('§4-> §c此装备已开通镶嵌功能!');
						return true;
					}
					if($sign['附魔商店'] == 32)
					{
						$arrays['镶嵌'] = Array('max' => $sign['等级']);
					}
					$this->Enchant->set($nbt_id,$arrays);
					$this->Enchant->save();
					$nbt = new CompoundTag("", [
						"display" => new CompoundTag("display", [
							"Name" => new StringTag("Name",$text),
							"strings" => new StringTag("strings",$nbt_id)
						])
					]);
					$ItemInHand->setNamedTag($nbt);
					foreach($Enchant as $k => $v)
					{
						$enchantment = Enchantment::getEnchantment($k);
						$enchantment->setLevel($v);
						$ItemInHand->addEnchantment($enchantment);
					}
					$enchantment = Enchantment::getEnchantment($sign['附魔商店']);
					$enchantment->setLevel($sign['等级']);
					$ItemInHand->addEnchantment($enchantment);
					$player->getInventory()->setItemInHand($ItemInHand);
					$player->sendMessage('§4-> §c已附魔§b' . $sign['等级'] . '§c级§a' . $this->getEnchantName($sign['附魔商店']) . '§a' . $Damage_TXT . '§e 花费: ' . $text . '');
					if($sign['交换物'] == '金币')
					{
						$money = EconomyAPI::getInstance()->myMoney($player_name);
						EconomyAPI::getInstance()->setMoney($player, $money - $sign['交换量']);
					}
					else if($sign['交换物'] == '经验')
					{
						$Experience = @$player->getTotalXp();
						$setExperience = $Experience - $sign['交换量'];
						@$player->setTotalXp($player->getTotalXp() - $sign['交换量']);
					}
					else if($sign['交换物'] == '点券')
					{
						ZXDAConnector::takePlayerCoupons($player_name,$sign['交换量']);
					}
					else if($sign['交换物'] == '等级')
					{
						@$player->setXpLevel($player->getXpLevel() - $sign['交换量']);
					}
					else if($sign['交换物'] == '附魔券')
					{
						$fmq = $this->Money->get($player_name);
						$this->Money->set($player_name,$fmq - $sign['交换量']);
						$this->Money->save();
					}
					$this->click[$player_name] = 1;
					return true;
				}
				else
				{
					$player->sendMessage('§4-> §c附魔 §b' . $sign['等级'] . '§c级§a' . $this->getEnchantName($sign['附魔商店']) . '!§e 花费: ' . $text . '§6，再次点击确认!');
					$this->click[$player_name] = Time() + (int)$this->set->get('设置')['双击冷却秒数'];
				}
				return;
			}
		}
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
  
	public function onSignChange(SignChangeEvent $event)
	{
		$block = $event->getBlock();
		$X = $block->getX();
		$Y = $block->getY();
		$Z = $block->getZ();
		$Level = $block->getLevel()->getName();
		$player = $event->getPlayer();
		if($event->getLine(0) == '一键修复')
		{
			if(!is_numeric($event->getLine(1)))
			{
				$player->sendMessage('§4-> §c第一行 : 一键修复');
				$player->sendMessage('§4-> §c第二行 : 价格');
				return true;
			}
			if(!$player->isOp())
			{
				$player->sendMessage('§4-> §c非OP不能创建!');
				return true;
			}
			$sign['一键修复'] = $event->getLine(1);
			$this->b->set($X . ':' . $Y . ':' . $Z , $sign);
			$this->b->save();
			$player->sendMessage('§4-> §c已创建一键修复商店!');
			$event->setLine(2,"§5".$event->getLine(1)."金币");
			$event->setLine(1,"§a-> §b修复商店 §a<-");
			$event->setLine(0,"");
			return true;
		}
		$sign_TEXT = explode(' ' , $event->getLine(0));
		$sign = Array();
		$sign_TEXTS = '';
		$Data = explode(':' , $event->getLine(3));
		$name = $player->getName();
		$Data[0] = strtolower($Data[0]);
		$SHOP_NAME = '';
		if(
		$event->getLine(0) == '修复' OR 
		$event->getLine(0) == '强化' OR 
		$event->getLine(0) == '附魔' OR 
		$event->getLine(0) == '回收' OR 
		$event->getLine(0) == '出售')
		{
			$contens = $this->set->get('设置')[$event->getLine(0)];
			$SHOP_NAME = $event->getLine(0);
		}
		else if(
		$sign_TEXT[0] == '修复' OR 
		$sign_TEXT[0] == '强化' OR 
		$sign_TEXT[0] == '附魔' OR 
		$sign_TEXT[0] == '回收' OR 
		$sign_TEXT[0] == '出售')
		{
			$contens = $this->set->get('设置')[$sign_TEXT[0]];
			$SHOP_NAME = $sign_TEXT[0];
		}
		else
		{
			return;
		}
		$set = $this->set->get('设置');
		if($set['白名单内成员才可创建商店'] == '开')
		{
			if(!in_Array($name,$set['白名单']))
			{
				$player->sendMessage('§4-> §c非白名单内成员不能创建附魔木牌!');
				return true;
			}
		}
		else
		{
			if($player->isOp() == False)
			{
				$player->sendMessage('§4-> §c非管理员不能创建附魔木牌!');
				return true;
			}
		}
		if(!isset($Data[0]) Or !isset($Data[1]) Or $Data[0] != 'x' And $Data[0] != 'm' And $Data[0] != 'd' And $Data[0] != 'l' And $Data[0] != 'w')
		{
			$player->sendMessage('§4-> §c[第四行]请设置正确的格式!');
			$player->sendMessage('§4-> §c[第四行]§6附魔交换物:');
			$player->sendMessage('§4-> §e[ X 或 x] 经验交易');
			$player->sendMessage('§4-> §e[ L 或 l] 等级交易');
			$player->sendMessage('§4-> §e[ M 或 m] 金钱交易');
			$player->sendMessage('§4-> §e[ D 或 d] 点券交易');
			$player->sendMessage('§4-> §e[ W 或 w] 独立经济交易');
			return true;
		}
		if(!is_numeric($Data[1]))
		{
			$player->sendMessage('§4-> §c[第四行]§6附魔交换量应是数字!');
			return true;
		}
		$Data[0] == 'm' ? $TXT = '金币' : $a = '';/*金币*/
		$Data[0] == 'x' ? $TXT = '经验' : $a = '';/*经验*/
		$Data[0] == 'd' ? $TXT = '点券' : $a = '';/*点券*/
		$Data[0] == 'l' ? $TXT = '等级' : $a = '';/*等级*/
		$Data[0] == 'w' ? $TXT = '附魔券' : $a = '';/*附魔券*/
		if(isset($sign_TEXT[1]))
		{
			$Datas1 = explode('%+' , $sign_TEXT[1]);
			$Datas2 = explode('%-' , $sign_TEXT[1]);
			$Datas3 = explode('%' , $sign_TEXT[1]);
			$Datas4 = explode('+' , $sign_TEXT[1]);
			$Datas5 = explode('-' , $sign_TEXT[1]);
			if(isset($Datas1[1]))
			{
				$sign['加百分比耐久'] = $Datas1[1];
				$sign_TEXTS = '§3耐久加' . $Datas1[1] . '%';
				if($SHOP_NAME == '回收'){$player->sendMessage('§4-> §c[第四行]§6回收商店不能加耐久!');return true;}
			}
			else if(isset($Datas2[1]))
			{
				$sign['减百分比耐久'] = $Datas2[1];
				$sign_TEXTS = '§3耐久减' . $Datas2[1] . '%';
				if($SHOP_NAME == '修复'){$player->sendMessage('§4-> §c[第四行]§6修复商店不能扣除耐久!');return true;}
				if($SHOP_NAME == '回收'){$player->sendMessage('§4-> §c[第四行]§6回收商店不能扣除耐久!');return true;}
			}
			else if(isset($Datas3[1]))
			{
				$sign['百分比耐久'] = $Datas3[1];
				$sign_TEXTS = '§3' . $Datas3[1] . '%耐久';
			}
			else if(isset($Datas4[1]))
			{
				$sign['加耐久'] = $Datas4[1];
				$sign_TEXTS = '§3加' . $Datas4[1] . '点耐久';
				if($SHOP_NAME == '回收'){$player->sendMessage('§4-> §c[第四行]§6回收商店不能加耐久!');return true;}
			}
			else if(isset($Datas5[1]))
			{
				$sign['减耐久'] = $Datas5[1];
				$sign_TEXTS = '§3减' . $Datas5[1] . '耐久';
				if($SHOP_NAME == '修复'){$player->sendMessage('§4-> §c[第四行]§6修复商店不能扣除耐久!');return true;}
				if($SHOP_NAME == '回收'){$player->sendMessage('§4-> §c[第四行]§6回收商店不能扣除耐久!');return true;}
			}
			else
			{
				$sign['耐久'] = $sign_TEXT[1];
				$sign_TEXTS = '§3' . $sign_TEXT[1] . '点耐久';
			}
		}
		if($event->getLine(1) < -1 or $event->getLine(1) > 50)
		{
			$player->sendMessage('§4-> §c[第二行]附魔ID应在0至50之间!');
			return true;
		}
		if(!is_numeric($event->getLine(1)))
		{
			$player->sendMessage('§4-> §c[第二行]附魔ID应是数字!');
			return true;
		}
		$Data_LV = explode(':' , $event->getLine(2));
		$TEST = $this->set->get('设置')[$SHOP_NAME];
		$IDS = array('@ID' => $this->getEnchantName($event->getLine(1)));
		$LVS = array('@LV' => $event->getLine(2));
		$Amount = array('@Amount' => @$Data[1]);
		$MC = array('@MC' => $this->set->get('设置')[$TXT . '名称']);
		$TSS = array('@TS' => $this->getEnchantXG($event->getLine(0)));
		$DAS = array('@DA' => $sign_TEXTS);
		$MIXS = 1;
		$MAXS = 2;
		if($SHOP_NAME == '修复' OR $SHOP_NAME == '回收' OR $SHOP_NAME == '强化')
		{
			$MIXS = array('@MIX' => $Data_LV[0]);
			$MAXS = array('@MAX' => $Data_LV[1]);
		}
		if($SHOP_NAME == '出售')
		{
			$Item = array('@Item' => $this->getIDTrue($event->getLine(1),$Data_LV[1]));
			$LVS = array('@LV' => $Data_LV[0]);
		}
		if(strstr($TEST[0] , '@ID')){$TEST[0] = strtr($TEST[0] , $IDS);}
		if(strstr($TEST[1] , '@ID')){$TEST[1] = strtr($TEST[1] , $IDS);}
		if(strstr($TEST[2] , '@ID')){$TEST[2] = strtr($TEST[2] , $IDS);}
		if(strstr($TEST[3] , '@ID')){$TEST[3] = strtr($TEST[3] , $IDS);}
		if(strstr($TEST[0] , '@LV')){$TEST[0] = strtr($TEST[0] , $LVS);}
		if(strstr($TEST[1] , '@LV')){$TEST[1] = strtr($TEST[1] , $LVS);}
		if(strstr($TEST[2] , '@LV')){$TEST[2] = strtr($TEST[2] , $LVS);}
		if(strstr($TEST[3] , '@LV')){$TEST[3] = strtr($TEST[3] , $LVS);}
		if(strstr($TEST[0] , '@MC')){$TEST[0] = strtr($TEST[0] , $MC);}
		if(strstr($TEST[1] , '@MC')){$TEST[1] = strtr($TEST[1] , $MC);}
		if(strstr($TEST[2] , '@MC')){$TEST[2] = strtr($TEST[2] , $MC);}
		if(strstr($TEST[3] , '@MC')){$TEST[3] = strtr($TEST[3] , $MC);}
		if(strstr($TEST[0] , '@Amount')){$TEST[0] = strtr($TEST[0] , $Amount);}
		if(strstr($TEST[1] , '@Amount')){$TEST[1] = strtr($TEST[1] , $Amount);}
		if(strstr($TEST[2] , '@Amount')){$TEST[2] = strtr($TEST[2] , $Amount);}
		if(strstr($TEST[3] , '@Amount')){$TEST[3] = strtr($TEST[3] , $Amount);}
		if(strstr($TEST[0] , '@TS')){$TEST[0] = strtr($TEST[0] , $TSS);}
		if(strstr($TEST[1] , '@TS')){$TEST[1] = strtr($TEST[1] , $TSS);}
		if(strstr($TEST[2] , '@TS')){$TEST[2] = strtr($TEST[2] , $TSS);}
		if(strstr($TEST[3] , '@TS')){$TEST[3] = strtr($TEST[3] , $TSS);}
		if(strstr($TEST[0] , '@DA')){$TEST[0] = strtr($TEST[0] , $DAS);}
		if(strstr($TEST[1] , '@DA')){$TEST[1] = strtr($TEST[1] , $DAS);}
		if(strstr($TEST[2] , '@DA')){$TEST[2] = strtr($TEST[2] , $DAS);}
		if(strstr($TEST[3] , '@DA')){$TEST[3] = strtr($TEST[3] , $DAS);}
		if(strstr($TEST[0] , '@MIX')){$TEST[0] = strtr($TEST[0] , $MIXS);}
		if(strstr($TEST[1] , '@MIX')){$TEST[1] = strtr($TEST[1] , $MIXS);}
		if(strstr($TEST[2] , '@MIX')){$TEST[2] = strtr($TEST[2] , $MIXS);}
		if(strstr($TEST[3] , '@MIX')){$TEST[3] = strtr($TEST[3] , $MIXS);}
		if(strstr($TEST[0] , '@MAX')){$TEST[0] = strtr($TEST[0] , $MAXS);}
		if(strstr($TEST[1] , '@MAX')){$TEST[1] = strtr($TEST[1] , $MAXS);}
		if(strstr($TEST[2] , '@MAX')){$TEST[2] = strtr($TEST[2] , $MAXS);}
		if(strstr($TEST[3] , '@MAX')){$TEST[3] = strtr($TEST[3] , $MAXS);}
		///////////////////////
		/*	修复商店
			修复 耐久
			附魔ID
			最低等级:最高等级
			交换物
		*/
		///////////////////////
		if($SHOP_NAME == '修复')
		{
			if(!isset($Data_LV[0]) Or !isset($Data_LV[1]))
			{
				$player->sendMessage('§4-> §c[第三行]请设置正确的格式["[最低等级:最高等级]"]!');
				return true;
			}
			if(!is_numeric($Data_LV[0]))
			{
				$player->sendMessage('§4-> §c[第三行]附魔最低等级应是数字!');
				return true;
			}
			if(!is_numeric($Data_LV[1]))
			{
				$player->sendMessage('§4-> §c[第三行]附魔最高等级应是数字!');
				return true;
			}
			if($Data_LV[0] > $Data_LV[1])
			{
				$player->sendMessage('§4-> §c[第三行]附魔最低等级不能大于最高等级!');
				return true;
			}
			$player->sendMessage('§4-> §c已创建一个§5|§b附魔ID[§1'.$event->getLine(1).'§b] §e最低等级[§1'.$Data_LV[0].'§e] §5最高等级[§1'.$Data_LV[1].'§5] §2回收价格[§1'.$Data[1].'§2]§6 的修复商店!');
			$event->setLine(0,$event->getLine(1));
			$sign['最低等级'] = $Data_LV[0];
			$sign['最高等级'] = $Data_LV[1];
			$sign['交换物'] = $TXT;
			$sign['交换量'] = $Data[1];
			$sign['修复商店'] = $event->getLine(0);
			$event->setLine(0,$TEST[0]);
			$event->setLine(1,$TEST[1]);
			$event->setLine(2,$TEST[2]);
			$event->setLine(3,$TEST[3]);
			$this->b->set($X . ':' . $Y . ':' . $Z , $sign);
			$this->b->save();
			return;
		}
		///////////////////////
		/*	回收商店
			回收 耐久
			附魔ID
			最低等级:最高等级
			交换物
		*/
		///////////////////////
		if($SHOP_NAME == '回收')
		{
			if(!isset($Data_LV[0]) Or !isset($Data_LV[1]))
			{
				$player->sendMessage('§4-> §c[第三行]请设置正确的格式["[最低等级:最高等级]"]!');
				return true;
			}
			if(!is_numeric($Data_LV[0]))
			{
				$player->sendMessage('§4-> §c[第三行]附魔最低等级应是数字!');
				return true;
			}
			if(!is_numeric($Data_LV[1]))
			{
				$player->sendMessage('§4-> §c[第三行]附魔最高等级应是数字!');
				return true;
			}
			if($Data_LV[0] > $Data_LV[1])
			{
				$player->sendMessage('§4-> §c[第三行]附魔最低等级不能大于最高等级!');
				return true;
			}
			$player->sendMessage('§4-> §c已创建一个§5|§b附魔ID[§1'.$event->getLine(1).'§b] §e最低等级[§1'.$Data_LV[0].'§e] §5最高等级[§1'.$Data_LV[1].'§5] §2回收价格[§1'.$Data[1].'§2]§6 的回收商店!');
			$event->setLine(0,(int)$event->getLine(1));
			$sign['最低等级'] = $Data_LV[0];
			$sign['最高等级'] = $Data_LV[1];
			$sign['交换物'] = $TXT;
			$sign['交换量'] = $Data[1];
			$sign['回收商店'] = $event->getLine(0);
			$event->setLine(0,$TEST[0]);
			$event->setLine(1,$TEST[1]);
			$event->setLine(2,$TEST[2]);
			$event->setLine(3,$TEST[3]);
			$this->b->set($X . ':' . $Y . ':' . $Z , $sign);
			$this->b->save();
			return;
		}
		///////////////////////
		/*	强化商店
			强化 耐久
			附魔ID
			每次提升的等级:等级上限
			交换物
		*/
		///////////////////////
		if($SHOP_NAME == '强化')
		{
			$Data_LV = explode(':' , $event->getLine(2));
			if(!isset($Data_LV[0]) Or !isset($Data_LV[1]))
			{
				$player->sendMessage('§4-> §c[第三行]请设置正确的格式["[每次强化的等级:强化上限]"]!');
				$event->setCancelled();
				return true;
			}
			if(!is_numeric($Data_LV[0]))
			{
				$player->sendMessage('§4-> §c[第三行]附魔每次强化的等级应是数字!');
				return true;
			}
			if(!is_numeric($Data_LV[1]))
			{
				$player->sendMessage('§4-> §c[第三行]附魔强化上限应是数字!');
				return true;
			}
			if($Data_LV[0] > $Data_LV[1])
			{
				$player->sendMessage('§4-> §c[第三行]附魔每次强化的等级不能大于强化上限!');
				return true;
			}
			$player->sendMessage('§4-> §c已创建一个§5|§b附魔ID[§1'.$event->getLine(1).'§b] §e每次强化的等级[§1'.$Data_LV[0].'§e] §5强化上限[§1'.$Data_LV[1].'§5] §2回收价格[§1'.$Data[1].'§2]§6 的强化商店!');
			$event->setLine(0,(int)$event->getLine(1));
			$sign['强化等级'] = $Data_LV[0];
			$sign['强化上限'] = $Data_LV[1];
			$sign['交换物'] = $TXT;
			$sign['交换量'] = $Data[1];
			$sign['强化商店'] = $event->getLine(0);
			$event->setLine(0,$TEST[0]);
			$event->setLine(1,$TEST[1]);
			$event->setLine(2,$TEST[2]);
			$event->setLine(3,$TEST[3]);
			$this->b->set($X . ':' . $Y . ':' . $Z , $sign);
			$this->b->save();
			return;
		}
		///////////////////////
		/*	附魔商店
			附魔 耐久
			附魔ID
			附魔LV
			交换物
		*/
		///////////////////////
		if($SHOP_NAME == '附魔')
		{
			if(!is_numeric($event->getLine(2)))
			{
				$player->sendMessage('§4-> §c[第三行]附魔等级应是数字!');
				return true;
			}
			$player->sendMessage('§4-> §c已创建一个§5|§b附魔ID[§1'.$event->getLine(1).'§b] §e附魔等级[§1'.$event->getLine(2).'§e] §2附魔价格[§1'.$Data[1].'§2]§6 的附魔商店!');
			$event->setLine(0,(int)$event->getLine(1));
			$sign['等级'] = $event->getLine(2);
			$sign['交换物'] = $TXT;
			$sign['交换量'] = $Data[1];
			$sign['附魔商店'] = $event->getLine(0);
			$event->setLine(0,$TEST[0]);
			$event->setLine(1,$TEST[1]);
			$event->setLine(2,$TEST[2]);
			$event->setLine(3,$TEST[3]);
			$this->b->set($X . ':' . $Y . ':' . $Z , $sign);
			$this->b->save();
			return;
		}
		///////////////////////
		/*	出售商店
			出售 耐久
			附魔ID
			附魔LV
			交换物
		*/
		///////////////////////
		if($SHOP_NAME == '出售')
		{
			$Data_LV = explode(':' , $event->getLine(2));
			if(!isset($Data_LV[0]) Or !isset($Data_LV[1]))
			{
				$player->sendMessage('§4-> §c[第三行]请设置正确的格式["[附魔LV:物品ID]"]!');
				$event->setCancelled();
				return true;
			}
			if($this->getIDTrue($Data_LV[1]) === False)
			{
				$player->sendMessage('§4-> §c' . $Data_LV[1] . '物品不支持附魔' . $this->getEnchantName($event->getLine(1)) . '!');
				$event->setCancelled();
				return true;
			}
			$player->sendMessage('§4-> §c已创建一个§5|§b附魔ID[§1'.$event->getLine(1).'§b] §e附魔等级[§1'.$event->getLine(2).'§e] §2附魔价格[§1'.$Data[1].'§2]§6 的出售商店!');
			$event->setLine(0,(int)$event->getLine(1));
			$sign['等级'] = $Data_LV[0];
			$sign['交换物'] = $TXT;
			$sign['交换量'] = $Data[1];
			$sign['装备'] = $Data_LV[1];
			$sign['出售商店'] = $event->getLine(0);
			$event->setLine(0,$TEST[0]);
			$event->setLine(1,$TEST[1]);
			$event->setLine(2,$TEST[2]);
			$event->setLine(3,$TEST[3]);
			$this->b->set($X . ':' . $Y . ':' . $Z , $sign);
			$this->b->save();
			return;
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
			$Damager_Player = $event->getDamager();
			$Entity_Player = $event->getEntity();
			$Note = Array();
			$set = $this->set->get('设置');
			if(!$event->isCancelled())
			{
				if($Damager_Player instanceof Player)
				{
					$Enchant = Array();
					$x_d = $Damager_Player->x;
					$y_d = $Damager_Player->y;
					$z_d = $Damager_Player->z;
					$x_e = $Entity_Player->x;
					$y_e = $Entity_Player->y;
					$z_e = $Entity_Player->z;
					$name_info = $this->info[$Damager_Player->getName()];
					$ItemInHand = $Damager_Player->getInventory()->getItemInHand();
					$Id = $ItemInHand->getId();
					if($Id == 261)
					{
						if(isset($this->gj['弓箭']))
						{
							if(isset($this->gj['弓箭']['火焰附加']) and isset($this->gj['弓箭']['火焰附加'][$Damager_Player->getName()]))
							{
								$event->getEntity()->setOnFire($this->gj['弓箭']['火焰附加'][$Damager_Player->getName()] * 0.5);
								unset($this->gj['弓箭']['火焰附加'][$Damager_Player->getName()]);
							}
							if(isset($this->gj['弓箭']['力量']) and isset($this->gj['弓箭']['力量'][$Damager_Player->getName()]))
							{
								$event->setDamage($event->getDamage() + $this->gj['弓箭']['力量'][$Damager_Player->getName()]);
								unset($this->gj['弓箭']['力量'][$Damager_Player->getName()]);
							}
							if(isset($this->gj['弓箭']['无限']) and isset($this->gj['弓箭']['无限'][$Damager_Player->getName()]))
							{
								$Damager_Player->getInventory()->addItem(new Item(262,0,1));
								unset($this->gj['弓箭']['无限'][$Damager_Player->getName()]);
							}
						}
					}
					$damage_mas = $event->getDamage() / 100;
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
					if(isset($Enchant[32]))//RPG
					{
						if(isset($Enchant['镶嵌']))
						{
							if(isset($Enchant['镶嵌'][33]))
							{
								$event->setDamage($event->getDamage() + $Enchant['镶嵌'][33]);
								if($Id > 298 and $Id < 317)
								{
									$ItemInHand->setDamage($ItemInHand->getDamage() - 1);
									$Damager_Player->getInventory()->setItemInHand($ItemInHand);
								}
							}
						}
					}
					if(isset($Enchant[9]))//锋利
					{
						$Enchant[9] < 30 ? $event->setDamage($event->getDamage() + ($Enchant[9] * 1.252)) : $event->setDamage($Enchant[9] * 1.25);
					}
					if(isset($Enchant[10]))
					{
						$Enchant[10] < 30 ? $event->setDamage($event->getDamage() + ($Enchant[10] * 1.252)) : $event->setDamage($Enchant[10] * 1.25);
					}
					if(isset($Enchant[11]))
					{
						$Enchant[11] < 30 ? $event->setDamage($event->getDamage() + ($Enchant[11] * 1.252)) : $event->setDamage($Enchant[11] * 1.25);
					}
					if(isset($Enchant[12]))//击退
					{
						$deltaX = $Entity_Player->x - $Damager_Player->x;
						$deltaZ = $Entity_Player->z - $Damager_Player->z;
						$yaw = \atan2($deltaX, $deltaZ);
						$Entity_Player->knockBack($Damager_Player,0,\sin($yaw),\cos($yaw),$Enchant[12] * 0.5);
					}
					if(isset($Enchant[13]))//火焰附加
					{
						$event->getEntity()->setOnFire($Enchant[13] * 0.5);
					}
					if(isset($Enchant[20]))//冲击
					{
						$deltaX = $Entity_Player->x - $Damager_Player->x;
						$deltaZ = $Entity_Player->z - $Damager_Player->z;
						$yaw = \atan2($deltaX, $deltaZ);
						$Entity_Player->knockBack($Damager_Player,0,\sin($yaw),\cos($yaw),$Enchant[20] * 0.5);
					}
					if(isset($Enchant[25]))//嗜血
					{
						$rand = 5 + ($Enchant[25] * 0.5);
						if(mt_rand(1,100) <= $rand)
						{
							$add_health = ($damage_mas * 25) + (($Enchant[25] % 10) * $damage_mas);
							$Damager_Player->setHealth($Damager_Player->getHealth() + $add_health);
						}
					}
					foreach($Damager_Player->getLevel()->getEntities() as $e)
					{
						if(!$e instanceof Player)
						{
							if(isset($Enchant[26]))//刃气
							{
								if($e->getGamemode() == 0)
								{
									$block_fanwei = 3 + ($Enchant[26] * 0.25);
									if($e->distance(new Vector3($Damager_Player->x,$Damager_Player->y,$Damager_Player->z)) <= $block_fanwei)
									{
										if($e !== $Damager_Player And $e !== $Entity_Player)
										{
											$del_health = $damage_mas * 35;
											$Damager_Player->getLevel()->addParticle(new ExplodeParticle(new Vector3($e->x, $e->y,$e->z)));
											$e->addEffect(Effect::getEffect(7)->setAmplifier(0)->setDuration(1)->setVisible(false));
											$e->setHealth($e->getHealth() - $del_health);
										}
									}
								}
							}
						}
					}
					if(isset($Enchant[27]))//血刃
					{
						$Health = (($Entity_Player->getHealth()) / 100) * (($Enchant[27] / 10) + 1);
						$rand = 5 + ($Enchant[27] * 0.25);
						if(mt_rand(1,100) < $rand)
						{
							$event->setDamage($event->getDamage() + $Health);
						}
					}
					$event->setDamage($event->getDamage() + $name_info['物攻']);
					if(mt_rand(1,100) <= $name_info['暴击'])
					{
						$Note['暴击'] = $event->getDamage();
						$event->setDamage($event->getDamage() * 2);
						for($i = 0; $i < 16 ;$i ++)
						{
							$Entity_Player->getLevel()->addParticle(new CriticalParticle(new Vector3($x_e + mt_rand(-1,1),$y_e + mt_rand(-1,1),$z_e + mt_rand(-1,1))));
						}
					}
				}
			}
			if($Entity_Player instanceof Player)
			{
				if(!$event->isCancelled())
				{
					$Enchant = $this->getAllEnction($Entity_Player);
					$boo = Array('头附魔','胸附魔','裤附魔','鞋附魔');
					for($a = 0; $a < 4; $a ++)
					{
						if(isset($Enchant[$boo[$a]][0]))//保护
						{
							$event->setDamage($event->getDamage() - ($Enchant[$boo[$a]][0] * 0.04));
						}
						if(isset($Enchant[$boo[$a]][1]))//火焰保护
						{
							if($Entity_Player->fireTicks > 0)
							{
								$Entity_Player->setOnFire($Entity_Player->fireTicks - ($Enchant[$boo[$a]][1] * 0.4));
							}
						}
						if(isset($Enchant[$boo[$a]][5]))//荆棘
						{
							$Damager_Player->addEffect(Effect::getEffect(7)->setAmplifier(0)->setDuration(1)->setVisible(false));
							$Damager_Player->setHealth($Damager_Player->getHealth() - (($event->getDamage() / 2) + $Enchant[$boo[$a]][5]));
						}
					}
					$name_info = $this->info[$Entity_Player->getName()];
					if(count($name_info['Note']) > 0)
					{
						if(isset($name_info['Note']['治疗汤']))
						{
							unset($this->info[$Entity_Player->getName()]['Note']['治疗汤']);
							$Entity_Player->sendMessage("§b＊§6治疗汤§b＊ §e你受到[".$Damager_Player->getName()."]攻击伤害被效果已失效!");
						}
						if(isset($Note['暴击']))
						{
							$event->setDamage($event->getDamage() - $name_info['抗暴']);
						}
					}
					$BUFF = Array();
					foreach($Enchant as $k => $v)
					{
						if(isset($v[29]))//凝魂之泪
						{
							if(!isset($BUFF[29]))
							{	
								if($event->getDamage() >= $v[29] * 10)
								{
									$string = $ItemInHand->getNamedTag()['display']['strings'];
									$BUFF[29] = True;
									$v['指数'] -= 1;
									$en = $v[29] * 30 >= $event->getDamage() ? $event->getDamage() : $v[29] * 30;
									$Entity_Player->sendMessage("§b＊§6凝魂之泪§b＊ §e你受到[§c".$event->getDamage()."点§e]伤害,已抵挡[§c". $en ."点§e],指数剩余[§c".$v['指数']."§e]点!");
									$Damager_Player->sendMessage("§b＊§6凝魂之泪§b＊ §e你的伤害被抵消§c".$en."§e点!");
									if($v['指数'] == 0)
									{
										$Entity_Player->sendMessage("§8＊§9物品§8＊ §e=》§7[凝魂之泪]已耗尽!");
										$Entity_Player->getInventory()->setItem($index,Item::get(Item::AIR, 0, 1));
										$this->Enchant->remove($string);
									}
									$this->Enchant->set($string,$v);
									$this->Enchant->save();
									$da = $event->getDamage() - ($v[29] * 30);
									$da <= 0 ? $da = 1 : [];
									$event->setDamage($da);
								}
							}
						}	
					}
				}
			}
			if($Entity_Player instanceof Player and $Damager_Player instanceof Player)
			{
				$item = $Damager_Player->getInventory()->getItemInHand();
				$name = $Entity_Player->getName();
				$name_D = $Damager_Player->getName();
				$name_info_D = $this->info[$name_D];
				$x_d = $Damager_Player->x;
				$y_d = $Damager_Player->y;
				$z_d = $Damager_Player->z;
				$x_e = $Entity_Player->x;
				$y_e = $Entity_Player->y;
				$z_e = $Entity_Player->z;
				if(isset($item->getNamedTag()['display']['strings']))
				{
					$nbt_id = $item->getNamedTag()['display']['strings'];
					$texts = $this->Enchant->get($nbt_id);
					if(isset($texts[30]))
					{
						$event->setCancelled();
						if($this->info[$name]['魔法'] == $this->info[$name]['魔法上限'] * (@$Entity_Player->getXpLevel() / 2))
						{
							$Damager_Player->sendMessage("§b＊§6魔法苹果§b＊ §e[".$name."]§6的魔法值处于巅峰,无需回复!");
						}
						else
						{
							$this->info[$name]['魔法'] += ($texts[30] * 10);
							if($this->info[$name]['魔法'] > $this->info[$name]['魔法上限'] * (@$Entity_Player->getXpLevel() / 2))
							{
								$this->info[$name]['魔法'] = $this->info[$name]['魔法上限'] * (@$Entity_Player->getXpLevel() / 2);
							}
							$Damager_Player->sendMessage("§b＊§6魔法苹果§b＊ §e[".$name."]§6的魔法值已回复§e" . $texts[30] * 10 . "§6点!");
							$Entity_Player->sendMessage("§b＊§6魔法苹果§b＊ §e[".$name_D."]§6为你回复§e" . $texts[30] * 10 . "§6点魔法值!");
							$item->setCount($item->getCount() - 1);
							$Damager_Player->getInventory()->setItemInHand($item); 
						}
					}
				}
			}
			if($event->getDamage() < 0)
			{
				$event->setDamage(0);
			}
		}
	}

	public function onPlayerBreakBlock(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		if(!$event->isCancelled())
		{
			$ItemInHand = $player->getInventory()->getItemInHand();
			$Enchant_Id = Array();
			$Enchant_Lv = Array();
			if(!empty($ItemInHand->getEnchantments()))
			{
				foreach($ItemInHand->getEnchantments() as $enchantment)
				{
					$Enchant_Id[] = $enchantment->getId();
					$Enchant_Lv[$enchantment->getId()] = $enchantment->getLevel();
				}
				if(in_array(17,$Enchant_Id))
				{
					if(mt_rand(1,100) < $Enchant_Lv[17])
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
					$IDS = Array(16,56,73,153,21);
					if(!in_array($ID,$IDS))
					{
						return;
					}
					$ID == 16 ? $ID = 263 : $a = '';
					$ID == 56 ? $ID = 264 : $a = '';
					$ID == 73 ? $ID = 331 : $a = '';
					$ID == 153 ? $ID = 406 : $a = '';
					$ID == 21 ? $ID = 351 : $a = '';
					$numbers = $Enchant_Lv[$enchantment->getId()];
					$numbers = ($numbers - mt_rand(0,$numbers)) - $numbers / 2;
					$numbers < 0 ? $numbers = 0 : [];
					$Level->dropItem(new Vector3($X,$Y,$Z),new Item($ID,0,$numbers));
				}
			}
		}
		if($event->getBlock()->getID() == 323 or $event->getBlock()->getID() == 63 or $event->getBlock()->getID() == 68)
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

	public function itemDamage($ID)
	{
		$TXT = 0;
		switch($ID){

		case '268':
		case '269':
		case '270':
		case '271':
		case '290':
			$TXT = 60;
		break;

		case '291':
		case '272':
		case '273':
		case '274':
		case '275':
			$TXT = 132;
		break;

		case '257':
		case '258':
		case '256':
		case '267':
		case '292':
			$TXT =  261;
		break;

		case '285':
		case '286':
		case '284':
		case '283':
		case '294':
			$TXT = 33;
		break;

		case '278':
		case '279':
		case '277':
		case '276':
		case '293':
			$TXT = 1562;
		break;

		case '346':
			$TXT = 65;
		break;

		case ' 261':
			$TXT = 385;
		case '298':
			$TXT = 56;
		break;
		case '299':
			$TXT = 81;
		break;
		case '300':
			$TXT = 76;
		break;
		case '301':
			$TXT = 144;
		break;
		case '302':
			$TXT = 166;
		break;
		case '303':
			$TXT = 241;
		break;
		case '304':
			$TXT = 226;
		break;
		case '305':
			$TXT = 144;
		break;
		case '306':
			$TXT = 116;
		break;
		case '307':
			$TXT = 241;
		break;
		case '308':
			$TXT = 226;
		break;
		case '309':
			$TXT = 144;
		break;
		case '310':
			$TXT = 364;
		break;
		case '311':
			$TXT = 529;
		break;
		case '312':
			$TXT = 496;
		break;
		case '313':
			$TXT = 114;
		break;
		case '314':
			$TXT = 78;
		break;
		case '315':
			$TXT = 113;
		break;
		case '316':
			$TXT = 106;
		break;
		case '317':
			$TXT = 144;
		break;
		}
		return $TXT;
	}

	public function getEnchantXG($ID)
	{
		$array = Array(
			0 => '§6增加装备保护能力',
			1 => '§6减少玩家着火时间',
			2 => '§6减少玩家的摔落伤害',
			3 => '§6减少玩家的爆炸伤害',
			4 => '§6减少弹射物对玩家的伤害',
			5 => '§6对攻击玩家的生物造成伤害',
			6 => '§6延长在水下呼吸的时间',
			7 => '§6在水下走的更快',
			8 => '§6在水下挖东西更快',
			9 => '§6增加剑对生物的伤害',
			10 => '§6增加剑对亡灵生物的伤害',
			11 => '§6增加剑对截肢生物的伤害',
			12 => '§6增加攻击生物时击退的距离',
			13 => '§6攻击生物同时让他着火',
			14 => '§6杀死生物后令其掉落物增加',
			15 => '§6让镐子挖方块更快',
			16 => '§6挖方块时掉落原方块',
			17 => '§6让所有装备和武器更耐久',
			18 => '§6让镐子挖矿物时掉落更多矿物',
			19 => '§6增加弓对生物的伤害',
			20 => '§6增加弓对生物的击退',
			21 => '§6让弓射出火箭',
			22 => '§6让弓在射箭时不消耗箭',
			23 => '§6钓鱼时更容易吊到其他东西',
			24 => '§6钓鱼时更容易钓到鱼',
			25 => '§6造成伤害同时嗜取血量',
			26 => '§6对周围玩家造成伤害',
			27 => '§6造成最大生命的额外伤害',
			28 => '§6人物等级+1',
			29 => '§6抵消更高的伤害',
			30 => '§6食用后回复相对的魔法值',
			31 => '§6在指定时间内恢复指定生命',
			32 => '§6让部分装备具有镶嵌宝石的功能',
			33 => '§6镶嵌入装备增加物攻上限',
			34 => '§6镶嵌入装备增加物防上限',
			35 => '§6镶嵌入装备增加血量上限',
			36 => '§6镶嵌入装备增加暴击上限',
			37 => '§e此属性已被移除',
			38 => '§e此属性已被移除',
			39 => '§6镶嵌入装备增加抗暴上限',
			40 => '§e此属性已被移除',
			41 => '§6镶嵌入装备增加魔攻上限',
			42 => '§6镶嵌入装备增加魔防上限',
			43 => '§6镶嵌入装备增加魔法上限',
		);
		if(isset($array[$ID]))
		{
			return $array[$ID];
		}
		return False;
	}

	public function getEnchantName($ID)
	{
		$arrays = Array(
			0 => '保护',
			1 => '火焰保护',
			2 => '摔落保护',
			3 => '爆炸保护',
			4 => '弹射物保护',
			5 => '荆棘',
			6 => '水下呼吸',
			7 => '深海探索者',
			8 => '水下速掘',
			9 => '锋利',
			10 => '亡灵杀手',
			11 => '截肢杀手',
			12 => '击退',
			13 => '火焰附加',
			14 => '抢夺',
			15 => '效率',
			16 => '精准采集',
			17 => '耐久',
			18 => '时运',
			19 => '力量',
			20 => '冲击',
			21 => '火矢',
			22 => '无限',
			23 => '海之眷顾',
			24 => '钓饵',

			25 => '嗜血',
			26 => '刃气',
			27 => '血刃',
			28 => '知识之书',
			29 => '凝魂之泪',
			30 => '魔法苹果',
			31 => '治疗汤',
			32 => 'RPG扩展架',
			
			33 => '物攻宝石',
			34 => '物防宝石',
			35 => '气血宝石',
			36 => '暴击宝石',
			37 => '格挡宝石',
			38 => '闪避宝石',
			39 => '抗暴宝石',
			40 => '命中宝石',
			41 => '魔防宝石',
			42 => '魔攻宝石',
			43 => '魔法宝石',
		);
		if(isset($arrays[$ID]))
		{
			return $arrays[$ID];
		}
		return False;
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
		if(isset($txt[$Money]))
		{
			return $txt[$Money];
		}
		return False;
	}
	
	public function getIDTrue($s)
	{
		$tk = Array(
			298 => '皮革帽子',302 => '链甲帽子',306 => '铁头盔',310 => '钻石头盔',314 => '金头盔',
			299 => '皮革上衣',303 => '锁链胸甲',307 => '铁胸甲',311 => '钻石胸甲',315 => '金胸甲',
			300 => '皮革裤子',304 => '锁链裤子',308 => '铁裤子',312 => '钻石裤子',316 => '金裤子',
			301 => '皮革靴子',305 => '锁链靴子',309 => '铁靴子',313 => '钻石靴子',317 => '金靴子',
			268 => '木剑',272 => '石剑',267 => '铁剑',276 => '钻石剑',283 => '金剑',
			269 => '木铲',273 => '石铲',256 => '铁铲',277 => '钻石铲',284 => '金铲',
			270 => '木镐',274 => '石镐',257 => '铁镐',278 => '钻石镐',285 => '金镐',
			271 => '木斧',275 => '石斧',258 => '铁斧',279 => '钻石斧',286 => '金斧',
			290 => '木锄',291 => '石锄',292 => '铁锄',293 => '钻石锄',294 => '金锄',
			346 => '钓鱼竿', 340 => '书',261 => '弓',359 => '剪刀',370 => '恶魂之泪',260 => '苹果',
			373 => '魔瓶',459 => '汤药',$this->set->get('设置')['宝石ID'] => '宝石',
		);
		if(isset($tk[$s]))
		{
			return $tk[$s];
		}	
		return False;
	}
	
	public function getTrue($ID,$s)
	{
		$ID = $this->getEnchantName($ID);
		$tk = Array(298,302,306,310,314);//头盔
		$xj = Array(299,303,307,311,315);//胸甲
		$kz = Array(300,304,308,312,316);//裤子
		$xz = Array(301,305,309,313,317);//鞋子
		$j = Array(268,272,267,276,283);//剑
		$cz = Array(269,273,256,277,284);//铲子
		$gz = Array(270,274,257,278,285);//镐
		$ft = Array(271,275,258,279,286);//斧头
		$ct = Array(290,291,292,293,294);//锄头
		
		if(strstr($ID,'宝石') and $s == $this->set->get('设置')['宝石ID'])
		{
			return True;
		}
		
		if($ID == '治疗汤' and $s == 459)
		{
			return True;
		}
		
		if($ID == '知识之书' and $s == 340)
		{
			return True;
		}
		
		if($ID == '凝魂之泪' and $s == 370)
		{
			return True;
		}
		
		if($ID == '魔法苹果' and $s == 260)
		{
			return True;
		}
		
		if(
		$ID == '保护'
		or $ID == '火焰保护'
		or $ID == '爆炸保护'
		or $ID == '弹射物保护'
		or $ID == '荆棘'
		or $ID == 'RPG扩展架'
		)
		{
			if(in_Array($s,$tk) or in_Array($s,$xj) or in_Array($s,$kz) or in_Array($s,$xz))
			{
				return True;
			}
		}
		
		if(
		$ID == '摔落保护'
		or $ID == '深海探索者'
		or $ID == '生命恢复'
		or $ID == '暴击守护'
		or $ID == 'RPG扩展架'
		)
		{
			if(in_Array($s,$xz))
			{
				return True;
			}
		}
		
		if(
		$ID == '水下呼吸'
		or $ID == '水下速掘'
		or $ID == 'RPG扩展架'
		)
		{
			if(in_Array($s,$tk))
			{
				return True;
			}
		}
		
		if(
		$ID == '锋利'
		or $ID == '亡灵杀手'
		or $ID == '截肢杀手'
		or $ID == '击退'
		or $ID == '火焰附加'
		or $ID == '抢夺'
		or $ID == '嗜血'
		or $ID == '刃气'
		or $ID == '血刃'
		or $ID == 'RPG扩展架'
		)
		{
			if(in_Array($s,$j))
			{
				return True;
			}
		}
		
		if(
		$ID == '效率'
		or $ID == '时运'
		or $ID == '嗜血'
		or $ID == 'RPG扩展架'
		)
		{
			if(in_Array($s,$gz) or in_Array($s,$cz) or in_Array($s,$ft))
			{
				return True;
			}
		}
		
		if(
		$ID == '精准采集'
		or $ID == '嗜血'
		or $ID == 'RPG扩展架'
		)
		{
			if(in_Array($s,$gz) or in_Array($s,$cz) or in_Array($s,$ft)or $s == 359)
			{
				return True;
			}
		}
		
		if(
		$ID == '耐久'
		)
		{
			if(
			in_Array($s,$tk) or 
			in_Array($s,$xj) or 
			in_Array($s,$kz) or 
			in_Array($s,$xz) or 
			in_Array($s,$j) or 
			in_Array($s,$gz) or 
			in_Array($s,$cz) or 
			in_Array($s,$ft) or 
			in_Array($s,$ct) or 
			$s == 259 or 
			$s == 261 or 
			$s == 346 or 
			$s == 359 or 
			$s == 398)
			{
				return True;
			}
		}
		
		if(
		$ID == '力量'
		or $ID == '冲击'
		or $ID == '火矢'
		or $ID == '无限')
		{
			if($s == 261)
			{
				return True;
			}
		}
		
		if(
		$ID == '海之眷顾'
		or $ID == '钓饵'
		)
		{
			if($s == 346)
			{
				return True;
			}
		}
		
		return False;
	}
	
	public function updateInt($player)
	{
		for($index = 0; $index < $player->getInventory()->getSize(); $index ++)
		{
			$ItemInHand = $player->getInventory()->getItem($index);
			if(isset($ItemInHand->getNamedTag()['display']['strings']))
			{
				$string = $ItemInHand->getNamedTag()['display']['strings'];
				$texts = $this->Enchant->get($string);
				$Enchant_Id = Array();
				$id = $ItemInHand->getId();
				$Enchant_Lv = Array();
				$Enchant = Array();
				if(!empty($ItemInHand->getEnchantments()))
				{
					foreach($ItemInHand->getEnchantments() as $enchantment)
					{
						$Enchant_Id[] = $enchantment->getId();
						$Enchant_Lv[] = $enchantment->getLevel();
					}
				}
				if(count($Enchant_Id) > 0)
				{
					$enchant_names =  "";
					$text_d = "";
					$texts = Array();
					$lv = Array(1 => 'Ⅰ',2 => 'ⅠⅠ',3 => 'ⅠⅠⅠ',4 => 'ⅠⅤ',5 => 'Ⅴ',6 => 'ⅤⅠ',7 => 'ⅤⅠⅠ',8 => 'ⅤⅠⅠⅠ',9 => 'ⅠⅩ',10 => 'Ⅹ');
					if(isset($ItemInHand->getNamedTag()['display']['strings']))
					{
						$nbt_id = $ItemInHand->getNamedTag()['display']['strings'];
						if($this->Enchant->exists($nbt_id))
						{
							$texts = $this->Enchant->get($nbt_id);
							foreach($texts as $key => $value)
							{
								if($this->getEnchantName($key) !== False And $key > 24)
								{
									$Enchant[$key] = $value;
									$lvt = $value;
									if(isset($lv[$value]))
									{
										$lvt = $lv[$value];
									}
									$enchant_name = $this->getEnchantName($key);
									if($enchant_name != 'RPG扩展架')
									{
										$enchant_names .= "\n§7$enchant_name $lvt";
									}
									$note = "";
									if(isset($texts['独立']))
									{
										if(isset($texts['指数']))
										{
											$note = "\n§5指数剩余:§e " . $texts['指数']."\n";
										}
									}
									$text_d = $enchant_names. $note .$this->getItemInfo($key,$value);
									$enchantment = Enchantment::getEnchantment(-1);
									$enchantment->setLevel(1);
								}
							}
								$enchant_names = "";
						}
						$max = count($Enchant) > 0 ? "\n§f§9+".$this->getZT($Enchant)." 属性评价" : "";
						$text = "§f§b".$this->getIDTrue($id).$text_d. $max;
						for($a = 33;$a < 44; $a ++)
						{
							if(isset($texts[$a]))
							{
								$text = "§f§b".$this->getIDTrue($id)."§8[专属ID:".$nbt_id."]§a".$text_d. $max;
							}
						}
						if(isset($texts['镶嵌']))
						{
							$max_buff = "";
							foreach($texts['镶嵌'] as $key => $value)
							{
								if($key != 'max')
								{
									$max_buff .= " §e " . $this->buff_txt($key,$value) . "\n";
								}
							}
							$max_buff == "" ? $max_buff = "§8未镶嵌" : [];
							$text = "§5RPG§f-§b".$this->getIDTrue($id)."§8[ID:".$nbt_id."]§a[".$ItemInHand->getDamage()."/".$this->itemDamage($id)."]".$text_d."\n§6可镶嵌凹槽[". (count($texts['镶嵌']) - 1) ."/".$texts['镶嵌']['max']."]\n".$max_buff.$max;
						}
						if($ItemInHand->getNamedTag()['display']['Name'] != $text)
						{
							$nbt = new CompoundTag("",[
								"display" => new CompoundTag("display",[
									"Name" => new StringTag("Name",$text),
									"strings" => new StringTag("strings",$nbt_id)
								]),
							]);
							$ItemInHand->setNamedTag($nbt);
							for($a = 0; $a < count($Enchant_Id); $a ++)
							{
								$enchantment = Enchantment::getEnchantment($Enchant_Id[$a]);
								$enchantment->setLevel($Enchant_Lv[$a]);
								$ItemInHand->addEnchantment($enchantment);
							}
							$player->getInventory()->setItem($index,$ItemInHand);
						}
					}
				}
			}
		}			
	}
	
	public function buff_txt($key,$value)
	{
		$txt = "";
		switch($key)
		{
			case 33:
			$txt = "+". $value * 0.6 ."物理攻击";
			break;
			case 34:
			$txt = "+". $value * 0.5 ."物理防御";
			break;
			case 35:
			$txt = "+". $value * 0.5 ."血量上限";
			break;
			case 36:
			$txt = "+". $value * 1 ."暴击";
			break;
			case 37:
			$txt = "+". $value * 0.7 ."格挡";
			break;
			case 38:
			$txt = "+". $value * 0.3 ."闪避";
			break;
			case 39:
			$txt = "+". $value * 0.8 ."抗暴";
			break;
			case 40:
			$txt = "+". $value * 0.3 ."命中";
			break;
			case 41:
			$txt = "+". $value * 0.6 ."魔防";
			break;
			case 42:
			$txt = "+". $value * 0.6 ."魔攻";
			break;
			case 43:
			$txt = "+". $value * 5 ."魔法上限";
			break;
		}
		$txt == "" ? $txt = "未知的宝石" : [];
		return $txt;
	}
	
	public function getItemInfo($ID,$lvt)
	{
		$arrays = Array(
			28 => "§6\n点击地面使用,人物等级§5+". $lvt ."",
			29 => "§6\n受到超过§5". 10 * $lvt ."点§6伤害自动触发§4[背包内有效]\n§6消耗一点数抵挡§5". 30 * $lvt ."点§6点伤害，指数点数消耗完自动消失。",
			30 => "§6\n使用之后立即回复§5". 10 * $lvt ."§6点魔法§4[可用于队友]\n§8-> 当饥饿为满状态时,点击地面饥饿-1",
			31 => "§6\n使用之后在§5". 2 * $lvt ."§6秒内回复§5". $lvt ."§6点生命值\n§4受到[玩家]攻击后效果消失§c[不可叠加效果]",
			
			33 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 0.6 * $lvt ."§4点物理攻击",
			34 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 0.5 * $lvt ."§4点物理防御",
			35 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 0.5 * $lvt ."§4点血量上限",
			36 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 1 * $lvt ."§4点暴击",
			37 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 0.7 * $lvt ."§4点格挡",
			38 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 0.3 * $lvt ."§4点闪避",
			39 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 0.8 * $lvt ."§4点抗暴",
			40 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 0.3 * $lvt ."§4点命中",
			41 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 0.6 * $lvt ."§4点魔法防御",
			42 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 0.6 * $lvt ."§4点魔法攻击",
			43 => "§6\n镶嵌入装备后:\n     §f*§4 +§c". 5 * $lvt ."§4点魔法上限",
		);
		if(isset($arrays[$ID]))
		{
			return $arrays[$ID];
		}
		return False;
	}
	
	public function getEnchantID($ID)
	{
		$array = Array(
			'保护' => 0,
			'火焰保护' => 1,
			'摔落保护' => 2,
			'爆炸保护' => 3,
			'弹射物保护' => 4,
			'荆棘' => 5,
			'水下呼吸' => 6,
			'深海探索者' => 7,
			'水下速掘' => 8,
			'锋利' => 9,
			'亡灵杀手' => 10,
			'截肢杀手' => 11,
			'击退' => 12,
			'火焰附加' => 13,
			'抢夺' => 14,
			'效率' => 15,
			'精准采集' => 16,
			'耐久' => 17,
			'时运' => 18,
			'力量' => 19,
			'冲击' => 20,
			'火矢' => 21,
			'无限' => 22,
			'海之眷顾' => 23,
			'钓饵' => 24,
			
			'嗜血' => 25,
			'刃气' => 26,
			'血刃' => 27,
			'知识之书' => 28,
			'凝魂之泪' => 29,
			'魔法苹果' => 30,
			'治疗汤' => 31,
			'RPG扩展架' => 32,
			'物攻宝石' => 33,
			'物防宝石' => 34,
			'气血宝石' => 35,
			'暴击宝石' => 36,
			'格挡宝石' => 37,
			'闪避宝石' => 38,
			'抗暴宝石' => 39,
			'命中宝石' => 40,
			'魔防宝石' => 41,
			'魔攻宝石' => 42,
			'魔法宝石' => 43,
		);
		if(isset($ID,$array))
		{
			return $array[$ID];
		}
		return False;
	}

	public function RandStr($length = 100)
	{
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    	$password = '';
	    for ( $i = 0; $i < $length; $i++ )
	    {
	        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
	    }
    	return $password;
	}

	public function RandStrs($length = 100)
	{
		$chars = '0123456789';
	    $password = '';
	    for ( $i = 0; $i < $length; $i++ )
	    {
	        $password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
	    }
	    return $password;
 	}
  
	public function untime($n,$s = False)
	{
		$h=time();
		$s ? $h = $n : $h=$n-$h;
		$r = "";
		if($h < 60){
			$r = $h.'秒';
		}else if($h >= 60 && $h <3600){
			//$this->getLogger()->info("	$r = floor($h/60).'分钟'.floor($h-(($h/60)*60)).'秒';");
			$r = floor($h/60).'分'.floor($h%60).'秒';
		}else if($h >=3600 && $h <86400){
			$r = floor($h/3600).'小时'.floor(($h%3600)/60).'分';
		}else if($h >=86400 && $h <2592000){
			$r = floor($h/86400).'天'.floor(($h%86400)/3600).'小时'.floor((($h%86400)%3600)/60).'分'.floor(((($h%86400)%3600)/60)/60).'秒';
		}else if($h >=2592000 && $h <31104000){
			$r = floor($h/2592000).'个月'.floor(($h%2592000)/86400).'天'.floor((($h%2592000)%86400)/3600).'小时'.floor(((($h%2592000)%86400)%3600)/60).'分'.floor((($h%86400)%3600)/60/60).'秒';
		}else if($h >=31104000){
			$r = floor($h/31104000).'年'.floor(($h%31104000)/2592000).'个月'.floor((($h%31104000)%2592000)/86400).'天'.floor(((($h%31104000)%2592000)%86400)/3600).'小时'.floor((((($h%31104000)%2592000)%86400)%3600)/60).'分'.floor(((((($h%31104000)%2592000)%86400)%3600)/60)/60).'秒';
		}
		return $r;
	}
	
	public function ZXDA_load()
	{
		$data=ZXDA::getInfo();
		ZXDA::tokenCheck('MTMzNjg4NzkwMDgzMTI3NTA5NTY1OTQ4MjYzMjc1NTk5MjkyMDI0MTY3MDc3MzA3NTYxMTg2NjY0MjA1MzAxMjY1MjQzMDQ5MTQzNDk1Mjg2NzcwMjg0MjE2OTQ4MzIzMDY2MjE3NTQ0NjU4ODg4OTc2MDQ5ODk4MTA2OTk4NTk4Njc0OTIzOTIxNzU0MTk3OTA3MzM0ODAzOTEwNDM0NjE4MDk3MTc1NDg1OTIzNTUxNTU0ODQ5MjE2NTczMzAwOTA5NjcwMzE1NTE5MjA3MzI4MzMwMjc4MDMwMTU4NDkyMTcwMzYzOTQ1MjczNTY4OTM4ODcwMDQ2NjMxNDk5NzQ2ODQ5MTIyNzAzMDkzNjA0ODYzODQwMTc5NTMxMDk0NDIyNjI1MDQ3OTA4NTg3NjczNTk5');
	}
	
	public function getZT($array)
	{
		$ms = 0;
		$c = Array(9,10,11,12,13,14,19,20,21,25,26,30);
		$a = Array(22,27,28,29,31);
		foreach($array as $key => $value)
		{
			$value *= 1.23;
			in_array($value,$c) ? $value *= 1.43 : [];
			in_array($value,$a) ? $value *= 2.63 : [];
			$ms += $value;
		}
		return round($ms);
	}
	
	public function onCommand(CommandSender $sender,Command $command,$label,array $args)
	{
		if($command->getName() == '附魔')
		{
			$set = $this->set->get('设置');
			$name = $sender->getName();
			if(!isset($args[0]) Or $args[0] == '帮助')
			{
				$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
				$sender->sendMessage('§e #§6 /附魔 白名单 帮助');
				$sender->sendMessage('§e #§6 /附魔 附魔券 帮助');
				$sender->sendMessage('§e #§6 /附魔 商店 帮助');
				$sender->sendMessage('§e #§6 /附魔 镶嵌 [宝石编号] [被镶嵌装备编号]');
				$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
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
				if(!isset($en_2['镶嵌']) or !isset($en_2['镶嵌']['max']))
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
						if($k == $m and $v == $t)
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
				if(!isset($args[1]) Or $args[1] == '帮助')
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
				if(!isset($args[1]) Or $args[1] == '帮助')
				{
					$sender->sendMessage('§e§1##=§2##=§3##=§5##=§6##=§7##=§a##=§b##=§c##=§e##=§4##');
					$sender->sendMessage('§e #§6 /附魔 附魔券 富豪榜 §5<查看附魔券前10名>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 所持 §5<查看自己所持有附魔券的数量>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 兑换 面值 卡号 密码 §5<用卡密兑换指定面额的附魔券>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 查看 [玩家] §5<查看其他玩家所持有附魔券数量>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 设置 [玩家] [数量] §5<设置玩家的附魔券数量>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 扣除 [玩家] [数量] §5<扣除玩家一定数量的附魔券>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 增加 [玩家] [数量] §5<为玩家增加一定数量的附魔券>');
					$sender->sendMessage('§e #§6 /附魔 附魔券 生成 [10/30/50/75/100] [数量] §5<在文件内生成指定数量的卡密>');
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
				if($args[1] == '兑换')
				{
					if(!isset($args[4]) or !is_numeric($args[3]))
					{
						$sender->sendMessage('§e #§6 /附魔 附魔券 兑换 面值 卡号 密码 §5<用卡密兑换指定面额的附魔券>');
						return true;
					}
					if($args[2] != 10 and $args[2] != 30 and $args[2] != 50 and $args[2] != 75 and $args[2] != 100)
					{
						$sender->sendMessage('§4-> §c错误的面值§e[ ' . $args[2] . ' ]§c他只能是§e[10/30/50/75/100]§c!');
						return true;
					}
					$Prepaid = $this->Prepaid->get($args[2]);
					if(!isset($Prepaid[$args[3]]))
					{
						$sender->sendMessage('§4-> §c无效的卡号!请核对面值!');
						return true;
					}
					if($this->Prepaid->get($args[2])[$args[3]] != $args[4])
					{
						$sender->sendMessage('§4-> §c无效的卡密!请核对面值!');
						return true;
					}
					$this->Prepaid_mz = new Config($this->getDataFolder() . 'Prepaid/' . $args[2] . '.Prepaid',Config::YAML,array());
					for($a = 0; $a < count($Prepaid); $a ++)
					{
						foreach($Prepaid as $a => $b)
						if($a == $args[3] and $b == $args[4])
						{
							unset($Prepaid[$a]);
							$this->Prepaid->set($args[2],$Prepaid);
							$this->Prepaid->save();
						}
					}
					$this->Prepaid_mz->remove($args[3]);
					$this->Prepaid_mz->save();
					$Money = $this->Money->get($name);
					$this->Money->set($name,$Money + $args[2]);
					$this->Money->save();
					$Money = $this->Money->get($name);
					$sender->sendMessage('§4-> §c已使用面值为§e[ ' . $args[2] . ' ]§c的附魔券!');
					$sender->sendMessage('§8[系统] §9您所持的附魔券增加了§e[ ' . $args[2] . ' ]§c,现持有附魔券§e[ ' . $Money . ' ]§c!');
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
					if(!isset($args[3]) or !is_numeric($args[3]))
					{
						$sender->sendMessage('§e #§6 /附魔 附魔券 设置 [玩家] [数量] §5<设置玩家的附魔券数量>');
						return true;
					}
					if($args[3] <= 0 or $args[3] > 100000000)
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
					if(!isset($args[3]) or !is_numeric($args[3]))
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
					if(!isset($args[3]) or !is_numeric($args[3]))
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
				if($args[1] == '生成')
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
					if(!isset($args[3]) or !is_numeric($args[3]))
					{
						$sender->sendMessage('§e #§6 /附魔 附魔券 生成 [10/30/50/75/100] [数量] §5<在文件内生成指定数量的卡密>');
						return true;
					}
					if($args[2] != 10 and $args[2] != 30 and $args[2] != 50 and $args[2] != 75 and $args[2] != 100)
					{
						$sender->sendMessage('§4-> §c错误的面值§e[ ' . $args[2] . ' ]§c他只能是§e[10/30/50/75/100]§c!');
						return true;
					}
					if($args[3] <= 0)
					{
						$sender->sendMessage('§4-> §c生成数量不能为§e[ ' . $args[3] . ' ]§c他只能大于0!');
						return true;
					}
					$this->Prepaid_mz = new Config($this->getDataFolder() . 'Prepaid/' . $args[2] . '.Prepaid',Config::YAML,array());
					$Prepaid = $this->Prepaid->get($args[2]);
					for($a = 0; $a < $args[3]; $a ++)
					{
						$zh = $this->RandStrs($set['卡密账号长度']);
						$mm = $this->RandStr($set['卡密密码长度']);
						$this->Prepaid_mz->set($zh,$mm);
						$this->Prepaid_mz->save();
						$Prepaid[$zh] = $mm;
					}
					$this->Prepaid->set($args[2],$Prepaid);
					$this->Prepaid->save();
					$sender->sendMessage('§4-> §c已生成面值为§e[ ' . $args[2] . ' ]§c的附魔券§e[ ' . $args[3] . ' ]§c个!在§e[ ' . $this->getDataFolder() . 'Prepaid/' . $args[2] . '.Prepaid' . ' ]§c目录下查看!');
					return true;
				}
			}
		}
	}
	
	public function Array_too($Array)
	{
		$All = $this->Command_Shop->getAll();
		foreach($All as $k => $v)
		{
			if(is_array($v))
			{
				foreach($v as $ac => $bd)
				{
					if(is_array($bd) and $ac == $Array)
					{
						return $bd;
					}
				}
			}
		}
		return False;
	}
	
	public function getAllEnction($player)
	{
		$Helmet = $player->getInventory()->getHelmet();
		$Chestplate = $player->getInventory()->getChestplate();
		$Leggings = $player->getInventory()->getLeggings();
		$Boots = $player->getInventory()->getBoots();
		$Helmet_ID = $Helmet->getID();
		$Chestplate_ID = $Chestplate->getID();
		$Leggings_ID = $Leggings->getID();
		$Boots_ID = $Boots->getID();
		$Enchant = Array();

		//头盔全部附魔属性
		if($Helmet_ID != 0)
		{
			if(!empty($Helmet->getEnchantments()))
			{
				foreach($Helmet->getEnchantments() as $enchantment)
				{
					$Enchant['头附魔'][$enchantment->getId()] = $enchantment->getLevel();
				}
				if(isset($Helmet->getNamedTag()['display']['strings']))
				{
					$nbt_id = $Helmet->getNamedTag()['display']['strings'];
					$texts = $this->Enchant->get($nbt_id);
					$Enchant['头附魔'][$nbt_id] = $texts;
					$Enchant[$nbt_id] = $texts;
				}
			}
		}
		//胸甲全部附魔属性
		if($Chestplate_ID != 0)
		{
			if(!empty($Chestplate->getEnchantments()))
			{
				foreach($Chestplate->getEnchantments() as $enchantment)
				{
					$Enchant['胸附魔'][$enchantment->getId()] = $enchantment->getLevel();
				}
				if(isset($Chestplate->getNamedTag()['display']['strings']))
				{
					$nbt_id = $Chestplate->getNamedTag()['display']['strings'];
					$texts = $this->Enchant->get($nbt_id);
					$Enchant[$nbt_id] = $texts;
					$Enchant['胸附魔'][$nbt_id] = $texts;
				}
			}
		}
		//裤子全部附魔属性
		if($Leggings_ID != 0)
		{
			if(!empty($Leggings->getEnchantments()))
			{
				foreach($Leggings->getEnchantments() as $enchantment)
				{
					$Enchant['裤附魔'][$enchantment->getId()] = $enchantment->getLevel();
				}
				if(isset($Leggings->getNamedTag()['display']['strings']))
				{
					$nbt_id = $Leggings->getNamedTag()['display']['strings'];
					$texts = $this->Enchant->get($nbt_id);
					$Enchant[$nbt_id] = $texts;
					$Enchant['裤附魔'][$nbt_id] = $texts;
				}
			}
		}
		//鞋子全部附魔属性
		if($Boots_ID != 0)
		{
			if(!empty($Boots->getEnchantments()))
			{
				foreach($Boots->getEnchantments() as $enchantment)
				{
					$Enchant['鞋附魔'][$enchantment->getId()] = $enchantment->getLevel();
				}
				if(isset($Boots->getNamedTag()['display']['strings']))
				{
					$nbt_id = $Boots->getNamedTag()['display']['strings'];
					$texts = $this->Enchant->get($nbt_id);
					$Enchant[$nbt_id] = $texts;
					$Enchant['鞋附魔'][$nbt_id] = $texts;
				}
			}
		}
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
		return $Enchant;
	}
	
	public function load_shop($name,$shop_type)
	{
		$player = $this->getServer()->getPlayer($name);
		$ItemInHand = $player->getInventory()->getItemInHand();
		$ID = $ItemInHand->getId();
		$Enchant = Array();
		$NBT = Array();
		$nbt_id = 0;
		if(!empty($ItemInHand->getEnchantments())){
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
				$NBT = $texts;
				foreach($texts as $key => $value)
				{
					$Enchant[$key] = $value;
				}
			}
		}
		$enchant_name = $this->getEnchantName($shop_type['ID']);
		if($shop_type['类型'] == '附魔')
		{
			if(!$this->getTrue($shop_type['ID'],$ID))
			{
				return "此物品不支持附魔".$enchant_name."!";
			}
			if($shop_type['ID'] == 32 And isset($NBT['镶嵌']) And $NBT['镶嵌']['max'] >= $shop_type['LV'])
			{
				return "此物品镶嵌宝石功能已开启,且凹槽数相等或更高!";
			}
			if(isset($Enchant[$shop_type['ID']]) and $Enchant[$shop_type['ID']] >= $shop_type['LV'])
			{
				return "你手中的物品已附魔".$shop_type["LV"]."级的".$enchant_name.",甚至更高!";
			}
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
				if(!is_Array($v) And is_numeric($k) And $k >= 0 And $k <= 24)
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
				if(!is_Array($v) And is_numeric($k) And $k >= 0 And $k <= 24)
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
				if(!is_Array($v) And is_numeric($k) And !is_Array($k))
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
			if(isset($Enchant[$shop_type['ID']]) and $Enchant[$shop_type['ID']] < $shop_type['LV'])
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

	public function setMoneys($player,$lis,$numeric,$type = '+')
	{
		$name = $player->getName();
		if($lis == '金币')
		{
			$numeric_one = EconomyAPI::getInstance()->myMoney($name);
			if($numeric > $numeric_one)
			{
				return '不足'.$numeric.$lis;
			}
			if($type == '+')
			{
				EconomyAPI::getInstance()->setMoney($player, $numeric_one + $numeric);
			}
			else
			{
				EconomyAPI::getInstance()->setMoney($player, $numeric_one - $numeric);
			}
			return true;
		} 
		if($lis == '经验')
		{
			$numeric_one = (int)@$player->getTotalXp();
			if($numeric > $numeric_one)
			{
				return '不足'.$numeric.$lis;
			}
			if($type == '+')
			{
				@$player->setTotalXp($player->getTotalXp() + $numeric);
			}
			else
			{
				@$player->setTotalXp($player->getTotalXp() - $numeric);
			}
			return true;
		} 
		if($lis == '等级')
		{
			$numeric_one = (int)@$player->getXpLevel();
			if($numeric > $numeric_one)
			{
				return '不足'.$numeric.$lis;
			}
			if($type == '+')
			{
				@$player->setXpLevel($player->getXpLevel() + $numeric);
			}
			else
			{
				@$player->setXpLevel($player->getXpLevel() - $numeric);
			}
			return true;
		} 
		if($lis == '附魔券')
		{
			$numeric_one = (int)$this->Money->get($name);
			if($numeric > $numeric_one)
			{
				return '不足'.$numeric.$lis;
			}
			if($type == '+')
			{
				$this->Money->set($name,$numeric_one + $numeric);
				$this->Money->save();
			}
			else
			{
				$this->Money->set($name,$numeric_one - $numeric);
				$this->Money->save();
			}
			return true;
		} 
		if($lis == '点券')
		{
			$numeric_one = (int)ZXDAConnector::getPlayerCoupons($player_name);
			if($numeric > $numeric_one)
			{
				return '不足'.$numeric.$lis;
			}
			if($type == '+')
			{
				ZXDAConnector::addPlayerCoupons($name,$numeric);
			}
			else
			{
				ZXDAConnector::takePlayerCoupons($name,$numeric);
			}
			return true;
		} 
		$this->getLogger()->info("404:在执行支付时出现未知的情况,请联系[附魔]开发者 #Array($lis,$numeric,$type) => $name");
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
		public static function rsa_decode($message,$modulus,$keylength=1024){$result=array();foreach(explode('***&&&***',$message) as $message){$result[]=self::remove_PKCS1_padding(self::number_to_binary(self::pow_mod(self::binary_to_number($message),'65537',$modulus),$keylength/8),$keylength/8);unset($message);}return implode('',$result);}
		private static function pow_mod($p,$q,$r){$factors=array();$div=$q;$power_of_two=0;while(bccomp($div,'0')==1){$rem=bcmod($div,2);$div=bcdiv($div,2);if($rem){array_push($factors,$power_of_two);}$power_of_two++;}$partial_results=array();$part_res=$p;$idx=0;foreach($factors as $factor){while($idx<$factor){$part_res=bcpow($part_res,'2');$part_res=bcmod($part_res,$r);$idx++;}array_push($partial_results,$part_res);}$result='1';foreach($partial_results as $part_res){$result=bcmul($result,$part_res);$result=bcmod($result,$r);}return $result;}
		private static function add_PKCS1_padding($data,$isprivateKey,$blocksize){$pad_length=$blocksize-3-strlen($data);if($isprivateKey){$block_type="\x02";$padding='';for($i=0;$i<$pad_length;$i++){$rnd=mt_rand(1,255);$padding .= chr($rnd);}}else{$block_type="\x01";$padding=str_repeat("\xFF",$pad_length);}return "\x00".$block_type.$padding."\x00".$data;}
		private static function remove_PKCS1_padding($data,$blocksize){assert(strlen($data)==$blocksize);$data=substr($data,1);if($data{0}=='\0'){return '';}assert(($data{0}=="\x01") or ($data{0}=="\x02"));$offset=strpos($data,"\0",1);return substr($data,$offset+1);}
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
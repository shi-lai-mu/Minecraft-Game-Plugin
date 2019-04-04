<?php
namespace print_journalists;

	use pocketmine\event\Listener;
	use pocketmine\Player;
	use pocketmine\Server;
	use pocketmine\plugin\PluginBase;

	use pocketmine\item\Item;
	use pocketmine\command\Command;
	use pocketmine\command\CommandSender;
	use pocketmine\command\ConsoleCommandSender;
	use pocketmine\nbt\tag\{ByteTag,CompoundTag,DoubleTag,ListTag,FloatTag,IntTag,ShortTag,NBT,StringTag,NamedTag};
	use pocketmine\tile\Tile;
	use pocketmine\level\Position;
	use pocketmine\block\Block;
	use pocketmine\level\Level;
	use pocketmine\tile\Sign;
	use pocketmine\utils\Config;
	use pocketmine\math\Vector3;
	use pocketmine\entity\Effect;
	use pocketmine\entity\Entity;
	use pocketmine\scheduler\PluginTask;
	use pocketmine\scheduler\CallbackTask;
	use pocketmine\event\player\PlayerInteractEvent;
	use pocketmine\event\entity\EntityDamageByEntityEvent;
	use pocketmine\event\entity\EntityDamageEvent;
	use pocketmine\event\player\PlayerQuitEvent;
	use pocketmine\event\player\PlayerJoinEvent;
	use pocketmine\event\entity\EntityDeathEvent;
	use pocketmine\event\player\PlayerDeathEvent;
	use pocketmine\event\block\BlockBreakEvent;
	use pocketmine\event\block\BlockPlaceEvent;
	use onebone\economyapi\EconomyAPI;

	use pocketmine\level\particle\MobSpawnParticle;
	use pocketmine\level\particle\BubbleParticle;
	use pocketmine\level\particle\CriticalParticle;
	use pocketmine\level\particle\GenericParticle;
	use pocketmine\level\particle\InkParticle;
	use pocketmine\level\particle\EntityFlameParticle;
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
	use pocketmine\network\protocol\AddEntityPacket;
	use pocketmine\network\protocol\RemoveEntityPacket;
	use pocketmine\event\player\PlayerCommandPreprocessEvent;


class print_journalists extends PluginBase implements Listener
{
	private $Room = [//房间
		'box' => [],			//房间设置
		'Inventory' => [],		//玩家背包
		'Room' => [],			//房间内的玩家
		'Sheep' => []			//房间内的绵羊
	];
	private $Interact = [];//点击
	private $y = 0;
	private $EXP = [];
	private $Break = [];
	private $Place = [];
	private $unLoad = [];
	private $TEXT = [];

	//////////////////////////////////////////////////////
	///					自创自带函数				   ///
	//////////////////////////////////////////////////////

	#### 加入房间 ####
	function Room_Add_Player(Player $Player,String $Rooms,Bool $UnLoad = True)
	{
		$Room = $this->Room['box']->get($Rooms);
		$name = $Player->getName();
		$Player->setGamemode(0);
		if($UnLoad)
		{
			if($Room['系统']['房间状态'] === True) return $Player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 此房间已经开始游戏!");
			if(in_Array($name,$Room['系统']['房内玩家'])) return $Player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 已加入该房间!");
			if(isset($this->Room['Room'][$name])) return $Player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 你已加入§8[".$this->Room['Room'][$name]."]号§b房间!");
			if(count($Room['系统']['房内玩家']) >= count($Room['玩家位置'])) return $Player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 此房间已满!");
		}
		$Room['系统']['房内玩家'][] = $name;
		$Room['系统']['存活玩家'][] = $name;
		$Room['系统']['绵羊印记'][$name] = 0;
		$Room['系统']['额外'][$name] = 0;
		$Room['系统']['助杀'][$name] = 0;
		$Room['系统']['杀人'][$name] = 0;
		$Room['系统']['杀羊'][$name] = 0;
		$this->Room['box']->set($Rooms,$Room);
		$this->Room['Room'][$name] = $Rooms;
		$XYZ = explode(':',$Room['玩家位置'][count($Room['系统']['房内玩家']) - 1]);
		$Pos = $this->Load_XYZ(new Vector3($XYZ[0],$XYZ[1],$XYZ[2]),$Room);
		$this->POS[$name] = [$Player->level,$Player->x,$Player->y,$Player->z];
		$Player->teleport(new Position($Pos->x,$Pos->y,$Pos->z,$this->getServer()->getLevelByName($Room['木牌位置'][0])));
		$this->Vector3[$name] = [$Pos->x,$Pos->y,$Pos->z,$this->getServer()->getLevelByName($Room['木牌位置'][0])];
		$this->RemoveInventory($Player);
		$Player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b< /绵羊印记 退出 >退出房间!");
		$this->sendMessage_Room($Room['系统']['房内玩家'],"印记争夺者§8[$name]§b加入房间!");
		$Player->getInventory()->addItem(clone Item::get(345,0,1));
		if($Room['最低人数'] <= count($Room['系统']['房内玩家']))
		{
			$this->sendMessage_Room($Room['系统']['房内玩家'],"本局游戏已达到最低人数要求! §8争夺者[".count($Room['系统']['房内玩家']) .'/'.count($Room['玩家位置'])."]位");
		} else {
			$code = $Room['最低人数'] - count($Room['系统']['房内玩家']);
			$this->sendMessage_Room($Room['系统']['房内玩家'],"还需§8[".$code."]位§b印记争夺者才能开始游戏! §8争夺者[".count($Room['系统']['房内玩家']) .'/'.count($Room['玩家位置'])."]位");
		}
		return true;
	}

	#### 退出房间 ####
	function Room_Del_Player(Player $Player)
	{
		$name = $Player->getName();
		if(!isset($this->Room['Room'][$name])) return $Player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 退出§8[".$this->Room['Room'][$name]."]号§b房间失败! ERROR:NOT ROOM IN ONE");
		$Room = $this->Room['box']->get($this->Room['Room'][$name]);
		foreach($Room['系统']['房内玩家'] as $key => $value)
		{
			if($value == $name) unset($Room['系统']['房内玩家'][$key]);
		}
		foreach($Room['系统']['存活玩家'] as $key => $value)
		{
			if($value == $name) unset($Room['系统']['存活玩家'][$key]);
		}
		if(isset($this->Vector3[$name]))
		{
			unset($this->Vector3[$name]);
		}
		$this->Room['box']->set($this->Room['Room'][$name],$Room);
		$this->AddInventory($Player);
		$Player->setGamemode(0);
		if(isset($this->EXP[$name])) $this->setLV($Player,$this->EXP[$name]);
		unset($this->EXP[$name]);
		$Player->teleport(new Position($this->POS[$name][1],$this->POS[$name][2],$this->POS[$name][3],$this->getServer()->getLevelByName($this->POS[$name][0])));
		$Player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 已成功退出§8[".$this->Room['Room'][$name]."]号§b房间!");
		unset($this->Room['Room'][$name]);
	}

	#### 计时任务 ####
	function Task()
	{
		foreach($this->Room['box']->getAll() as $key => $value)
		{
			$Room = $this->Room['box']->get($key);
			if($value['最低人数'] <= count($value['系统']['房内玩家']) && $Room['系统']['游戏时间'] < time())
			{
				$Room['游戏时长'] < 50 ? $Room['游戏时长'] = 50 : [];
				$Room['系统']['游戏时间'] = Time() + $value['游戏时长'] + 10;
				$this->sendMessage_Room($value['系统']['房内玩家'],'10秒后 开始游戏!','Message',False);
				$this->Room['box']->set($key,$Room);
			}
			if($Room['系统']['游戏时间'] - ($value['游戏时长'] + 9) == Time()) $this->sendMessage_Room($value['系统']['房内玩家'],'所有工具点地使用!','Message',False);
			if($Room['系统']['游戏时间'] - ($value['游戏时长'] + 8) == Time()) $this->sendMessage_Room($value['系统']['房内玩家'],'杀羊得印记,击败玩家则获得该玩家所有分数,击杀BOSS得BUFF!','Message',False);
			if($Room['系统']['游戏时间'] - ($value['游戏时长'] + 6) == Time()) $this->sendMessage_Room($value['系统']['房内玩家'],'游戏时长'.$Room['游戏时长'].'秒,等级为剩余时间!','Message',False);
			if($Room['系统']['游戏时间'] - Time() == $value['游戏时长'] - 10)
			{
				$BOSS = 0;
				$this->sendMessage_Room($value['系统']['房内玩家'],'房间内诞生了'.$Room['粒子绵羊数量'].'只粒子羊,去追杀它们吧!');
				foreach($Room['绵羊位置'] as $xyz)
				{
					if($BOSS < $Room['粒子绵羊数量']) $this->create_sheep($key,$xyz,2,True);
					$BOSS += 1;
				}
			}
			$time = $Room['系统']['游戏时间'] - Time() - $Room['游戏时长'];
			if($time >= -1 && $time <= 5)
			{
				if($time == 0) $time = 'GO! GO! GO!';
				if($time == -1) $time = '目标:击杀羊或玩家!';
				$this->sendMessage_Room($value['系统']['房内玩家'],'§'.mt_rand(1,9) . $time,'title');
			}
			if($Room['系统']['游戏时间'] - $value['游戏时长'] == Time())
			{
				$this->sendMessage_Room($value['系统']['房内玩家'],'开始游戏!');
				foreach($value['系统']['房内玩家'] as $name)
				{
					unset($this->Vector3[$name]);
					$player = $this->getServer()->getPlayer($name);
					$player->setAllowFlight(False);
					$player->setHealth(20);
					$Room['系统']['房间状态'] = True;
					$this->Room['box']->set($key,$Room);
				}
				$this->unSheep($key);
			}
			if($Room['系统']['房间状态'])
			{
				if($Room['系统']['游戏时间'] > Time())
				{
					$level = $this->getServer()->getLevelByName($Room['木牌位置'][0]);
					if($level instanceof Level)
					{
						foreach($level->getEntities() as $Entity)
						{
							if(isset($Entity->namedtag->SPJ) && !$Entity instanceof Player && $Entity->namedtag->SPJ[0] == $key and $Entity->namedtag->SPJ[3] == 1)
							{
								foreach($value['系统']['房内玩家'] as $name)
								{
									$Player = $this->getServer()->getPlayer($name);
									if($Player instanceof Player)
									{
										$Di = $Entity->distance($Player->getPosition());
										if(isset($Entity->namedtag->gjz))
										{
											if($Di < 15 && $Di > 0.5)
											{
												$x = $Player->x - $Entity->x;
												$y = $Player->y - $Entity->y;
												$z = $Player->z - $Entity->z;
												$atn = atan2($z,$x);
												$Entity->setRotation(rad2deg($atn - M_PI_2),rad2deg(-atan2($y,sqrt($x ** 2 + $z ** 2))));
												$Entity->move($x/10,$y,$z/10);
											}
											if($Di <= 4)
											{
												$ev = new EntityDamageEvent($Entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, 6);
												$Player->attack($ev->getFinalDamage(), $ev);
												$deltaX = $Player->x - $Entity->x;
												$deltaZ = $Player->z - $Entity->z;
												$yaw = \atan2($deltaX, $deltaZ);
												$Player->knockBack($Entity,0,\sin($yaw),\cos($yaw),0.4);
											}
										}
										if($Player->getGamemode() == 3)
										{
											unset($Entity->namedtag->gjz);
										}
									}
								}
								if(isset($Entity->namedtag->SPJ))
								{
									$r = 1;
									for($i = 0; $i < 25; $i ++)
									{
										$xx = $Entity->x + $r * cos($i * 3.1415926 / 10) ;
										$zz = $Entity->z + $r * sin($i * 3.1415926 / 10) ;
										$this->y < 3 ? $this->y += 0.1 : $this->y = 0;
										$level->addParticle(new CriticalParticle(new Vector3($xx, $Entity->y+0.5+$this->y, $zz)));
									}
								}
							}
						}
					}
				}
				foreach($value['系统']['房内玩家'] as $name)
				{
					$Player = $this->getServer()->getPlayer($name);
					if($Player instanceof Player)
					{
						if($Room['系统']['游戏时间'] > Time())
						{
							if(!isset($this->EXP[$name])) $this->EXP[$name] = $this->getLV($Player);
							$this->setLV($Player,$Room['系统']['游戏时间'] - Time());
						}
					}
				}
				if($Room['系统']['游戏时间'] - Time() == 10) $this->sendMessage_Room($value['系统']['房内玩家'],'10秒后 游戏结束!','Message',False);
				if($Room['系统']['游戏时间'] - Time() == 5) $this->sendMessage_Room($value['系统']['房内玩家'],'5秒后 游戏结束!','title');
				if($Room['系统']['游戏时间'] != 0 && $Room['系统']['游戏时间'] <= Time() || count($Room['系统']['存活玩家']) <= 0)
				{
					$this->sendMessage_Room($value['系统']['房内玩家'],'§'.mt_rand(1,9).'游戏结束!','title');
					$this->OverGame($key);
				}
			}
		}
		$this->UpDate_Sign();
		foreach($this->getServer()->getOnlinePlayers() as $player)
		{
			$name = $player->getName();
			if(isset($this->Vector3))
			{
				if(isset($this->Vector3[$name]))
				{
					$pos = $this->Vector3[$name];
					if($player->x - $pos[0] > 1 || $player->z - $pos[2] > 1 || $player->x - $pos[0] < -1 || $player->z - $pos[2] < -1 || $player->level->getName() != $pos[3]->getName())
					{
						$player->teleport(new Position($pos[0],$pos[1],$pos[2],$pos[3]));
					}
				}
			}
		}
	}

	#### 获取等级 ####
	function getLV($Player)
	{
		if($this->getServer()->getName() == 'Tesseract')
		{
			return $Player->getXpLevel();
		} else {
			return $Player->getXp();
		}
	}

	#### 设置等级 ####
	function setLV($Player,$LV)
	{
		if($this->getServer()->getName() == 'Tesseract')
		{
			$Player->setXpLevel($LV);
		} else {
			$Player->setXp($LV);
		}
	}

	#### 房间广播 ####
	function sendMessage_Room($List,$SendMessage,$type = 'Message',$Title = True)
	{
		if(!is_Array($List)) return;
		foreach($List as $name)
		{
			$player = $this->getServer()->getPlayer($name);
			if($player instanceof Player)
			{
				if(!$Title) $player->sendMessage("§b$SendMessage");
				if($type == 'Message' && $Title) $player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※\n§b $SendMessage");
				if($type == 'title') $player->sendTitle("§8$SendMessage");
			}
		}
	}

	#### 添加房间 ####
	function addRoom(Array $Room)
	{
		$default = [
			'最低人数' => 1,
			'游戏时长' => 20,
			'生成范围' => 10,
			'粒子绵羊数量' => 1,
			'粒子绵羊捕捉难度' => 1,
			'绵羊捕捉难度' => 0,
			'第1名奖励' => 1000,
			'第2名奖励' => 500,
			'第3名奖励' => 200,
			'参赛奖励' => 10,
			'第1名印记' => 10,
			'第2名印记' => 5,
			'第3名印记' => 2,
			'第1名指令' => 'give @p 264 3',
			'第2名指令' => 'give @p 264 2',
			'第3名指令' => 'give @p 264 1',
			'参赛印记' => 1,
			'绵羊狂暴时长' => 20,
			'待加入显示内容' => [
				'第一行' => '§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌',
				'第二行' => '§c{房间号}号房间 §b人数: {房内人数}/{人数上限}',
				'第三行' => '§d⊙⊙⊙ §a点击加入房间 §d⊙⊙⊙',
				'第四行' => '§e♦♦♦♦♦♦♦♦♦♦♦♦♦♦♦♦♦♦♦♦♦♦♦♦',
			],
			'游戏时显示内容' => [
				'第一行' => '§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌',
				'第二行' => '§c{房间号}号房间 §b人数: {房内人数}/{人数上限}',
				'第三行' => '§d⊙⊙⊙ §a游戏已开始... §d⊙⊙⊙',
				'第四行' => '§eMVP: {MVP} 活:{存活人数} 亡:{阵亡人数} 羊:{剩余绵羊}',
			],
			'绵羊位置' => [],
			'玩家位置' => [],
			'木牌位置' => [
			$Room['level'],
				$Room['x'],
				$Room['y'],
				$Room['z']
			]
		];
		$this->Room['box']->set(count($this->Room['box']->getAll()) + 1,$default);
		$this->Room['box']->save();
		$default['系统'] = [
			'房间状态' => False,
			'游戏时间' => 0,
			'绵羊印记' => [],
			'房内玩家' => [],
			'存活玩家' => [],
			'阵亡玩家' => [],
			'掉线玩家' => [],
			'助杀' => [],
			'杀人' => [],
			'杀羊' => [],
			'绵羊' => []
		];
		$this->Room['box']->set(count($this->Room['box']->getAll()),$default);
	}

	#### 随机范围 ####
	function Load_XYZ(Vector3 $Pos,$Room)
	{
		if($this->Config->get('随机出生') === False) return $Pos;
		$Pos->x += floor(mt_rand(-$Room['生成范围'],$Room['生成范围']));
		$Pos->y += 0;
		$Pos->z += floor(mt_rand(-$Room['生成范围'],$Room['生成范围']));
		$level = $this->getServer()->getLevelByName($Room['木牌位置'][0]);
		if(!$level instanceof Level) return $Pos;
		while($level->getBlockIdAt($Pos->x,$Pos->y,$Pos->z) != 0)
		{
			$Pos->y += 1;
		}
		while($level->getBlockIdAt($Pos->x,$Pos->y-2,$Pos->z) == 0)
		{
			$Pos->y -= 1;
		}
		$Pos->y += 1;
		return $Pos;
	}

	#### 移除背包 ####
	function RemoveInventory(Player $Player)
	{
		$this->Room['Inventory'][$Player->getName()] = $Player->getInventory()->getContents();
		$Player->getInventory()->setContents([new Item(0,0,0)]);
	}

	#### 还原背包 ####
	function AddInventory(Player $Player)
	{
		if(!isset($this->Room['Inventory'][$Player->getName()])) return;
		$Player->getInventory()->setContents($this->Room['Inventory'][$Player->getName()]);
		unset($this->Room['Inventory'][$Player->getName()]);
	}

	#### 设置木牌 ####
	function setSign(String $Level,Vector3 $Vector3,Array $TXT)
	{
		$Level = $this->getServer()->getLevelByName($Level);
		if(!$Level instanceof Level) return;
		$Sign = $Level->getTile($Vector3);
		$TXT = $this->Get_TEXT($Level,$Vector3,$TXT);
		if($Sign instanceof Sign)
		{
			$text = $Sign->getText();
			$Sign->setText($TXT['第一行'],$TXT['第二行'],$TXT['第三行'],$TXT['第四行']);
		}
	}

	function Get_TEXT($Level,Vector3 $Vector3,Array $TXT)
	{
		$keys = $this->XYZ_Room($Vector3,$Level->getName());
		$Room = $this->Room['box']->get($keys);
		$Score = $this->Ranking($keys);
		$mvp = '无';
		$no = 1;
		foreach($Score as $name => $score)
		{
			if($no == 1) $mvp = $name;
			$no += 1;
		}
		foreach($TXT as $key => $value)
		{
			$TXT[$key] = str_replace('{房内人数}',count($Room['系统']['房内玩家']),$TXT[$key]);
			$TXT[$key] = str_replace('{人数上限}',count($Room['玩家位置']),$TXT[$key]);
			$TXT[$key] = str_replace('{存活人数}',count($Room['系统']['存活玩家']),$TXT[$key]);
			$TXT[$key] = str_replace('{阵亡人数}',count($Room['系统']['阵亡玩家']),$TXT[$key]);
			$TXT[$key] = str_replace('{MVP}',$mvp,$TXT[$key]);
			$TXT[$key] = str_replace('{房间号}',$keys,$TXT[$key]);
			$TXT[$key] = str_replace('{剩余绵羊}',count($Room['系统']['绵羊']),$TXT[$key]);
		}
		return $TXT;
	}

	#### 刷新木牌 ####
	function UpDate_Sign()
	{
		foreach($this->Room['box']->getAll() as $key => $value)
		{
			$Level = $this->getServer()->getLevelByName($value['木牌位置'][0]);
			if($Level instanceof Level)
			{
				$txt = $value['待加入显示内容'];
				if($value['系统']['房间状态'] === True) $txt = $value['游戏时显示内容'];
				$this->setSign($Level->getName(),new Vector3($value['木牌位置'][1],$value['木牌位置'][2],$value['木牌位置'][3]),$txt);
			}
		}
	}

	#### 返回房间 ####
	function XYZ_Room(Vector3 $Pos,String $Level)
	{
		foreach($this->Room['box']->getAll() as $key => $value)
		{
			if($value['木牌位置'][0] == $Level)
			{
				if($value['木牌位置'][1] == $Pos->x && $value['木牌位置'][2] == $Pos->y && $value['木牌位置'][3] == $Pos->z)
				{
					return $key;
				}
			}
		}
		return False;
	}

	#### 刷新绵羊 ####
	function unSheep($Rooms)
	{
		$Room = $this->Room['box']->get($Rooms);
		foreach($Room['绵羊位置'] as $xyz)
		{
			$this->create_sheep($Rooms,$xyz,0);
		}
	}

	#### 添加绵羊 ####
	function create_sheep($Rooms,$xyz,$note,$True = False,$type = 0)
	{
		$Room = $this->Room['box']->get($Rooms);
		$level = $this->getServer()->getLevelByName($Room['木牌位置'][0]);
		$texts = '';
		$add = 0;
		if($note > 0 or $True)
		{
			$type = 1;
			$note -= 1;
			$add = 15;
			$texts = ' 粒子羊';
		}
		$rnd = mt_rand(10000,99999);
		$XYZ = explode(':',$xyz);
		$Pos = $this->Load_XYZ(new Vector3($XYZ[0],$XYZ[1],$XYZ[2]),$Room);
		$nbt = new CompoundTag;
		$nbt->Pos = new ListTag("Pos",[
			new DoubleTag("", $Pos->x),
			new DoubleTag("", $Pos->y + 0.5),
			new DoubleTag("", $Pos->z)
		]);
		$nbt->Rotation = new ListTag("Rotation",[
			new FloatTag("", 0),
			new FloatTag("", 0)
		]);
		$nbt->SPJ = new ListTag("SPJ",[
			new FloatTag("房间", $Rooms),
			new FloatTag("时间", Time() + $Room['游戏时长'] + 10),
			new FloatTag("编号", $rnd),
			new StringTag("种类", $type),
			new StringTag("仇恨", "无")
		]);
		$Room['系统']['绵羊'][$rnd] = true;
		$pk = Entity::createEntity(13,$level,$nbt);
		$pk->spawnToAll();
		$pk->setMaxHealth(15+$add);
		$pk->setHealth(15+$add);
		$pk->setNameTag("§lSheep §eHP§e[§c ".$pk->getHealth()."/".$pk->getMaxHealth()." §e] ".$texts);
		$pk->setNameTagVisible(true);
		$pk->setNameTagAlwaysVisible(true);
		$this->Room['box']->set($Rooms,$Room);
	}

	#### 恢复世界 ####
	function undata_world(String $Room)
	{
		if(isset($this->Place[$Room]))
		{
			$blocks = $this->Place[$Room];
			for($a = count($blocks); $a >= 0; $a --)
			{
				if(isset($blocks[$a]) and $blocks[$a]->level instanceof Level)
				{
					$blocks[$a]->level->setBlockIdAt($blocks[$a]->x,$blocks[$a]->y,$blocks[$a]->z,0);
				}
			}
		}
		if(isset($this->Break[$Room]))
		{
			$blocks = $this->Break[$Room];
			for($a = count($blocks); $a >= 0; $a --)
			{
				if(isset($blocks[$a]) and $blocks[$a]->level instanceof Level)
				{
					$blocks[$a]->level->setBlockIdAt($blocks[$a]->x,$blocks[$a]->y,$blocks[$a]->z,$blocks[$a]->getId());
					$blocks[$a]->level->setBlockDataAt($blocks[$a]->x,$blocks[$a]->y,$blocks[$a]->z,$blocks[$a]->getDamage());
				}
			}
		}
	}

	#### 移除文字 ####
	function Del_Text($pk)
	{
		$pk->respawn();
		unset($pk);
	}

	#### 获取排行 ####
	function Ranking(String $Rooms)
	{
		$Room = $this->Room['box']->get($Rooms);
		$Array = [];
		foreach($Room['系统']['房内玩家'] as $Name)
		{
			$Array[$Name] = $this->Score($Rooms,$Name);
		}
		arsort($Array);
		return $Array;
	}

	#### 分数计算 ####
	function Score(String $Rooms,String $Name)
	{
		$Room = $this->Room['box']->get($Rooms);
		$Sheep = $Room['系统']['杀羊'][$Name] * 5;
		$Playe = $Room['系统']['杀人'][$Name] * 20;
		$HKill = $Room['系统']['助杀'][$Name] * 2;
		$Sum = $Sheep + $Playe + $HKill;
		$Sum += $Room['系统']['额外'][$Name];
		if(in_Array($Name,$Room['系统']['阵亡玩家'])) $Sum -= 10;
		if($Sum < 0) $Sum = 0;
		return $Sum;
	}

	#### 停止游戏 ####
	function StopGame(String $Admin = 'Admin',$Rooms)
	{
		$Room = $this->Room['box']->get($Rooms);
		$this->sendMessage_Room($Room['系统']['房内玩家'],"§4[警告]§b管理员§8[".$Admin."]§b强制结束游戏!");
		$level = $this->getServer()->getLevelByName($Room['木牌位置'][0]);
		$Room['系统']['房间状态'] = False;
		$Room['系统']['游戏时间'] = 0;
		$this->Room['box']->set($Rooms,$Room);
		if($level instanceof Level)
		{
			foreach($level->getEntities() as $Entity)
			{
				if(!$Entity instanceof Player)
				{
					if(isset($Entity->namedtag->SPJ) && $Entity->namedtag->SPJ[0] == $Rooms)
					{
						$Entity->namedtag->gjz = 'ADMIN';
						$Entity->kill();
					}
				}
			}
			foreach($Room['系统']['房内玩家'] as $name)
			{
				$player = $this->getServer()->getPlayer($name);
				$this->Room_Del_Player($player);
				$this->setLV($player,$this->EXP[$name]);
			}
		}
		$this->undata_world($Rooms);
	}

	#### 游戏结束 ####
	function OverGame($Rooms)
	{
		$Room = $this->Room['box']->get($Rooms);
		$level = $this->getServer()->getLevelByName($Room['木牌位置'][0]);
		$Room['系统']['房间状态'] = False;
		$Room['系统']['游戏时间'] = 0;
		$this->Room['box']->set($Rooms,$Room);
		$Ranking = $this->Ranking($Rooms);
		$no = 1;
		$Money = True;
		if($level instanceof Level)
		{
			foreach($level->getEntities() as $Entity)
			{
				if(!$Entity instanceof Player)
				{
					if(isset($Entity->namedtag->SPJ) && $Entity->namedtag->SPJ[0] == $Rooms)
					{
						$Entity->namedtag->gjz = 'ADMIN';
						$Entity->kill();
					}
				}
			}
			foreach($Room['系统']['房内玩家'] as $name)
			{
				$player = $this->getServer()->getPlayer($name);
				$this->Room_Del_Player($player);
			}
		}
		foreach($Ranking as $name => $Socre)
		{
			if($no >= 1 && $no <= 3)
			{
				$this->sendMessage_Room($Room['系统']['房内玩家'],"§bNO".$no."§8<".$name."> §b印记:§6".$Room['系统']['绵羊印记'][$name]." §b杀羊:§6".$Room['系统']['杀羊'][$name]." §b杀人:§6".$Room['系统']['杀人'][$name]." §b助杀:§6".$Room['系统']['助杀'][$name]." §b总分:§6".$Socre,'Message',False);
				$this->sendMessage_Room($Room['系统']['房内玩家'],"§c第".$no."名",'title');
				if($no == 1 && $Socre <= 10) $Money = False;
				if($Money)
				{
					$player = $this->getServer()->getPlayer($name);
					if($player instanceof Player)
					{
						if(isset($Room['第'.$no.'名指令']))
						{
							$command = str_replace('@p',$name,$Room['第'.$no.'名指令']);
							$this->getServer()->dispatchCommand(new ConsoleCommandSender,$command);
						}
					}
				} else {
					$this->sendMessage_Room($Room['系统']['房内玩家'],"§4本局过于放水,无任何奖励!",'title');
				}
			}
			$no += 1;
		}
		$this->undata_world($Rooms);
	}
	
	public function ZXDA_load()
	{
		$data=ZXDA::getInfo();
		ZXDA::tokenCheck('OTcyOTg2MzU4NDY1NDQ3ODk0NjkwNTc3MjE2Nzg1MTU1MzQ1MDUyNzYzOTIyMzUwMzcyODExMjgxMjUzNTkyMzEwNzU1MzA3MTQxNDgxNDY2MjU0NjA5MzI2MTgwMzI2MDg1Mjg2MzI5MjI1NzIyMzI2MjE4NTQxMzcxMjgxOTkwODYzMDk1MzY0NjEyNDM2OTk0MjkwNDg1NjQ0MjU5ODU2NjIzODA3Mjk4ODU0OTA3MDI2MzQ3OTU0ODQxODk2NjQwNzY2NDE1NTYzNTI3NzA0OTgwMzc3NDQzNTE0OTQyODMyNjcz');
	}

	//////////////////////////////////////////////////////
	///					服务器自带函数				   ///
	//////////////////////////////////////////////////////
	
	public function onLoad()
	{
		ZXDA::init(796,$this);
		ZXDA::requestCheck();
	}

	#### 加载函数 ####
	public function onEnable()
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->info('§2绵羊印记[SPJ]插件加载中...');
		$this->ZXDA_load();
		$this->Root = $this->getDataFolder();
		@mkdir($this->Root);
		$this->Room['box'] = new Config($this->Root . 'Room.yml',Config::YAML,[]);
		$this->Config = new Config($this->Root . 'Config.yml',Config::YAML,[
			'第一名全服通知' => True,
			'关服结束游戏归还背包' => True,
			'禁用全部指令' => True,
			'随机出生' => True,
			'位移拉回' => True,
			'切换背包' => True,
			'游戏物品' => True,
			'不可丢弃' => True,
			'等待粒子' => True,
			'绵羊粒子' => True,
			'玩家随机出生' => True,
			'开始时介绍游戏规则' => True,
			'粒子绵羊死亡反噬' => False,
			'发生异常清空房间' => True,
			'游戏快结束时绵羊狂暴' => True,
			'提示方式' => 'message'
		]);
		foreach($this->Room['box']->getAll() as $key => $Room)
		{
			$Room['系统'] = [
				'房间状态' => False,
				'游戏时间' => 0,
				'房内玩家' => [],
				'存活玩家' => [],
				'阵亡玩家' => [],
				'掉线玩家' => [],
				'绵羊印记' => [],
				'助杀' => [],
				'杀人' => [],
				'杀羊' => [],
				'绵羊' => [],
				'额外' => []
			];
			$this->Room['box']->set($key,$Room);
		}
		$this->getLogger()->info('§a#加载完成!');
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,'Task']),20);
	}

	#### 关闭函数 ####
	public function onDisable()
	{
		if(count($this->Room['Inventory']) > 0)
		{
			foreach($this->Room['Inventory'] as $name => $Inventory)
			{
				$player = $this->getServer()->getPlayer($name);
				if($player instanceof Player)
				{
					$this->AddInventory($player);
				}
			}
		}
		$this->Room['box'] = new Config($this->Root . 'Room.yml',Config::YAML,[]);
		foreach($this->Room['box']->getAll() as $key => $Room)
		{
			if(isset($Room['系统']))
			{
				unset($Room['系统']);
				$this->Room['box']->set($key,$Room);
				$this->Room['box']->save();
			}
		}
	}

	#### 指令事件 ####
	public function CommandEvent(PlayerCommandPreprocessEvent $event)
	{
		$player = $event->getPlayer();
		$name = $player->getName();
		if(!isset($this->Room['Room'][$name])) return;
		$say = $event->getMessage();
		if(strstr($say , '/绵羊印记 ')) return;
		if(strstr($say , '/'))
		{
			$player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬§6※ §b游戏中...不能使用任何指令!");
			$event->setCancelled();
			unset($event);
		}
		unset($this);
	}

	#### 方块破坏 ####
	public function BreakEvent(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		$name = $player->getName();
		$block = $event->getBlock();
		if(isset($this->Room['Room'][$name]))
		{
			$this->Break[$this->Room['Room'][$name]][] = $block;
		}
	}

	#### 方块放置 ####
	public function PlaceEvent(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		$name = $player->getName();
		$block = $event->getBlock();
		if(isset($this->Room['Room'][$name]))
		{
			$this->Place[$this->Room['Room'][$name]][] = $block;
		}
	}

	#### 玩家退出 ####
	public function QuitPlayer(PlayerQuitEvent $event)
	{
		$player = $event->getPlayer();
		$name = $player->getName();
		if(isset($this->Room['Room'][$name]))
		{
			$Room = $this->Room['box']->get($this->Room['Room'][$name]);
			$Room['系统']['掉线玩家'][] = $name;
			foreach($Room['系统']['存活玩家'] as $key => $value)
			{
				if($value == $name) unset($Room['系统']['存活玩家'][$key]);
			}
			$this->unLoad[$name] = $this->Room['Room'][$name];
			$this->Room['box']->set($this->Room['Room'][$name],$Room);
			$this->sendMessage_Room($Room['系统']['房内玩家'],"§6※§b §8[".$name."]§b掉线!");
			$this->Room_Del_Player($player);
		}
	}

	#### 指令函数 ####
	public function onCommand(CommandSender $sender,Command $command,$label,array $args)
	{
		if($command->getName() == '绵羊印记')
		{
			$name = $sender->getName();
			if(!isset($args[0]) || $args[0] == 'help')
			{
				$sender->sendMessage("§a▁▁▁▁▁▁▁▁ §8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §a▁▁▁▁▁▁▁▁");
				$sender->sendMessage("§a▏♈ §6/绵羊印记 退出 §e<退出房间> §8[玩家]");
				//$sender->sendMessage("§a▏♈ §6/绵羊印记 join [房间号] §e<快速加入房间> §8[玩家]");
				//$sender->sendMessage("§a▏♈ §6/绵羊印记 list §e<房间列表> §8[玩家]");
				$sender->sendMessage("§a▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁▁");
				$sender->sendMessage("§a▏♈ §6/绵羊印记 创建 §e<创建房间> §8[管理]");
				$sender->sendMessage("§a▏♈ §6/绵羊印记 玩家 [房间号] §e<重新设置玩家出生点> §8[管理]");
				$sender->sendMessage("§a▏♈ §6/绵羊印记 绵羊 [房间号] §e<重新设置绵羊出生点> §8[管理]");
				$sender->sendMessage("§a▏♈ §6/绵羊印记 停止 [房间号] §e<强制停止此房间的游戏> §8[管理]");
				$sender->sendMessage("§a▏♈ §6/绵羊印记 信息 [房间号] §e<查看房间设置> §8[管理]");
				//$sender->sendMessage("§a▏♈ §6/绵羊印记 set [房间号] [属性] [值] §e<在线设置房间> §8[管理]");
				return true;
			}
			if($args[0] == '退出')
			{
				$this->Room_Del_Player($sender);
				return true;
			}
			if($args[0] == '创建')
			{
				$this->Interact[$name]['Sign'] = True;
				$sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 请点击一个木牌!");
				return true;
			}
			if($args[0] == '信息')
			{
				if(!isset($args[1])) return $sender->sendMessage("§a▏♈ §6/绵羊印记 info [房间号] §8<查看房间设置>");
				$Room = $this->Room['box']->get($args[1]);
				$sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬§6※§b $args[1]号房间设置 §6※§c▬ §9印记 §8▄▃▂▌ §6※§b ");
				foreach($Room as $key => $value)
				{
					if($value === True) $value = 'True';
					if($value === False) $value = 'False';
					if(!is_Array($value)) $sender->sendMessage("§a▏§b$key §e-> §6 $value");
				}
				$sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬§6※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※※§b ");
				return true;
			}
			if($args[0] == '停止')
			{
				if(!isset($args[1])) return $sender->sendMessage("§a▏♈ §6/绵羊印记 stop [房间号] §8<强制停止此房间的游戏>");
				$this->StopGame($name,$args[1]);
				return true;
			}
			if($args[0] == '玩家')
			{
				if(!isset($args[1])) return $sender->sendMessage("§a▏♈ §6/绵羊印记 玩家 [房间号] §8<重新设置玩家出生点>");
				if(!$this->Room['box']->exists($args[1])) return $sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 不存在此房间号!");
				if(!isset($this->Interact[$name]))
				{
					$this->Interact[$name]['spawn'] = [];
					$sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 请点击多个地点来设置玩家出生点!");
					$sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 重新输入/绵羊印记 玩家 $args[1] 退出并保存设置!");
				}
				else
				{
					$Room = $this->Room['box']->get($args[1]);
					$Room['玩家位置'] = $this->Interact[$name]['spawn'];
					$this->Room['box']->set($args[1],$Room);
					$this->Room['box']->save();
					unset($this->Interact[$name]);
					$sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 已保存并退出[玩家出生点]设置!");
				}
				return true;
			}
			if($args[0] == '绵羊')
			{
				if(!isset($args[1])) return $sender->sendMessage("§a▏♈ §6/绵羊印记 绵羊 [房间号] §8<重新设置绵羊出生点>");
				if(!$this->Room['box']->exists($args[1])) return $sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 不存在此房间号!");
				if(!isset($this->Interact[$name]))
				{
					$this->Interact[$name]['Sheep'] = [];
					$sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 请点击多个地点来设置绵羊出生点!");
					$sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 重新输入/绵羊印记 绵羊 $args[1] 退出并保存设置!");
				}
				else
				{
					$Room = $this->Room['box']->get($args[1]);
					$Room['绵羊位置'] = $this->Interact[$name]['Sheep'];
					$this->Room['box']->set($args[1],$Room);
					$this->Room['box']->save();
					unset($this->Interact[$name]);
					$sender->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 已保存并退出[绵羊出生点]设置!");
					$sender->sendMessage("§a▏♈ §6/绵羊印记 spawn $args[1] §8<重新设置玩家出生点>");
				}
				return true;
			}
		}
	}

	#### 点击事件 ####
	public function InteractEvent(PlayerInteractEvent $event)
	{
		$block = $event->getBlock();
		$ID = $block->getID();
		$player = $event->getPlayer();
		$name = $player->getName();
		$level = $player->level;
		if($player->getInventory()->getItemInHand()->getId() == 345)
		{
			if(isset($this->TEXT[$name]))
			{
				if($this->TEXT[$name] > Time())
				{
					$player->sendMessage('§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b物品正在冷却中...');
					return;
				} else {
					$this->TEXT[$name] = Time() + 10;
				}
			} else {
				$this->TEXT[$name] = Time() + 10;
			}
			if(isset($this->Room['Room'][$name]))
			{
				$Room = $this->Room['box']->get($this->Room['Room'][$name]);
				$Score = $this->Ranking($this->Room['Room'][$name]);
				$text = "§8▌▂▃▄§d 绵羊 §c▬§6※§b ".$this->Room['Room'][$name]."号房间状态 §6※§c▬ §9印记 §8▄▃▂▌ §6
     §e房间人数: ".count($Room['系统']['房内玩家'])."/".count($Room['玩家位置'])." §d阵亡:".count($Room['系统']['阵亡玩家'])."  存活:".count($Room['系统']['存活玩家'])."  §9绵羊:".count($Room['系统']['绵羊'])." 掉线:".count($Room['系统']['掉线玩家']);
     			$no = 1;
     			$Note = '存活';
				foreach($Score as $name => $score)
				{
     				$color = 'a';
					if(in_Array($name,$Room['系统']['阵亡玩家'])) $Note = '阵亡';
					if(in_Array($name,$Room['系统']['掉线玩家'])) $Note = '掉线';
					if($Note == '阵亡') $color = '8';
					if($Note == '掉线') $color = '4';
					$no == 1 ? $tx = '§9MVP' : $tx = '§8NO'.$no;
					$text .= "\n$tx §$color$name 印记:".$Room['系统']['绵羊印记'][$name]." 杀羊:".$Room['系统']['杀羊'][$name]." 杀人:".$Room['系统']['杀人'][$name]." 助杀:".$Room['系统']['助杀'][$name]." 总分:".$score." 状态:".$Note;
					$no += 1;
				}
				$txt = new Text();
				$txt->spawn(new Vector3($block->x,$block->y+3,$block->z),$text,$player);
				$this->getServer()->getScheduler()->scheduleDelayedTask(new CallbackTask([$this,"Del_Text"],[$txt]), 180);
			}
		}
		if(isset($this->Interact[$name]))//点击开启
		{
			if(isset($this->Interact[$name]['spawn']))
			{
				$XYZ = $block->x . ':' . $block->y . ':' . $block->z;
				if(in_Array($XYZ,$this->Interact[$name]['spawn'])) return $player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 已存在此坐标!");
				$this->Interact[$name]['spawn'][] = $block->x . ':' . $block->y . ':' . $block->z;
				return $player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 已添加一个坐标! §8[共:".count($this->Interact[$name]['spawn'])."个]");
			}
			if(isset($this->Interact[$name]['Sheep']))
			{
				$XYZ = $block->x . ':' . $block->y . ':' . $block->z;
				if(in_Array($XYZ,$this->Interact[$name]['Sheep'])) return $player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 已存在此坐标!");
				$this->Interact[$name]['Sheep'][] = $block->x . ':' . $block->y . ':' . $block->z;
				return $player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 已添加一个坐标! §8[共:".count($this->Interact[$name]['Sheep'])."个]");
			}
			if(isset($this->Interact[$name]['Sign']))
			{
				if($ID == 323 || $ID == 63 || $ID == 68)
				{
					if($this->XYZ_Room(new Vector3($block->x,$block->y,$block->z),$level->getName()) !== False)
					{
						$player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 此房间已存在!");
						return True;
					}
					$this->addRoom(['level' => $level->getName(),'x' => $block->x,'y' => $block->y,'z' => $block->z]);
					$this->setSign($level->getName(),new Vector3($block->x,$block->y,$block->z),$this->Room['box']->get(count($this->Room['box']->getAll()))['待加入显示内容']);
					unset($this->Interact[$name]);
					$player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b 成功添加房间[".count($this->Room['box']->getAll())."]!");
					$player->sendMessage("§8▌▂▃▄§d 绵羊 §c▬ §9印记 §8▄▃▂▌ §6※§b /绵羊印记 sheep ".count($this->Room['box']->getAll())." §8<设置绵羊出生点>");
					return True;
				}
			}
			return;
		}
		if($ID == 323 || $ID == 63 || $ID == 68)
		{
			$Room = $this->XYZ_Room(new Vector3($block->x,$block->y,$block->z),$level->getName());
			if($Room !== False)
			{
				return $this->Room_Add_Player($player,$Room);
			}
		}
	}

	#### 攻击事件 ####
	public function EntityDamageEvent(EntityDamageEvent $event)
	{
		if($event instanceof EntityDamageByEntityEvent)
		{
			if(!$event->isCancelled())
			{
				$Damager = $event->getDamager();
				$Entity = $event->getEntity();
				if($Damager instanceof Player)
				{
					if(!$Entity instanceof Player)
					{
						if(isset($Entity->namedtag->SPJ))
						{
							if(isset($Entity->namedtag->gjz) && $Entity->namedtag->gjz != $Damager->getName())
							{
								$Entity->namedtag->old_gjz = $Entity->namedtag->gjz;
							}
							$Entity->setNameTag("§lSheep §eHP§e[§c ".$Entity->getHealth()."/".$Entity->getMaxHealth()." §e]");
							$Entity->namedtag->gjz = $Damager->getName();
						}
					} else {
						if($Entity->getHealth() - $event->getDamage() < 0)
						{
							if(isset($this->Room['Room'][$Entity->getName()]) and isset($this->Room['Room'][$Damager->getName()]))
							{
								$Entity_Score = $this->Score($this->Room['Room'][$Entity->getName()],$Entity->getName()) / 2;
								$Entity_Room = $this->Room['box']->get($this->Room['Room'][$Entity->getName()]);
								$Damage_Room = $this->Room['box']->get($this->Room['Room'][$Damager->getName()]);
								$Damage_Room['系统']['绵羊印记'][$Damager->getName()] += $Entity_Room['系统']['绵羊印记'][$Entity->getName()];
								$Damage_Room['系统']['杀人'][$Damager->getName()] += 1;
								$Damage_Room['系统']['额外'][$Damager->getName()] += $Entity_Score;
								$this->Room['box']->set($this->Room['Room'][$Damager->getName()],$Damage_Room);
							}
						}
					}
				}
			}
		}
	}
	
	#### 玩家死亡 ####
	public function PlayerDeathEvent(PlayerDeathEvent $event)
	{
        $Entity = $event->getEntity();
        $name = $Entity->getName();
        if(isset($this->Room['Room'][$name]))
        {
            $Room = $this->Room['box']->get($this->Room['Room'][$name]);
            foreach($Room['系统']['存活玩家'] as $key => $value)
            {
                if($Room['系统']['存活玩家'][$key] == $name) unset($Room['系统']['存活玩家'][$key]);
            }
            $Room['系统']['阵亡玩家'][] = $name;
            $Entity->setGamemode(3);
            $this->Room['box']->set($this->Room['Room'][$name],$Room);
            $this->sendMessage_Room($Room['系统']['房内玩家'],"§6※§b §8[".$name."]§b死亡!");
            $event->setDrops([Item::get(Item::AIR, 0, 1)]);
        }
	}
	
	#### 生物死亡 ####
	public function EntityDeathEvent(EntityDeathEvent $event)
	{
		$Entity = $event->getEntity();
		if(!$Entity instanceof Player)
		{
			if(isset($Entity->namedtag->SPJ))
			{
				$Rooms = $Entity->namedtag->SPJ[0];
				$Room = $this->Room['box']->get($Rooms);
				if(isset($Entity->namedtag->gjz))
				{
					if($Entity->namedtag->gjz == 'ADMIN') return;
					$Room['系统']['杀羊'][$Entity->namedtag->gjz] += 1;
					unset($Room['系统']['绵羊'][$Entity->namedtag->SPJ[2]]);
					$Room['系统']['绵羊印记'][$Entity->namedtag->gjz] += 1;
					$txt = '';
					if(isset($Entity->namedtag->old_gjz))
					{
						$Room['系统']['助杀'][$Entity->namedtag->old_gjz] += 1;
						$txt = "§9[".$Entity->namedtag->old_gjz."]助杀";
					}
					var_dump($Entity->namedtag->SPJ[3]);
					if($Entity->namedtag->SPJ[3] === 1)
					{
						$player = $this->getServer()->getPlayer($Entity->namedtag->gjz);
						if($player instanceof Player)
						{
							$player->addEffect(Effect::getEffect(1)->setAmplifier(40*20)->setDuration(0)->setVisible(True));
							$player->setNameTag('§e[已捕获粒子羊] §6'.$player->getName());
							$player->sendMessage('§e[已捕获粒子羊] §6获得40秒速度,但你将成为了其他玩家的目标!');
							$player->getInventory()->addItem(new Item(35,1,1));
							$Room['系统']['额外'][$Entity->namedtag->gjz] += 30;
						}
					} else {
						$player = $this->getServer()->getPlayer($Entity->namedtag->gjz);
						if($player instanceof Player)
						{
							$player->getInventory()->addItem(new Item(35,0,1));
						}
					}
					$this->Room['box']->set($Entity->namedtag->SPJ[0],$Room);
					$this->sendMessage_Room($Room['系统']['房内玩家'],"§8[".$Entity->getName()."]§b被§8[".$Entity->namedtag->gjz."]§b杀死! " . $txt);
				} else {
					$this->create_sheep($Rooms,$Room['绵羊位置'][0],0,$Entity->namedtag->SPJ[3]);
					$this->sendMessage_Room($Room['系统']['房内玩家'],"绵羊§8编号为[".$Entity->namedtag->SPJ[2]."]§b非人为死亡已重生!");
				}
				$event->setDrops([Item::get(Item::AIR, 0, 1)]);
			}
		}
	}
}

class Text
{
	protected $pos;
	protected $text;
	protected $entityId;
	protected $invisible = true;

	public function isInvisible()
	{
 		return $this->invisible;
 	}

 	public function spawn($pos, $text = "",$player)
 	{
		$this->pos = $pos;
		$this->text = $text;
		$this->player = $player;
		$this->level = $player->level;
		$this->entityId = Entity::$entityCount ++;
		$pk = new AddEntityPacket();
 		$pk->eid = $this->entityId;
		$pk->type = 11;
		$pk->x = $this->pos->x;
		$pk->y = $this->pos->y;
		$pk->z = $this->pos->z;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$flags = 0;
		$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
		$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
  
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $text],
			38 => [7, -1],
			39 => [3, 0.001]
		];
  		Server::getInstance()->broadcastPacket([$player],$pk);
  		$this->invisible = true;
  	}

  	public function respawn()
  	{
 		$pk = new RemoveEntityPacket();
 		$pk->eid = $this->entityId;
 		Server::getInstance()->broadcastPacket([$this->player], $pk);
 		$this->invisible = false;
 	}
}

class ZXDA
{
	const API_VERSION=5013;
	
	private static $_PLUGIN=null;
	
	public static function init($pid,$plugin)
	{
		if(!\is_numeric($pid))
		{
			self::unknownError(10003);
			exit();
		}
		self::ks('PID',$pid);
		self::$_PLUGIN=$plugin;
	}
	
	public static function killit($msg)
	{
		if(self::$_PLUGIN===\null)
		{
			echo('抱歉,插件授权验证失败[SDK:'.self::API_VERSION."]:\n".$msg);
		}
		else
		{
			@self::$_PLUGIN->getLogger()->warning('§e抱歉,插件授权验证失败[SDK:'.self::API_VERSION.']:');
			@self::$_PLUGIN->getLogger()->warning('§e'.$msg);
			@self::$_PLUGIN->getServer()->forceShutdown();
		}
		@\pocketmine\kill(\getmypid());
		exit();
	}
	
	public static function getInfo()
	{
		try
		{
			self::checkKernelVersion();
			$manager=self::$_PLUGIN->getServer()->getPluginManager();
			if(!($plugin=$manager->getPlugin('ZXDAKernel')) instanceof \ZXDAKernel\Main)
			{
				self::unknownError(10010);
				exit();
			}
			if(($data=\ZXDAKernel\Main::getPluginInfo(self::ks('PID')))===\false)
			{
				self::unknownError(10011);
				exit();
			}
			if(\count($data=\explode(',',$data))!=2)
			{
				return array(
					'success'=>\false,
					'message'=>'未知错误');
			}
			return array(
				'success'=>\true,
				'version'=>\base64_decode($data[0]),
				'update_info'=>\base64_decode($data[1]));
		}
		catch(\Exception $err)
		{
			@\file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'error.dump',\var_export($err,\true));
			self::unknownError(10012);
			exit();
		}
	}
	
	public static function tokenCheck($key)
	{
		try
		{
			self::checkKernelVersion();
			$manager=self::$_PLUGIN->getServer()->getPluginManager();
			if(!($plugin=$manager->getPlugin('ZXDAKernel')) instanceof \ZXDAKernel\Main)
			{
				self::unknownError(10013);
			}
			if(!$manager->isPluginEnabled($plugin))
			{
				$manager->enablePlugin($plugin);
			}
			$key=\base64_decode($key);
			if(($token=\ZXDAKernel\Main::getResultToken(self::ks('PID')))===\false)
			{
				self::unknownError(10014);
			}
			$token=self::rsa_decode(\base64_decode($token),$key,768);
			if(self::kv('TOKEN',$token)!==\true)
			{
				self::unknownError(10015);
			}
		}
		catch(\Exception $err)
		{
			@\file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'error.dump',\var_export($err,\true));
			self::unknownError(10009);
			exit();
		}
	}
	
	public static function requestCheck()
	{
		try
		{
			self::checkKernelVersion();
			if(self::kv('TOKEN')!==\null)
			{
				self::unknownError(10006);
				exit();
			}
			self::kv('TOKEN',\sha1(\strrev($t=\sha1(\uniqid().\var_export($_SERVER,\true)))));
			if(!@\ZXDAKernel\Main::requestAuthorization(self::ks('PID'),self::$_PLUGIN,\substr($t.'Moe',0,-3)))
			{
				self::unknownError(10007);
				exit();
			}
		}
		catch(\Exception $err)
		{
			@\file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'error.dump',\var_export($err,\true));
			self::unknownError(10008);
			exit();
		}
	}
	
	public static function isTrialVersion()
	{
		try
		{
			self::checkKernelVersion();
			return \ZXDAKernel\Main::isTrialVersion(self::ks('PID'));
		}
		catch(\Exception $err)
		{
			@\file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'error.dump',\var_export($err,\true));
			self::unknownError(10016);
			exit();
		}
	}
	
	public static function checkKernelVersion()
	{
		if(self::ks('PID')===\false)
		{
			self::unknownError(10004);
			exit();
		}
		if(!\class_exists('\\ZXDAKernel\\Main'))
		{
			self::killit('请到 https://pl.zxda.net/ 下载并安装最新版ZXDA Kernel后再使用此插件');
			exit();
		}
		$version=@\ZXDAKernel\Main::getVersion();
		if($version<self::API_VERSION)
		{
			self::killit('当前ZXDA Kernel版本太旧,请到 https://pl.zxda.net/ 下载并安装最新版ZXDA Kernel后再使用此插件');
			exit();
		}
		return $version;
	}
	
	private static function ks($key,$val=null)
	{
		static $storage=array();
		if(\is_numeric($key))
		{
			self::unknownError(10001);
			exit();
		}
		if(isset($storage[$key]))
		{
			if($val!==\null)
			{
				self::unknownError(10002);
				exit();
			}
		}
		else if($val===\null)
		{
			return \null;
		}
		else
		{
			$storage[$key]=$val;
		}
		return $storage[$key];
	}
	
	private static function kv($key,$val=null)
	{
		static $storage=array();
		if(\is_numeric($key))
		{
			self::unknownError(10005);
			exit();
		}
		if(isset($storage[$key]))
		{
			return $storage[$key]===$val;
		}
		else if($val===\null)
		{
			return \null;
		}
		else
		{
			$storage[$key]=$val;
		}
		return \false;
	}
	
	private static function unknownError($code)
	{
		self::killit('未知错误[S-'.$code.'],请访问 https://pl.zxda.net/docs/autherr 获取帮助');
	}
	
	//////////////////////////
	
	public static function rsa_encode($message,$modulus,$keylength=1024,$isPriv=\true)
    {
        $result=array();
        while(\strlen($msg=\substr($message,0,$keylength/8-5))>0)
        {
            $message=\substr($message,\strlen($msg));
            $result[]=self::number_to_binary(self::pow_mod(self::binary_to_number(self::add_PKCS1_padding($msg,$isPriv,$keylength/8)),'65537',$modulus),$keylength/8);
            unset($msg);
        }
        return \implode('***&&&***',$result);
    }
	
    public static function rsa_decode($message,$modulus,$keylength=1024)
    {
        $result=array();
        foreach(\explode('***&&&***',$message) as $message)
        {
            $result[]=self::remove_PKCS1_padding(self::number_to_binary(self::pow_mod(self::binary_to_number($message),'65537',$modulus),$keylength/8),$keylength/8);
            unset($message);
        }
        return \implode('',$result);
    }
	
    private static function pow_mod($p,$q,$r)
    {
        $factors=array();
        $div=$q;
        $power_of_two=0;
        while(\bccomp($div,'0')==1)
        {
            $rem=\bcmod($div,2);
            $div=\bcdiv($div,2);
            if($rem)
            {
                \array_push($factors,$power_of_two);
            }
            $power_of_two++;
        }
        $partial_results=array();
        $part_res=$p;
        $idx=0;
        foreach($factors as $factor)
        {
            while($idx<$factor)
            {
                $part_res=\bcpow($part_res,'2');
                $part_res=\bcmod($part_res,$r);
                $idx++;
            }
            \array_push($partial_results,$part_res);
        }
        $result='1';
        foreach($partial_results as $part_res)
        {
            $result=\bcmul($result,$part_res);
            $result=\bcmod($result,$r);
        }
        return $result;
    }
	
    private static function add_PKCS1_padding($data,$isprivateKey,$blocksize)
    {
        $pad_length=$blocksize-3-\strlen($data);
        if($isprivateKey)
        {
            $padding="\x00\x02";
            for($i=0; $i<$pad_length; $i++)
            {
                $padding.=\chr(\mt_rand(1,255));
            }
        }
        else
        {
            $padding="\x00\x01".\str_repeat("\xFF",$pad_length);
        }
        return $padding."\x00".$data;
    }
	
    private static function remove_PKCS1_padding($data,$blocksize)
    {
        \assert(\strlen($data)==$blocksize);
        $data=\substr($data,1);
        if($data{0}=="\0")
        {
            return '';
        }
        \assert($data{0}=="\x01" || $data{0}=="\x02");
        $offset=\strpos($data,"\0",1);
        return \substr($data,$offset+1);
    }
	
    private static function binary_to_number($data)
    {
        $radix='1';
        $result='0';
        for($i=\strlen($data)-1;$i>=0;$i--)
        {
            $result=\bcadd($result,\bcmul(\ord($data{$i}),$radix));
            $radix=\bcmul($radix,'256');
        }
        return $result;
    }
	
    private static function number_to_binary($number,$blocksize)
    {
        $result='';
        $div=$number;
        while($div>0)
        {
            $result=\chr(\bcmod($div,'256')).$result;
            $div=\bcdiv($div,'256');
        }
        return \str_pad($result,$blocksize,"\x00",\STR_PAD_LEFT);
    }
}
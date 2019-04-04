<?php
namespace SSHOP;

use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use SSHOP\SHOP\SHOP;
use SSHOP\SHOP\CODE;
use pocketmine\item\Item;
use pocketmine\tile\Sign;
use SSHOP\File;
use pocketmine\math\Vector3;
use onebone\economyapi\EconomyAPI;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\server\DataPacketReceiveEvent;
use \ZXDAConnector\Main as ZXDAConnector;
use pocketmine\network\protocol\InteractPacket;

use pocketmine\event\block\SignChangeEvent;

class SSHOP extends PluginBase implements Listener
{
	/*
		- 1.1.0 by slm47888
		- 加入附近判断箱子机制
		- 加入"回收详细"
		- 加入特殊值判断
		- 更新物品数据库
		- 修复icovn兼容
		- 修复替换时产生的错误
		- 修复部分面板商店打不开
		- 修复tess和pro扣除等级时产生的溢出
		- 修复类型B商店时必须大写
		- 修复抖动反馈产生的残影
	*/
	public $shop = [];
	public $Sneak = [];
	public $Time = [];
	public $max = [];

	public function onLoad()
	{
		ZXDA::init(815,$this);
		ZXDA::requestCheck();
	}

	public function onEnable()
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->getLogger()->info('§e--------------------------------------');
		$this->getLogger()->info('§2浮空商店加载中...');
		$this->ZXDA_load();
		$Load = new File($this);
		$this->getLogger()->info('§2商店已加载完成...');
		$this->getLogger()->info('§e--------------------------------------');
		$this->public = new Config($this->getDataFolder().'public.yml',Config::YAML,[]);
		$this->SHOP = new Config($this->getDataFolder().'SHOP.yml',Config::YAML,[]);
		$this->item = new Config($this->getDataFolder().'item.yml',Config::YAML,[]);
		$this->Money = new Config($this->getDataFolder().'Money.yml',Config::YAML,[]);
		$this->Spending = new Config($this->getDataFolder().'Spending.yml',Config::YAML,[]);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,'TASK']),20);
	}
	
	public function ZXDA_load()
	{
		$data=ZXDA::getInfo();
		ZXDA::tokenCheck('MTAxMDU4NDE2MDk2OTExNDA0NTExMjg2NDM4NTYwODgxMTUxNTQ0MjA1NDc0NjcwMzc3NDYwNDY5MTIwNDYzODI1OTM4ODE2NTAwNjM3NzQ5MTExODkxNDIzODMzODc5NjA1MjMwNzkyMTQwNjMyNTcxNDMxNjEyODI3MzUyMjc3ODEyNjUyMTc4NzA4OTgxNDAxMzM4NDE3NDI1MTAxMzUxNDMwMzA2ODU1MTcwOTgyNzk2NzExOTI3NjI2ODg4Njc3NzU0MDIzODczNDA1MzEwODU3NjM2MTIyODMzNTQ0NTU0NDg1MQ==');
	}

	public function ToggleSneak(PlayerToggleSneakEvent $event)
	{
		$this->Sneak[$event->getPlayer()->getName()] = $event->isSneaking();
	}

	public function JoinEvent(PlayerJoinEvent $event)
	{
		$player = $event->getPlayer();
		$name = $player->getName();
		if(!$this->Money->exists($name))
		{
			$this->Money->set($name,0);
			$this->Money->save();
		}
	}

	public function QuitEvent(PlayerQuitEvent $event)
	{
		$player = $event->getPlayer();
		$name = $player->getName();
		if(isset($this->shop[$name]))
		{
			$shops = new SHOP($this);
			$shops->removeSHOP($name);
			unset($this->shop[$name]);
		}
	}

	public function DataPacketRec(DataPacketReceiveEvent $event)
	{
		$Packet = $event->getPacket();
		if($Packet instanceof InteractPacket and $Packet->action == InteractPacket::ACTION_LEFT_CLICK)
		{
			var_dump($Packet->target);
			foreach($this->max as $api)
			{
				var_dump($api);
				var_dump($api->entityId);
				if($api->entityId == $Packet->target)
				{
					$api->respawn();
				}
			}
			if($Packet->target == 100000000)
			{
				if($event->getPlayer()->getGamemode() == 0)
				{
				}
			}
		}
	}

	public function Onlick(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$name = $player->getName();
		$level = $block->level;
		$xyz = new Vector3($block->x,$block->y,$block->z);
		$shops = new SHOP($this);
		$ID = $this->SHOP->get('设置');
		if($block->getID() == $ID['商店方块ID'] or $block->getID() == $ID['商店按钮ID'])
		{
			if($shops->Button_Yes($block->getX(),$block->getY(),$block->getZ(),$name)) return $player->sendMessage('§4-> §c此商店已被占用!');
			$shops->button_click($xyz,$name,$level);
			return;
		}
		if($block->getID() == $ID['商店中间ID'] and $player->getGamemode() == 0)
		{
			if(!isset($this->shop[$name]) and isset($this->Sneak[$name]) and $this->Sneak[$name] == true)
			{
				if($shops->Button_Yes($block->getX(),$block->getY(),$block->getZ(),$name)) return $player->sendMessage('§4-> §c此商店已被占用!');
				if($ID['附近方块检测'])
				{
					for($x = $block->x-1;$x <= $block->x+1; $x ++)
					{
						for($y = $block->y;$y <= $block->y+2; $y ++)
						{
							for($z = $block->z-1;$z <= $block->z+1; $z ++)
							{
								if($level->getBlockIdAt($x,$y,$z) == $ID['商店中间ID'])
								{
									if($x == $block->x and $y == $block->y and $z == $block->z)  continue;
									return $player->sendMessage('§4-> §c附近发现'.$this->Get_Item_Name($ID['商店中间ID'],0).'不能创建商店!');
								}
							}
						}
					}
				}
				$shops->create_shop($xyz,$level,$name,$event->getFace());
				$event->setCancelled();
				return;
			}
			else
			{
				if(isset($this->shop[$name]['block']['0-0-0']))
				{
					foreach($this->shop[$name]['block']['0-0-0'] as $v)
					{
						if($v[0] == $block->x and $v[1] == $block->y and $v[2] == $block->z)
						{
							if($shops->button_click($xyz,$name,$level) !== False) $event->setCancelled();
							return;
						}
					}
				}
			}
		}
	}

	public function onPlayerBreakBlock(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		if(!$event->isCancelled())
		{
			$block = $event->getBlock();
			foreach($this->shop as $name => $v)
			{
				$shops = new SHOP($this);
				if(isset($this->shop[$name]['block'][$block->x.'-'.$block->y.'-'.$block->z]))
				{
					if($block->getId() != 116) $event->setCancelled();
					$shops->removeSHOP($name);
					unset($this->shop[$name]);
					return;
				}
				if(isset($this->shop[$name]['block']['0-0-0']))
				{
					foreach($this->shop[$name]['block']['0-0-0'] as $v)
					{
						if($v[0] == $block->x and $v[1] == $block->y and $v[2] == $block->z)
						{
							$event->setCancelled();
							$shops->removeSHOP($name);
							unset($this->shop[$name]);
							return;
						}
					}
				}
			}
		}
	}

	public function onCommand(CommandSender $sender,Command $command,$label,array $args)
	{
		if($command == 'SSHOP' or $command == 'S')
		{
			if(!isset($args[0]) or $args[0] == 'help')
			{
				$sender->sendMessage('§e#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#');
				$sender->sendMessage('§8用灰色标记的框内值可不填,白色为默认值!橘黄为RPG附魔插件选项!');
				$sender->sendMessage('§e#§c/s add a [ID:特殊值:数量] [需货币量] [货币名称] §8[ 库存§f0 §8] [ 获得积分§f1 §8] §6[附魔ID:附魔LV] §b<上架一个"购买"商品>');
				$sender->sendMessage('§e#§c/s add b [ID:特殊值:数量] [需货币量] [货币名称] §8[ 库存§f0 §8] [ 获得积分§f1 §8] §6[附魔ID:附魔LV] §b<上架一个"回收"商品>');
				$sender->sendMessage('§e#§c/s add c [ID:特殊值:数量] [需货币量] [货币名称] §b<上架一个"个人"商品> [个人商店]');
				$sender->sendMessage('§e#§c/s add d 得到[ID:特殊值:数量] 失去[ID:特殊值:数量] §b<上架一个"兑换"商品>');
				$sender->sendMessage('§e#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#=#');
				return True;
			}
			if($args[0] == 'add')
			{
				if(strtolower($args[1]) == 'a' or strtolower($args[1]) == 'b')
				{
					if(!$sender->isOp()) return $sender->sendMessage('§a[SSHOP] >§e 非管理员无权限!');
					$Type = '购买';
					if(strtolower($args[1]) == 'b') $Type = '回收';
					if(!isset($args[4])) return false;
					if(!$this->explode($args[2])) return $sender->sendMessage('§a[SSHOP] >§e [ID:特殊值:数量]为错误值!');
					if(!is_numeric($args[3]) or $args[3] < 0) return $sender->sendMessage('§a[SSHOP] >§e [需货币量]为错误值!');
					if(!$this->Money_Name($args[4])) return $sender->sendMessage('§a[SSHOP] >§e '.$args[4].'是不存在的货币!');
					if(!isset($args[5])) $args[5] = -1;
					if(!is_numeric($args[5]) or $args[5] < -1) return $sender->sendMessage('§a[SSHOP] >§e [库存]为错误值!');
					if(!isset($args[6])) $args[6] = 0;
					if(!is_numeric($args[6]) or $args[6] < 0) return $sender->sendMessage('§a[SSHOP] >§e [积分]为错误值!');
					if(!isset($args[7])) $args[7] = -1;
					if(!is_numeric($args[7]) or $args[7] < -1) return $sender->sendMessage('§a[SSHOP] >§e [附魔ID:附魔LV]为错误值!');
					$SHOP = [
						'物品' => $this->explode($args[2]),
						'价格' => $args[3],
						'所需' => $args[4],
						'库存' => $args[5],
						'积分' => $args[6],
						'附魔' => $args[7]
					];
					$this->ADD_SHOP($Type,$SHOP);
					$sender->sendMessage('§a[SSHOP] >§e 成功添加§4'.$Type.'§e商品§c[§e §4[§a'.$args[2].'§4] §4[需货币量§a'.$args[3].'§4] [货币名称§a'.$args[4].'§4] [ 库存§a'.$args[5].'§4] [ 获得积分§a'.$args[6].'§4] [附魔ID§a'.$args[7].'§4] §c]');
					return True;
				}
				if(strtolower($args[1]) == 'c')
				{
					if(!isset($args[4])) return $sender->sendMessage('§e#§c/s add c [ID:特殊值:数量] [需货币量] [货币名称] §b<上架一个"个人"商品>');
					if(!$this->explode($args[2])) return $sender->sendMessage('§a[SSHOP] >§e [ID:特殊值:数量]为错误值!');
					if(!is_numeric($args[3]) or $args[3] < 0) return $sender->sendMessage('§a[SSHOP] >§e [需货币量]为错误值!');
					if(!$this->Money_Name($args[4])) return $sender->sendMessage('§a[SSHOP] >§e '.$args[4].'是不存在的货币!');
					$shop = $this->explode($args[2]);
					$GetItem = $this->GetItem($sender,new Item($shop[0],$shop[1],$shop[2]));
					if($GetItem !== True) return $sender->sendMessage('§a[SSHOP] >§c 背包内['.$this->Get_Item_Name($shop[0],$shop[1]).'] 还差 '.$GetItem.'个');
					$SHOP = [
						'物品' => $this->explode($args[2]),
						'价格' => $args[3],
						'所需' => $args[4],
						'卖家' => $sender->getName()
					];
					$this->RemoveItem($sender,new Item($shop[0],$shop[1],$shop[2]));
					$this->ADD_SHOP('个人',$SHOP);
					$sender->sendMessage('§a[SSHOP] >§e 成功添加§4个人§e商品§c[§e §4[§a'.$args[2].'§4] §4[需货币量§a'.$args[3].'§4] [货币名称§a'.$args[4].'§4] §c]');
					return True;
				}
				if(strtolower($args[1]) == 'd')
				{
					if(!$sender->isOp()) return $sender->sendMessage('§a[SSHOP] >§e 非管理员无权限!');
					if(!isset($args[3])) return $sender->sendMessage('§e#§c/s add d 得到[ID:特殊值:数量] 失去[ID:特殊值:数量] §b<上架一个"兑换"商品>');
					if(!$this->explode($args[2]) or !$this->explode($args[3])) return $sender->sendMessage('§a[SSHOP] >§e [ID:特殊值:数量]为错误值!');
					$SHOP = [
						'所需' => $this->explode($args[2]),
						'获得' => $this->explode($args[3]),
					];
					$this->ADD_SHOP('兑换',$SHOP);
					$sender->sendMessage('§a[SSHOP] >§e 成功添加§4兑换§e商品§c[§e §4[得到物品§a'.$args[2].'§4] §4[失去物品§a'.$args[3].'§4] §c]');
					return True;
				}
			}
		}
	}

	function TASK()
	{
		$players = [];
		foreach($this->getServer()->getOnlinePlayers() as $player) $players[] = $player->getName();
		foreach($this->Spending->getAll() as $name => $values)
		{
			if(in_array($name,$players))
			{
				$player = $this->getServer()->getPlayer($name);
				foreach($values as $value)
				{
					$this->Moneys($player,$value[0],$value[1],'+');
					$player->sendMessage(''.$value[2]);
				}
				$this->Spending->remove($name);
				$this->Spending->save();
			}
		}
		foreach($this->Time as $name => $Time)
		{
			if($Time < Time())
			{
				$shops = new SHOP($this);
				$shops->removeSHOP($name);
				unset($this->shop[$name]);
				$player = $this->getServer()->getPlayer($name);
				if($player instanceof Player) $player->sendMessage('§a[SSHOP] >§e 商店无操作超过30秒,已自动关闭!');
			}
		}
	}

	function SHOP($x,$y,$m,$player)//商店分配
	{
		$name = $player->getName();
		$shop = new SHOP($this);
		$class = $this->SHOP->get('设置')['标签'][$x];
		$list = $this->SHOP->get($class);
		if($class == '购买') return $this->BUY_SHOP($x,$y,$m,$player);
		if($class == '回收') return $this->DEL_SHOP($x,$y,$m,$player);
		if($class == '个人') return $this->PLAYER_SHOP($x,$y,$m,$player);
		if($class == '兑换') return $this->UNITEM_SHOP($x,$y,$m,$player);
	}

	function UNITEM_SHOP($x,$y,$m,$player)//兑换商店
	{
		$name = $player->getName();
		$shop = new SHOP($this);
		$class = $this->SHOP->get('设置')['标签'][$x];
		$list = $this->SHOP->get($class);
		if(isset($this->shop[$player->getName()]['搜索'])) $list = isset($this->shop[$player->getName()]['搜索'][$class]) ? $this->shop[$player->getName()]['搜索'][$class] : [];
		$bh = $y + (($m - 1) * 10);
		if(!isset($list[$bh])) return null;
		$shop_info = $list[$bh];
		$GetItem = $this->GetItem($player,new Item($shop_info['所需'][0],$shop_info['所需'][1],$shop_info['所需'][2]));
		if($GetItem !== True) return $player->sendMessage('§a[SSHOP] >§c 背包内['.$this->Get_Item_Name($shop_info['所需'][0],$shop_info['所需'][1]).'] 还差 '.$GetItem.'个');
		if(!$this->Inventory($player)) return $player->sendMessage('§a[SSHOP] >§c 背包内至少留一个空位才能购买!');
		$this->RemoveItem($player,new Item($shop_info['所需'][0],$shop_info['所需'][1],$shop_info['所需'][2]));
		$player->getInventory()->addItem(new Item($shop_info['获得'][0],$shop_info['获得'][1],$shop_info['获得'][2]));
		return $player->sendMessage('§a[SSHOP] >§c 成功兑换['.$this->Get_Item_Name($shop_info['获得'][0],$shop_info['获得'][1]).'] 共 '.$shop_info['获得'][2].'个');
	}

	function PLAYER_SHOP($x,$y,$m,$player)//个人商店
	{
		$name = $player->getName();
		$shop = new SHOP($this);
		$class = $this->SHOP->get('设置')['标签'][$x];
		$list = $this->SHOP->get($class);
		if(isset($this->shop[$player->getName()]['搜索'])) $list = isset($this->shop[$player->getName()]['搜索'][$class]) ? $this->shop[$player->getName()]['搜索'][$class] : [];
		$bh = $y + (($m - 1) * 10);
		if(!isset($list[$bh])) return null;
		$shop_info = $list[$bh];
		if($shop_info['卖家'] == $name) return $player->sendMessage('§a[SSHOP] >§c 不能购买自己的商品!');
		$buy = $this->Moneys($player,$shop_info['所需'],$shop_info['价格'],'-');
		if($buy !== True) return $player->sendMessage('§a[SSHOP] >§c '.$buy);
		$infos = [];
		if($this->Spending->exists($shop_info['卖家'])) $infos = $this->Spending->get($shop_info['卖家']);
		$infos[] = [$shop_info['所需'],$shop_info['价格'],"§c[S-SOP]§e ".$name."购买了你的商品[".$shop_info['物品'][0].":".$shop_info['物品'][1]."] 共".$shop_info['物品'][2]."个 以下为获得的物品...."];
		$this->Spending->set($shop_info['卖家'],$infos);
		$this->Spending->save();
		unset($list[$bh]);
		$list = $shop->list_array($list);
		$this->SHOP->set($class,$list);
		$this->SHOP->save();
		$shop->undata($this->shop[$name]['api'],$shop->home($x,$y,$m,$name));
		$player->getInventory()->addItem(new Item($shop_info['物品'][0],$shop_info['物品'][1],$shop_info['物品'][2]));
		return $player->sendMessage('§a[SSHOP] >§c 成功购买['.$this->Get_Item_Name($shop_info['物品'][0],$shop_info['物品'][1]).'] 共 '.$shop_info['物品'][2].'个');
	}

	function DEL_SHOP($x,$y,$m,$player)//回收商品
	{
		$name = $player->getName();
		$shop = new SHOP($this);
		$class = $this->SHOP->get('设置')['标签'][$x];
		$list = $this->SHOP->get($class);
		if(isset($this->shop[$player->getName()]['搜索'])) $list = isset($this->shop[$player->getName()]['搜索'][$class]) ? $this->shop[$player->getName()]['搜索'][$class] : [];
		$bh = $y + (($m - 1) * 10);
		if(!isset($list[$bh])) return null;
		$shop_info = $list[$bh];
		$GetItem = $this->GetItem($player,new Item($shop_info['物品'][0],$shop_info['物品'][1],$shop_info['物品'][2]));
		if($GetItem !== True) return $player->sendMessage('§a[SSHOP] >§c 背包内['.$this->Get_Item_Name($shop_info['物品'][0],$shop_info['物品'][1]).'] 还差 '.$GetItem.'个');
		$buy = $this->Moneys($player,$shop_info['所需'],$shop_info['价格'],'+');
		if($buy !== True) return $player->sendMessage('§a[SSHOP] >§c '.$buy);
		if($shop_info['积分'] > 0)
		{
			$this->Money->set($name,$this->Money->get($name) + $shop_info['积分']);
			$this->Money->save();
		}
		$this->RemoveItem($player,new Item($shop_info['物品'][0],$shop_info['物品'][1],$shop_info['物品'][2]));
		return $player->sendMessage('§a[SSHOP] >§c 成功回收['.$this->Get_Item_Name($shop_info['物品'][0],$shop_info['物品'][1]).'] 共 '.$shop_info['物品'][2].'个');
	}

	function BUY_SHOP($x,$y,$m,$player)//购买商品
	{
		$name = $player->getName();
		$shop = new SHOP($this);
		$class = $this->SHOP->get('设置')['标签'][$x];
		$list = $this->SHOP->get($class);
		if(isset($this->shop[$player->getName()]['搜索'])) $list = isset($this->shop[$player->getName()]['搜索'][$class]) ? $this->shop[$player->getName()]['搜索'][$class] : [];
		$bh = $y + (($m - 1) * 10);
		if(!isset($list[$bh])) return null;
		$shop_info = $list[$bh];
		if(!$this->Inventory($player)) return $player->sendMessage('§a[SSHOP] >§c 背包内至少留一个空位才能购买!');
		if($shop_info['库存'] == 0) return $player->sendMessage('§a[SSHOP] >§c 此商品库存已不足!');
		$buy = $this->Moneys($player,$shop_info['所需'],$shop_info['价格'],'-');
		if($buy !== True) return $player->sendMessage('§a[SSHOP] >§c '.$buy);
		$player->getInventory()->addItem(new Item($shop_info['物品'][0],$shop_info['物品'][1],$shop_info['物品'][2]));
		if($shop_info['库存'] > 0)
		{
			$list[$bh]['库存'] -= 1;
			$this->SHOP->set($class,$list);
			$this->SHOP->save();
		}
		if($shop_info['积分'] > 0)
		{
			$this->Money->set($name,$this->Money->get($name) + $shop_info['积分']);
			$this->Money->save();
		}
		return $player->sendMessage('§a[SSHOP] >§c 成功购买['.$this->Get_Item_Name($shop_info['物品'][0],$shop_info['物品'][1]).'] 共 '.$shop_info['物品'][2].'个');
	}

	function ADD_SHOP($Type,$Array)//添加商品
	{
		$this->SHOP = new Config($this->getDataFolder().'SHOP.yml',Config::YAML,[]);
		$SHOP = $this->SHOP->get($Type);
		$SHOP[] = $Array;
		$this->SHOP->set($Type,$SHOP);
		$this->SHOP->save();
	}

	function explode($String)//分割物品
	{
		$String = explode(':',$String);
		if(count($String) != 3) return False;
		return $String;
	}

	function String_Repeat($String,$SHOP,$BH = 0)
	{
		$String = str_replace('{编号}',$BH,$String);
		if(isset($SHOP['物品']))
		{
			$Item = !$this->Get_Item_Name($SHOP['物品'][0],$SHOP['物品'][1]) ? '未知' : $this->Get_Item_Name($SHOP['物品'][0],$SHOP['物品'][1]);
			$String = str_replace('{物品}',$Item,$String);
		}
		if(isset($SHOP['物品'])) $String = str_replace('{数量}',$SHOP['物品'][2],$String);
		if(isset($SHOP['价格'])) $String = str_replace('{价格}',$SHOP['价格'],$String);
		if(isset($SHOP['所需'])) $String = str_replace('{货币}',$this->Money_Name($SHOP['所需']),$String);
		if(isset($SHOP['库存'])) $String = str_replace('{库存}',$SHOP['库存'],$String);
		if(isset($SHOP['积分'])) $String = str_replace('{积分}',$SHOP['积分'],$String);
		if(isset($SHOP['附魔'])) $String = str_replace('{附魔}',$SHOP['附魔'],$String);
		if(isset($SHOP['获得']))
		{
			$item1 = $this->Get_Item_Name($SHOP['所需'][0],$SHOP['所需'][1]);
			$String = str_replace('{物品1}',$item1,$String);
			$item2 = $this->Get_Item_Name($SHOP['获得'][0],$SHOP['获得'][1]);
			$String = str_replace('{物品2}',$item2,$String);
			$String = str_replace('{数量1}',$SHOP['所需'][2],$String);
			$String = str_replace('{数量2}',$SHOP['获得'][2],$String);
		}
		if(isset($SHOP['卖家']))
		{
			if(strlen($SHOP['卖家']) > 6)
			{
				$SHOP['卖家'] = substr($SHOP['卖家'],0,6);
				$SHOP['卖家'] .= '...';
			}
			$String = str_replace('{卖家}',$SHOP['卖家'],$String);
		}
		$all = '';
		if($this->SHOP->get('设置')['iconv对齐字体[推荐开启]']) $String = iconv('utf-8','gb2312',$String);
		for($a = 1; $a < 4; $a ++)
		{
			$shop = $this->SHOP->get('设置')['商店自定义']["[$a]"];
			$String = explode("[$a]",$String);
			if(isset($String[1]))
			{
				$cd = strlen($String[0]);
				for($b = strlen($String[0]); $b < $shop; $b ++)
				{
					$String[0] .= ' ';
				}
				$String = $String[0].$String[1];
			}
			if(is_Array($String)) foreach($String as $txt) $all .= $txt;
			if($all != '') $String = $all and $all = '';
		}
		if($this->SHOP->get('设置')['iconv对齐字体[推荐开启]']) $String = iconv('gb2312','utf-8',$String);
		return $String;
	}

	function Get_Item_Name($ID,$DATA)
	{
		if(!$this->item->exists($ID.':'.$DATA)) return False;
		return $this->item->get($ID.':'.$DATA);
	}

	function Money_Name($m)
	{
		$SHOP = $this->SHOP->get('设置')['货币名称'];
		if($m == '金币') return $SHOP['金币'];
		if($m == '等级') return $SHOP['等级'];
		if($m == '经验') return $SHOP['经验'];
		if($m == '附魔券') return $SHOP['附魔券'];
		if($m == '积分') return $SHOP['积分'];
		if($m == '点券') return $SHOP['点券'];
		if($m == '物品') return $SHOP['物品'];
		return False;
	}

	function Inventory($player)
	{
		for($index = 0; $index < $player->getInventory()->getSize(); $index ++)
		{
			$item = $player->getInventory()->getItem($index);
			if($item->getID() == 0) return True;
		}
		return False;
	}

	function GetItem($player,$Item)
	{
		$Count = 0;
		for($index = 0; $index < $player->getInventory()->getSize(); $index ++)
		{
			$item = $player->getInventory()->getItem($index);
			if($item->getID() != 0)
			{
				if($item->getID() == $Item->getID() and $item->getDamage() == $Item->getDamage()) $Count += $item->getCount();
			}
		}
		if($Count >= $Item->getCount()) return True;
		$sy = $Item->getCount() - $Count;
		return $sy;
	}

	function RemoveItem($player,$Item)
	{
		$ItemCount = $Item->getCount();
		if($ItemCount <= 0) return;
		for($index = 0; $index < $player->getInventory()->getSize(); $index ++)
		{
			$ItemSet = $player->getInventory()->getItem($index);
			if($Item->getID() == $ItemSet->getID() and $Item->getDamage() == $ItemSet->getDamage())
			{
				if($ItemCount >= $ItemSet->getCount())
				{
					$ItemCount -= $ItemSet->getCount();
					$player->getInventory()->setItem($index, Item::get(Item::AIR, 0, 1));
				} else if($ItemCount < $ItemSet->getCount()) {
					$player->getInventory()->setItem($index, Item::get($Item->getID(), 0, $ItemSet->getCount() - $ItemCount));
					break;
				}
			}
		}
	}

	function Moneys(Player $player,String $lis,int $numeric,String $type = '+',$return = False)
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
				$player->sendMessage('§7 你的账户余额增加了'.$numeric.'金币');
				return true;
			}
			else
			{
				if($numeric > $numeric_one) return '你的账户余额不足'.$numeric.$lis;
				EconomyAPI::getInstance()->setMoney($player, $numeric_one - $numeric);
				$player->sendMessage('§7 你的账户余额支出了'.$numeric.'金币');
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
				$player->sendMessage('§7 你的经验增长了'.$numeric);
				return true;
			}
			else
			{
				if($numeric > $numeric_one) return '你的经验不足'.$numeric;
				$this->delEXP($player,$numeric);
				$player->sendMessage('§7 你的经验减少了'.$numeric);
				return true;
			}
		}
		if($lis == '等级' or $lis == 'l')
		{
			$numeric_one = $this->getLV($player);
			if($return) return $numeric_one;
			if($type == '+')
			{
				$this->addLV($player,$numeric);
				$player->sendMessage('§7 你的等级增加了'.$numeric);
				return true;
			}
			else
			{
				if($numeric > $numeric_one) return '你的等级不足'.$numeric;
				$this->delLV($player,$numeric);
				$player->sendMessage('§7 你的等级减少了'.$numeric);
				return true;
			}
		}
		if($lis == '附魔券' or $lis == 'w')
		{
			if(!$this->Enchant_RPG_SHOP) return '未检测到RPG附魔插件,支付失败!';
			$numeric_one = $this->Enchant_RPG_SHOP->Money->get($name);
			if($return) return $numeric_one;
			if($type == '+')
			{
				$this->Enchant_RPG_SHOP->Money->set($name,$numeric_one + $numeric);
				$this->Enchant_RPG_SHOP->Money->save();
				$player->sendMessage('§7 你账户内的附魔券增加了'.$numeric);
				return true;
			}
			else
			{
				if($numeric > $numeric_one) return '你账户内的附魔券不足'.$numeric;
				$this->Enchant_RPG_SHOP->Money->set($name,$numeric_one - $numeric);
				$this->Enchant_RPG_SHOP->Money->save();
				$player->sendMessage('§7 你账户内的附魔券支出'.$numeric);
				return true;
			}
		}
		if($lis == '点券' or $list == 'j')
		{
			if(!class_exists('\\ZXDAConnector\\Main',false)) return '本服务器未安装ZXDAConnector插件';
			$numeric_one = ZXDAConnector::getPlayerCoupons($player_name);
			if($return) return $numeric_one;
			if($type == '+')
			{
				ZXDAConnector::addPlayerCoupons($name,$numeric);
				$player->sendMessage('§7 你的点券增加了'.$numeric);
				return true;
			}
			else
			{
				if($numeric > $numeric_one) return '你账户内的点券不足'.$numeric;
				ZXDAConnector::takePlayerCoupons($name,$numeric);
				$player->sendMessage('§7 你账户内的点券增加了'.$numeric);
				return true;
			}
		} 
		$this->getLogger()->info("404:在执行支付时出现未知的情况,请联系[SSHOP]开发者 #Array($lis,$numeric,$type) => $name");
		return False;
	}

	function getEXP($player)
	{
		$name = $this->getServer()->getName();
		if($name == 'Tesseract' || $name == 'GenisysPro') return $player->getTotalXp();
		return $player->getExp();
	}

	function delEXP($player,int $EXP)
	{
		$name = $this->getServer()->getName();
		if($name == 'Tesseract' || $name == 'GenisysPro') return $player->setTotalXp($this->getEXP($player) - $EXP);
		return $player->setExp($this->getEXP($player) - $EXP);
	}

	function addEXP($player,int $EXP)
	{
		$name = $this->getServer()->getName();
		if($name == 'Tesseract' || $name == 'GenisysPro') return $player->setTotalXp($this->getEXP($player) + $EXP);
		return $player->setExp($this->getEXP($player) + $EXP);
	}

	function getLV($player)
	{
		$name = $this->getServer()->getName();
		if($name == 'Tesseract' || $name == 'GenisysPro') return $player->getXpLevel();
		return $player->getExpLevel();
	}

	function delLV($player,int $LV)
	{
		$name = $this->getServer()->getName();
		if($name == 'Tesseract' || $name == 'GenisysPro') return $player->takeXpLevel($LV);
		$LV = $this->getLV($player) - $LV;
		return $player->setExpLevel($LV);
	}

	function addLV($player,int $LV)
	{
		$name = $this->getServer()->getName();
		if($name == 'Tesseract' || $name == 'GenisysPro') return $player->addXpLevel($LV);
		return $player->setExpLevel($this->getLV($player) + $LV);
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
/*
	看到此代码说明您已经解密或通过其他手段得到本插件源码!
	申明:本插件为[史莱姆]开发!如您[发布/抄袭/转载]等任何侵权行为,我将追究到底!
	©2016 - 2017 注明 : 2017/1/22 20:39:48
	史莱姆:
		QQ:478889187
		ZXDA UID:8897
		ZXDA USER:slm47888
*/
<?php
namespace island;
//引用USE
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo as Info;
use pocketmine\Server;
use pocketmine\Player;
//tile
use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
//item
use pocketmine\item\Item;
use pocketmine\item\ItemBlock;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentEntry;
use pocketmine\item\enchantment\EnchantmentLevelTable;
use pocketmine\item\enchantment\EnchantmentList;
//block
use pocketmine\block\Block;
use pocketmine\block\Lava;
use pocketmine\block\Water;
//level
use pocketmine\utils\Config;
use pocketmine\level\format\Chunk;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\level\generator\object\Tree;
use pocketmine\level\generator\Generator;
use pocketmine\level\format\generic\BaseLevelProvider;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\format\LevelProvider;
//plugin
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginLoader;
use pocketmine\plugin\PluginBase as Base;
//math
use pocketmine\math\Vector3;
//event
use pocketmine\event\Listener;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\TranslationContainer;

use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\event\inventory\InventoryPickupArrowEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\inventory\ChestInventory;
//inventory
use pocketmine\inventory\Inventory;
//command
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
//nbt
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\LongTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
//entity
use pocketmine\entity\Effect;
//other
use onebone\economyapi\EconomyAPI;
use pocketmine\level\ChunkManager;
use pocketmine\entity\Entity;
use pocketmine\entity\Lightning;
use pocketmine\level\generator\LightPopulationTask;

use pocketmine\level\format\FullChunk;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ShortTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\scheduler\PluginTask;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use island\Inventory as IsLand_Inventory;
use island\API;
use island\Command as CMD;
use island\Data;
use pocketmine\scheduler\CallbackTask;
use pocketmine\event\server\DataPacketReceiveEvent;
/*
 V2.3.0 start the update 2017/7/29
	*		岛屿管理员 添加权限-无视岛屿购买数量 ok
	*		岛屿管理员 添加权限-无视拓展岛屿方块数量 ok
	*		岛屿管理员 添加权限-无视周围有领地无法拓展 ok
	*		岛屿管理员 添加权限-无视玩家岛屿权限 ok
	*		岛屿管理员 添加权限-无视禁止命令 ok
	*		修复岛屿管理员只能破坏岛屿方块,不能放置方块 ok
	*		修复修改拓展文件价格后,导致机制失效问题 ok
	*		添加指令 玩家用指令关闭底部显示 ok

	看到此代码说明您已经解密或通过其他手段得到本插件源码!
	申明:本插件为[史莱姆]开发!如您[发布/抄袭/转载]等任何侵权行为,都需承担法律责任,侵权解释权归作者本人所有!
	©2016 - 2017 注明 : 2017/1/22 20:39:48
	史莱姆:
		QQ:478889187
		ZXDA UID:8897
		ZXDA USER:slm47888
*/
//侦听器
class Main extends Base implements Listener
{
    private $Type_List = Array();//已加载拓展列表
    private $Type_World_List = Array();//已加载拓展世界列表
    private $Set = NULL;//公共设置
    private $dir_1 = NULL;//生成器第一点
    private $dir_2 = NULL;//生成器第二点
    private $dir_all = NULL;//生成器总开关
    private $add_World = False;//生成器世界开关
    private $add_Y = False;//生成器海拔开关
    private $add_M = False;//生成器价格开关
    private $standard = False;//生成器基准点开关
    private $click = [];//箱子双击事件

    public $API = Null;
    public $Create_Mod = False;
    public $Tasks = [];
    public $Windows = [];

    public function onLoad()
    {
        //ZXDA::init(497,$this);
        //ZXDA::requestCheck();
    }

    public function onDisable(){
        $this->API->Data->save_all();
    }

    public function onda(DataPacketReceiveEvent $event)
    {
        $PK = $event->getPacket();
        if($PK::NETWORK_ID == Info::CONTAINER_SET_SLOT_PACKET)
        {
            $player = $event->getPlayer();
            $Name = $player->getName();
            $item = $PK->item;
            if(isset($this->Windows[$Name]) and $player->getWindowId($this->Windows[$Name]) == $PK->windowid)
            {
                $player->getInventory()->sendContents($player);
                $this->Windows[$Name]->sendContents($player);
                $player->removeWindow($this->Windows[$Name]);
                //$player->getInventory()->sendContents($player);
                $event->setCancelled(true);
            }
        }
    }

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        $this->API = new API($this);
        /* 类初赋值 */
        $this->API->Land->API = $this->API;
        $this->API->Data->API = $this->API;
        $this->API->Command->API = $this->API;
        $this->API->Inventory->API = $this->API;
        /* 类初始化 */
        $this->API->Data->Load_Config();//加载配置文件
        $this->API->Data->Load_File();//加载拓展文件


        $this->API->Land->Load_Land();//加载领地
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this, "Task"]), 20);



        /*$this->n = 0;
        //$this->ZXDA_load();
        @mkdir($this->getDataFolder());
        $this->b = new Config($this->getDataFolder() . 'set/Config.yml', Config::YAML,array());//设置文件
        $this->Set = $this->b->get('设置');
        $this->Repair_World_Name();
        //$this->warp = new Config($this->getDataFolder() . 'set/warp.yml', Config::YAML,array());//公共传送点文件
        $this->beibao = new Config($this->getDataFolder() . 'set/beibao.yml', Config::YAML, array());//背包文件
        $this->Player = new Config($this->getDataFolder() . 'set/Player.yml', Config::YAML, array());//玩家文件
        $this->land = new Config($this->getDataFolder() . 'set/lands.yml', Config::YAML, array());//岛屿信息文件
        $this->world = new Config($this->getDataFolder() . 'set/world.yml', Config::YAML, array());//区块
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,"timer"]),5);
        $this->island = new Config($this->getDataFolder() . 'set/IsLand.yml', Config::YAML, array(
            '坐标X' => -1000,
            '坐标Z' => -1000,
            '资源坐标X' => -2200,
            '资源坐标Z' => -2200
        ));//领地文件
        $this->SkyBlock = new Config($this->getDataFolder() . 'set/SkyBlock.yml', Config::YAML, array(
            'IsIand_Name' => Array(),//岛屿名字[主人]
            'Player_IsIand_Name' => Array(),//玩家所拥有的岛屿
            'DATA' => '0'//领地编号
        ));
        */
    }

    public function Task()
    {
        foreach($this->getServer()->getOnlinePlayers() as $player)
        {
            $Level = $player->level;
            $LevelName = $Level->getName();
            if(isset($this->API->Data->IsLand_Disk['ISLAND_LEVEL'][$LevelName]))
            {
                $X = round($player->getX());
                $Z = round($player->getZ());
                $Y = round($player->getY());
                $numberin = $this->API->Land->getXZ($X.','.$Z,$LevelName);
                if($numberin)
                {
                    $c = $this->API->Config_yml;
                    $Content = $c->get('Bottom_Content');
                    $Content = str_replace('{换行}',"\n",$Content);
                    $Content = str_replace('{编号}',$numberin,$Content);
                    if($c->get('Bottom_Type') == 'popup') $player->sendPopup($Content);
                    if($c->get('Bottom_Type') == 'tip') $player->sendTip($Content);
                }
            }
        }
        foreach($this->Tasks as $Nu => $Info)
        {
            if(isset($Info['Time']))
            {
                if($Info['Time'] <= Time())
                {
                    if($Info['Type'] == 'AddInventory')
                    {
                        $this->API->Inventory->AddPock($Info['Player'],$Info['Inventory_Box']);
                    }
                    unset($this->Tasks[$Nu]);
                }
            }
        }
    }

    public function onCommand(CommandSender $sender,Command $command,$label,array $args)
    {
        // $this->Data->Dispose_Mod('1505749922',$sender);
        if(!$sender instanceof Player) return;
        return $this->API->Command->Code(strtolower($command->getName()),$sender,$args);
        if(strtolower($command->getName() == '岛屿'))
        {
            $IsIand_Name = $this->SkyBlock->get('IsIand_Name');
            $name = $sender->getName();
            if(!(isset($args[0])))
            {
                $sender->sendMessage("§e岛屿插件目前版本 ==>§5 ".$this->API."");
                $sender->sendMessage("§e岛屿插件 ZXDA作者 ==>§5 史莱姆 UID:8897");
                $sender->sendMessage("§4如发现BUG请联系开发者,QQ: 478889187");
                return true;
            }
            if($args[0] == '帮助')
            {
                $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§7■■§a■■§b■■§c■■§e■■§4■■');
                //公共区
                $sender->sendMessage('§b -◎ §a/岛屿 模型 列表 §8< 查看服务器已安装的所有岛屿模型 >');//ok
                $sender->sendMessage('§b -◎ §a/岛屿 购买 [岛屿名字] [岛屿模型名字] §8< 购买一个岛屿 >');//ok
                $sender->sendMessage('§b -◎ §a/岛屿 底部 [开/关] §8< 岛屿底部开关 >');//ok
                //岛主区
                $sender->sendMessage('§b -◎ §a/岛屿 列表 §8< 查看自己所拥有的全部岛屿 >');//ok
                $sender->sendMessage('§b -◎ §a/岛屿 回 [岛屿名字/岛屿编号] §8< 传送回岛屿 >');//ok
                $sender->sendMessage('§b -◎ §a/岛屿 分享 [添加/删除/列表] [岛屿名字] [玩家] §8< 岛屿互动权限设置 >');//ok
                $sender->sendMessage('§b -◎ §a/岛屿 黑名单 [添加/删除/列表] [领地编号] [游戏名] §8< 岛屿黑名单系统操作 >');//ok
                $sender->sendMessage('§b -◎ §a/岛屿 公共 [开/关] [岛屿名字] §8< 开关此编号岛屿为公共场所,所有人可互动 >');//ok
                $sender->sendMessage('§b -◎ §a/岛屿 领地 [转让/列表/邀请/踢出] [岛屿名字] [玩家名] §8< 对玩家进行领地编号参观类操作 >');//ok
                //$sender->sendMessage('§b -◎ §a/岛屿 领地 [全邀/全踢] [领地编号] §8< 对所有玩家进行领地编号参观类操作 >');
                //$sender->sendMessage('§b -◎ §a/岛屿 备份 [开/关/列表] §8< 每天备份岛屿的方块/这需要名单 >');//需名单[后台指令]
                //$sender->sendMessage('§b -◎ §a/岛屿 回档 [时间名/列表] §8< 回档这个岛屿的全部方块[仅限方块] >');//需名单[后台指令][一天只能操作一次]
                $sender->sendMessage('§b -◎ §a/岛屿 生态 [修改/列表] [领地名称] [生态编号] §8< 设置岛屿上的生态环境 >');//可设置价格[默认免费] //ok
                //后台区
                //$sender->sendMessage('§b -◎ §a/岛屿 备份权限 [添加/删除/列表] 游戏名 §8< 岛屿备份权限操作 >');
                //$sender->sendMessage('§b -◎ §a/岛屿 黑名单 [添加/删除/列表] 游戏名 §8< 岛屿黑名单操作 >');
                $sender->sendMessage('§b -◎ §a/岛屿 管理员 [添加/删除/列表] 游戏名 §8< 岛屿管理员操作 >');
                //$sender->sendMessage('§b -◎ §a/岛屿 模型 [卸载/加载] §8< 岛屿模型文件员操作 >');
                //OP区
                $sender->sendMessage('§b -◎ §a/岛屿 创建 §8< 刚安装插件请用此指令 >');//ok
                $sender->sendMessage('§b -◎ §a/岛屿 生成器 1 §8< 设置生成器第一点 >');//ok
                $sender->sendMessage('§b -◎ §a/岛屿 生成器 2 §8< 设置生成器第二点 >');//ok
                $sender->sendMessage('§b -◎ §a/岛屿 生成器 输出 §8< 设置完一二点,将方块输出到文件内 >');//ok
                $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§7■■§a■■§b■■§c■■§e■■§4■■');
                return true;
            }
            if($args[0] == '开')
            {

                return true;
            }
            if($args[0] == '底部')
            {
                if(!isset($args[1]) or $args[1] != '开' and $args[1] != '关') return $sender->sendMessage('§b -◎ §a/岛屿 底部 [开/关] §8< 岛屿底部开关 >');
                $info = $this->Player->get($name);
                if($args[1] == '关')
                {
                    $info['底部'] = False;
                } else {
                    $info['底部'] = True;
                }
                $this->Player->set($name,$info);
                $this->Player->save();
                $sender->sendMessage('§b -◎ §a已将底部设为['.$args[1].']!');
                return true;
            }
            if($args[0] == '创建')
            {
                if(!$sender->isOp())
                {
                    $sender->sendMessage('§b -◎ §a非管理员不能进行此操作!');
                    return true;
                }
                !isset($args[1]) ? $args[1] = 'world' : [];
                $level = $this->getServer()->getDefaultLevel();
                $path = $level->getFolderName();
                $p1 = dirname($path);
                $p2 = $p1."/worlds/".$args[1]."/";
                if(file_exists($p2)) return $sender->sendMessage('§b -◎ §a发现服务器已安装"'.$args[1].'地图,需关服删除后再进行创建! ');
                @mkdir($p2);
                @mkdir($p2 . 'region/');
                file_put_contents($p2 . 'level.dat',stream_get_contents($this->getResource("level.dat")));
                if(filesize($p1."/worlds/".$args[1]."/level.dat") === 0) return $sender->sendMessage('§b -◎ §a"'.$args[1].'"创建失败!请尝试下载群中文件! error:' . php_uname());
                $sender->sendMessage('§b -◎ §a"'.$args[1].'"已创建完成!正在"'.$args[1].'"加载地图!');
                $this->getServer()->generateLevel($args[1]);
                $this->getServer()->loadLevel($args[1]);
                $level = $this->getServer()->getLevelbyName($args[1]);
                $cc = 0;
                $block = 0;
                $this->Repair_World_Name();
                $sender->sendMessage('§b -◎ §a开始写入地图!');
                $times = explode(' ',microtime());
                $aa = 0;
                for($a = -1000; $a < $cc; $a ++)
                {
                    for($b = -1000; $b < $cc; $b ++)
                    {
                        if($a == -250 and $aa == 2)
                        {
                            $aa = 3;
                            $timess = explode(' ',microtime());
                            $timetext = $timess[0]+$timess[1]-($times[0]+$times[1]);
                            $sender->sendMessage('§c已完成75%-耗时:§6'.round($timetext,5).'§c秒');
                            $level->save();
                        }
                        if($a == -500 and $aa == 1)
                        {
                            $aa = 2;
                            $timess = explode(' ',microtime());
                            $timetext = $timess[0]+$timess[1]-($times[0]+$times[1]);
                            $sender->sendMessage('§c已完成50%-耗时:§6'.round($timetext,5).'§c秒');
                            $level->save();
                        }
                        if($a == -750 and $aa == 0)
                        {
                            $aa = 1;
                            $timess = explode(' ',microtime());
                            $timetext = $timess[0]+$timess[1]-($times[0]+$times[1]);
                            $sender->sendMessage('§c已完成25%-耗时:§6'.round($timetext,5).'§c秒');
                            $level->save();
                        }
                        $block += 1;
                        $this->setTile($level,$a,2,$b,0,0);
                    }
                }
                $level->save();
                $sender->sendMessage('§b -◎ §a尝试更正"200"出生点...');
                $level->setSpawnLocation(new Vector3(-1002,3,-1002));
                $sender->sendMessage('§b -◎ §a尝试在"200"出生点写入方块...');
                $level->setBlockIdAt(-1002,2,-1002,3);
                $timess = explode(' ',microtime());
                $timetext = $timess[0]+$timess[1]-($times[0]+$times[1]);
                $sender->sendMessage('§e[isLand]§c生成岛屿地图区块共§6耗时:'.round($timetext,5).'秒§c,§6加载:' . $block . '个方块§c,§6区块:' . $block / 16 . '个');
                file_put_contents($this->getDataFolder() . '1.island',stream_get_contents($this->getResource("1.island")));
                $sender->sendMessage('§e[isLand]§c已安装内置§6"岛屿"§c拓展文件...');
                $sender->sendMessage('§e[isLand]§a正在启动拓展加载器...');
                $this->island_load();
                return true;
            }
            if($args[0] == '列表')
            {
                $island = $this->SkyBlock->get('Player_IsIand_Name')[$name];
                $txt = "无购买任何岛屿";
                foreach($island as $a => $b)
                {
                    if($txt == "无购买任何岛屿")
                    {
                        $txt = "";
                    }
                    $txt .= $txt == "" ? "§l* §e岛屿名称 §5|§r§6 " . $a . " §5|§l* §e岛屿编号 §5|§r§6 " . $b . " §5| §4[主岛]\n" : "§l* §e岛屿名称 §5|§r§6 " . $a . " §5| §l* §e岛屿编号 §5|§r§6 " . $b . " §5|§7\n";
                }
                $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§8§l IsLand §9[ §d' . $name . ' §9]§8 Lists §r§7■■§a■■§b■■§c■■§e■■§4■■');
                $sender->sendMessage('§c' . $txt);
                $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§8§l IsLand §9[ §d' . $name . ' §9]§8 Lists §r§7■■§a■■§b■■§c■■§e■■§4■■');
                return true;
            }
            if($args[0] == '生态')
            {
                if(!isset($args[1]) Or $args[1] == '列表')
                {
                    $sender->sendMessage('§b -◎ §a/岛屿 生态 [修改] [领地编号] [生态编号] §8< 设置岛屿上的生态环境 >');
                    $sender->sendMessage('
                        §e§1■■§2■■§3■■§5■■§6■■§7■■§a■■§b■■§c■■§e■■§4■■
                        §c[编号]   §6[生态名称]
                        §7 - §c0         §6海洋生态
                        §7 - §c1         §6平原生态
                        §7 - §c2         §6沙漠生态
                        §7 - §c3         §6山脉生态
                        §7 - §c4         §6森林生态
                        §7 - §c5         §6针叶林生态
                        §7 - §c6         §6沼泽生态
                        §7 - §c7         §6河生态
                        §7 - §c8         §6地狱生态
                        §7 - §c12       §6雪地生态
                        §7 - §c20       §6小山生态
                        §7 - §c27       §6桦树林生态
                        §e §1■■§2■■§3■■§5■■§6■■§7■■§a■■§b■■§c■■§e■■§4■■');
                    return true;
                }
                if($args[1] == '修改')
                {
                    if(!isset($args[3]))
                    {
                        $sender->sendMessage('§b -◎ §a/岛屿 生态 修改 [领地编号] [生态编号] §8< 设置岛屿上的生态环境 >');
                        return true;
                    }
                    if(!$this->SkyBlock->exists($args[2]))
                    {
                        $sender->sendMessage('§b -◎ §a不存在此编号领地!');
                        return true;
                    }
                    $coordinates = explode(':',$this->SkyBlock->get($args[2]));
                    if($coordinates[4] != $name)
                    {
                        $sender->sendMessage('§b -◎ §a此编号主人为§e[ ' . $coordinates[4] . ' ]§a你不能进行修改生态!');
                        return true;
                    }
                    $old = $args[2];
                    $args[2] = $this->SkyBlock->get('Player_IsIand_Name')[$name][$args[2]];
                    if($this->getBiomeName($args[3]) === false)
                    {
                        $sender->sendMessage('§b -◎ §a并没有生态环境编号为§e[ ' . $args[3] . ' ]§a!');
                        return true;
                    }
                    $world = $this->getServer()->getLevelbyName($coordinates[3]);
                    $Block_All = $this->island->get($args[2]);
                    for($a = 0; $a < count($Block_All); $a ++)
                    {
                        $Block = explode(':',$Block_All[$a]);
                        $world->setBiomeId($Block[0],$Block[1],$args[3]);
                    }
                    $world->save();
                    $info = $this->land->get($args[2]);
                    $info['生态环境'] = $args[3];
                    $this->land->set($args[2],$info);
                    $this->land->save();
                    $pos = $this->getServer()->getDefaultLevel()->getSpawnLocation();
                    $sender->teleport($pos);
                    $sender->sendMessage('§b -◎ §a成功修改§e[ ' . $args[2] . ' ]§a岛屿,生态环境为§e[ ' .$this->getBiomeName($args[3]) . ' ]§a!');
                    $sender->sendMessage('§b -◎ §a领地面积§e[ ' . count($Block_All) . ' ]§a|为了更好的刷新岛屿,正在刷新岛屿§a!或许要重进才生效!');
                    $coordinates = explode(':',$this->SkyBlock->get($old));
                    $world = $this->getServer()->getLevelbyName($coordinates[3]);
                    $sender->teleport(new Position($coordinates[0],$coordinates[1] + 1,$coordinates[2],$this->getServer()->getLevelByName($coordinates[3])));
                    $sender->sendMessage('§b -◎ §a欢迎回到§e[ ' . $args[2] . ' ]§a岛屿!');
                    return true;
                }
            }
            if($args[0] == '领地')
            {
                if($args[1] == '列表')
                {
                    if(!isset($args[2]))
                    {
                        $sender->sendMessage('§b -◎ §a/岛屿 领地 列表 [领地编号] §8< 查看哪些玩家在这个编号领地上 >');
                        return true;
                    }
                    if(!$this->SkyBlock->exists($args[2]))
                    {
                        $sender->sendMessage('§b -◎ §a不存在此编号领地!');
                        return true;
                    }
                    $coordinates = explode(':',$this->SkyBlock->get($args[2]));
                    if($coordinates[4] != $name)
                    {
                        $sender->sendMessage('§b -◎ §a此编号主人为§e[ ' . $coordinates[4] . ' ]§a你不能进行此操作!');
                        return true;
                    }
                    $args[2] = $this->SkyBlock->get('Player_IsIand_Name')[$name][$args[2]];;
                    $txt = "无任何玩家在此岛屿领地内!";
                    $number = 0;
                    $land = $this->island->get($args[2]);
                    $info = $this->land->get($args[2]);
                    foreach($this->getServer()->getOnlinePlayers() as $player)
                    {
                        $x = round($player->getX());
                        $z = round($player->getZ());
                        $level = $player->getLevel()->getName();
                        $txt1 = $x . ':' . $z;
                        if($info['世界'] == $level And in_array($txt1,$land))
                        {
                            if($txt == "无任何玩家在此岛屿领地内!")
                            {
                                $txt = "";
                            }
                            $names = $player->getName() == $name ? "§l* §eMaster §5|§r§6 " . $player->getName() : $player->getName();
                            $txt .= "§l* §ePlayer §5|§r§6 " . $names . "\n";
                            $number += 1;
                        }
                    }
                    $txt == "" ? $txt == '无任何玩家在此岛屿领地内!' : $txt = $txt;
                    $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§8§l IsLand §9[ §d' . $args[2] . ' §9]§8 Player §r§7■■§a■■§b■■§c■■§e■■§4■■');
                    $sender->sendMessage('§c' . $txt);
                    $number > 0 ? $sender->sendMessage('§b共发现玩家§e[' . $number . ']§b个玩家在此领地内') : $a = [];
                    $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§8§l IsLand §9[ §d' . $args[2] . ' §9]§8 Player §r§7■■§a■■§b■■§c■■§e■■§4■■');
                    return true;
                }
                if($args[1] == '转让')
                {
                    if(!isset($args[2]))
                    {
                        $sender->sendMessage('§b -◎ §a/岛屿 领地 [转让] [领地编号] [玩家名] §8< 对玩家进行领地编号转让类操作 >');
                        return true;
                    }
                    if(!$this->SkyBlock->exists($args[2]))
                    {
                        $sender->sendMessage('§b -◎ §a不存在此编号领地!');
                        return true;
                    }
                    $coordinates = explode(':',$this->SkyBlock->get($args[2]));
                    if($coordinates[4] != $name)
                    {
                        $sender->sendMessage('§b -◎ §a此编号主人为§e[ ' . $coordinates[4] . ' ]§a你不能进行此操作!');
                        return true;
                    }
                    $args[2] = $this->SkyBlock->get('Player_IsIand_Name')[$name][$args[2]];;
                    $info = $this->land->get($args[2]);
                    $info['岛主'] = $args[3];
                    $this->land->set($args[2],$info);
                    $this->land->save();
                    $player = $this->getServer()->getPlayer($args[3]);
                    if($player instanceof Player)
                    {
                        $player->sendMessage('§b -◎ §a§e[ ' . $name . ' ]§a已将编号§e[ ' . $args[2] . ' ]§a的岛屿控制权转让给你§a!');
                        $player->sendMessage('§b -◎ §8你获得新的编号§e[ ' . $args[2] . ' ]§a岛屿控制权§a!');
                    }
                    $sender->sendMessage('§b -◎ §a已将编号§e[ ' . $args[2] . ' ]§a的岛屿转让给§e[ ' . $args[3] . ' ]§a!');
                    $sender->sendMessage('§b -◎ §8你失去了编号§e[ ' . $args[2] . ' ]§8岛屿控制权§a!');
                    return true;
                }
                if($args[1] == '踢出' or $args[1] == '邀请')
                {
                    if(!isset($args[2]))
                    {
                        $sender->sendMessage('§b -◎ §a/岛屿 领地 [踢出/邀请] [领地编号] [玩家名] §8< 对玩家进行领地编号参观类操作 >');
                        return true;
                    }
                    if(!$this->SkyBlock->exists($args[2]))
                    {
                        $sender->sendMessage('§b -◎ §a不存在此编号领地!');
                        return true;
                    }
                    $coordinates = explode(':',$this->SkyBlock->get($args[2]));
                    if($coordinates[4] != $name)
                    {
                        $sender->sendMessage('§b -◎ §a此编号主人为§e[ ' . $coordinates[4] . ' ]§a你不能进行此操作!');
                        return true;
                    }
                    $args[2] = $this->SkyBlock->get('Player_IsIand_Name')[$name][$args[2]];;
                    $number = 0;
                    if($args[1] == '邀请')
                    {
                        foreach($this->getServer()->getOnlinePlayers() as $player)
                        {
                            if($player->getName() == $args[3])
                            {
                                $note = $this->Player->get($args[3]);
                                $txt = $args[2] . ':' . (Time()+20);
                                $note['Note'] = $txt;
                                $this->Player->set($args[3],$note);
                                $this->Player->save();
                                $sender->sendMessage('§b -◎ §a已将§e[ ' . $args[2] . ' ]§a岛屿邀请信息发送给玩家§e[ ' . $args[3] . ' ]§a,请等待同意!');
                                $player->sendMessage('§b -◎ §e[ ' . $args[2] . ' ]§a岛屿的岛主§e[ ' . $name . ' ]§a邀请你进行游玩!');
                                $player->sendMessage('§b -◎ §9请在20秒内直接发送"ok"或"no"!');
                                $number += 1;
                            }
                        }
                        if($number == 0)
                        {
                            $sender->sendMessage('§b -◎ §a玩家§e[ ' . $args[3] . ' ]§a不在线!');
                        }
                        return true;
                    }
                    $player = $this->getServer()->getPlayer($args[3]);
                    if($player instanceof Player)
                    {
                        $x = round($player->getX());
                        $z = round($player->getZ());
                        $level = $player->getLevel()->getName();
                        $txt = $x . ':' . $z;
                        $land = $this->island->get($args[2]);
                        $info = $this->land->get($args[2]);
                        if($info['世界'] == $level And in_array($txt,$land))
                        {
                            $pos = $this->getServer()->getDefaultLevel()->getSpawnLocation();
                            $player->teleport($pos);
                            $sender->sendMessage('§b -◎ §a已将§e[ ' . $args[3] . ' ]§a踢出§e[ ' . $args[2] . ' ]§a岛屿!');
                            $player->sendMessage('§b -◎ §a你被岛主§e[ ' . $info['岛主'] . ' ]§a从§e[ ' . $args[2] . ' ]§a岛屿踢回出生点!');
                        }
                        else
                        {
                            $sender->sendMessage('§b -◎ §a玩家§e[ ' . $args[3] . ' ]§a不在§e[ ' . $args[2] . ' ]§a岛屿上!');
                        }
                    }
                    else
                    {
                        $sender->sendMessage('§b -◎ §a玩家§e[ ' . $args[3] . ' ]§a不在线!');
                    }
                }
                return true;
            }
            if($args[0] == '回')
            {
                if(!isset($args[1]))
                {
                    $sender->sendMessage('§b -◎ §a/岛屿 回 [岛屿名字/岛屿编号] §8< 传送回岛屿 >');
                    return true;
                }
                $island_info = $this->SkyBlock->get('Player_IsIand_Name')[$name];
                if(!isset($island_info[$args[1]]) and !in_array($args[1],$island_info))
                {
                    $sender->sendMessage('§b -◎ §a不存在此岛屿名称或编号!');
                    return true;
                }
                if(in_array($args[1],$island_info))
                {
                    foreach($island_info as $keyname => $val)
                    {
                        if($val == $args[1]) $args[1] = $keyname;
                    }
                }
                $coordinates = explode(':',$this->SkyBlock->get($args[1]));
                if($coordinates[4] != $name)
                {
                    $sender->sendMessage('§b -◎ §a此岛屿主人为§e[ ' . $coordinates[4] . ' ]§a你不能传送过去!');
                    return true;
                }
                $world = $this->getServer()->getLevelbyName($coordinates[3]);
                $sender->teleport(new Position($coordinates[0],$coordinates[1],$coordinates[2],$this->getServer()->getLevelByName($coordinates[3])));
                $sender->sendMessage('§b -◎ §a欢迎回到§e[ ' . $args[1] . ' ]§a岛屿!');
                return true;
            }
            if($args[0] == '公共')
            {
                if(!isset($args[2]))
                {
                    $sender->sendMessage('§b -◎ §a/岛屿 公共 [开/关] [岛屿名字] §8< 开关磁编号岛屿为公共场所,所有人可互动 >');
                    return true;
                }
                if(!$this->SkyBlock->exists($args[2]))
                {
                    $sender->sendMessage('§b -◎ §a不存在此编号领地!');
                    return true;
                }
                $coordinates = explode(':',$this->SkyBlock->get($args[2]));
                if($coordinates[4] != $name)
                {
                    $sender->sendMessage('§b -◎ §a此编号主人为§e[ ' . $coordinates[4] . ' ]§a你不能进行分享操作!');
                    return true;
                }
                $args[2] = $this->SkyBlock->get('Player_IsIand_Name')[$name][$args[2]];;
                $list = $this->land->get($args[2]);
                if($args[1] == '开')
                {
                    if($list['公共'] === True)
                    {
                        $sender->sendMessage('§b -◎ §a§e[ ' . $args[2] . ' ]§a的公共领地项目已经打开!');
                        return true;
                    }
                    $list['公共'] = True;
                    $sender->sendMessage('§b -◎ §a§e[ ' . $args[2] . ' ]§a的公共领地项目已开启!');
                    $this->land->set($args[2],$list);
                    $this->land->save();
                    return true;
                }
                if($args[1] == '关')
                {
                    if($list['公共'] === False)
                    {
                        $sender->sendMessage('§b -◎ §a§e[ ' . $args[2] . ' ]§a的公共领地项目已经关闭!');
                        return true;
                    }
                    $list['公共'] = False;
                    $sender->sendMessage('§b -◎ §a§e[ ' . $args[2] . ' ]§a的公共领地项目已关闭!');
                    $this->land->set($args[2],$list);
                    $this->land->save();
                    return true;
                }
                return true;
            }
            if($args[0] == '分享')
            {
                if($args[1] == '列表')
                {
                    if(!isset($args[2]))
                    {
                        $sender->sendMessage('§b -◎ §a/岛屿 分享 [列表] [领地编号] §8< 岛屿互动权限 >');
                        return true;
                    }
                    $list = $this->land->get($args[2]);
                    $txt = "§l* §eMaster §5|§r§6 " . $list['岛主'] . "\n";
                    for($a = 0; $a < count($list['共享者']); $a ++)
                    {
                        $txt .= "§l* §ePlayer §5|§r§6 " . $list['共享者'][$a] . "\n";
                    }
                    $txt == "" ? $txt == '无更多分享者' : $txt = $txt;
                    $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§8§l IsLand §9[ §d' . $args[2] . ' §9] §8Number §r§7■■§a■■§b■■§c■■§e■■§4■■');
                    $sender->sendMessage('§c' . $txt);
                    $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§8§l IsLand §9[ §d' . $args[2] . ' §9] §8Number §r§7■■§a■■§b■■§c■■§e■■§4■■');
                    return true;
                }
                if(!isset($args[3]))
                {
                    $sender->sendMessage('§b -◎ §a/岛屿 分享 [添加/删除/列表] [领地编号] [玩家] §8< 此玩家可在这个岛屿互动权限 >');
                    return true;
                }
                if(!$this->SkyBlock->exists($args[2]))
                {
                    $sender->sendMessage('§b -◎ §a不存在此编号领地!');
                    return true;
                }
                $coordinates = explode(':',$this->SkyBlock->get($args[2]));
                if($coordinates[4] != $name)
                {
                    $sender->sendMessage('§b -◎ §a此编号主人为§e[ ' . $coordinates[4] . ' ]§a你不能进行分享操作!');
                    return true;
                }
                $args[2] = $this->SkyBlock->get('Player_IsIand_Name')[$name][$args[2]];;
                $list = $this->land->get($args[2]);
                if($args[1] == '添加')
                {
                    if(in_array($args[3],$list['共享者']))
                    {
                        $sender->sendMessage('§b -◎ §a玩家§e[ ' . $args[3] . ' ]§a已在§e[ ' . $args[2] . ' ]§a分享列表内!');
                        return true;
                    }
                    $list['共享者'][] = $args[3];
                    $sender->sendMessage('§b -◎ §a已将玩家§e[ ' . $args[3] . ' ]§a添加至§e[ ' . $args[2] . ' ]§a分享列表内!');
                    $this->land->set($args[2],$list);
                    $this->land->save();
                    return true;
                }
                if($args[1] == '删除')
                {
                    if(!in_array($args[3],$list['共享者']))
                    {
                        $sender->sendMessage('§b -◎ §a玩家§e[ ' . $args[3] . ' ]§a不在§e[ ' . $args[2] . ' ]§a分享列表内!');
                        return true;
                    }
                    for($a = 0; $a < count($list['共享者']); $a ++)
                    {
                        if($list['共享者'][$a] == $args[3])
                        {
                            unset($list['共享者'][$a]);
                        }
                    }
                    $load = $list['共享者'];
                    $lists = array_values($load);
                    $list['共享者'] = $lists;
                    $sender->sendMessage('§b -◎ §a已将玩家§e[ ' . $args[3] . ' ]§a移除§e[ ' . $args[2] . ' ]§a分享列表内!');
                    $this->land->set($args[2],$list);
                    $this->land->save();
                    return true;
                }
                return true;
            }
            if($args[0] == '管理员')
            {
                $list = $this->Set['管理员'];
                if($args[1] == '列表')
                {
                    $txt = "";
                    for($a = 0; $a < count($list); $a ++)
                    {
                        $txt .= "§l* §eADMIN §5|§r§6 " . $list[$a] . "\n";
                    }
                    $txt == "" ? $txt == '无更多管理员' : $txt = $txt;
                    $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§8§l IsLand §8Admin §r§7■■§a■■§b■■§c■■§e■■§4■■');
                    $sender->sendMessage('§c' . $txt);
                    $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§8§l IsLand §8Admin §r§7■■§a■■§b■■§c■■§e■■§4■■');
                    return true;
                }
                if(!isset($args[2]))
                {
                    $sender->sendMessage('§b -◎ §a/岛屿 管理员 [添加/删除/列表] 游戏名 §8< 岛屿管理员操作 >');
                    return true;
                }
                if($sender instanceof Player)
                {
                    $sender->sendMessage('§e ->§c玩家不能进行此操作,这个指令需要控制台权限! ');
                    return true;
                }
                if($args[1] == '添加')
                {
                    if(in_array($args[2],$list))
                    {
                        $sender->sendMessage('§b -◎ §a玩家§e[ ' . $args[2] . ' ]§a已在管理员列表内!');
                        return true;
                    }
                    $this->Set['管理员'][] = $args[2];
                    $sender->sendMessage('§b -◎ §a已将玩家§e[ ' . $args[2] . ' ]§a添加至管理员列表内!');
                    $this->b->set('设置',$this->Set);
                    $this->b->save();
                    return true;
                }
                if($args[1] == '删除')
                {
                    if(!in_array($args[2],$list))
                    {
                        $sender->sendMessage('§b -◎ §a玩家§e[ ' . $args[2] . ' ]§a不在管理员列表内!');
                        return true;
                    }
                    for($a = 0; $a < count($list); $a ++)
                    {
                        if($list[$a] == $args[2])
                        {
                            unset($list[$a]);
                        }
                    }
                    $load = $list;
                    $lists = array_values($load);
                    $this->Set['管理员'] = $lists;
                    $sender->sendMessage('§b -◎ §a已将玩家§e[ ' . $args[2] . ' ]§a移除管理员列表内!');
                    $this->b->set('设置',$this->Set);
                    $this->b->save();
                    return true;
                }
                return true;
            }
            if($args[0] == '黑名单')
            {
                if(!isset($args[1]))
                {
                    $sender->sendMessage('§b -◎ §a/岛屿 黑名单 [添加/删除/列表] [领地编号] [玩家] §8< 岛屿黑名单系统操作 >');
                    return true;
                }
                if($args[1] == '列表')
                {
                    $txt = "";
                    for($a = 0; $a < count($list['黑名单']); $a ++)
                    {
                        $txt .= "§l* §ePlayer §5|§r§6 " . $list['黑名单'][$a] . "\n";
                    }
                    $txt == "" ? $txt == '§c* 没有更多黑名单成员!' : $txt = $txt;
                    $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§8§l Black §9[ §d' . $args[2] . ' §9]§8 Lists §r§7■■§a■■§b■■§c■■§e■■§4■■');
                    $sender->sendMessage('§c' . $txt);
                    $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§8§l Black §9[ §d' . $args[2] . ' §9]§8 Lists §r§7■■§a■■§b■■§c■■§e■■§4■■');
                    return true;
                }
                if(!isset($args[3]))
                {
                    $sender->sendMessage('§b -◎ §a/岛屿 黑名单 [添加/删除/列表] [领地编号] [玩家] §8< 岛屿黑名单系统操作 >');
                    return true;
                }
                if(!$this->SkyBlock->exists($args[2]))
                {
                    $sender->sendMessage('§b -◎ §a不存在此编号领地!');
                    return true;
                }
                $coordinates = explode(':',$this->SkyBlock->get($args[2]));
                if($coordinates[4] != $name)
                {
                    $sender->sendMessage('§b -◎ §a此编号主人为§e[ ' . $coordinates[4] . ' ]§a你不能进行黑名操作!');
                    return true;
                }
                $args[2] = $this->SkyBlock->get('Player_IsIand_Name')[$name][$args[2]];;
                $list = $this->land->get($args[2]);
                if($args[1] == '添加')
                {
                    if(in_array($args[3],$list['黑名单']))
                    {
                        $sender->sendMessage('§b -◎ §a玩家§e[ ' . $args[3] . ' ]§a已在§e[ ' . $args[2] . ' ]§a黑名列表内!');
                        return true;
                    }
                    $list['黑名单'][] = $args[3];
                    $sender->sendMessage('§b -◎ §a已将玩家§e[ ' . $args[3] . ' ]§a添加至§e[ ' . $args[2] . ' ]§a黑名列表内!');
                    $this->land->set($args[2],$list);
                    $this->land->save();
                    return true;
                }
                if($args[1] == '删除')
                {
                    if(!in_array($args[3],$list['黑名单']))
                    {
                        $sender->sendMessage('§b -◎ §a玩家§e[ ' . $args[3] . ' ]§a不在§e[ ' . $args[2] . ' ]§a黑名列表内!');
                        return true;
                    }
                    for($a = 0; $a < count($list['黑名单']); $a ++)
                    {
                        if($list['黑名单'][$a] == $args[3])
                        {
                            unset($list['黑名单'][$a]);
                        }
                    }
                    $load = $list['黑名单'];
                    $lists = array_values($load);
                    $list['黑名单'] = $lists;
                    $sender->sendMessage('§b -◎ §a已将玩家§e[ ' . $args[3] . ' ]§a移除§e[ ' . $args[2] . ' ]§a黑名列表内!');
                    $this->land->set($args[2],$list);
                    $this->land->save();
                    return true;
                }
                return true;
            }
            if($args[0] == '模型')
            {
                if($args[1] == '列表')
                {
                    $txt = "\n";
                    for($a = 0; $a < count($this->Type_List); $a ++)
                    {
                        $this->expand = new Config($this->getDataFolder() . $this->Type_List[$a] . '.island' , Config::YAML,array());
                        $money = $this->expand->get('价格');
                        $txt .= "    §e-§6 " . $this->Type_List[$a] . " §7|§5   " . $money . "$\n 在".$this->expand->get('允许生成的世界')."世界生成";
                    }
                    $sender->sendMessage('§b -◎  §a岛屿模型列表    §b ◎-' . $txt);
                    return true;
                }
            }
            if($args[0] == '购买')
            {
                if(!$sender instanceof Player)
                {
                    $sender->sendMessage('§b -◎ §6此指令只能在游戏内执行!');
                    return true;
                }
                if(count($this->SkyBlock->get('Player_IsIand_Name')[$name]) >= $this->Set['可购买岛屿'] and !in_Array($name,$this->Set['管理员']))
                {
                    $sender->sendMessage('§b -◎ §8最高可购买的岛屿为§4[' . $this->Set['可购买岛屿'] . ']§8个!');
                    return true;
                }
                if(!isset($args[2]))
                {
                    $sender->sendMessage('§b -◎ §a/岛屿 购买 [岛屿名字] [模型名称] §8< 购买一个岛屿 >');
                    $sender->sendMessage('§b -◎ §9提示: /岛屿 模型 列表');
                    return true;
                }
                if($this->SkyBlock->exists($args[1]))
                {
                    $sender->sendMessage('§b -◎ §c§e[名称: §5' . $args[1] . '§e]§c岛屿已被购买!');
                    return true;
                }
                $world = $sender->getLevel()->getFolderName();
                if(in_array($world,$this->Type_World_List))
                {
                    $sender->sendMessage('§b -◎ §c请不要在岛屿世界购买岛屿!');
                    return true;
                }
                $playerst = $this->SkyBlock->get('Player_IsIand_Name');
                $playerst = $playerst[$name];
                if(isset($playerst[$args[1]]))
                {
                    $sender->sendMessage('§b -◎ §c你已购买§e[名称: §5' . $args[1] . '§e]§c岛屿 §8| §c领地§e编号[§5' . $playerst[$args[1]] . '§e]');
                    $sender->sendMessage('§b -◎ §9提示: /岛屿 编号传送 [ 编号]');
                    return true;
                }
                if(!in_array($args[2],$this->Type_List))
                {
                    $txt = "\n";
                    for($a = 0; $a < count($this->Type_List); $a ++)
                    {
                        $this->expand = new Config($this->getDataFolder() . $this->Type_List[$a] . '.island' , Config::YAML,array());
                        $money = $this->expand->get('价格');
                        $txt .= "    §e-§6 " . $this->Type_List[$a] . " §8|§5   " . $money . "$\n";
                    }
                    $sender->sendMessage('§b -◎ §c未找到§e[名称: §5' . $args[2] . '§e]§c的岛屿模型 §8|§c 模型列表:' . $txt);
                    return true;
                }
                $money = EconomyAPI::getInstance()->myMoney($name);
                $this->expand = new Config($this->getDataFolder() . $args[2] . '.island' , Config::YAML,array());
                $island_money = $this->expand->get('价格');
                if($island_money != 0 And $money < $island_money)
                {
                    $sender->sendMessage('§b -◎ §c你所携带的金币不足以购买这个岛屿模型,他需要§e[ §5' . $island_money . '§e$]');
                    return true;
                }
                $world = $this->expand->get('允许生成的世界');
                if(!file_exists($this->getServer()->getDataPath() . "worlds/$world"))
                {
                    $sender->sendMessage('§b -◎ §e[名称: §5' . $args[2] . '§e]§c的岛屿模型 需要在§e[世界: §5' . $world . '§e]§c中生成!但服务器内未发现此世界!');
                    return true;
                }
                $this->setIsLand($name , $args[2] , $args[1] , $world);
                return true;
            }
            if($args[0] == '生成s器')
            {
                if(!$sender instanceof Player)
                {
                    $sender->sendMessage('§b -◎ §6此指令只能在游戏内执行!');
                    return true;
                }
                if(!$sender->isOp())
                {
                    $sender->sendMessage('§b -◎ §6此指令只能管理员执行!');
                    return true;
                }
                $X = round($sender->getX());
                $Y = round($sender->getY());
                $Z = round($sender->getZ());
                $World = $sender->getLevel()->getName();
                if($args[1] != '1' And $args[1] != '2' And $args[1] != '输出')
                {
                    $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§7■■§a■■§b■■§c■■§e■■§4■■');
                    $sender->sendMessage('§b -◎ §a/岛屿 生成器 1 §8< 设置生成器第一点 >');
                    $sender->sendMessage('§b -◎ §a/岛屿 生成器 2 §8< 设置生成器第二点 >');
                    $sender->sendMessage('§b -◎ §a/岛屿 生成器 输出 §8< 设置完一二点,将方块输出到文件内 >');
                    $sender->sendMessage('§e§1■■§2■■§3■■§5■■§6■■§7■■§a■■§b■■§c■■§e■■§4■■');
                    return true;
                }
                if($args[1] == '1')
                {
                    $this->dir_1 = "$X:$Y:$Z:$World";
                    $sender->sendMessage('§b -◎ §6已设置生成器第一点: §4' . "$X : $Y : $Z");
                    return true;
                }
                if($args[1] == '2')
                {
                    if($this->dir_1 === NULL)
                    {
                        $sender->sendMessage('§b -◎ §c你需要先设置第一点!');
                        return true;
                    }
                    $this->dir_2 = "$X:$Y:$Z:$World";
                    $sender->sendMessage('§b -◎ §6已设置生成器第二点: §4' . "$X : $Y : $Z");
                    return true;
                }
                if($args[1] == '输出')
                {
                    if($this->dir_2 === NULL)
                    {
                        $sender->sendMessage('§b -◎ §c你需要先设置第二点!');
                        return true;
                    }
                    $this->dir_all = $name;
                    $this->add_World = True;
                    $sender->sendMessage('§b -◎ §6请输入允许在哪个世界生成此岛屿[请以说话方式发送]');
                    return true;
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
                $Damager_x = round($Damager->getX());
                $Damager_y = round($Damager->getY());
                $Damager_z = round($Damager->getZ());
                $Damager_world = $Damager->getLevel()->getFolderName();
                $all = $this->island->getAll();
                $txt = $Damager_x . ':' . $Damager_z;
                if($this->in_arrays($txt,$all))
                {
                    if($this->in_array_key($txt,$all) !== false)
                    {
                        $key = $this->in_array_key($txt,$all);
                        $info = $this->land->get($key);
                        if($info['公共'] === True)
                        {
                            return;
                        }
                        if($info['世界'] == $Damager_world)
                        {
                            if($Damager instanceof Player)
                            {
                                $Damager_name = $Damager->getName();
                                if($Entity instanceof Player)
                                {
                                    $Entity_name = $Entity->getName();
                                    if(in_Array($Entity_name,$this->Set['管理员']))
                                    {
                                        $Entity->sendMessage('§c管理员: §a# > §8§4[' . $Damager_name . ']§8正在攻击你,已被阻止 §a< #');
                                        $Damager->sendMessage('§c警告: §a# > §8你无法攻击 岛屿管理员§4[' . $Entity_name . ']§8他拥有较高的权限! §a< #');
                                        $event->setCancelled();
                                        return;
                                    }
                                    if($Damager_name != $info['岛主'])
                                    {
                                        if(!in_array($Damager_name,$info['共享者']))
                                        {
                                            if($Entity_name == $info['岛主'])
                                            {
                                                $Entity->sendMessage('§a# > §8你的岛屿访客§4[' . $Damager_name . ']§8试图攻击你,已被阻止 §a< #');
                                                $Damager->sendMessage('§a# > §8你的身份为访客无法攻击§4[' . $info['岛主'] . ']§8 §a< #');
                                                $event->setCancelled();
                                                return;
                                            }
                                        }
                                    }
                                    if($Damager_name == $info['岛主'])
                                    {
                                        $Entity->sendMessage('§a# > §8岛主§4[' . $info['岛主'] . ']§8正在攻击你 §a< #');
                                        return;
                                    }
                                }
                                if(in_Array($Damager_name,$this->Set['管理员']))
                                {
                                    $Entity->sendMessage('§a# > §8岛屿管理员§4[' . $Damager_name . ']§8正在攻击你 §a< #');
                                    return;
                                }
                                if($Damager_name != $info['岛主'] And !in_array($Damager_name,$info['共享者']))
                                {
                                    $Damager->sendMessage('§a# > §8你无法攻击岛主§4[' . $info['岛主'] . ']§8的生物 §a< #');
                                    $event->setCancelled();
                                    return;
                                }
                            }
                            else
                            {
                                if($Entity instanceof Player)
                                {
                                    $Entity_name = $Entity->getName();
                                    if($Entity_name != $info['岛主'] And !in_array($Entity_name,$info['共享者']))
                                    {
                                        $Entity->sendMessage('§a# > §8岛主§4[' . $info['岛主'] . ']§8上的生物试图攻击你,已被阻止 §a< #');
                                        $event->setCancelled();
                                        return;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function PlayerChatEvent(PlayerChatEvent $event)
    {
        $player=$event->getPlayer();
        $name = $player->getName();
        $say = $event->getMessage();
        $note = $this->Player->get($name);
        if($note['Note'] !== False)
        {
            $say = strtolower($say);
            $event->setCancelled();
            if($say == 'no')
            {
                $info = explode(':',$note['Note']);
                if($info[1] < Time())
                {
                    $player->sendMessage('§a# > §e已超过20秒!此邀请已自动关闭!');
                    $note['Note'] = False;
                    $this->Player->set($name,$note);
                    $this->Player->save();
                    return;
                }
                $skyblock = $this->land->get($info[0]);
                $isPlayer = $this->getServer()->getPlayer($skyblock['岛主']);
                if($isPlayer instanceof Player)
                {
                    $isPlayer->sendMessage('§a# > §c对方拒绝了你的邀请!');
                }
                $player->sendMessage('§a# > §e已拒绝§a[ ' . $skyblock['岛主'] . ' ]§e的邀请!');
                $note['Note'] = False;
                $this->Player->set($name,$note);
                $this->Player->save();
                return;
            }
            if($say == 'yes')
            {
                $info = explode(':',$note['Note']);
                if($info[1] < Time())
                {
                    $player->sendMessage('§a# > §e已超过20秒!此邀请已自动
						!');
                    $note['Note'] = False;
                    $this->Player->set($name,$note);
                    $this->Player->save();
                    return;
                }
                $skyblock = $this->land->get($info[0]);
                $isPlayer = $this->getServer()->getPlayer($skyblock['岛主']);
                if($isPlayer instanceof Player)
                {
                    $isPlayer->sendMessage('§a# > §a[ ' . $name . ' ]§e同意了你的邀请,已传送至§a[ ' . $info[0] . ' ]§e岛屿出生点!');
                }
                $player->sendMessage('§a# > §e欢迎你来游玩§a[ ' . $skyblock['岛主'] . ' ]§e的§a[ ' . $info[0] . ' ]§e岛屿!');
                $coordinates = explode(':',$this->SkyBlock->get($info[0]));
                $world = $this->getServer()->getLevelbyName($coordinates[3]);
                $id1 = $world->getBlockIdAt($coordinates[0],$coordinates[1],$coordinates[2]);
                $id2 = $world->getBlockIdAt($coordinates[0],$coordinates[1] + 1,$coordinates[2]);
                if($id1 != 0 Or $id2 != 0)
                {
                    $player->sendMessage('§b -◎ §c传送点发现方块,正在自动调整高度!');
                }
                $XYZ_Y = 0;
                for($a = 2; $world->getBlockIdAt($coordinates[0],$coordinates[1] + $a,$coordinates[2]) != 0; $a ++)
                {
                    $XYZ_Y = $a + 1;
                }
                $XYZ_Y != 0 ? $player->sendMessage('§b -◎ §c传送高度自动上升' . $XYZ_Y . '格!') : $r = '';
                $player->teleport(new Position($coordinates[0],$coordinates[1] + $XYZ_Y,$coordinates[2],$this->getServer()->getLevelByName($coordinates[3])));
                $note['Note'] = False;
                $this->Player->set($name,$note);
                $this->Player->save();
                return;
            }
        }
        if($this->dir_all == $name)
        {
            $this->con = new Config($this->getDataFolder().'set/scq/New_island.island',Config::YAML,array());
            $event->setCancelled();
            if($this->add_World == True)
            {
                $this->con->set('允许生成的世界',$say);
                $this->con->save();
                $player->sendMessage('§a# > §e已存储可生成世界为:' . $say . '!');
                $player->sendMessage('§a# > §e请输入岛屿生成的§5"最低"与"最高"§b海拔[Y轴],输入如:§5"60-70"');
                $this->add_World = False;
                $this->add_Y = True;
                return;
            }
            //添加 海拔
            if($this->add_Y == True)
            {
                $ys = explode('-',$say);
                if(!isset($ys[1]))
                {
                    $player->sendMessage('§a# > §e请输入岛屿生成的§5"最低"与"最高"§b海拔[Y轴],输入如:§5"60-70"');
                    return;
                }
                $this->con->set('允许生成高度',$say);
                $this->con->save();
                $player->sendMessage('§a# > §e已存储可生成高度为:' . $say . '!');
                $player->sendMessage('§a# > §e请输入岛屿购买的价格,0为免费!');
                $this->add_Y = False;
                $this->add_M = True;
                return;
            }
            //添加 价格
            if($this->add_M == True)
            {
                $this->con->set('价格',$say);
                $this->con->save();
                $player->sendMessage('§a# > §e已存储岛屿价格为:' . $say . '!');
                $player->sendMessage('§a# > §e请点击一个§c[空手]§e方块作为基准点，生成岛屿时会从此方块开始向外生成');
                $this->add_M = False;
                $this->standard = True;
                return;
            }
            //添加 基准点
            if($this->standard == True)
            {
                $event->setCancelled();
                $player->sendMessage('§a# > §e请点击一个§c[空手]§e方块作为基准点，生成岛屿时会从此方块开始向外生成');
                return;
            }
        }
    }

    public function BlockPlaceEvent(BlockPlaceEvent $event)
    {
        $block = $event->getBlock();
        $world = $block->getLevel()->getFolderName();
        if(in_array($world,$this->Type_World_List))
        {
            $x = $block->getX();
            $y = $block->getY();
            $z = $block->getZ();
            $player = $event->getPlayer();
            $name = $player->getName();
            $all = $this->island->getAll();
            $number = 0;
            $txt = $x . ':' . $z;
            if(!in_array($world,$this->Type_World_List))
            {
                return;
            }
            if($this->in_arrays($txt,$all))
            {
                $info = $this->land->get($this->in_array_key($txt,$all));
                if($info['岛主'] !== $name And !in_Array($name,$info['共享者']) And !in_Array($name,$this->Set['管理员']))
                {
                    $player->sendMessage('§b -◎ §8此处岛主为§4[' . $info['岛主'] . ']§8你无权限进行操作!');
                    $event->setCancelled();
                    return;
                }
                return;
            }
            $x1 = $x - 2;
            $z1 = $z - 2;
            $x2 = $x + 3;
            $z2 = $z + 3;
            $ee = 0;
            $island = Array();
            for($a = 0;$x1+$a < $x2;$a ++)
            {
                for($b = 0;$z1+$b < $z2;$b ++)
                {
                    $number += 1;
                    $c = $x1+$a;
                    $d = $z1+$b;
                    $txt = $c . ':' . $d;
                    $ee += 1;
                    if($this->in_arrays($txt,$all))
                    {
                        if($this->in_array_key($txt,$all) !== false)
                        {
                            if(!in_array($this->in_array_key($txt,$all),$island))
                            {
                                $island[] = $this->in_array_key($txt,$all);
                            }
                        }
                    }
                }
            }
            if(!isset($island[0]))
            {
                $player->sendMessage('§b -◎ §8并未发现可拓展的岛屿领地!');
                $event->setCancelled();
                return;
            }
            if(isset($island[1]) and !in_Array($name,$this->Set['管理员']))
            {
                $player->sendMessage('§b -◎ §8附近发现其他编号的领地,不能继续拓展岛屿领地!');
                $event->setCancelled();
                return;
            }
            $info = $this->land->get($island[0]);
            if($info['岛主'] !== $name And !in_Array($name,$info['共享者']) And !in_Array($name,$this->Set['管理员']))
            {
                $player->sendMessage('§b -◎ §8此处岛主为§4[' . $info['岛主'] . ']§8你无权限进行操作!');
                $event->setCancelled();
                return;
            }
            if($info['世界'] == $world)
            {
                if(!isset($info['领地']))
                {
                    $info['领地'] = 0;
                }
                if($info['领地'] > $this->Set['岛最多可拓展多少领地'] and !in_Array($name,$this->Set['管理员']))
                {
                    $player->sendMessage('§b -◎ §8岛屿最高可拓展§4['.$this->Set['岛最多可拓展多少领地'].']§8格领地!');
                    $event->setCancelled();
                    return;
                }
                $money = EconomyAPI::getInstance()->myMoney($name);
                if($money < $this->Set['单个领地价格'] and !in_Array($name,$this->Set['管理员']))
                {
                    $player->sendMessage('§b -◎ §8你没有多余的钱进行拓展领地!');
                    $event->setCancelled();
                    return;
                }
                $land = $this->island->get($island[0]);
                $land[] = $x . ':' . $z;
                $this->island->set($island[0],$land);
                $this->island->save();
                if(!in_Array($name,$this->Set['管理员'])) EconomyAPI::getInstance()->setMoney($player,$money - $this->Set['单个领地价格']);
                $Note = '';
                $mc = $block->getLevel();
                $mc->setBiomeId($x,$z,$info['生态环境']);
                $admin = '';
                in_Array($name,$this->Set['管理员']) ? $admin = '§b管理员权限: ' : [];
                $info['领地'] += 1;
                $this->land->set($island[0],$info);
                $this->land->save();
                if($this->Set['单个领地价格'] > 0)
                {
                    $Note = !in_Array($name,$this->Set['管理员']) ? '§9    [支出§4 ' . $this->Set['单个领地价格'] . ' §9]' : '§9 岛屿管理员权限';
                }
                if($this->Set['拓展领地显示方式'] == 'popup')
                {
                    $player->sendPopup($admin . '§b -◎ §8已拓展§4[' . $island[0] . ']§8领地§b ◎-' . $Note);
                }
                else
                {
                    $player->sendTip($admin . '§b -◎ §8已拓展§4[' . $island[0] . ']§8领地§b ◎-' . $Note);
                }
            }
        }
    }

    public function BlockBreakEvent(BlockBreakEvent $event)
    {
        $block = $event->getBlock();
        $world = $block->getLevel()->getFolderName();
        if(in_array($world,$this->Type_World_List))
        {
            $x = $block->getX();
            $y = $block->getY();
            $z = $block->getZ();
            $player = $event->getPlayer();
            $name = $player->getName();
            $all = $this->island->getAll();
            $txt = $x . ':' . $z;
            if($this->in_arrays($txt,$all))
            {
                if($this->in_array_key($txt,$all) !== false)
                {
                    $key = $this->in_array_key($txt,$all);
                    $info = $this->land->get($key);
                    if($info['岛主'] != $name And !in_Array($name,$info['共享者']) And !in_Array($name,$this->Set['管理员']))
                    {
                        $player->sendMessage('§b -◎ §8此处岛主为§4[' . $info['岛主'] . ']§8你无权限进行操作!§b ◎-');
                        $event->setCancelled();
                        return;
                    }
                }
            }
            if($block->getLevel()->getBlockIdAt($x,$y,$z) == 4)
            {
                $x1 = $x + 1;
                $x2 = $x - 1;
                $z1 = $z + 1;
                $z2 = $z - 1;
                $x1 = $block->getLevel()->getBlockIdAt($x1,$y,$z);
                $x2 = $block->getLevel()->getBlockIdAt($x2,$y,$z);
                $x2 = $block->getLevel()->getBlockIdAt($x,$y,$z1);
                $x2 = $block->getLevel()->getBlockIdAt($x,$y,$z2);
                if(
                    $x1 == 8 Or $x1 == 9 And $z1 == 10 Or $z1 == 11 ||
                    $x2 == 8 Or $x2 == 9 And $z2 == 10 Or $z2 == 11 ||
                    $x1 == 10 Or $x1 == 11 And $z1 == 8 Or $z1 == 9 ||
                    $x2 == 10 Or $x2 == 11 And $z2 == 8 Or $z2 == 9
                )
                {
                    $block->getLevel()->dropItem(new Vector3($x,$y+1,$z),new Item(4,0,1));
                    $event->setCancelled();
                    return;
                }
            }
        }
    }

    public function PlayerInteractEvent(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
        $xs = $event->getBlock()->getX();
        $ys = $event->getBlock()->getY();
        $zs = $event->getBlock()->getZ();
       /* $world = $event->getBlock()->getLevel()->getName();
        $level = $event->getBlock()->getLevel();
        $mc = $this->getServer()->getLevelByName($world);
        $txt = $xs . ':' . $zs;
        $all = $this->island->getAll();
        if($this->dir_all == $name)
        {
            $this->con = new Config($this->getDataFolder().'set/scq/New_island.island',Config::YAML,array());
            $item = $player->getInventory()->getItemInHand()->getId();
            if($item != 0)
            {
                $player->sendMessage('§a# > §c你必须空手点击!');
                return;
            }
            if($this->standard == True)
            {
                $this->dir_all = False;
                $this->standard = False;
                $this->con->set('基准点',$xs.':'.$ys.':'.$zs);
                $this->con->save();
                $player->sendMessage('§a# > §e已设置基准点设置 §5( '.$xs.' : '.$ys.' : '.$zs.' )§e');
                $Data = 0;
                $Air = 0;
                $Level = $this->getServer()->getLevelByName($world);
                $xyz1 = explode(':',$this->dir_1);
                $xyz2 = explode(':',$this->dir_2);
                $x1 = (int)$xyz1[0];
                $y1 = (int)$xyz1[1];
                $z1 = (int)$xyz1[2];
                $x2 = (int)$xyz2[0];
                $y2 = (int)$xyz2[1];
                $z2 = (int)$xyz2[2];
                if($x1>$x2){$x=$x1;$x1=$x2;$x2=$x;$x=0;}
                if($y1>$y2){$y=$y1;$y1=$y2;$y2=$y;$y=0;}
                if($z1>$z2){$z=$z1;$z1=$z2;$z2=$z;$z=0;}
                for($x=$x1;$x<=$x2;$x++)
                {
                    for($y=$y1;$y<=$y2;$y++)
                    {
                        for($z=$z1;$z<=$z2;$z++)
                        {
                            if($Level->getBlockIdAt($x,$y,$z) == 0)
                            {
                                $Air = $Air + 1;
                            }
                            else
                            {
                                $Data = $Data + 1;
                                $BlockId = $Level->getBlockIdAt((int)$x,(int)$y,(int)$z);
                                $BlockData = $Level->getBlockDataAt((int)$x,(int)$y,(int)$z);
                                $x3 = (int)$x - (int)$xs;
                                $y3 = (int)$y - (int)$ys;
                                $z3 = (int)$z - (int)$zs;
                                $this->con->set($Data,$x3.'*'.$y3.'*'.$z3.'*'.$BlockId.'*'.$BlockData);
                            }
                        }
                    }
                }
                if($Data == 0 And $Air == 0)
                {
                    $player->sendMessage('§a# > §e输出失败!未知原因!');
                    return true;
                }
                if($Data == 0 And $Air != 0)
                {
                    $player->sendMessage('§a# > §e输出失败!原因: 检测全为空气!无任何方块!');
                    return true;
                }
                $this->con->set('DATA',$Data);
                $this->con->save();
                $player->sendMessage('§a# > §e一共写入:'.$Data.'个方块数据，忽略'.$Air.'个空气');
                $player->sendMessage('§a# > §e文件输出完成,目录: ' . $this->getDataFolder().'set/scq/New_island.island');
                return;
            }
        }
        if(!in_array($world,$this->Type_World_List))
        {
            return;
        }
        if($this->in_arrays($txt,$all))
        {
            $info = $this->land->get($this->in_array_key($txt,$all));
            if($info['岛主'] !== $name And !in_Array($name,$info['共享者']) And !in_Array($name,$this->Set['管理员']))
            {
                $player->sendMessage('§b -◎ §8此处岛主为§4[' . $info['岛主'] . ']§8你无权限进行操作!');
                $event->setCancelled();
                return;
            }
            return;
        }*/
    }

    public function PlayerJoinEvent(PlayerJoinEvent $event)
    {
        $Player = $event->getPlayer();
       /* $Player_Name = $Player->getName();
        $skyblock = $this->SkyBlock->get('Player_IsIand_Name');
        if(!isset($skyblock[$Player_Name]))
        {
            $skyblock[$Player_Name] = Array();
            $this->SkyBlock->set('Player_IsIand_Name',$skyblock);
            $this->SkyBlock->save();
        }
        if(!$this->Player->exists($Player_Name))
        {
            $Player = Array(
                '黑名单' => False,
                '黑名单原因' => False,
                '背包' => False,
                'VIP' => Array(),
                'Note' => False,
                '底部' => True
            );
            $this->Player->set($Player_Name , $Player);
            $this->Player->save();
        }
        else
        {
            $info = $this->Player->get($Player_Name);
            if(
                !isset($info['黑名单']) OR
                !isset($info['黑名单原因']) OR
                !isset($info['背包']) OR
                !isset($info['VIP']) OR
                !isset($info['Note']) OR
                !isset($info['底部'])
            )
            {
                $Player = Array(
                    '黑名单' => False,
                    '黑名单原因' => False,
                    '背包' => False,
                    'VIP' => Array(),
                    'Note' => False,
                    '底部' => True
                );
                $this->Player->set($Player_Name , $Player);
                $this->Player->save();
            }
        }*/
    }

    public function unbeibao($name)
    {
        $player = $this->getServer()->getPlayer($name);
        if($player instanceof Player)
        {
            $name = $player->getName();
            $world = $player->getLevel()->getFolderName();
            $info = $this->Player->get($name);
            $beibao = $player->getInventory();
            $item = $beibao->getItemInHand();
            //1:岛屿世界 0:正常世界
            if(!isset($info['背包']))
            {
                $info['背包'] = False;
            }
            if($info['背包'] === False)
            {
                in_array($world,$this->Type_World_List) ? $info['背包'] = 1 : $info['背包'] = 0;
                $this->Player->set($name,$info);
                $this->Player->save();
            }
            if(!in_array($world,$this->Type_World_List) And $info['背包'] == 1)
            {
                $info['背包'] = 0;
                $this->Player->set($name,$info);
                $this->Player->save();
                $itemdata=0;
                $c = Array();
                for($index = 0; $index < $player->getInventory()->getSize(); $index ++)
                {
                    $setitem = $player->getInventory()->getItem($index);
                    if($setitem->getID() !== 0 and $setitem->getCount() > 0)
                    {
                        $itemdata += 1;
                        $Enchant_ID = Array();
                        $Enchant_LV = Array();
                        if(!empty($setitem->getEnchantments()))
                        {
                            foreach($setitem->getEnchantments() as $Enchantment)
                            {
                                $Enchant_ID[] = $Enchantment->getId();
                                $Enchant_LV[] = $Enchantment->getLevel();
                            }
                        }
                        $itemstart = Array(
                            $setitem->getID(),
                            $setitem->getDamage(),
                            $setitem->getCount(),
                            $Enchant_ID,
                            $Enchant_LV
                        );
                        $c[$index]=$itemstart;
                    }
                }
                $beibao->setContents(array(new Item(0,0,0)));
                for($i = 0; $i < 36; $i ++)
                {
                    $Item_ID = 0;
                    $Item_Danage = 0;
                    $Item_Count = 0;
                    if(isset($this->beibao->get($name)[$i]))
                    {
                        $Item_ID = $this->beibao->get($name)[$i][0];
                        $Item_Danage = $this->beibao->get($name)[$i][1];
                        $Item_Count = $this->beibao->get($name)[$i][2];
                        $Item_Enchant_ID = $this->beibao->get($name)[$i][3];
                        $Item_Enchant_LV = $this->beibao->get($name)[$i][4];
                        $items = new Item((int)$Item_ID,(int)$Item_Danage,(int)$Item_Count);
                        if(count($Item_Enchant_ID) > 0 And count($Item_Enchant_LV) > 0)
                        {
                            for($a = 0; $a < count($Item_Enchant_ID); $a ++)
                            {
                                $Enchantment = Enchantment::getEnchantment($Item_Enchant_ID[$a]);
                                $Enchantment->setLevel($Item_Enchant_LV[$a]);
                                $items->addEnchantment($Enchantment);
                            }
                        }
                        $player->getInventory()->addItem(clone $items);
                    }
                }
                $itemdata == 0 ? $this->beibao->remove($name) : $this->beibao->set($name,$c);
                $this->beibao->save();
                $player->sendMessage('§b -◎ §6背包模式变更为§e[§a生存§e]');
                return;
            }
            if(in_array($world,$this->Type_World_List) And $info['背包'] == 0)
            {
                $info['背包'] = 1;
                $this->Player->set($name,$info);
                $this->Player->save();
                $itemdata=0;
                $c = Array();
                for($index = 0; $index < $player->getInventory()->getSize(); $index ++)
                {
                    $setitem = $player->getInventory()->getItem($index);
                    if($setitem->getID() !== 0 and $setitem->getCount() > 0)
                    {
                        $itemdata += 1;
                        $Enchant_ID = Array();
                        $Enchant_LV = Array();
                        if(!empty($setitem->getEnchantments()))
                        {
                            foreach($setitem->getEnchantments() as $Enchantment)
                            {
                                $Enchant_ID[] = $Enchantment->getId();
                                $Enchant_LV[] = $Enchantment->getLevel();
                            }
                        }
                        $itemstart = Array(
                            $setitem->getID(),
                            $setitem->getDamage(),
                            $setitem->getCount(),
                            $Enchant_ID,
                            $Enchant_LV
                        );
                        $c[$index]=$itemstart;
                    }
                }
                $beibao->setContents(array(new Item(0,0,0)));
                for($i = 0; $i < 36; $i ++)
                {
                    $Item_ID = 0;
                    $Item_Danage = 0;
                    $Item_Count = 0;
                    if(isset($this->beibao->get($name)[$i]))
                    {
                        $Item_ID = $this->beibao->get($name)[$i][0];
                        $Item_Danage = $this->beibao->get($name)[$i][1];
                        $Item_Count = $this->beibao->get($name)[$i][2];
                        $Item_Enchant_ID = $this->beibao->get($name)[$i][3];
                        $Item_Enchant_LV = $this->beibao->get($name)[$i][4];
                        $items = new Item((int)$Item_ID,(int)$Item_Danage,(int)$Item_Count);
                        if(count($Item_Enchant_ID) > 0 And count($Item_Enchant_LV) > 0)
                        {
                            for($a = 0; $a < count($Item_Enchant_ID); $a ++)
                            {
                                $Enchantment = Enchantment::getEnchantment($Item_Enchant_ID[$a]);
                                $Enchantment->setLevel($Item_Enchant_LV[$a]);
                                $items->addEnchantment($Enchantment);
                            }
                        }
                        $player->getInventory()->addItem(clone $items);
                    }
                }
                $itemdata == 0 ? $this->beibao->remove($name) : $this->beibao->set($name,$c);
                $this->beibao->save();
                $player->sendMessage('§b -◎ §6背包模式变更为§e[§a岛屿§e]');
                return;
            }
        }
    }

    public function ti()
    {
        if($this->Create_Mod !== False)
        {
            $player = $this->Create_Mod['Player'];
            $name = $this->Create_Mod['Name'];
            if($this->Data->IsLand_Disk[$name]['Z'] <= 10000)
            {
                $this->n += 1;
                $n = $this->Create_Mod['nu'];
                $player->sendMessage("§c[提示]§6 已生成 [".$this->n."]个 岛屿! 共[".$n."]个 剩余".$this->Data->untime(($n - $this->n)/10,true));
                $this->Data->Create_IsLand($name,$player,10000);
            }
        }
        foreach($this->getServer()->getOnlinePlayers() as $player)
        {
            $x = round($player->getX());
            $z = round($player->getZ());
            $y = round($player->getY());
            $player->sendPopup("$x,$y,$z");
        }
    }

    public function timer()
    {
        foreach($this->getServer()->getOnlinePlayers() as $player)
        {
            $x = round($player->getX());
            $z = round($player->getZ());
            $y = round($player->getY());
            //$player->sendPopup("$x,$y,$z");
            //$this->Data->Create_IsLand('1505756776');
            //$xy = $this->Data->IsLand_Disk['1505756776'];
            //$player->teleport(new Position($xy['X'],80,$xy['Z'],$xy['Level']));
            //$this->n += 1;
            //$player->sendMessage('共生成'.$this->n."个岛屿 ".$xy['X']." , ".$xy['Z']."");
            $world = $player->getLevel()->getName();
            $name = $player->getName();
            $mc = $player->getLevel();
            $all = $this->island->getAll();
            $txt = $x . ':' . $z;
            $level = $player->getLevel();
            if($world == 'world')
            {
                $wide = 40;
                $mx=round($x-($x+1600000)%$wide);
                $mz=round($z-($z+1600000)%$wide);
                if(!$this->world->exists($mx.$mz.$world))
                {
                    $this->world->set($mx.$mz.$world,True);
                    $mx += 39;$mz += 39;
                    for($x = $mx - 39; $x <= $mx; $x ++)
                    {
                        for($z = $mz - 39; $z <= $mz; $z ++)
                        {
                            $this->setTile($level,$x,1,$z,0,0);
                        }
                    }
                }
            }
            $this->world->save();
            if($this->Set['背包'] == '开') $this->unbeibao($name);
            if(!in_array($world,$this->Type_World_List)) return;
            if($this->Set['领地显示'] != '开') return;
            if($player->getGamemode() != 1 and !$player->isOp()) $player->setGamemode(0);
            if($player->getAllowFlight() === true and !$player->isOp()) $player->setAllowFlight(False);
            if($this->in_arrays($txt,$all))
            {
                if($this->in_array_key($txt,$all) !== false)
                {
                    $key = $this->in_array_key($txt,$all);
                    $info = $this->land->get($key);
                    if($info['世界'] == $world)
                    {
                        $gx = '无';
                        if(count($info['共享者']) > 0)
                        {
                            if($gx == '无')
                            {
                                $gx = '';
                            }
                            for($a = 0;$a < count($info['共享者']) and $a < 4; $a ++)
                            {
                                $txt = '§e' . $info['共享者'][$a];
                                $ae = '';
                                if($a == 3) $txt = "§8...";
                                if($a < 3) $ae = ' §5|§e ';
                                $gx .= $txt . $ae;
                            }
                        }
                        $concent = $this->Set['领地显示内容'];
                        strstr($concent , '{编号}') ? $concent = strtr($concent , Array('{编号}' => $key)) : [];
                        strstr($concent , '{岛主}') ? $concent = strtr($concent , Array('{岛主}' => $info['岛主'])) : [];
                        strstr($concent , '{共享列表}') ? $concent = strtr($concent , Array('{共享列表}' => $gx)) : [];
                        strstr($concent , '{领地面积}') ? $concent = strtr($concent , Array('{领地面积}' => count($this->island->get($key)))) : [];
                        strstr($concent , '{生态环境}') ? $concent = strtr($concent , Array('{生态环境}' => $this->getBiomeName($info['生态环境']))) : [];
                        if($this->Player->get($name)['底部'])
                        {
                            $this->Set['领地显示方式'] == 'popup' ? $player->sendPopup("$concent") : $player->sendTip("$concent");
                        }
                        if(in_Array($name,$info['黑名单']))
                        {
                            $player->sendMessage('§b -◎ §8你被岛主§4[' . $info['岛主'] . ']§8列入此岛黑名单,无法以访客身份参观!§b ◎-');
                            $player->sendMessage('§b -◎ §8正在传送回出生点!§b ◎-');
                            $pos = $this->getServer()->getDefaultLevel()->getSpawnLocation();
                            $sender->teleport($pos);
                        }
                    }
                }
            }
        }
    }

    public function bancommand(PlayerCommandPreprocessEvent $event)
    {
        $player = $event->getPlayer();
        $sendMessage = $event->getMessage();
        $world = $player->getLevel()->getFolderName();
        if(in_array($world,$this->Type_World_List) and !$player->isOp())
        {
            foreach($this->Set['禁止命令'] as $command)
            {
                if(strstr('/'.$command,$sendMessage) and !in_Array($name,$this->Set['管理员']))
                {
                    $player->sendMessage('§b -◎ §8岛屿世界内不允许使用此指令!§b ◎-');
                    $event->setCancelled();
                }
            }
        }
    }

    public function setIsLand($Player_Name , $Expamd , $IsLand_Name , $world)
    {
        $player = $this->getServer()->getPlayer($Player_Name);
        if($player instanceof Player)
        {
            $this->Expand = new Config($this->getDataFolder() . $Expamd . '.island', Config::YAML,array());
            $sky = $this->Expand->get('DATA');
            $world = $this->Expand->get('允许生成的世界');
            $yz = explode('-',$this->Expand->get('允许生成高度'));
            $mc = $this->getServer()->getLevelByName($world);
            $binX = $this->island->get('坐标X');
            $binZ = $this->island->get('坐标Z');
            if($binX >= 1300)
            {
                $this->island->set('坐标X',-1000);
                $this->island->set('坐标Z',$binZ +    $this->Set['岛屿之间的距离']);
                $binZ +=    $this->Set['岛屿之间的距离'];
                $binX = -1000;
            } else {
                $this->island->set('坐标X',$binX + $this->Set['岛屿之间的距离']);
                $binX +=    $this->Set['岛屿之间的距离'];
            }
            $binZ1 = $binZ +    $this->Set['岛屿之间的距离'];
            $this->island->save();
            $randY = 60;
            $DATA = $this->SkyBlock->get('DATA') + 1;
            $island_XZ = Array();
            $xyz1 = explode('*',$this->Expand->get('1'));
            $xyz2 = explode('*',$this->Expand->get($sky));
            $xS = $player->getX();
            $yS = $player->getY();
            $zS = $player->getZ();
            $island_XZ_yz = Array();
            for($a = 1; $a < $sky; $a ++)
            {
                $xyz = $this->Expand->get($a);
                $xyz1 = explode('*', $xyz);
                $x1 = $binX + $xyz1[0] + 0;
                $z1 = $binZ + $xyz1[2] + 0;
                if(!in_Array($x1 . ':' . $z1,$island_XZ_yz))
                {
                    $island_XZ_yz[] = $x1 . ':' . $z1;
                    $y1 = $randY + $xyz1[1] + 0;
                    $txt = $x1 . ':' . $z1;
                    $all = $this->island->getAll();
                    if($this->in_arrays($txt,$all))
                    {
                        if($this->in_array_key($txt,$all) !== false)
                        {
                            $key = $this->in_array_key($txt,$all);
                            $info = $this->land->get($key);
                            if($info['世界'] == $world And $info['岛主'] != 'SMT.NETS')
                            {
                                $player->sendMessage('§e-> §c在检测时发现此岛屿附近有§4[' . $info['岛主'] . ']§c的领地,正在重新帮助!');
                                $this->setIsLand($Player_Name , $Expamd , $IsLand_Name , $world);
                                return;
                            }
                        }
                    }
                    if(@$mc->getBlockIdAt($x1,$y1,$z1) != 0)
                    {
                        if(!$this->in_arrays($txt,$all))
                        {
                            $player->sendMessage('§e-> §c在检测时发现此岛屿附近存在方块,正在重新帮助!');
                            $this->setIsLand($Player_Name , $Expamd , $IsLand_Name , $world);
                            return;
                        }
                    }
                }
            }
            $times = explode(' ',microtime());
            for($a = 1; $a < $sky; $a ++)
            {
                $xyz = $this->Expand->get($a);
                $xyz1 = explode('*', $xyz);
                $x1 = $binX + $xyz1[0] + 0;
                $y1 = $randY + $xyz1[1] + 0;
                $z1 = $binZ + $xyz1[2] + 0;
                $id = $xyz1[3] + 0;
                $date = $xyz1[4] + 0;
                if($id == 58)
                {
                    $xS = $x1;
                    $yS = $y1;
                    $zS = $z1;
                }
                $this->setTile($mc,$x1,$y1,$z1,$id,$date);
                if(!in_array($x1 . ':' . $z1,$island_XZ))
                {
                    $island_XZ[] = $x1 . ':' . $z1;
                    $mc->setBiomeId($x1,$z1,1);
                }
            }
            $x1 = ($binX +  $this->Set['岛屿之间的距离'] + $xyz1[0]) - 10;
            $z1 = ($binZ + $xyz1[2]) - 10;
            $x2 = ($binX +  $this->Set['岛屿之间的距离'] + $xyz2[0]) + 10;
            $z2 = ($binZ + $xyz2[2]) + 10;
            for($a = 0;$x1 + $a < $x2;$a ++)
            {
                for($b = 0;$z1 + $b < $z2;$b ++)
                {
                    $this->setTile($mc,$x1 + $a,4,$z1 + $b,0,0);
                }
            }
            $land_info = Array(
                '岛主' => $Player_Name,
                '世界' => $world,
                '生态环境' => 1,
                '公共' => false,
                '领地' => 0,
                '共享者' => Array(),
                '黑名单' => Array()
            );
            $mc->save();
            $this->land->set($DATA , $land_info);
            $this->island->set($DATA , $island_XZ);
            $island_name = $this->SkyBlock->get('IsIand_Name');
            $island_name[$IsLand_Name] = $Player_Name;
            $this->SkyBlock->set('DATA',$DATA);
            $this->SkyBlock->set('IsIand_Name',$island_name);
            $block = $this->Expand->get($sky);
            $playerst = $this->SkyBlock->get('Player_IsIand_Name');
            $playerst[$Player_Name][$IsLand_Name] = $DATA;
            $this->SkyBlock->set('Player_IsIand_Name',$playerst);
            $block = $this->Expand->get($sky);
            $xyz1 = explode('*',$block);
            $timess = explode(' ',microtime());
            $timetext = $timess[0]+$timess[1]-($times[0]+$times[1]);
            $p_x = (int)($xyz1[0] + $binX);
            $p_y = (int)($xyz1[1] + 3 + $randY);
            $p_z = (int)($xyz1[2] + $binZ);
            $player->teleport(new Position($p_x,$p_y,$p_z,$this->getServer()->getLevelByName($world)));
            $this->unbeibao($Player_Name);
            if($DATA == 1) return $this->setIsLand($Player_Name , $Expamd , $IsLand_Name , $world);
            if($this->Set['购买岛屿显示内容'] == '关')
            {
                $player->sendMessage('§e-> §6岛屿已生成,耗时:§c'.round($timetext,4).'§6秒');
                $player->sendMessage('§e-> §6祝你空岛生存愉快!');
            } else {
                $player->sendMessage(''.$this->Set['购买岛屿显示内容']);
            }
            for($a = 0;$a < count($this->Set['购买岛屿后工作台上的掉落物']);$a ++)
            {
                $item = explode(':',$this->Set['购买岛屿后工作台上的掉落物'][$a]);
                if($this->Set['岛屿自带物品给予方式'] == '掉落')
                {
                    $mc->dropItem(new Vector3($xS,$yS,$zS),new Item($item[0],$item[1],$item[2]));
                }
                else
                {
                    $player->getInventory()->addItem(clone new Item($item[0],$item[1],$item[2]));
                }
            }
            $money = EconomyAPI::getInstance()->myMoney($Player_Name);
            $island_money = $this->expand->get('价格');
            EconomyAPI::getInstance()->setMoney($player,$money - $island_money);
            $txt1 = (int)$xyz1[0] + (int)$binX;
            $txt2 = (int)$xyz1[1] + 2 + (int)$randY;
            $txt3 = (int)$xyz1[2] + (int)$binZ;
            $txt = $txt1 . ':' . $txt2 . ':' . $txt3 . ':' . $world . ':' . $Player_Name;
            $this->SkyBlock->set($IsLand_Name,$txt);
            $this->SkyBlock->save();
            $this->land->save();
            $this->island->save();
            if($this->Set['生成方案'] == '兼容')
            {
                if($binX >= 1300)
                {
                    $this->island->set('坐标X',-1000);
                    $this->island->set('坐标Z',$binZ +    ($this->Set['岛屿之间的距离'] * 2));
                    $binZ +=    ($this->Set['岛屿之间的距离'] * 2);
                    $binX = -1000;
                }
                else
                {
                    $this->island->set('坐标X',$binX + ($this->Set['岛屿之间的距离'] * 2));
                    $binX +=    ($this->Set['岛屿之间的距离'] * 2);
                }
                $binZ1 = $binZ +    ($this->Set['岛屿之间的距离'] * 2);
                $this->island->save();
                $randY = 60;
                $DATA = $this->SkyBlock->get('DATA') + 1;
                $island_XZ = Array();
                $xyz1 = explode('*',$this->Expand->get('1'));
                $xyz2 = explode('*',$this->Expand->get($sky));
                $xS = $player->getX();
                $yS = $player->getY();
                $zS = $player->getZ();
                $island_XZ_yz = Array();
                for($a = 1; $a < $sky; $a ++)
                {
                    $xyz = $this->Expand->get($a);
                    $xyz1 = explode('*', $xyz);
                    $x1 = $binX + $xyz1[0] + 0;
                    $y1 = $randY + $xyz1[1] + 0;
                    $z1 = $binZ + $xyz1[2] + 0;
                    $id = $xyz1[3] + 0;
                    $date = $xyz1[4] + 0;
                    if($id == 58)
                    {
                        $xS = $x1;
                        $yS = $y1;
                        $zS = $z1;
                    }
                    $this->setTile($mc,$x1,$y1,$z1,$id,$date);
                    if(!in_array($x1 . ':' . $z1,$island_XZ))
                    {
                        $island_XZ[] = $x1 . ':' . $z1;
                    }
                }
                $x1 = ($binX +  ($this->Set['岛屿之间的距离'] * 2) + $xyz1[0]) - 10;
                $z1 = ($binZ + $xyz1[2]) - 10;
                $x2 = ($binX +  ($this->Set['岛屿之间的距离'] * 2) + $xyz2[0]) + 10;
                $z2 = ($binZ + $xyz2[2]) + 10;
                for($a = 0;$x1 + $a < $x2;$a ++)
                {
                    for($b = 0;$z1 + $b < $z2;$b ++)
                    {
                        $this->setTile($mc,$x1 + $a,4,$z1 + $b,0,0);
                    }
                }
            }
        }
    }

    public function bin_load()
    {
        if(!(is_dir($this->getDataFolder() . 'set/')))
        {
            @mkdir($this->getDataFolder());
            @mkdir($this->getDataFolder() . 'set/');
            $this->getLogger()->warning('§a正在创建 §5== >§e[/plugins/set/]§b主要目录 ...');
        }
        if(!(is_dir($this->getDataFolder() . 'set/scq/')))
        {
            @mkdir($this->getDataFolder() . 'set/scq/');
            $this->getLogger()->warning('§a正在创建 §5== >§e [set/scq/]§b拓展生成器目录 ...');
        }
        $this->b = new Config($this->getDataFolder() . 'set/Config.yml' , Config::YAML , array());
        $set1 = array(
            '配置版本' => '010',
            ' ========== 基础设置 ==========' => '.',
            '可购买岛屿' => 3,
            '单个领地价格' => 10,
            '生成方案' => '正常',
            '岛屿自带物品给予方式' => '背包',
            '岛屿之间的距离' => 50,
            '岛最多可拓展多少领地' => 100,
            '拓展领地显示方式' => 'tip',
            '领地显示方式' => 'tip',
            '领地显示内容' => '
                                                                                                                                                §e§1■■§2■■§3■■§5■■§6■■§8■■§a■■§b■■§c■■§e■■§4■■
                                                                                                                                                §a编号[§e{编号}§a] §b岛主[§e{岛主}§b] 
                                                                                                                                                §c共享者[§e{共享列表}§c] 
                                                                                                                                                §3领地面积[§e{领地面积}§3] §5生态[§6{生态环境}§5]
                                                                                                                                                §e§1■■§2■■§3■■§5■■§6■■§8■■§a■■§b■■§c■■§e■■§4■■





',
            '购买岛屿后工作台上的掉落物' => Array('347:0:1','325:10:1','360:0:1','81:0:1','79:0:2','39:0:1','40:0:1','361:0:1','338:0:1','323:0:1','6:0:1'),
            ' ========== 功能开关 ==========' => '.',
            '背包' => '开',
            '禁止流动' => '开',
            '领地显示' => '开',
            '购买岛屿显示内容' => '关',
            '禁止命令' => array('tpa' , 'home' , 'warp'),
            '管理员' => array()
        );
        if(!$this->b->exists('设置'))
        {
            $this->b->set('设置',$set1);
            $this->b->save();
        }
        else
        {
            $set = $this->b->get('设置');
            if($set['配置版本'] != $set1['配置版本'])
            {
                $this->getLogger()->info('§4发现配置文件为其他版本,正在智能覆盖此版本[' . $set1['配置版本'] . ']!这会尽量保留原设置,从而加入新的设置!');
                unset($set['IsLandWorld-BanCommand']);
                $as = array_merge($set1,$set);
                $as['配置版本'] = $set1['配置版本'];
                $this->b->set('设置',$as);
                $this->b->save();
            }
        }
        $this->Set = $this->b->get('设置');
    }

    public function getBeiBao(Player $player)
    {
        $pk = new UpdateBlockPacket();
        $pk->x = $player->x;
        $pk->y = $player->y;
        $pk->z = $player->z;
        $pk->blockId = Item::CHEST;
        $pk->blockData = 0;
        $pk->CustomName = 'sasdd';
        $pk->flags = UpdateBlockPacket::FLAG_NONE;
        $player->dataPacket($pk);

        $tile = new Chest($player->level,$this->getChestNBT($player));
        $inventory = $tile->getInventory();
        $this->AddItem($inventory,$this->Inventory->getHome());
        $player->addWindow($inventory);
        $this->Windows[$player->getName()] = $inventory;
        return true;
    }

    public function island_load()
    {
        $nbfb = Array();
        if(is_dir($this->getDataFolder()))
        {
            $Now_Dir = scandir($this->getDataFolder(),2);
            foreach ($Now_Dir as $Dir)
            {
                $Dirs = explode('.',$Dir);
                if(count($Dirs) > 0)
                {
                    $number = count($Dirs) - 1;
                    if($Dirs[$number] == 'island')
                    {
                        $this->expand = new Config($this->getDataFolder() . $Dir , Config::YAML,array());
                        if($this->expand->exists('DATA'))
                        {
                            $txt = '';
                            for($a = 0; $a < $number; $a ++)
                            {
                                $txt .= $Dirs[$a];
                            }
                            if($txt != 'Resources')
                            {
                                $sky = $this->expand->get('DATA');
                                $world = $this->expand->get('允许生成的世界');
                                $yz = $this->expand->get('允许生成高度');
                                $money = $this->expand->get('价格');
                                $worldOK = file_exists($this->getServer()->getDataPath() . "worlds/$world") ? '§a[§d世界存在§a]' : '§a[§d世界不存在§a]';
                                $money = $money > 0 ? '§6付费§a[§d' . $money . '金币§a]' : '§6免费§a[§d' . $money . '金币§a]';
                                $blockOK = $this->expand->exists($sky) ? '§a[§d内部正常§a]' : '§a[§d内部损坏§a]';
                                $this->getLogger()->info('§5 =======§a ' . $txt . ' §5======= ');
                                $this->getLogger()->info('§e # §6允许生成的世界 §b==> §6' . $world .'§9|' . $worldOK);
                                $this->getLogger()->info('§e # §6岛屿方块数量 §b==> §6' . $sky .'§9|' . $blockOK);
                                $this->getLogger()->info('§e # §6允许生成高度 §b==> §6' . $yz);
                                $this->getLogger()->info('§e # §6岛屿价格 §b==> §6' . $money);
                                if(!in_array($world,$this->Type_World_List))
                                {
                                    $this->Type_World_List[] = $world;
                                }
                                $this->Type_List[] = $txt;
                            }
                        }
                        else
                        {
                            $nbfb[] = $Dir;
                        }
                    }
                }
            }
        }
        if(count($this->Type_List) === 0)
        {
            $this->getLogger()->info('§4[警告]§c本次一共加载了§e[ 0 ]§c个拓展文件!,请下载拓展文件或自制拓展文件!§a请输入/岛屿 创建');
        }
        else
        {
            $this->getLogger()->info("§6一共加载了§4[§5 ". count($this->Type_List) ." §4]§6个拓展文件!");
        }
        if(count($nbfb) != 0)
        {
            $ace = "\n";
            for($a = 0;$a < count($nbfb);$a ++)
            {
                $ace .= "§e- 文件: §b" . $nbfb[$a] . "\n";
            }
            $this->getLogger()->info("\n§c共发现" . count($nbfb) . "个文件内部损坏:" . $ace);
        }
    }

    public function Repair_World_Name()
    {
        $level = $this->getServer()->getDefaultLevel();
        $path = $level->getFolderName();
        $p1 = dirname($path);
        $p2 = $p1."/worlds/";
        $dirnowfile = scandir($p2, 1);
        foreach ($dirnowfile as $dirfile){
            if($dirfile != '.' && $dirfile != '..' && $dirfile != $path && is_dir($p2.$dirfile))
            {
                if ($this->getServer()->isLevelLoaded($dirfile))
                {
                    $level = $this->getServer()->getLevelbyName($dirfile);
                    if($level->getName() != $dirfile)
                    {
                        $provider = $level->getProvider();
                        $provider->getLevelData()->LevelName = new StringTag("LevelName", $level->getFolderName());
                        $provider->saveLevelData();
                        $this->getLogger()->info("§a[岛屿-修复] §e您加载的世界文件夹名§4 $dirfile §e的文件夹名与地图名不符,已修复!");
                    }
                }
            }
        }
    }

    public function in_array_key($value,$array)
    {
        $list = array_keys($array);
        for($a = 0; $a < count($array); $a ++)
        {
            if(@in_array($value,$array[$list[$a]]))
            {
                return $list[$a];
            }
        }
        return false;
    }

    public function in_arrays($value,$array)
    {
        if(count($array) == 0)
        {
            return false;
        }
        foreach($array as $item)
        {
            if(is_array($item))
            {
                if(in_array($value,$item))
                {
                    return true;
                }
            }
        }
        return false;
    }

    public function setTile($level,$x,$y,$z,$id,$data)
    {
        if(!$level instanceof Level) return var_dump('出现错误: '.$level);
        $level->setBlockIdAt($x,$y,$z,$id);
        $level->setBlockDataAt($x,$y,$z,$data);
    }

    public function getBiomeName($id)
    {
        switch($id)
        {
            case '0':
                return '海洋';
                break;
            case '1':
                return '平原';
                break;
            case '2':
                return '沙漠';
                break;
            case '3':
                return '山脉';
                break;
            case '4':
                return '森林';
                break;
            case '5':
                return '针叶林';
                break;
            case '6':
                return '沼泽';
                break;
            case '7':
                return '河';
                break;
            case '8':
                return '地狱';
                break;
            case '12':
                return '雪地';
                break;
            case '20':
                return '小山';
                break;
            case '27':
                return '桦树林';
                break;
            case '256':
                return '马克斯小黑森林';
                break;
        }
        return false;
    }

    public function onBlockUpdate(BlockUpdateEvent $event)
    {
        if($this->Set['禁止流动'] == "开")
        {
            $Block = $event->getBlock();
            if(($Block instanceof Water) OR ($Block instanceof Lava))
            {
                $event->setCancelled(true);
            }
        }
    }

    public function ZXDA_load()
    {
        ZXDA::tokenCheck('MTIzMTY3MzExNjU0Nzg0NTQxNjA4NjU3MzgyNjMyNTMyMDc5NDI5NTk0ODYyNTAyNDU2NjIzNzI3NDM2ODk0NDE2NjQzOTkyMjQ5NzMwMTk3MDg3MzU0OTU2MTczMTQ2NjY4NjcyNTY5MDUwODQzMzcwNDM2NTE5NjM3NTg0MTExNDEyMDYyMjcyNzA4MjM0NTI5MDYzMjk2MTI0MTU3MzcxOTMxNzUzNzAzNzg2MDQ3NjQ5NDM0MjA4MzE5NjkyMTExNjA5MTA2MTA2ODYzMDg2MTg1NjM1OTc1MTc3MjU2MDM2Mzg0NTcyOTkzOTQxMjQ2NjU2MDg0MzE4NDA5MTI0NjUyNTgyMTM3NzM1OTI2NjQ4ODg4MDI4MzQ5MzQ4MTQwODE5MjU5Mjg3MTg2MDAxMTY5');
        $data=ZXDA::getInfo();
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

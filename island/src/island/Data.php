<?php
/**
 * User: slm
 * Date: 2017/9/9
 * Time: 21:21
 * Version: 2.3.0
 */
namespace island;

use island\Main;
use pocketmine\utils\Config;
use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\StringTag;
use pocketmine\level\Position;
class Data
{
    //Main主类
    private $Main = Null;
    //生成器变量
    public $Make = [
        'pos' => 0,
        /* 基点 */
        'pos1' => 0,
        /* 坐标1 */
        'pos2' => 0,
        /* 坐标2 */
        'Level' => 0,
    ];
    //岛屿模型 存储器
    public $IsLand_Disk = [];
    public function __construct(Main $Main)
    {
        $this->Main = $Main;
        $this->getDataFolder = $this->Main->getDataFolder();
    }
    /**
     * 处理模式
     */
    public function Dispose_Mod($File_Name, $player)
    {
        $File = $this->IsLand_Disk[$File_Name];
        $player->sendMessage('§c正在启动 §e[  处理  ] §c模式系统');
        $player->sendMessage('§c[警告] §e接下来本插件将直接性的操控你,进行模拟生成!切勿退出游戏.');
        $player->sendMessage('§c[警告] §e退出游戏将自动停止生成,生成信息会在聊天内提示!此操作依生成间隔定义,循环生成两次,至少需要5分钟!');
        $player->sendMessage("§c[提示] §6生成间隔[" . $File['岛屿间距'] . "] 对角点: Min[-1000] Max[10000]");
        $number = 0;
        while ($File['Z'] <= 10000) {
            if ($File['X'] >= 10000) {
                $File['X'] = -1000;
                $File['Z'] += $File['岛屿间距'];
            } else {
                $File['X'] += $File['岛屿间距'];
            }
            $number += 1;
        }
        $this->Main->Create_Mod = ['Name' => $File_Name, 'Player' => $player, 'nu' => $number];
        $player->sendMessage("§c[提示] §6预计生成 岛屿数量[" . $number . "] 约[" . $this->untime($number, true) . "]");
    }
    /**
     * 购买岛屿 生成模式
     */
    public function Buy_IsLand($File_Name,$p)
    {
        if(!isset($this->API->Box['拓展文件'][$File_Name])) return $this->send($p,"未发现{$File_Name}拓展模型文件!");
        $Config = $this->API->Box['拓展文件'][$File_Name];
        $name = $p->getName();
        if(!$this->API->FunMoney($name,$Config->get('岛屿价格'))) return $this->send($p,"身上金币不足以购买一个岛屿!");
        $this->API->delMoney($name,$Config->get('岛屿价格'));
        $this->Create_IsLand($File_Name,$p);
        if($Config->get('购买岛屿后赠送物品'))
        {
            $Item_Box = [];
            foreach ($Config->get('附赠物品') as $item)
            {
                $Item_Box[] = explode(':',$item);
            }
            $this->send($p,"在十秒钟后您将收到一个购买岛屿所附赠的物品!");
            $this->Main->Tasks[] = [
                'Time' => Time() + 1,
                'Type' => 'AddInventory',
                'Player' => $p,
                'Inventory_Box' => $Item_Box
            ];
        }
    }
    /**
     * 创建岛屿函数
     */
    public function Create_IsLand($File_Name, $player, $Xmax = 0,$Land = True)
    {
        $File = $this->IsLand_Disk[$File_Name];
        if ($File['X'] >= $Xmax) {
            $this->IsLand_Disk[$File_Name]['X'] = -1000;
            $this->IsLand_Disk[$File_Name]['Z'] += $File['岛屿间距'];
        } else {
            $this->IsLand_Disk[$File_Name]['X'] += $File['岛屿间距'];
        }
        if ($File['Level'] instanceof Level) {
            $Land_XZ = [];
            foreach ($File['Block'] as $Block) {
                $x = $File['X'] + $Block[0];
                $z = $File['Z'] + $Block[2];
                $File['Level']->setBlockIdAt($x, 80 + $Block[1], $z, $Block[3]);
                $Land_XZ[] = $x . ',' . $z;
            }
            if($Land)
            {
                $Numberin = $this->API->Land_yml->get('当前编号');
                $this->API->Land_yml->set('当前编号',$Numberin + 1);
                $this->API->Land_yml->save();
                $this->API->Land->AddLand($Numberin+1,$Land_XZ);
            }
            $player->teleport(new Position($File['X'], 82, $File['Z'], $File['Level']));
        } else {
            $this->error('Level Not Class![Create]');
            var_dump($File['Level']);
            return False;
        }
    }
    /**
     * 删除岛屿函数
     */
    public function Delete_IsLand($Numberin, $player)
    {
        if(!isset($this->API->Land_List[$Numberin])) return $this->send($player,"未发现编号为{$Numberin}的岛屿!");
        $this->info("§c[警告] ".$player->getName()."执行了 删除{$Numberin}号岛屿的操作!");
        $Level = $this->Main->getServer()->getLevelByName('island');
        if ($Level instanceof Level)
        {
            foreach($this->API->Land_List[$Numberin] as $Land)
            {
                $XZ = explode(',',$Land);
                for($y = 1;$y < 128; $y ++)
                {
                    $Level->setBlockIdAt($XZ[0], $y, $XZ[1], 0);
                }
            }
        }
        $this->send($player,"§c[警告] 已将{$Numberin}号岛屿删除!");
    }
    /**
     * 创建地图函数
     */
    public function Create_Map($Name)
    {
        $this->Log('§b > "' . $Name . '"地图已创建完成!正在根据拓展初始"' . $Name . '"地图!');
        $path = $this->Main->getServer()->getDefaultLevel()->getFolderName();
        $p1 = dirname($path);
        $p2 = $p1 . "/worlds/" . $Name . "/";
        @mkdir($p2);
        @mkdir($p2 . 'region/');
        file_put_contents($p2 . 'level.dat', stream_get_contents($this->Main->getResource("level.dat")));
        $this->Main->getServer()->generateLevel($Name);
        $this->Main->getServer()->loadLevel($Name);
        $level = $this->Main->getServer()->getLevelbyName($Name);
        if ($this->IsLand_Disk[$this->IsLand_Disk['ISLAND_LEVEL'][$Name]]['生成世界时预铺方块']) {
            $this->Log('生成世界时预铺方块 > 开始放置方块...');
            for ($a = -1000; $a < 0; $a++) {
                for ($b = -1000; $b < 0; $b++) {
                    $level->setBlockIdAt($a, 2, $b, 0);
                    $level->setBlockDataAt($a, 2, $b, 0);
                }
            }
            $level->save();
        }
        if ($level->getName() != $Name) {
            $provider = $level->getProvider();
            $provider->getLevelData()->LevelName = new StringTag("LevelName", $level->getFolderName());
            $provider->saveLevelData();
            $this->Log('§b > 重命名 "' . $Name . ' 地图,并重新加载!');
            $this->Main->getServer()->unloadLevel($level);
            $this->Main->getServer()->generateLevel($Name);
            $this->Main->getServer()->loadLevel($Name);
        }
    }
    /**
     * 创建拓展文件
     */
    public function Write_File()
    {
        if ($this->Make['pos'] === 0 or $this->Make['pos1'] === 0 or $this->Make['pos2'] === 0) {
            $this->error('Write private Make Error!');
            var_dump($this->Make);
            return False;
        }
        $Config = $this->Make;
        $x1 = min($Config['pos1'][0], $Config['pos2'][0]);
        $x2 = max($Config['pos1'][0], $Config['pos2'][0]);
        $y1 = min($Config['pos1'][1], $Config['pos2'][1]);
        $y2 = max($Config['pos1'][1], $Config['pos2'][1]);
        $z1 = min($Config['pos1'][2], $Config['pos2'][2]);
        $z2 = max($Config['pos1'][2], $Config['pos2'][2]);
        if ($Config['Level'] instanceof Level) {
            $File = "";
            /*方块读取*/
            for ($x = $x1; $x <= $x2; $x++) {
                for ($y = $y1; $y <= $y2; $y++) {
                    for ($z = $z1; $z <= $z2; $z++) {
                        $ID = $Config['Level']->getBlockIdAt($x, $y, $z);
                        if ($ID !== 0) {
                            $DATA = $Config['Level']->getBlockDataAt($x, $y, $z);
                            $xs = $x - $Config['pos'][0];
                            $ys = $y - $Config['pos'][1];
                            $zs = $z - $Config['pos'][2];
                            $File .= $xs . ',' . $ys . ',' . $zs . ',' . $ID . ',' . $DATA . '.';
                        }
                    }
                }
            }
            $file = new Config($this->Main->getDataFolder() . time() . '.island', Config::YAML, [
                '指定世界' => 'island',
                '# 生成模式可选为 [生成/地图/处理] 生成:购买时生成岛屿 | 地图:已用岛屿地图,购买时不生成岛屿 | 处理:服主进服手动生成岛屿,但不包括领地类',
                '生成模式' => '生成',
                '岛屿价格' => 3000,
                '领地价格' => 30,
                '领地上限' => 100,
                '拥有上限' => 3,
                '默认生态' => 12,
                '岛屿间距' => 50,
                '处理模式循环次数' => 2,
                '异区块大小' => 16,
                '同时扫描异区块数量' => 8,
                '关服自动检测并删除长期未操作岛屿' => 90,
                '世界不存在时自动生成世界' => True,
                '异区块对齐加载' => False,
                '岛屿领地分割线' => True,
                '岛屿带领地' => True,
                '生成世界时预铺方块' => True,
                '世界禁止流动体' => True,
                '世界禁止PVP' => False,
                '掉入虚空回主岛' => True,
                '生成检测附近方块' => True,
                '购买岛屿后赠送物品' => True,
                'X' => -1000,
                'Z' => -1000,
                '处理模式 XZ轴上限' => -10000,
                '处理模式 生成频率' => 5,
                '附赠物品' => $this->Main->Config->get('Inventory_Item'),
                'Block' => $File]);
        } else {
            $this->error('Level Not Class![Write]');
            var_dump($Config['Level']);
            return False;
        }
    }
    /**
     * 拓展文件加载
     */
    public function Load_File()
    {
        $Data = $this->Main->getDataFolder();
        if (is_dir($Data)) {
            $Now_Dir = scandir($Data, 2);
            foreach ($Now_Dir as $Dir) {
                $Dirs = explode('.', $Dir);
                if (count($Dirs) > 0) {
                    $number = count($Dirs) - 1;
                    if ($Dirs[$number] == 'island') {
                        $Config = new Config($Data . $Dir, Config::YAML, []);
                        $txt = '';
                        for ($a = 0; $a < $number; $a++) {
                            $txt .= $Dirs[$a];
                        }
                        $this->API->Box['拓展文件'][$txt] = $Config;
                        $times = explode(' ', microtime());
                        /*方块加载*/
                        $Block = $Config->get('Block');
                        $Blocks = explode('.', $Block);
                        $Block_Array = $Config->getAll();
                        $Block_ALL = [];
                        foreach ($Blocks as $INFO) {
                            $Block_Info = explode(',', $INFO);
                            $Block_ALL[] = $Block_Info;
                        }
                        unset($Block_ALL[count($Block_ALL) - 1]);
                        $Block_Array['Block'] = $Block_ALL;
                        $Block_Array['Config'] = $Config;
                        /*信息加载*/
                        $Map = $Config->get('指定世界');
                        $Block_Array['Level'] = $this->Main->getServer()->getLevelByName($Map);
                        $this->IsLand_Disk[$txt] = $Block_Array;
                        $this->IsLand_Disk['ISLAND_LEVEL'][$Map] = $txt;
                        if (!$Block_Array['Level'] instanceof Level) {
                            $this->error('未发现拓展内指定的世界...[' . $Map . ']正在创建...');
                            $this->Create_Map($Map);
                            $Block_Array['Level'] = $this->Main->getServer()->getLevelByName($Map);
                        }
                        $this->IsLand_Disk[$txt] = $Block_Array;
                        $timess = explode(' ', microtime());
                        $timetext = $timess[0] + $timess[1] - ($times[0] + $times[1]);
                        $this->info("§c > [{$txt}]加载完成...[§6" . round($timetext, 6) . "§aS§c]");
                    }
                }
            }
        }
    }
    /**
     * 关服保存数据
     */
    public function Save_all()
    {
        $this->Log('正在保存关键数据...');
        $this->API->Land->Save_Land();
        foreach ($this->IsLand_Disk as $File) {
            if (isset($File['Config'])) {
                /*
                 *岛屿坐标保存
                 */
                $File['Config']->set('Z', $File['Z']);
                $File['Config']->set('X', $File['X']);
                $File['Config']->save();
            }
        }
    }
    /**
     * 加载配置文件
     */
    public function Load_Config()
    {
        if(!file_exists($this->getDataFolder . 'Config/')) mkdir($this->getDataFolder . 'Config/');
        /* 配置文件 */
        $this->API->Config_yml = new Config($this->getDataFolder . 'Config/Config.yml', Config::YAML,$this->Config_yml());
        /* 岛屿信息 */
        $this->API->Land_yml = new Config($this->getDataFolder . 'Config/Land.yml', Config::YAML,['当前编号' => 1]);
    }
    /**
     * Config.yml 文件内容
     */
    public function Config_yml() : Array
    {
        return [
            'Version' => '2.3.0_Beta',
            '#开发者模式,开启后后台显示数据 [True/False]',
            'Exploitation_Mode' => True,
            '#底部显示方式 [popup/tip]',
            'Bottom_Type' => 'popup',
            '#底部自定义内容 [Content]',
            'Bottom_Content' => "                                                                     §d{名字}信息:
                                                                     §e§1■■ §2■■ §3■■ §5■■ §6■■ §8■■ §a■■ §b■■ §c■■ §e■■ §4■■
                                                                     §a编号[§e{编号}§a] §b岛主[§e{岛主}§b] 
                                                                     §c共享者[§e{列表}§c] 
                                                                     §3领地面积[§e{面积}§3] §5生态[§6{环境}§5]
                                                                     §e§1■■ §2■■ §3■■ §5■■ §6■■ §8■■ §a■■ §b■■ §c■■ §e■■ §4■■{换行}{换行}{换行}{换行}{换行}{换行}{换行}",

        ];
    }
    /**
     * 秒数转换
     */
    public function untime($n, $s = False)
    {
        $h = time();
        $s ? $h = $n : ($h = $n - $h);
        $r = "";
        if ($h < 60) {
            $r = $h . '秒';
        } else {
            if ($h >= 60 && $h < 3600) {
                $r = floor($h / 60) . '分' . floor($h % 60) . '秒';
            } else {
                if ($h >= 3600 && $h < 86400) {
                    $r = floor($h / 3600) . '小时' . floor($h % 3600 / 60) . '分';
                } else {
                    if ($h >= 86400 && $h < 2592000) {
                        $r = floor($h / 86400) . '天' . floor($h % 86400 / 3600) . '小时' . floor($h % 86400 % 3600 / 60) . '分' . floor($h % 86400 % 3600 / 60 / 60) . '秒';
                    } else {
                        if ($h >= 2592000 && $h < 31104000) {
                            $r = floor($h / 2592000) . '个月' . floor($h % 2592000 / 86400) . '天' . floor($h % 2592000 % 86400 / 3600) . '小时' . floor($h % 2592000 % 86400 % 3600 / 60) . '分' . floor($h % 86400 % 3600 / 60 / 60) . '秒';
                        } else {
                            if ($h >= 31104000) {
                                $r = floor($h / 31104000) . '年' . floor($h % 31104000 / 2592000) . '个月' . floor($h % 31104000 % 2592000 / 86400) . '天' . floor($h % 31104000 % 2592000 % 86400 / 3600) . '小时' . floor($h % 31104000 % 2592000 % 86400 % 3600 / 60) . '分' . floor($h % 31104000 % 2592000 % 86400 % 3600 / 60 / 60) . '秒';
                            }
                        }
                    }
                }
            }
        }
        return $r;
    }
    /**
     * 提示
     */
    function send($player,string $text)
    {
        $player->sendMessage("§c[§eIsLand-Data§c]§6 $text");
    }
    /**
     * 输出信息
     */
    function info(string $text)
    {
        $this->Main->getLogger()->info("$text");
    }
    /**
     * 输出类信息
     */
    function Log(string $text)
    {
        $this->Main->getLogger()->warning("Data.php: $text");
    }
    /**
     * 输出警告信息
     */
    function error(string $text)
    {
        $this->Main->getLogger()->error("Data.php: $text");
    }
}
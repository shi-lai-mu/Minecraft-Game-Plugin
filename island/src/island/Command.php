<?php
/**
 * User: slm
 * Date: 2017/9/9
 * Time: 21:21
 * Version: 2.3.0
 */
namespace island;

use island\API;
use island\Main;
use pocketmine\scheduler\CallbackTask;
class Command
{
    //Main主类
    private $Main = Null;

    public function __construct(Main $Main)
    {
        $this->Main = $Main;
    }
    public function Code($Type, $Player, $args)
    {
        $x = round($Player->x);
        $y = round($Player->y);
        $z = round($Player->z);
        if ($Type == '岛屿' or $Type == 'is')
        {
            /**
             * 岛屿 处理
             */
            if ($args[0] == '删除') {
                $this->API->Data->Delete_IsLand($args[1], $Player);
                return true;
            }
            /**
             * 岛屿 处理
             */
            if ($args[0] == '处理') {
                if (isset($args[1]) and $args[1] == 'stop') {
                    $this->Main->Create_Mod = False;
                } else {
                    $this->API->Data->Dispose_Mod('1505749922', $Player);
                    $this->Main->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this->Main, "ti"]), 2);
                }
                return true;
            }
            /**
             * 岛屿 购买(buy) 名字[name] 模型[island]
             */
            if ($args[0] == '购买' or $args[0] == 'buy') {
                if (!isset($args[1])) {
                }
                $this->API->Data->Buy_IsLand('1505749922', $Player);
                return true;
            }
            /**
             * 岛屿 生成器(builder) [0/1/2/输出]
             */
            if ($args[0] == '生成器' or $args[0] == 'builder') {
                if (!isset($args[1]) or $args[1] == '帮助' or $args[1] == 'help') {
                    $Player->sendMessage('§5>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<');
                    $Player->sendMessage('§5> §a/岛屿 生成器 0    §6"设置基点坐标/玩家出生点"');
                    $Player->sendMessage('§5> §a/岛屿 生成器 1    §6"设置对角 第一点"');
                    $Player->sendMessage('§5> §a/岛屿 生成器 2    §6"设置对角 第二点"');
                    $Player->sendMessage('§5> §a/岛屿 生成器 输出  §6"输出对角范围内方块至文件"');
                    $Player->sendMessage('§5>━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━<');
                }
                if ($args[1] == '0') {
                    $this->API->Data->Make['pos'] = [$x, $y, $z];
                    $this->Message('已设置生成器 基点', $Player);
                    return True;
                }
                if ($args[1] == '1') {
                    $this->API->Data->Make['pos1'] = [$x, $y, $z];
                    $this->Message('已设置生成器 第一点', $Player);
                    return True;
                }
                if ($args[1] == '2') {
                    $this->API->Data->Make['pos2'] = [$x, $y, $z];
                    $this->Message('已设置生成器 第二点', $Player);
                    return True;
                }
                if ($args[1] == '输出') {
                    $this->API->Data->Make['Level'] = $Player->level;
                    return $this->API->Data->Write_File();
                }
            }
        }
    }
    /**
     * 玩家提示
     */
    function Message(string $text, $Player)
    {
        $Player->sendMessage("§a[§6IsLand§a] >§c {$text}");
    }
    /**
     * 输出信息
     */
    function info(string $text)
    {
        $this->Main->getLogger()->info("{$text}");
    }
    /**
     * 输出类信息
     */
    function Log(string $text)
    {
        $this->Main->getLogger()->warning("Command.php: {$text}");
    }
    /**
     * 输出警告信息
     */
    function error(string $text)
    {
        $this->Main->getLogger()->error("Command.php: {$text}");
    }
}
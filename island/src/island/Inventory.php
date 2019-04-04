<?php
/**
 * User: slm
 * Date: 2017/9/9
 * Time: 21:21
 * Version: 2.3.0
 */
namespace island;
use island\Main;
use pocketmine\network\protocol\UpdateBlockPacket;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\inventory\Inventory as Inventorys;
class Inventory
{

    //Main主类
    private $Main = Null;

    public $Windows = [];

    public function __construct(Main $Main)
    {
        $this->Main = $Main;
    }
    /**
     * 发送箱子数据包
     */
    public function AddPock($player,$IsLand = False)
    {
        $pk = new UpdateBlockPacket();
        $pk->x = $player->x;
        $pk->y = $player->y;
        $pk->z = $player->z;
        $pk->blockId = Item::CHEST;
        $pk->blockData = 0;
        $pk->flags = UpdateBlockPacket::FLAG_NONE;
        $player->dataPacket($pk);

        $tile = new Chest($player->level,new CompoundTag("", [
            new StringTag("id", Tile::CHEST),
            new IntTag("x",$player->x),
            new IntTag("y",$player->y),
            new IntTag("z",$player->z)
        ]));
        $inventory = $tile->getInventory();
        //如果为岛屿模式 则发送普通物品给玩家 反之 发送选项物品
        !$IsLand ?  $this->AddItem($inventory,$IsLand) : $this->AddItem($inventory,$this->getHome());
        $player->addWindow($inventory);
        $this->Main->Windows[$player->getName()] = $inventory;
    }
    /**
     * 往背包内发送物品
     */
    public function AddItem(Inventorys $inventory,Array $Item)
    {
        if($inventory instanceof Inventorys and is_array($Item))
        {
            $size = [];
            foreach($Item as $pos => $Info)
            {
                $new = Item::get($Info[0],$Info[1],$Info[2]);
                if(isset($Info[4]))
                {
                    $nbt = new CompoundTag("", [
                        "display" => new CompoundTag("display", [
                            "Name" => new StringTag("Name",$Info[4]),
                            "String" => new StringTag("String",$Info[5])
                        ])
                    ]);
                    $new->setNamedTag($nbt);
                }
                if(!isset($Info[3])) $Info[3] = $pos;
                $inventory->setItem($Info[3],$new);
                $size[] = $Info[3];
            }
            for($a = 0; $a < 26; $a ++)
            {
                if(!in_array($a,$size))
                {
                    $inventory->setItem($a,Item::get(0,0,1));
                }
            }
        }
    }
    /**
     *  return Chest Home
     *  type Array
     *  [ID,DATA,NUMBER,SIZE,STRING:namedtag]
     */
    public function getHome() : Array {
        return [
            [339,0,2,5,"§b帮助\n§a双击 物品执行内容\n单击 查看物品功能\n长按 执行拓展功能\nOP会有附魔级物品选项",'?'],
            [345,0,3,12,"§5双击 -> 回到主岛\n长按 -> 显示已有岛屿列表",'回岛/列表'],
            [322,0,3,13,"§5双击 -> 购买默认岛屿\n长按 -> 选择岛屿模型种类",'购买/选择'],
            [323,0,3,14,"§5双击 -> 显示目前所在岛屿信息\n长按 -> 将所在岛屿设为主岛",'信息/主岛'],
            [276,0,3,15,"§5双击 -> 驱逐此岛所有游客\n长按 -> 设置岛屿黑名单",'驱逐/黑名']
        ];
    }
    
     /**
     *  return Chest Home
     *  type Array
     *  [ID,DATA,NUMBER,SIZE,STRING:namedtag]
     */
    public function getIsLand_List() : Array {
        return [
            [339,0,1,8,"§b帮助\n§a双击 物品执行内容\n单击 查看物品功能\n长按 执行拓展功能\nOP会有附魔级物品选项",'?'],
            [345,0,3,1,"§5双击 -> 回到主岛\n长按 -> 显示已有岛屿列表",'回岛/列表'],
            [322,0,3,13,"§5双击 -> 购买默认岛屿\n长按 -> 选择岛屿模型种类",'购买/选择'],
            [323,0,3,4,"§5双击 -> 显示目前所在岛屿信息\n长按 -> 将所在岛屿设为主岛",'信息/主岛'],
            [276,0,3,5,"§5双击 -> 驱逐此岛所有游客\n长按 -> 设置岛屿黑名单",'驱逐/黑名']
        ];
    }
    
}

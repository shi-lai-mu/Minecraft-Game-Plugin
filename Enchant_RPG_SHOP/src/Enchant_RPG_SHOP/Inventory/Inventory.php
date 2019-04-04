<?php
/**
 * User: slm47888
 * Date: 2017/11/28
 * Time: 08:20
 * Version: 2.4.0
 */
namespace Enchant_RPG_SHOP\Inventory;
use Enchant_RPG_SHOP\Enchant_RPG_SHOP as Main;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\inventory\Inventory as Inventorys;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\math\Vector3;

class Inventory{
    /**
     * 加载项
     */
    public function __construct(Main $Main){
        $this->Main = $Main;
    }

    /**
     * 移除箱子数据包
     */
    public function DelPock($player,$Win){
        $this->Main->Windows[$player->getName()]->clearAll();
    	$pk = new UpdateBlockPacket();
        $pk->x = $Win->getHolder()->getX();
        $pk->y = $Win->getHolder()->getY();
        $pk->z = $Win->getHolder()->getZ();
        $pk->blockId = Item::AIR;
        $pk->blockData = 0;
        $pk->flags = UpdateBlockPacket::FLAG_NONE;
        $player->dataPacket($pk);
        unset($this->Main->Windows[$player->getName()]);
        unset($this->Main->Data[$player->getName()]);
						$this->Main->addLZ($player);
						$this->Main->addLZ($player);
    }
     
    /**
     * 发送箱子数据包
     */
    public function AddPock($player,$Conten,$Title,$Class){
        $pk = new UpdateBlockPacket();
        $pk->x = $player->x;
        $pk->y = $player->y;
        $pk->z = $player->z;
        $pk->blockId = Item::CHEST;
        $pk->blockData = 0;
        $pk->flags = UpdateBlockPacket::FLAG_NONE;
        $player->dataPacket($pk);

        $tile = new Chest($player->level,new CompoundTag('', [
            new StringTag('id', Tile::CHEST),
            new IntTag('x',$player->x),
            new IntTag('y',$player->y),
            new IntTag('z',$player->z),
            new StringTag('CustomName', $Title)
        ]));
        $inventory = $tile->getInventory();
        $this->AddItem($inventory,$Conten);
        $player->addWindow($inventory);
        $name = $player->getName();
        $this->Main->Class[$name] = $Class;
        $this->Main->Windows[$name] = $inventory;
        $this->Main->Data[$name] = [
            'Page' => 0,
            'Click' => 0,
            'Data' => 0,
            'Shop' => [
                'Type' => 0,
                'Enchant_ID' => 0,
                'Enchant_LV' => 1,
                'Money_Type' => 0,
                'Money_Numb' => 0,
                'Player' => Null,
                'Item' => Null
            ]
        ];
    }

    /**
     * 往背包内发送物品
     */
    public function AddItem(Inventorys $inventory,Array $Item,$Name = False,$Class = False){
        if($inventory instanceof Inventorys and is_array($Item)){
            if($Class) $this->Main->Class[$Name] = $Class;
            $size = [];
            foreach($Item as $pos => $Info){
                if(count($Info) > 6){
                    $new = Item::get($Info[0],$Info[1],$Info[2]);{
                        $nbt = new CompoundTag('', [
                            'display' => new CompoundTag('display', [
                                'Name' => new StringTag('Name',$Info[6]),
                            ])
                        ]);
                        $new->setNamedTag($nbt);
                        if($Info[4] !== -2){
                            $enchantment = Enchantment::getEnchantment($Info[4]);
                            $enchantment->setLevel($Info[5]);
                            $new->addEnchantment($enchantment);
                        }
                        $new->setNamedTag($nbt);
                    }
                    if(!isset($Info[3])) $Info[3] = $pos;
                    $inventory->setItem($Info[3],$new);
                    $size[] = $Info[3];
                } else {
                    $inventory->setItem($pos,$Info);
                    $size[] = $pos;
                }
            }
            for($a = 0; $a < 27; $a ++){
                if(!in_array($a,$size)) $inventory->setItem($a,Item::get(0,0,1));
            }
        }
    }

    /**  [ID,Data,Number,Size,Enchand_ID,Enchand_LV,Conten,Tag]

    /**
     *  return 主页 列表
     */
    public function getHome() : Array {
        return [
            [351,4,1,0,-1,0,'§l§e>>§b 附魔 §e<<'],
            [145,0,1,1,-1,0,'§l§e>>§6 升级 §e<<'],
            [368,0,1,2,-1,0,'§l§e>>§2 打孔 §e<<'],
            [377,0,1,3,-1,0,'§l§e>>§3 镶嵌 §e<<'],
            [69,0,1,4,-1,0, '§l§e>>§a 拆卸 §e<<'],
            [371,0,1,5,-1,0,'§l§e>>§c 贩卖 §e<<'],
            [51,0,1,6,-1,0, '§l§e>>§5 淬火 §e<<'],
            [54,0,1,6,-1,0, '§l§e>>§5 开箱 §e<<']
        ];
    }
    
     /**
     *  return 等级 选择界面
     */
    public function SelectLV($name) : Array {
        $Lv = $this->Main->Data[$name]['Shop']['Enchant_LV'];
        return [
            [340,0,1,0,-2,0,'§l§c<<<< §b-1 = '.$Lv],
            [262,0,1,1,-2,0,'§l§c<<<< §b-10 = '.$Lv],
            [322,0,1,2,-1,0, '§l§c需要 '.$Lv.' 级的附魔?'],
            [262,0,1,3,-2,0,'§l§c>>>> §b+10 = '.$Lv],
            [340,0,1,4,-2,0,'§l§c>>>> §b+1 = '.$Lv],
            [288,0,1,26,-2,0,'§l§e<< 返回']
        ];
    }
    
     /**
     *  return 货币 选择界面
     */
    public function SelectMoney() : Array {
        $Main = $this->Main->Config['支付自定义'];
        return [
            [398,0,1,0,-1,0,'§l§c选择支付 >> '.$Main['金币'].'§b'],
            [398,0,1,1,-2,0,'§l§c选择支付 >> 附魔券§b'],
            [398,0,1,2,-2,0,'§l§c选择支付 >> 等级§b'],
            [398,0,1,3,-2,0,'§l§c选择支付 >> 经验§b'],
            [398,0,1,4,-2,0,'§l§c选择支付 >> 物品§b']
        ];
    }
    
     /**
     *  return 附魔ID 选择界面
     */
    public function getEnchant_GUI($Page = 0) : Array {
        $ALL = [];
        $Page ? $start = 0 : $start = $Page * 24;
        for($i = $Page * 24; $i < ($Page + 1) * 24; $i ++){
            if(isset($this->Main->ID[$i])) $ALL[] = [351,4,1,count($ALL),-1,0,'§l§c选择附魔 >> §b'.$this->Main->ID[$i]->name];
        }
        $ALL[] = [288,0,1,25,-2,0,'§l§e<< 上一页'];
        $ALL[] = [288,0,1,26,-2,0,'§l§e下一页 >>'];
        return $ALL;
    }

     /**
     *  return 经过 附魔ID 筛选后的 界面
     */
    public function getEnchant_Inventory($Page = 0) : Array {
        $ALL = [];
        for($i = 0; $i < ($Page + 1) * 25; $i ++){
            $ALL[] = [351,4,1,count($ALL),-1,0,'§b'.$this->Main->ID[$i]->name];
        }
        return $ALL;
    }
    
}

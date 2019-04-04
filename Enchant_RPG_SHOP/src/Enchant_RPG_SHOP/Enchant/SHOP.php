<?php
namespace Enchant_RPG_SHOP\Enchant;

use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\level\Level;
use pocketmine\utils\Config;
use Enchant_RPG_SHOP\Enchant_RPG_SHOP as Main;

class SHOP
{
  private $Main;
  private $block;

  public function __construct(Main $Main)
  {
    $this->Main = $Main;
    $this->SHOP = new Config($this->Main->getDataFolder() . 'SHOP.yml',Config::YAML,[]);
  }

  public function create_shop($pos,$level,$name)
  {
    $this->setSHOP($level,$pos);
    $pos->y += 2.5;
    $pos->z += 0.5;
    $shop = new Text($pos,$this->home(),$level,$name);
    $shop->spawn();
    $this->Main->shop[$name] = 
    [
      'api' => $shop,
      'xyz' => $pos,
      'x' => 0,
      'y' => 0,
      'c' => 0,
      'm' => 0
    ];
  }

  public function add_shop($pos, $text = "",$level,$player = False)
  {
    if($text == '首页') $text = $this->home();
    $shop = new Text($pos, $text,$level,$player);
    $shop->spawn();
  }

  public function del_shop($pid)
  {
    $pid->respawn();
    unset($pid);
  }

  public function xia($level,$n)
  {
    $this->del_shop($this->Main->shop[$n]['api']);
    if($this->Main->shop[$n]['y'] > 11) $this->Main->shop[$n]['y'] = 0;
    $this->Main->shop[$n]['y'] += 1;
    $xy = $this->Main->shop[$n];
    $shop = new Text($xy['xyz'],$this->home($xy['x'],$xy['y']),$level,$n);
    $this->Main->shop[$n]['api'] = $shop;
    $shop->spawn();
  }

  public function home($x = 0,$y = 0,$m = 1)
  {
    $x_t;$y_t;$z_t;$txt;
    switch ($x)
    {
      case 0:
        $x_t = '§c附魔 §6| §e购买 §6| §e回收 §6| §e宝石';
        $txt = '附魔';
        break;
      case 1:
        $x_t = '§e附魔 §6| §c购买 §6| §e回收 §6| §e宝石';
        $txt = '购买';
        break;
      case 2:
        $x_t = '§e附魔 §6| §e购买 §6| §c回收 §6| §e宝石';
        $txt = '回收';
        break;
      case 3:
        $x_t = '§e附魔 §6| §e购买 §6| §e回收 §6| §c宝石';
        $txt = '宝石';
        break;
    }
    $list = $this->SHOP->get($txt);
    $all = count($list) / 10;
    count($list) % 10 > 0 ? $all = $all - ((count($list) % 10) / 10) + 1 : [];
    $y_t = "§6---------------------------------------";
    $note = "§e关闭 §6| §e管理";
    for($a = 0 * $m; $a < 10 * $m; $a ++)
    {
      $y_t .= "\n";
      if(count($list) == 0)
      {
        if(!$a) $y_t .= ' §c无任何商品!';
      }
      else
      {
        $lists = $list[$a];
        if($y == $a)
        {
          $y_t .= "§4> §c$lists[0] $lists[1]$lists[2] $lists[3] 级";
        }
        else
        {
          $y_t .= "  §e$lists[0] $lists[1]$lists[2] $lists[3] 级";
        }
        if($y == 10)
        {
          $note = "§c关闭 §6| §e管理";
        }
        if($y == 11)
        {
          $note = "§e关闭 §6| §c管理§e";
        }
      }
    }
    $text = "                §5附魔商店            \n§6---------------------------------------
$x_t
$y_t
§6---------------------------------------
$note                   ".$m."页/".$all."页
";
    return $text;
  }

  private function setSHOP($level,$pos)
  {
    $x = $pos->x;
    $y = $pos->y;
    $z = $pos->z;
    $this->setTile($level,$x,$y,$z+1,95,0);
    $this->setTile($level,$x,$y+1,$z+1,95,0);
    $this->setTile($level,$x,$y+2,$z+1,95,0);
    $this->setTile($level,$x+1,$y,$z+1,77,5);
    $this->setTile($level,$x+1,$y+1,$z+1,77,5);
    $this->setTile($level,$x+1,$y+2,$z+1,77,5);

    $this->setTile($level,$x,$y,$z-1,95,0);
    $this->setTile($level,$x,$y+1,$z-1,95,0);
    $this->setTile($level,$x,$y+2,$z-1,95,0);
    $this->setTile($level,$x+1,$y,$z-1,77,5);
    $this->setTile($level,$x+1,$y+1,$z-1,77,5);
    $this->setTile($level,$x+1,$y+2,$z-1,77,5);
  }

  private function setTile($level,$x,$y,$z,$id,$data)
  {
    $block = $this->getTile($level,$x,$y,$z);
    $this->block[$x.'-'.$y.'-'.$z] = [$block[0],$block[1],$level->getName()];
    $level->setBlockIdAt($x,$y,$z,$id);
    $level->setBlockDataAt($x,$y,$z,$data);
  }

  private function getTile($level,$x,$y,$z)
  {
    return [$level->getBlockIdAt($x,$y,$z),$level->getBlockDataAt($x,$y,$z)];
  }
}
class Text
{
 
 protected $pos;
 protected $text;
 protected $entityId;
 protected $invisible = true;
 protected $player;

 public function __construct($pos, $text = "",$level,$player = False)
 {
  $this->pos = $pos;
  $this->text = $text;
  $this->level = $level;
  $this->player = $player;
  $this->entityId = Entity::$entityCount ++;
 }
 
 public function isInvisible()
 {
  return $this->invisible;
 }
 
 public function spawn()
 {
 
  $pk = new \pocketmine\network\protocol\AddEntityPacket();
  $pk->eid = $this->entityId;
  $pk->type = 11;
  $pk->x = $this->pos->x;
  $pk->y = $this->pos->y;
  $pk->z = $this->pos->z;
  $pk->speedX = 0;
  $pk->speedY = 0;
  $pk->speedZ = 0;
  
  $flags = 0;
  $flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
  $flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
  $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
  
  $pk->metadata = [
   Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
   Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $this->text],
   38 => [7, -1],
   39 => [3, 0.001]
  ];
  
  if($this->player == False)
  {
    Server::getInstance()->broadcastPacket($this->level->getPlayers(), $pk);
  }
  else
  {
    foreach($this->level->getPlayers() as $player)
    {
      if($player->getName() == $this->player)
      {
        Server::getInstance()->broadcastPacket([$player], $pk);
      }
    }
  }
  $this->invisible = true;
 }
 
 public function setText(String $text)
 {
 
  $this->text = $text;
  $this->spawn();
  unset($text);
 }
 
 public function respawn(){
 
  $pk = new RemoveEntityPacket();
  $pk->eid = $this->entityId;
  if($this->player == False)
  {
    Server::getInstance()->broadcastPacket($this->level->getPlayers(), $pk);
  }
  else
  {
    foreach($this->level->getPlayers() as $player)
    {
      if($player->getName() == $this->player)
      {
        Server::getInstance()->broadcastPacket([$player], $pk);
      }
    }
  }
  $this->invisible = false;
 }
 
}
















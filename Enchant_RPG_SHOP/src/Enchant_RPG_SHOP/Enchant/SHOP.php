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
use pocketmine\scheduler\CallbackTask;

class SHOP
{
  private $Main;
  private $name;
  private $block;

  public function __construct(Main $Main)
  {
    $this->Main = $Main;
    $this->SHOP = new Config($this->Main->getDataFolder() . 'SHOP.yml',Config::YAML,[]);
  }

  public function del_shop($pid)
  {
    $pid->respawn();
    unset($pid);
  }

  public function get_money_name($m)
  {
    switch ($m)
    {
      case 'm':
        return '金币';
        break;
      case 'l':
        return '等级';
        break;
      case 'x':
        return '经验';
        break;
      case 'd':
        return '点券';
        break;
      case 'w':
        return '附魔券';
        break;
      default:
        return '未知';
        break;
    }
  }

  public function home($x = 0,$y = 0,$m = 1,$n = "all",$gui = 0)
  {
    if($gui == 1) return $this->admin_gui($x,$y,$m,$n);
    $x_t = '';$y_t;$z_t;$txt;
    $set = $this->SHOP->get('设置');
    for($a = 0; $a < count($set['标签']); $a ++)
    {
      if($x == $a)
      {
        $a == count($set['标签']) -1 ? $x_t .= '§c' . $set['标签'][$a] : $x_t .= '§c' . $set['标签'][$a] . ' §6|| ';
        $txt = $set['标签'][$a];
      }
      else
      {
        $a == count($set['标签']) - 1 ? $x_t .= '§e' . $set['标签'][$a] : $x_t .= '§e' . $set['标签'][$a] . '§6 || ';
      }
    }
    $list = $this->SHOP->get($txt);
    $all = count($list) / 10;
    count($list) % 10 > 0 ? $all = $all - ((count($list) % 10) / 10) + 1 : [];
    $all == 0 ? $all = 1 : [];
    $y_t = "§6---------------------------------------";
    $note = "§e关闭 §6| §e管理";
    $qs = 0;
    $m > 1 ? $qs = ($m - 1) * 10 : [];
    for($a = 0 + $qs; $a < 10 * $m; $a ++)
    {
      $y_t .= "\n";
        if(isset($list[$a]))
        {
          $lists = $list[$a];
          $lists[0] = $this->Main->get_file($lists[0])->name;
          $lists[3] .= $this->get_money_name($lists[2]);
          if(strlen($lists[3]) < 15)
          {
            for($c = strlen($lists[3]);$c < 15; $c ++)
            {
              $lists[3] .= ' ';
            }
          }
          $bq = "";
          switch ($txt)
          {
            case '附魔':
              $bq = "  §6花费 §c$lists[3] §4附魔§c  $lists[1]级$lists[0]";
              $bq2 = "§4> §b花费 $lists[3] 附魔  $lists[1]级$lists[0]";
              break;

            case '回收':
              $bq = "  获得 §c$lists[3] §4需要§c  $lists[1]级$lists[0] 物品";
              $bq2 = "§4> 获得 §c$lists[3] §4需要§c  $lists[1]级$lists[0] 物品";
              break;
            
            default:
              $bq = "  ERROR：§c$lists[3] $lists[1] $lists[0] to $txt";
              $bq2 = "§4> ERROR：§c$lists[3] $lists[1] $lists[0] to $txt";
              break;
          }
          if($y + $qs == $a)
          {
            $y_t .= $bq2;
          }
          else
          {
            $y_t .= $bq;
          }
        }
        else
        {
          if($y + $qs == $a)
          {
            $y_t .= '§4> §f['.$a.']号商品空缺...';
          }
          else
          {
            $y_t .= '  §8['.$a.']号商品空缺...';
          }
        }
        if($y + $qs == 10 + $qs)
        {
          $note = "§c关闭 §6| §e添加";
        }
        if($y + $qs == 11 + $qs)
        {
          $note = "§e关闭 §6| §c添加§e";
        }
    }
    $text = "              §5附魔商店              \n§6---------------------------------------
$x_t
$y_t
§6---------------------------------------
$note          ".$m."页/".$all."页 共计:". count($list) ."商品
";
    $this->Main->shop[$n]['a'] = $all;
    return $text;
  }

  public function OK($n)
  {
    $info = $this->Main->shop[$n];
    $txt = $this->home($info['x'],$info['y'],$info['m'],$n);
    $qs = 0;
    $player = $this->Main->getServer()->getPlayer($n);
    $info['m'] > 1 ? $qs = ($info['m'] - 1) * 10 : [];
    $y = $info['y'] + $qs;
    $kj = '§6---------------------------------------';
    if($y < 10 + $qs)
    {
      $txt = $this->Main->buy_enchant($info['x'],$info['y'],$info['m'],$player);
      return null;
    }
    if($y == 10 + $qs)
    {
      $txt = 'del';
      $this->removeSHOP($n);
    }
    if($y == 11 + $qs)
    {
      if($player->isOp())
      {
        $txt = $this->home($info['x'],$info['y'],$info['m'],$n,1);
      }
      else
      {
        $txt = "$kj\n   抱歉非管理员不能打开此界面!\n$kj";
      }
    }
    return $txt;
  }

  public function admin_gui($x = 0,$y = 0,$m = 1,$n = "all")
  {
    $set = $this->SHOP->get('设置');
    if(!isset($set['标签'][$x])) return null;
    $info = $this->Main->shop[$n];
    $pos = $info['xyz'];
    $level = $info['level'];
    $txt = '';
    $level->setBlockIdAt($pos->x,$pos->y-1.5,$pos->z-0.5,89);
    $this->Main->shop[$n]['block']['0-0-0'][] = [$pos->x,$pos->y-1.5,$pos->z-0.5,'添加'];
    switch($set['标签'][$x])
    {
      case '附魔':
        $txt = "§eEnchant_RPG_SHOP §c>>> §bAdmin
[无格式的检测及阻止,将直接记录,请按要求书写,以免报错]

§5添加附魔商店:§a
    请在此方块上贴木牌,写如下格式:
        >> 第一行 << 附魔ID
        >> 第二行 << 附魔等级
        >> 第三行 << 货币
        >> 第四行 << 价格
§6
#附魔ID:纯数字范围在0-60之间
#附魔等级:纯数字范围在1-附魔等级上限
#货币:[m,x,d,l,w]
#价格:纯数字价格范围-1以上";
        break;
      
      default:
        $txt = '§4在计算时发生错误§c[#991] §ein shop and admin_gui';
        break;
    }
    return $txt;
  }

  public function addSHOP_OK($name)
  {
    $info = $this->Main->shop[$name];
    $pos = $info['xyz'];
    $level = $info['level'];
    $level->setBlockIdAt($pos->x+1,$pos->y-1.5,$pos->z-0.5,0);
    unset($this->Main->shop[$name]['添加']);
  }
  
  public function button_click($pos,$n,$level)
  {
    if(isset($this->Main->shop[$n]))
    {
      foreach($this->Main->shop[$n]['block']['0-0-0'] as $v)
      {
        if($v[0] == $pos->x and $v[1] == $pos->y and $v[2] == $pos->z)
        {
          $set = $this->SHOP->get('设置');
          switch($v[3])
          {
            case '右':
              $this->Main->shop[$n]['x'] += 1;
              if($this->Main->shop[$n]['x'] >= count($set['标签'])) $this->Main->shop[$n]['x'] = 0;
              $this->Main->shop[$n]['y'] = 0;
            break;
            case '左':
              $this->Main->shop[$n]['x'] -= 1;
              if($this->Main->shop[$n]['x'] < 0) $this->Main->shop[$n]['x'] = count($set['标签']) - 1;
              $this->Main->shop[$n]['y'] = 0;
            break;
            case '下':
              $this->Main->shop[$n]['y'] += 1;
              if($this->Main->shop[$n]['y'] > 11) $this->Main->shop[$n]['y'] = 0;
            break;
            case '上':
              $this->Main->shop[$n]['y'] -= 1;
              if($this->Main->shop[$n]['y'] < 0) $this->Main->shop[$n]['y'] = 11;
            break;
            case '下一页':
              $this->Main->shop[$n]['m'] += 1;
              if($this->Main->shop[$n]['m'] > $this->Main->shop[$n]['a']) $this->Main->shop[$n]['m'] = 1;
              $this->Main->shop[$n]['y'] = 0;
            break;
            case '添加':
              $this->Main->shop[$n]['添加'] = True;
              return;
            break;
            case '上一页':
              $this->Main->shop[$n]['m'] -= 1;
              if($this->Main->shop[$n]['m'] < 1) $this->Main->shop[$n]['m'] = $this->Main->shop[$n]['a'];
              $this->Main->shop[$n]['y'] = 0;
            break;
            case '确定':
            $text = $this->OK($n);
            if($text === null)
            {
              return;
            }
            $this->del_shop($this->Main->shop[$n]['api']);
            if($text != 'del')
            {
              $xy = $this->Main->shop[$n];
              $shop = new Text($xy['xyz'],$text,$level,$n);
              $this->Main->shop[$n]['api'] = $shop;
              $shop->spawn();
            }
            else
            {
                unset($this->Main->shop[$n]);
            }
            return;
            break;
          }
          foreach($this->Main->shop[$n]['block']['0-0-0'] as $k => $v)
          {
            if(in_array('添加',$v))
            {
              $info = $this->Main->shop[$n];
              $pos = $info['xyz'];
              $level = $info['level'];
              $level->setBlockIdAt($pos->x,$pos->y-1.5,$pos->z-0.5,0);
              unset($this->Main->shop[$n]['block']['0-0-0'][$k]);
            }
          }
          $this->del_shop($this->Main->shop[$n]['api']);
          $xy = $this->Main->shop[$n];
          $shop = new Text($xy['xyz'],$this->home($xy['x'],$xy['y'],$xy['m'],$n),$level,$n);
          $this->Main->shop[$n]['api'] = $shop;
          $shop->spawn();
         }
      }
    }
  }

  public function create_shop($pos,$level,$name)
  {
   $this->name = $name;
    $xyz = $pos;
    $pos->y += 2.5;
    $pos->z += 0.5;
    $shop = new Text($pos,$this->home(0,0,1,$name),$level,$name);
    $shop->spawn();
    $this->Main->shop[$name] = 
    [
      'api' => $shop,
      'xyz' => $pos,
      'level' => $level,
      'x' => 0,
      'y' => 0,
      'c' => 0,
      'a' => 1,
      'm' => 1,
      'block' => []
    ];
    $this->setSHOP($level,$pos);
  }

  public function removeSHOP($n)
  {
    if(isset($this->Main->shop[$n]))
    {
      foreach($this->Main->shop[$n]['block']['0-0-0'] as $block)
      {
        if($block[3] != '确定') $this->Main->shop[$n]['level']->setBlockIdAt($block[0],$block[1],$block[2],0);
        unset($this->Main->shop[$n]['block']['0-0-0']);
      }
      foreach($this->Main->shop[$n]['block'] as $xyz => $block)
      {
        $xyz = explode('-',$xyz);
        if($block[0] != 116)
        {
          $this->Main->shop[$n]['level']->setBlockIdAt($xyz[0],$xyz[1],$xyz[2],$block[0]);
          $this->Main->shop[$n]['level']->setBlockDataAt($xyz[0],$xyz[1],$xyz[2],$block[1]);
        }
      }
      $this->del_shop($this->Main->shop[$n]['api']);
    }
  }

  private function setSHOP($level,$pos)
  {
    $x = $pos->x;
    $y = $pos->y - 2.5;
    $z = $pos->z - 0.5;
    $arr = [];
    $yes = False;
    if($this->SHOP->get('设置')['书架附加按钮功能'] == True) $yes = True;
    $this->setTile($level,$x,$y,$z,116,0);
    $this->setTile($level,$x,$y,$z+1,47,0);
    $this->setTile($level,$x,$y+1,$z+1,47,0);
    $this->setTile($level,$x,$y+2,$z+1,47,0);
    $arr[] = [$x,$y,$z,'确定'];
    
    $this->setTile($level,$x+1,$y,$z+1,77,5);
    $arr[] = [$x+1,$y,$z+1,'上一页'];
    if($yes) $arr[] = [$x,$y,$z+1,'上一页'];
    $this->setTile($level,$x+1,$y+1,$z+1,77,5);
    $arr[] = [$x+1,$y+1,$z+1,'上'];
    if($yes) $arr[] = [$x,$y+1,$z+1,'上'];
    $this->setTile($level,$x+1,$y+2,$z+1,77,5);
    $arr[] = [$x+1,$y+2,$z+1,'左'];
    if($yes) $arr[] = [$x,$y+2,$z+1,'左'];

    $this->setTile($level,$x,$y,$z-1,47,0);
    $this->setTile($level,$x,$y+1,$z-1,47,0);
    $this->setTile($level,$x,$y+2,$z-1,47,0);
    
    $this->setTile($level,$x+1,$y,$z-1,77,5);
    $arr[] = [$x+1,$y,$z-1,'下一页'];
    if($yes) $arr[] = [$x,$y,$z-1,'下一页'];
    $this->setTile($level,$x+1,$y+1,$z-1,77,5);
    $arr[] = [$x+1,$y+1,$z-1,'下'];
    if($yes) $arr[] = [$x,$y+1,$z-1,'下'];
    $this->setTile($level,$x+1,$y+2,$z-1,77,5);
    $arr[] = [$x+1,$y+2,$z-1,'右'];
    if($yes) $arr[] = [$x,$y+2,$z-1,'右'];
    $this->Main->shop[$this->name]['block']['0-0-0'] = $arr;
  }

  private function setTile($level,$x,$y,$z,$id,$data)
  {
    $block = $this->getTile($level,$x,$y,$z);
    $this->Main->shop[$this->name]['block'][$x.'-'.$y.'-'.$z] = [$block[0],$block[1]];
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
















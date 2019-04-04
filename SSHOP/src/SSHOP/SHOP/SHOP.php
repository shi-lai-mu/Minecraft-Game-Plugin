<?php
namespace SSHOP\SHOP;

use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\level\Level;
use pocketmine\utils\Config;
use SSHOP\SSHOP as Main;
use pocketmine\scheduler\CallbackTask;
use pocketmine\math\Vector3;

class SHOP
{
  private $Main;
  private $name;
  private $block;

  public function __construct(Main $Main)
  {
    $this->Main = $Main;
    $this->SHOP = $Main->SHOP;
    $this->item = new Config($this->Main->getDataFolder() . 'item.yml',Config::YAML,[]);
  }

  public function del_shop($pid)//移除浮空文字
  {
    $pid->respawn();
    unset($pid);
  }

  public function get_money_name($m)//获取货币名称
  {
    $SHOP = $this->SHOP->get('设置')['货币名称'];
    if($m == '金币') return $SHOP['金币'];
    if($m == '等级') return $SHOP['等级'];
    if($m == '经验') return $SHOP['经验'];
    if($m == '附魔券') return $SHOP['附魔券'];
    if($m == '积分') return $SHOP['积分'];
    if($m == '点券') return $SHOP['点券'];
    if($m == '物品') return $SHOP['物品'];
    return '未知';
  }

  public function home($x = 0,$y = 0,$m = 1,$n = "all",$gui = 0)//获取主页内容
  {
    $this->SHOP = new Config($this->Main->getDataFolder() . 'SHOP.yml',Config::YAML,[]);
    if($gui == 2) $this->Main->shop['ADMIN_DEL'][] = $n;
    if($gui == 3) $this->Main->shop['SHOP_INFO'][] = $n;
    $x_t='';$y_t;$z_t;$txt;$del = False;$info = False;
    if(isset($this->Main->shop['ADMIN_DEL']) and in_array($n,$this->Main->shop['ADMIN_DEL'])) $del = True;
    if(isset($this->Main->shop['SHOP_INFO']) and in_array($n,$this->Main->shop['SHOP_INFO'])) $info = True;
    $set = $this->SHOP->get('设置');
    for($a = 0; $a < count($set['标签']); $a ++)
    {
      if($x == $a)
      {
        $a == count($set['标签'])-1 ? $x_t .= '§'.$set['商店自定义']['选中颜色'].$set['标签'][$a] : $x_t .= '§'.$set['商店自定义']['选中颜色'].$set['标签'][$a].' §6|| ';
        $txt = $set['标签'][$a];
      } else {
        $a == count($set['标签'])-1 ? $x_t .= '§'.$set['商店自定义']['标签颜色'].$set['标签'][$a] : $x_t .= '§'.$set['商店自定义']['标签颜色'].$set['标签'][$a].' §6|| ';
      }
    }
    if(!$this->SHOP->exists($txt)) return $this->Main->getLogger()->info('未知的类型:'.$txt);
    if(in_array($txt,['附魔','镶嵌','强化','更多'])) return "§5S-SHOP\n".$x_t."\n".$set['商店自定义']['分割线']."\n§c此功能正在开发中...";
    $list = $this->SHOP->get($txt);
    if(isset($this->Main->shop[$n]['搜索'])) $list = isset($this->Main->shop[$n]['搜索'][$txt]) ? $this->Main->shop[$n]['搜索'][$txt] : [];
    $all = count($list) / 10;
    count($list) % 10 > 0 ? $all = $all - ((count($list) % 10) / 10) + 1 : [];
    $all == 0 ? $all = 1 : [];
    $y_t = $set['商店自定义']['分割线'];
    $note = "§e关闭 §6| §e添加 §6| §e删除 §6| §e详细§e";
    $qs = 0;
    $m > 1 ? $qs = ($m - 1) * 10 : [];
    $xz = $set['商店自定义']['选中符号'];
    if($del) $xz = '§8'.$set['商店自定义']['选中符号'];
    if($info) $xz = '§a'.$set['商店自定义']['选中符号'];
    for($a = 0 + $qs; $a < 10 * $m; $a ++)
    {
      $y_t .= "\n";
      if(isset($list[$a]))
      {
        $lists = $list[$a];
        $bq = "";
        if(isset($set['商店自定义'][$txt.'选项']))
        {
          $nr1 = $this->Main->String_Repeat($set['商店自定义'][$txt.'选项'],$lists,$a);
          $bq = "  §".$set['商店自定义']['标签颜色'].$nr1;
          $bq2 = '§'.$set['商店自定义']['选中颜色']."$xz ".$nr1;
        }
        else
        {
          $bq = "ERROR: > §c 此选项为错误内容!";
          $bq2 = "§4此选项为错误内容!";;
        }
        $y + $qs == $a ? $y_t .= $bq2 : $y_t .= $bq;
      }
      else
      {
        if($y + $qs == $a)
        {
        $y_t .= "§4$xz §f[".$a."]号商品空缺...";
        }
        else
        {
          $y_t .= '  §8['.$a.']号商品空缺...';
        }
      }
      if($y + $qs == 10 + $qs) $note = "§b关闭 §6| §e添加 §6| §e删除 §6| §e详细§e";
      if($y + $qs == 11 + $qs) $note = "§e关闭 §6| §b添加 §6| §e删除 §6| §e详细§e";
      if($y + $qs == 12 + $qs) $note = "§e关闭 §6| §e添加 §6| §b删除 §6| §e详细§e";
      if($y + $qs == 13 + $qs) $note = "§e关闭 §6| §e添加 §6| §e删除 §6| §b详细§e";
    }
    $ZTL = '';
    if($del) $ZTL = '§8[§9选中并点击需下架的商品,拆除为退出此模式§8]';
    if($info) $ZTL = '§8[§9选中并点击需查看的商品,拆除为退出此模式§8]';
    $text = "§5S-SHOP$ZTL \n$x_t
$y_t
".$set['商店自定义']['分割线']."
$note          ".$m."页/".$all."页 共计:". count($list) ."商品
";
    $this->Main->shop[$n]['a'] = $all;
    return $text;
  }

  public function OK($n,$type = 0)
  {
    $info = $this->Main->shop[$n];
    $txt = $this->home($info['x'],$info['y'],$info['m'],$n);
    $qs = 0;
    if(isset($this->Main->shop['ADMIN_DEL']) and in_array($n,$this->Main->shop['ADMIN_DEL'])) $type = 1;
    if(isset($this->Main->shop['SHOP_INFO']) and in_array($n,$this->Main->shop['SHOP_INFO'])) $type = 2;
    $player = $this->Main->getServer()->getPlayer($n);
    $info['m'] > 1 ? $qs = ($info['m'] - 1) * 10 : [];
    $y = $info['y'] + $qs;
    $kj = '§6---------------------------------------';
    if($y < 10 + $qs)
    {
      if($type == 0) $txt = $this->Main->SHOP($info['x'],$info['y'],$info['m'],$player);
      if($type == 1) $txt = $this->delShop($info['x'],$info['y'],$info['m'],$player);
      if($type == 2) $txt = $this->Shop_info($info['x'],$info['y'],$info['m'],$player);
      return null;
    }
    if($y == 10 + $qs)
    {
      $txt = 'del';
      $this->removeSHOP($n);
    }
    if($y == 11 + $qs)
    {
      $player->isOp() ?  $txt = $this->home($info['x'],$info['y'],$info['m'],$n,1) : $txt = "$kj\n   抱歉非管理员不能打开此功能!\n$kj";
    }
    if($y == 12 + $qs)
    {
      $player->isOp() ?  $txt = $this->home($info['x'],$info['y'],$info['m'],$n,2) : $txt = "$kj\n   抱歉非管理员不能打开此功能!\n$kj";
    }
    if($y == 13 + $qs)
    {
      $txt = $this->home($info['x'],$info['y'],$info['m'],$n,3);
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
              if($this->Main->shop[$n]['y'] > 13) $this->Main->shop[$n]['y'] = 0;
            break;
            case '上':
              $this->Main->shop[$n]['y'] -= 1;
              if($this->Main->shop[$n]['y'] < 0) $this->Main->shop[$n]['y'] = 13;
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
            if($text === null) return;
            if($text != 'del')
            {
              $this->undata($this->Main->shop[$n]['api'],$text);
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
          $xy = $this->Main->shop[$n];
          $this->undata($xy['api'],$this->home($xy['x'],$xy['y'],$xy['m'],$n));
          $this->Main->Time[$n] = time() + 30;
        }
      }
      return False;
    }
  }

  public function create_shop($pos,$level,$name,$Face)
  {
   $this->name = $name;
    $xyz = $pos;
    $pos->y += 2.5;
    $pos->z += 0.5;
    if($Face == 3)
    {
      $pos->x += 3.5;
      var_dump($pos);
    }
    var_dump($pos);
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
    $this->setSHOP($level,$pos,$Face,$name);
    $this->Main->Time[$name] = time() + 30;
  }

  public function Shop_info($x,$y,$m,$player)
  {
    $class = $this->SHOP->get('设置')['标签'][$x];
    if(!$this->SHOP->exists($class)) return False;
    $list = $this->SHOP->get($class);
    if(isset($this->Main->shop[$player->getName()]['搜索'])) $list = isset($this->Main->shop[$player->getName()]['搜索'][$class]) ? $this->Main->shop[$player->getName()]['搜索'][$class] : [];
    $bh = $y + (($m - 1) * 10);
    if(!isset($list[$bh])) return null;
    $player->sendMessage('§a正在获取§8['.$bh.']号§a商品的信息在§8['.$class.']§a区...');
    $info = $list[$bh];
    $item_list = "";
    $a = 0;
    $txt = '';$note;
    $over = $this->Main->Moneys($player,$info['所需'],$info['价格'],'+',True);

    if($class != '回收')
    {
      if(!is_numeric($over))
      {
        $over = "§e ||| 警告:".$over."§e ||| ";
      } else {
        $insufficient = $info['价格'] - $over;
        $remaining = $over - $info['价格'];
        if($over < $info['价格']) $over = "§a".$over.$this->get_money_name($info['所需'])." §e不足以满足需求!还需§c".$insufficient.$this->get_money_name($info['所需']);
        if($over >= $info['价格']) $over = "§a".$over.$this->get_money_name($info['所需'])." §e支出§a".$info['价格']."§e后账户内剩余§a".$remaining.$this->get_money_name($info['所需']);
      }
    }
    if($class == '购买')
    {
      $note = "§e消费§c需".$info['价格'].$this->get_money_name($info['所需'])." §c[ §e你的余额:§c$over §c]";
      if($info['库存'] == 0) $info['库存'] = '不足[已售完]';
      if($info['库存'] == -1) $info['库存'] = '无限';
      if($info['库存'] < 0) $info['库存'] = '剩余['.$info['库存'].']';
      if($info['附魔'] != -1) $info['附魔'] = '物品将附魔['.$info['附魔'].']';
      if($info['附魔'] == -1) $info['附魔'] = '附魔[无]';
      $txt = "§eS-SHOP §c>>> §8[".$class."]区[".$bh."]号§b商品详细
      §5信息:§a ".$this->Main->Get_Item_Name($info['物品'][0],$info['物品'][1])."  §5商品编号:§a ".$bh."
      §5库存:§a ".$info['库存']."  §5附魔:§a ".$info['附魔']."
      §5购买后可获得积分:§a ".$info['积分']."
      §5购买物品后:§a
      $note";
    }
    if($class == '回收')
    {
      $txt = "§eS-SHOP §c>>> §8[".$class."]区[".$bh."]号§b商品详细
      §5信息:§a ".$this->Main->Get_Item_Name($info['物品'][0],$info['物品'][1])."  §5商品编号:§a ".$bh."
      §5回收物品后:§a".$this->get_money_name($info['所需'])." §e增长§a".$info['价格'];
    }
    if($class == '兑换' or $class == '个人')
    {
      $txt = "§eS-SHOP §c>>> §8[".$class."]区[".$bh."]号§b商品详细
      §5信息:§a 功能正在制作,请耐心等待...";
    }
    $this->undata($this->Main->shop[$player->getName()]['api'],$txt);
    return True;
  }

  public function delShop($x,$y,$m,$player)
  {
    $class = $this->SHOP->get('设置')['标签'][$x];
    if(!$this->SHOP->exists($class)) return False;
    $list = $this->SHOP->get($class);
    $bh = $y + (($m - 1) * 10);
    if(!isset($list[$bh])) return null;
    unset($list[$bh]);
    $list = $this->list_array($list);
    $this->SHOP->set($class,$list);
    $this->SHOP->save();
    $player->sendMessage('§a成功将['.$bh.']号商品从['.$class.']区下架...');
    $this->undata($this->Main->shop[$player->getName()]['api'],$this->home($x,$y,$m,$player->getName()));
    return True;
  }

  public function getShopInfo($x,$y,$m)
  {
    $class = $this->SHOP->get('设置')['标签'][$x];
    if(!$this->SHOP->exists($class)) return False;
    $list = $this->SHOP->get($class);
    $bh = $y + (($m - 1) * 10);
    if(!isset($list[$bh])) return null;
    $shop_info = $list[$bh];
    return [$shop_info,$class];
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
        $xyz = explode(':',$xyz);
        if($block[0] != $this->SHOP->get('设置')['商店中间ID'])
        {
          $this->Main->shop[$n]['level']->setBlockIdAt($xyz[0],$xyz[1],$xyz[2],$block[0]);
          $this->Main->shop[$n]['level']->setBlockDataAt($xyz[0],$xyz[1],$xyz[2],$block[1]);
        }
      }
      $this->del_shop($this->Main->shop[$n]['api']);
      if(isset($this->Main->shop['ADMIN_DEL']) and in_array($n,$this->Main->shop['ADMIN_DEL']))
      {
        foreach($this->Main->shop['ADMIN_DEL'] as $key => $value)
        {
          if($value == $n) unset($this->Main->shop['ADMIN_DEL'][$key]);
        }
      }
      if(isset($this->Main->shop['SHOP_INFO']) and in_array($n,$this->Main->shop['SHOP_INFO']))
      {
        foreach($this->Main->shop['SHOP_INFO'] as $key => $value)
        {
          if($value == $n) unset($this->Main->shop['SHOP_INFO'][$key]);
        }
      }
      unset($this->Main->shop['BLOCK-ALL'][$n]);
      unset($this->Main->Time[$n]);
    }
  }

  private function setSHOP($level,$pos,$Face,$player = null)
  {
    $x = $pos->x;
    $y = $pos->y - 2.5;
    $z = $pos->z - 0.5;
    $arr = [];
    $yes = False;
    $ID = $this->SHOP->get('设置');
    if($ID['商店方块附加按钮功能'] == True) $yes = True;
    //$this->setTile($level,$x,$y,$z,$ID['商店中间ID'],0);
    $arr[] = [$x,$y,$z,'确定'];
    $list = [];
    switch($Face)
    {
      case 2:
        $this->setTile($level,$x+1,$y,$z,47,0);
        $this->setTile($level,$x+1,$y+1,$z,47,0);
        $this->setTile($level,$x+1,$y+2,$z,47,0);
        
        $this->setTile($level,$x+1,$y,$z-1,77,2);
        $this->setTile($level,$x+1,$y+1,$z-1,77,2);
        $this->setTile($level,$x+1,$y+2,$z-1,77,2);
        $arr[] = [$x+1,$y,$z-1,'上一页'];
        $arr[] = [$x+1,$y+1,$z-1,'上'];
        $arr[] = [$x+1,$y+2,$z-1,'左'];
        if($yes) $arr[] = [$x+1,$y,$z,'上一页'];
        if($yes) $arr[] = [$x+1,$y+1,$z,'上'];
        if($yes) $arr[] = [$x+1,$y+2,$z,'左'];

        $this->setTile($level,$x-1,$y,$z,47,0);
        $this->setTile($level,$x-1,$y+1,$z,47,0);
        $this->setTile($level,$x-1,$y+2,$z,47,0);
        
        $this->setTile($level,$x-1,$y,$z-1,77,2);
        $this->setTile($level,$x-1,$y+1,$z-1,77,2);
        $this->setTile($level,$x-1,$y+2,$z-1,77,2);
        $arr[] = [$x-1,$y,$z-1,'下一页'];
        $arr[] = [$x-1,$y+1,$z-1,'下'];
        $arr[] = [$x-1,$y+2,$z-1,'右'];
        if($yes) $arr[] = [$x-1,$y,$z,'下一页'];
        if($yes) $arr[] = [$x-1,$y+1,$z,'下'];
        if($yes) $arr[] = [$x-1,$y+2,$z,'右'];
      break;

      case 3:
        $this->setTile($level,$x+1,$y,$z,47,0);
        $this->setTile($level,$x+1,$y+1,$z,47,0);
        $this->setTile($level,$x+1,$y+2,$z,47,0);
        
        $this->setTile($level,$x+1,$y,$z+1,77,3);
        $this->setTile($level,$x+1,$y+1,$z+1,77,3);
        $this->setTile($level,$x+1,$y+2,$z+1,77,3);
        $arr[] = [$x+1,$y,$z,'下一页'];
        $arr[] = [$x+1,$y+1,$z,'下'];
        $arr[] = [$x+1,$y+2,$z,'右'];
        if($yes) $arr[] = [$x+1,$y,$z+1,'下一页'];
        if($yes) $arr[] = [$x+1,$y+1,$z+1,'下'];
        if($yes) $arr[] = [$x+1,$y+2,$z+1,'右'];

        $this->setTile($level,$x-1,$y,$z,47,0);
        $this->setTile($level,$x-1,$y+1,$z,47,0);
        $this->setTile($level,$x-1,$y+2,$z,47,0);
        
        $this->setTile($level,$x-1,$y,$z+1,77,3);
        $this->setTile($level,$x-1,$y+1,$z+1,77,3);
        $this->setTile($level,$x-1,$y+2,$z+1,77,3);
        $arr[] = [$x-1,$y,$z,'上一页'];
        $arr[] = [$x-1,$y+1,$z,'上'];
        $arr[] = [$x-1,$y+2,$z,'左'];
        if($yes) $arr[] = [$x-1,$y,$z+1,'上一页'];
        if($yes) $arr[] = [$x-1,$y+1,$z+1,'上'];
        if($yes) $arr[] = [$x-1,$y+2,$z+1,'左'];
      break;

      case 4:
        $this->setTile($level,$x,$y,$z+1,47,0);
        $this->setTile($level,$x,$y+1,$z+1,47,0);
        $this->setTile($level,$x,$y+2,$z+1,47,0);
        
        $this->setTile($level,$x-1,$y,$z+1,77,4);
        $this->setTile($level,$x-1,$y+1,$z+1,77,4);
        $this->setTile($level,$x-1,$y+2,$z+1,77,4);
        $arr[] = [$x-1,$y,$z+1,'下一页'];
        $arr[] = [$x-1,$y+1,$z+1,'下'];
        $arr[] = [$x-1,$y+2,$z+1,'右'];
        if($yes) $arr[] = [$x,$y,$z+1,'下一页'];
        if($yes) $arr[] = [$x,$y+1,$z+1,'下'];
        if($yes) $arr[] = [$x,$y+2,$z+1,'右'];

        $this->setTile($level,$x,$y,$z-1,47,0);
        $this->setTile($level,$x,$y+1,$z-1,47,0);
        $this->setTile($level,$x,$y+2,$z-1,47,0);
        
        $this->setTile($level,$x-1,$y,$z-1,77,4);
        $this->setTile($level,$x-1,$y+1,$z-1,77,4);
        $this->setTile($level,$x-1,$y+2,$z-1,77,4);
        $arr[] = [$x-1,$y,$z-1,'上一页'];
        $arr[] = [$x-1,$y+1,$z-1,'上'];
        $arr[] = [$x-1,$y+2,$z-1,'左'];
        if($yes) $arr[] = [$x,$y,$z-1,'上一页'];
        if($yes) $arr[] = [$x,$y+1,$z-1,'上'];
        if($yes) $arr[] = [$x,$y+2,$z-1,'左'];
      break;

      default:
        $list[] = new Blcokss(new Vector3($x,$y,$z+1),$level,$player);
        $list[] = new Blcokss(new Vector3($x,$y+1,$z+1),$level,$player);
        $list[] = new Blcokss(new Vector3($x,$y+2,$z+1),$level,$player);
        if($yes) $arr[] = [$x,$y,$z+1,'上一页'];
        if($yes) $arr[] = [$x,$y+1,$z+1,'上'];
        if($yes) $arr[] = [$x,$y+2,$z+1,'左'];

        $list[] = new Blcokss(new Vector3($x,$y,$z-1),$level,$player);
        $list[] = new Blcokss(new Vector3($x,$y+1,$z-1),$level,$player);
        $list[] = new Blcokss(new Vector3($x,$y+2,$z-1),$level,$player);
        if($yes) $arr[] = [$x,$y,$z-1,'下一页'];
        if($yes) $arr[] = [$x,$y+1,$z-1,'下'];
        if($yes) $arr[] = [$x,$y+2,$z-1,'右'];

        /*$this->setTile($level,$x,$y,$z+1,47,0);
        $this->setTile($level,$x,$y+1,$z+1,47,0);
        $this->setTile($level,$x,$y+2,$z+1,47,0);
        $this->setTile($level,$x+1,$y,$z+1,77,5);
        $this->setTile($level,$x+1,$y+1,$z+1,77,5);
        $this->setTile($level,$x+1,$y+2,$z+1,77,5);
        $arr[] = [$x+1,$y,$z+1,'上一页'];
        $arr[] = [$x+1,$y+1,$z+1,'上'];
        $arr[] = [$x+1,$y+2,$z+1,'左'];
        $this->setTile($level,$x,$y,$z-1,47,0);
        $this->setTile($level,$x,$y+1,$z-1,47,0);
        $this->setTile($level,$x,$y+2,$z-1,47,0);
        $this->setTile($level,$x+1,$y,$z-1,77,5);
        $this->setTile($level,$x+1,$y+1,$z-1,77,5);
        $this->setTile($level,$x+1,$y+2,$z-1,77,5);
        $arr[] = [$x+1,$y,$z-1,'下一页'];
        $arr[] = [$x+1,$y+1,$z-1,'下'];
        $arr[] = [$x+1,$y+2,$z-1,'右'];
        if($yes) $arr[] = [$x,$y,$z-1,'下一页'];
        if($yes) $arr[] = [$x,$y+1,$z-1,'下'];
        if($yes) $arr[] = [$x,$y+2,$z-1,'右'];*/
      break;
    }
    $this->Main->shop[$this->name]['block']['0-0-0'] = $arr;
    $this->Main->shop['BLOCK-ALL'][$this->name] = $arr;
  }

  private function setTile($level,$x,$y,$z,$id,$data)
  {
    $block = $this->getTile($level,$x,$y,$z);
    $this->Main->shop[$this->name]['block'][$x.':'.$y.':'.$z] = [$block[0],$block[1]];
    $level->setBlockIdAt($x,$y,$z,$id);
    $level->setBlockDataAt($x,$y,$z,$data);
  }

  private function getTile($level,$x,$y,$z)
  {
    return [$level->getBlockIdAt($x,$y,$z),$level->getBlockDataAt($x,$y,$z)];
  }

  public function Button_Yes($x,$y,$z,$name)
  {
    if(!isset($this->Main->shop['BLOCK-ALL'])) return False;
    foreach($this->Main->shop['BLOCK-ALL'] as $names => $value)
    {
      foreach($value as $pos)
      {
        if($pos[0] == $x and $pos[1] == $y and $pos[2] == $z and $name != $names) return True; 
      }
    }
    return False;
  }

  function undata($shop,$txt,$type = 0)
  {
    if($type == 0) $shop->setText($txt);
  }

  function list_array($array)
  {
    $new_array = [];
    foreach($array as $value)
    {
      $new_array[] = $value;
    }
    return $new_array;
  }
}

class Blcokss
{
 
 public $pos;
 public $entityId;
 public $player;

 public function __construct($pos,$level,$player = False)
 {
  $this->pos = $pos;
  $this->level = $level;
  $this->player = $player;
  $this->entityId = Entity::$entityCount ++;
  $pk = new \pocketmine\network\protocol\AddEntityPacket();
  $pk->eid = $this->entityId;
  $pk->x = $this->pos->x + 0.5;
  $pk->y = $this->pos->y + 0.5;
  $pk->z = $this->pos->z + 0.5;
  $pk->type = 66;
  $flags = 0;
  $pk->pitch = 1;
  $pk->yaw = 1;
  $flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
  $pk->metadata[Entity::DATA_FLAGS] = [Entity::DATA_TYPE_LONG, $flags];
  $pk->metadata[Entity::DATA_AIR] = [Entity::DATA_TYPE_SHORT, 400];
  $pk->metadata[Entity::DATA_MAX_AIR] = [Entity::DATA_TYPE_SHORT, 400];
  $pk->metadata[Entity::DATA_LEAD_HOLDER_EID] = [Entity::DATA_TYPE_LONG, -1];
  $pk->metadata[Entity::DATA_VARIANT] = [Entity::DATA_TYPE_INT, 47];
  $pk->metadata[Entity::DATA_BOUNDING_BOX_HEIGHT] = [Entity::DATA_TYPE_FLOAT, 0.5];
  $pk->metadata[Entity::DATA_BOUNDING_BOX_HEIGHT] = [Entity::DATA_TYPE_FLOAT, 0.5];

  if(!$this->player)
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
 }
 
 public function isInvisible()
 {
  return $this->invisible;
 }
 
 public function respawn(){
 
  $pk = new RemoveEntityPacket();
  $pk->eid = $this->entityId;
  if(!$this->player)
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
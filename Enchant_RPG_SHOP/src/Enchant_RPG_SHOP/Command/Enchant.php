<?php
/**
 * User: slm47888
 * Date: 2017/11/28
 * Time: 08:11
 * Version: 2.4.0
 */
namespace Enchant_RPG_SHOP\Command;

use Enchant_RPG_SHOP\Enchant_RPG_SHOP as Main;
use Enchant_RPG_SHOP\Inventory\Inventory;
use Enchant_RPG_SHOP\Command\setName;
class Enchant
{
    //Main主类
    public function __construct(Main $Main)
    {
        $this->Main = $Main;
        $this->Nessage = $Main->Message;
        $this->Inventory = new Inventory($Main);
    }

    /**
    *   指令
    **/
    public function Command($Type, $sender, $args)
    {
        $x = round($sender->x);
        $y = round($sender->y);
        $z = round($sender->z);
        if ($Type == '附魔' or strtolower($Type) == 'ers')
        {
            $CMD = $args[0];
            /**
             * 附魔 界面
             */
            if (!isset($args[0])) {
                $this->Inventory->AddPock($sender,$this->Inventory->getHome(),'§c§l附 魔 商 店 §e[ '.$this->getsenderAdmin($sender).' ]','Home');
                return true;
            }
            switch ($CMD) {
                case '命名':
                        $Out = new setName($sender,$args,$this);
                        unset($Out);
                    break;
                
                default:
                    
                    break;
            }
        }
    }

    /**
    *   获取玩家权限
    **/
    public function getsenderAdmin($sender){
        $sender->isop() ? $op = '管理员' : $op = '玩家';
        if($sender->getName() == 'shilaimu' and $op == '管理员') $op = '开发者';
        return $op;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: slm
 * Date: 2017/12/2
 * Time: 9:26
 */

namespace Enchant_RPG_SHOP\Enchant;


use Enchant_RPG_SHOP\Enchant_RPG_SHOP;
use pocketmine\utils\Config;

class Message
{
    public function __construct(Enchant_RPG_SHOP $Main){
        $this->Main = $Main;
        $this->Message = new Config($Main->getDataFolder() . 'Message.yml',Config::YAML,[
            'Top' => '§l§a[§eERS§a]§6 ',
            '###### 附魔 选项 提示 ######',
            'Enchant.ail.independent' => '装备已附魔%1,因此不能附魔其他属性!',
            'Enchant.ail.Hight' => '装备已附魔%1,且等级等于或高于需附魔等级!',
            'Enchant.ail.NoId' => '%1属性不支持附魔%2!',
            'Enchant.OK.Nice' => '成功为%1附魔%2级的%3',
            '###### 升级 选项 提示 ######',
            'Upgrade.OK.Nice' => '成功升级 %1级%2附魔 --> %3级%4附魔',
            '###### 命令 提示 ######',
            'Command.setName.001' => '正确格式 :  /附魔 命名 [新名字]',
            'Command.setName.Nice' => '装备信息更新: [%1] 命名变更为 [%2]',
        ]);
    }

    public function get($Key,$v1 = '%1',$v2 = '%2',$v3 = '%3',$v4 = '%4'){
        if($this->Message->exists($Key)){
            return $this->Message->get('Top') . str_replace(['%1','%2','%3','%4'],[$v1,$v2,$v3,$v4],$this->Message->get($Key));
        } else {
            $this->Main->getLogger()->warning("[ERS] 严重警告 : Message Class Key [{$Ket}] Lose!");
        }
    }
}
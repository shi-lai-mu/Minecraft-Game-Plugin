<?php
/**
 * Created by PhpStorm.
 * User: slm
 * Date: 2017/12/2
 * Time: 8:44
 */

namespace Enchant_RPG_SHOP\Enchant;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\utils\Config;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\NamedTag;

use onebone\economyapi\EconomyAPI;
use Enchant_RPG_SHOP\Enchant_RPG_SHOP;
use Enchant_RPG_SHOP\DATA;


class Shop_Handling
{
    public function __construct(Enchant_RPG_SHOP $Main){
        $this->Main = $Main;
        $this->Message = $Main->Message;
    }

    public function Enchant_Hamdling($Data){
        if($Data['Item'] === Null or $Data['Item']->getId() == 388) return;
        $Player = $Data['Player'];
        $Name = $Player->getName();
        $Money = EconomyAPI::getInstance()->myMoney($Player);
        $Player_LV = $Player->getXpLevel();
        $EnchantMoney = $this->Main->Money->get($Name);
        $EnchantItem = $Data['Item'];
        $IdDamage = $EnchantItem->getID().':0';
        $item = new Config($this->Main->getDataFolder() . 'item.yml',Config::YAML,[]);
        $item->exists($IdDamage) ? $ItemName = $item->get($IdDamage) : $ItemName = '未知';
        $EnchantData = new DATA($this->Main,$Data['Enchant_ID']);
        $TagId = 0;
        $Item_EnchantList = [];

        if(isset($EnchantItem->getNamedTag()['display']['strings'])){
            $TagId = $EnchantItem->getNamedTag()['display']['strings'];
            if($this->Main->Enchant->exists($TagId)){
                $List = $this->Main->Enchant->get($TagId);
                foreach($List as $key => $value){
                    $Item_EnchantList[$key] = $value;
                }
            }
        }
        if(!empty($EnchantItem->getEnchantments())) {
            foreach($EnchantItem->getEnchantments() as $Enchant){
                $Item_EnchantList[$Enchant->getId()] = $Enchant->getLevel();
            }
        }

        switch($Data['Type']){
            case '升级':
                $Enchant = $this->Main->Enchant->get($TagId);
                $Old_LV = $Enchant[$Data['Enchant_ID']];
                $Enchant[$Data['Enchant_ID']] = $Data['Enchant_LV'];
                $this->Main->Enchant->set($TagId,$Enchant);
                $this->Main->Enchant->save();
                return $this->Message->get('Upgrade.OK.Nice',$Old_LV,$this->Main->ID[$Data['Enchant_ID']]->name,$Data['Enchant_LV']);
            break;
            case '附魔':
                foreach($Item_EnchantList as $ID => $LV){
                    if(isset($this->Main->ID[$ID])){
                        $Old_Enchant = $this->Main->ID[$ID];
                        if($Old_Enchant->independent == True and $ID != $Data['Enchant_ID']){
                            return $this->Message->get('Enchant.ail.independent',$Old_Enchant->name);
                        }
                    }
                }
                if(isset($Item_EnchantList[$Data['Enchant_ID']]) and $Item_EnchantList[$Data['Enchant_ID']] >= $Data['Enchant_LV']){
                    return $this->Message->get('Enchant.ail.Hight',$Old_Enchant->name);//等级过高
                }
                $Enchant = $this->Main->ID[$Data['Enchant_ID']];
                if(!in_array($EnchantItem->getID(),$Enchant->getItemId)){
                    return $this->Message->get('Enchant.ail.NoId',$Old_Enchant->name);//不支持
                }
                $Buy_Results = $this->Main->setMoneys($Player,$Data['Money_Type'],$Data['Money_Numb'],'-');
                if(!$Buy_Results) return $Player->sendMessage($Buy_Results);//金钱不足
                if($TagId){
                    $Item_EnchantList[$Data['Enchant_ID']] = $Data['Enchant_LV'];
                } else {
                    $TagId = $this->Main->Enchant->get('Enchant') + 1;
                    $Item_EnchantList[$Data['Enchant_ID']] = $Data['Enchant_LV'];
                    $Item_EnchantList['Name'] = $ItemName;
                    if($Enchant->damage > 0) $Item_EnchantList['Damage'] = $Enchant->damage;
                    $this->Main->Enchant->set('Enchant',$TagId);
                }
                $this->Main->Enchant->set($TagId,$Item_EnchantList);
                $this->Main->Enchant->save();
                $NBT = new CompoundTag("", [
                    "display" => new CompoundTag("display", [
                        "Name" => new StringTag("Name",$ItemName),
                        "strings" => new StringTag("strings",$TagId)
                    ])
                ]);
                $Solt = $Data['Item']->slot;
                $Data['Item']->setNamedTag($NBT);
                $enchantment = Enchantment::getEnchantment(-1);
                $enchantment->setLevel(1);
                $Data['Item']->addEnchantment($enchantment);
                unset($Data['Item']->slot);
                $Player->getInventory()->setItem($Solt,$Data['Item']);
                $this->Main->UpDateInventory($Player);
                return $this->Message->get('Enchant.OK.Nice',$ItemName,$Data['Enchant_LV'],$this->Main->ID[$Data['Enchant_ID']]->name);//不支持
            break;
        }
    }

}
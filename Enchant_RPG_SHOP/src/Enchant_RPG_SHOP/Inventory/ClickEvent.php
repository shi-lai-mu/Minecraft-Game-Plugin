<?php
/**
 * User: slm47888
 * Date: 2017/11/28
 * Time: 08:20
 * Version: 2.4.0
 */
namespace Enchant_RPG_SHOP\Inventory;

use Enchant_RPG_SHOP\Enchant_RPG_SHOP as Main;
use Enchant_RPG_SHOP\Inventory\Inventory;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;

class ClickEvent{
	
	/**
	* 加载项
	**/
	public function __construct(Main $Main){
		$this->Main = $Main;
		$this->Inventory = new Inventory($Main);
	}

	/**
	*  数据报判断事件
	**/
	public function Event($event,$PK){
		$player = $event->getPlayer();
		$Name = $player->getName();
		$item = $PK->item;
		if(isset($this->Main->Windows[$Name]) and $player->getWindowId($this->Main->Windows[$Name]) == $PK->windowid){
			$event->setCancelled();
			$player->getInventory()->sendContents($player);
			$this->Main->Windows[$Name]->sendContents($player);
			$Slot = $PK->slot;
			$Win = $this->Main->Windows[$Name];
			switch($this->Main->Class[$Name]){
				case 'Home':
					$this->Home($player,$Slot,$Win);
				break;
				//附魔
				case '选择附魔':
					$this->Enchant_Class($player,$Slot,$Win);
				break;
				case '选择等级':
					$this->LV_Class($player,$Slot,$Win);
				break;
				case '选择支付':
					$this->Buy_Class($player,$Slot,$Win);
				break;
				case '选择物品':
					$this->Select_Item($player,$Slot,$Win);
				break;
				//升级
				case '分解属性':
					$this->Decompose_Enchant($player,$Slot,$Win);
				break;
				case '升级属性':
					if(!$this->Click_Event($name,$Slot)) return;
					$this->Upgrade_LV($player,$Slot,$Win);
				break;
				////////// 指令 //////////
				case 'setName':
					$this->setName($player,$Slot,$Win);
				break;
			}
		}
	}
	
	/**
	* 主页点击事件处理
	**/
	function Home($player,$Slot,$Win){
		$name = $player->getName();
		if(!$this->Click_Event($name,$Slot)) return;
		switch($Slot){
			case 0://附魔
				$this->Main->Data[$name]['Shop']['Type'] = '附魔';
				$this->Inventory->AddItem($Win,$this->Inventory->getEnchant_GUI(),$name,'选择附魔');
			break;
			case 1://升级
				$this->Main->Data[$name]['Shop']['Type'] = '升级';
				$this->Main->Data[$name]['Shop']['Enchant_ID'] = '选择';
				$this->Inventory->AddItem($Win,$this->Select_Upgrade_Item($player,$Win),$name,'分解属性');
			break;
		}
	}



	/***********************   附魔 选项 函数  ***********************
	*****************************************************************
	* 附魔选择 点击事件处理
	**/
	function Enchant_Class($player,$Slot,$Win){
		$name = $player->getName();
		if($Slot == 25){
			if($this->Main->Data[$name]['Page'] > 0){
				$this->Main->Data[$name]['Page'] -= 1;
				$this->Inventory->AddItem($Win,$this->Inventory->getEnchant_GUI($this->Main->Data[$name]['Page']),$name);
			}
			return;
		}
		if($Slot == 26){
			if(Count($this->Main->ID) / 25 > $this->Main->Data[$name]['Page']){
				$this->Main->Data[$name]['Page'] += 1;
				$this->Inventory->AddItem($Win,$this->Inventory->getEnchant_GUI($this->Main->Data[$name]['Page']),$name);
			}
			return;
		}
		if(!$this->Click_Event($name,$Slot)) return;
		$this->Main->Data[$name]['Shop']['Enchant_ID'] = $Slot + ($this->Main->Data[$name]['Page'] * 25);
		$this->Inventory->AddItem($Win,$this->Inventory->SelectLV($name),$name,'选择等级');
	}

	/**
	* 等级选择 点击事件处理
	**/
	function LV_Class($player,$Slot,$Win){
		$name = $player->getName();
		$LV = $this->Main->Data[$name]['Shop']['Enchant_LV'];
		if($Slot == 2 and !$this->Click_Event($name,$Slot)) return $this->Inventory->AddItem($Win,$this->Inventory->SelectMoney(),$name,'选择支付');
		if($Slot == 0 and $LV > 1) $this->Main->Data[$name]['Shop']['Enchant_LV'] -= 1;
		if($Slot == 1 and $LV > 11) $this->Main->Data[$name]['Shop']['Enchant_LV'] -= 10;
		if($Slot == 3) $this->Main->Data[$name]['Shop']['Enchant_LV'] += 10;
		if($Slot == 4) $this->Main->Data[$name]['Shop']['Enchant_LV'] += 1;
		if($Slot == 26) return $this->Inventory->AddItem($Win,$this->Inventory->getEnchant_GUI(),$name,'选择附魔');
		$this->Inventory->AddItem($Win,$this->Inventory->SelectLV($name),$name,'选择等级');
	}


	/***********************   升级 选项 函数  ***********************
	*****************************************************************/
	/**
	* 筛选背包内已附魔的装备
	**/
	public function Select_Upgrade_Item($player,$Win = False){
		$name = $player->getName();
		$List = [];
		for($index = 0; $index < $player->getInventory()->getSize(); $index ++){
			$Item = $player->getInventory()->getItem($index);
			if(isset($Item->getNamedTag()['display']['strings'])){
				if($this->Main->Enchant->exists($Item->getNamedTag()['display']['strings'])){
					$Strings = $Item->getNamedTag()['display']['strings'];
					$AllEnchant = $this->Main->Enchant->get($Strings);
					if(count($AllEnchant)){
						$List[] = $Item;
						$Old_Name = $Item->getNamedTag()['display']['Name'];
						$nbt = new CompoundTag('', [
							'display' => new CompoundTag('display', [
									'Name' => new StringTag('Name',"§c§l操作此装备的附魔属性?\n".$Old_Name ),
									"strings" => new StringTag("strings",$Strings)
							])
						]);
						$Item->setNamedTag($nbt);
						$enchantment = Enchantment::getEnchantment(-1);
						$enchantment->setLevel(1);
						$Item->addEnchantment($enchantment);
						$Item->setNamedTag($nbt);
						$Item->slot = $index;
					}
				}
			}
		}
		if(!count($List)){
			$new = Item::get(388,0,1);
			$nbt = new CompoundTag('', [
				'display' => new CompoundTag('display', [
				'Name' => new StringTag('Name','§c§l未在您的背包内搜索到任何有效的附魔装备!')
				])
			]);
			$new->setNamedTag($nbt);
			$List[] = $new;
		}
		return $List;
	}

	/**
	* 分解附魔
	**/
	public function Decompose_Enchant($player,$Slot,$Win){
		$name = $player->getName();
		$SUI = $this->Select_Upgrade_Item($player,$Win);
		if(!isset($SUI[$Slot])) return;
		$Item = $SUI[$Slot];
		$this->Main->Data[$name]['Shop']['Item'] = $Item;
		$Strings = $Item->getNamedTag()['display']['strings'];
		$AllEnchant = $this->Main->Enchant->get($Strings);
		$List = [];
		$IL = [];
		foreach($AllEnchant as $ID => $LV){
			if(is_numeric($ID) and $ID !== -1) {
				$new = Item::get(351,9,1);
				$nbt = new CompoundTag('', [
				'display' => new CompoundTag('display', [
					'Name' => new StringTag('Name',"§c§l选择升级属性§a >{$LV} 级 {$this->Main->ID[$ID]->name}<")
				])
				]);
				$IL[] = [$ID,$LV];
				$new->setNamedTag($nbt);
				$List[] = $new;
			}
		}
		if(!count($List)){
			$new = Item::get(388,0,1);
			$nbt = new CompoundTag('', [
				'display' => new CompoundTag('display', [
				'Name' => new StringTag('Name','§c§l似乎发生了错误,未在NBT内搜索到相关数值,已在后台输出,请反馈...')
				])
			]);
			$new->setNamedTag($nbt);
			$List[] = $new;
		}
		if(!$this->Click_Event($name,$Slot)) return;
		$this->Main->Data[$name]['Shop']['Enchant_ID'] = $IL;
		$info = $this->Main->Data[$name]['Shop']['Enchant_ID'][$Slot];
		$this->Main->Data[$name]['Shop']['Enchant_ID'] = $info[0];
		$this->Main->Data[$name]['Shop']['Min_LV'] = $info[1] + 1;
		$this->Main->Data[$name]['Shop']['Player'] = $player;
		$this->Main->Data[$name]['Shop']['Enchant_LV'] = $info[1] + 1;
		$this->Inventory->AddItem($Win,$List,$name,'升级属性');
	}

	/**
	* 提升等级 点击事件处理
	**/
	function Upgrade_LV($player,$Slot,$Win){
		$name = $player->getName();
		$info = $this->Main->Data[$name]['Shop'];
		$LV = $info['Enchant_LV'];
		if($Slot == 2 and !$this->Click_Event($name,$Slot)) return $this->Inventory->AddItem($Win,$this->Inventory->SelectMoney(),$name,'选择支付');
		if($Slot == 0 and $LV > $info['Min_LV']) $this->Main->Data[$name]['Shop']['Enchant_LV'] -= 1;
		if($Slot == 1 and $LV > $info['Min_LV'] + 10) $this->Main->Data[$name]['Shop']['Enchant_LV'] -= 10;
		if($Slot == 3) $this->Main->Data[$name]['Shop']['Enchant_LV'] += 10;
		if($Slot == 4) $this->Main->Data[$name]['Shop']['Enchant_LV'] += 1;
		if($Slot == 26) return $this->Inventory->AddItem($Win,$this->Select_Upgrade_Item($player,$Win),$name,'分解属性');
		$this->Inventory->AddItem($Win,$this->Inventory->SelectLV($name),$name,'升级属性');
	}


	/***********************   公共	 函数  ***********************
	*****************************************************************/

	/**
	* 分解附魔
	**/
	public function setName($player,$Slot,$Win){
		$name = $player->getName();
		$SUI = $this->Select_Upgrade_Item($player,$Win);
		if(!isset($SUI[$Slot])) return;
		$Item = $SUI[$Slot];
		$Strings = $Item->getNamedTag()['display']['strings'];
		$AllEnchant = $this->Main->Enchant->get($Strings);
		$Old_Name = $AllEnchant['Name'];
		$AllEnchant['Name'] = $this->Main->Command['setName'][$name];
		$this->Main->Enchant->set($Strings,$AllEnchant);
		$this->Main->Enchant->save();
		$player->sendMessage($this->Main->Message->get('Command.setName.Nice',$Old_Name,$AllEnchant['Name']));
		$this->Inventory->DelPock($player,$Win);
		unset($this->Main->Command['setName'][$name]);
		$this->Main->addLZ($player);
	}

	/**
	* 支付选择 点击事件处理
	**/
	function Buy_Class($player,$Slot,$Win){
		$name = $player->getName();
		if(!$this->Click_Event($name,$Slot)) return;
		$this->Main->Data[$name]['Shop']['Money_Type'] = $this->getMoneyType()[$Slot];
		$this->Main->Data[$name]['Shop']['Money_Numb'] = $this->Money_Function($name);
		if($this->Main->Data[$name]['Shop']['Type'] == '升级'){		
			$player->sendMessage($this->Main->Shop_Handling->Enchant_Hamdling($this->Main->Data[$name]['Shop']));
			$this->Inventory->DelPock($player,$Win);
		}
		$this->Inventory->AddItem($Win,$this->Inventory_Load($player),$name,'选择物品');
	}
	
	/**
	* 筛选背包内的装备
	**/
	public function Inventory_Load($player){
		$name = $player->getName();
		$Shop_info = $this->Main->Data[$name]['Shop'];
		$Enchant_ID = $Shop_info['Enchant_ID'];
		$Enchant = $this->Main->ID[$Enchant_ID];
		$List = [];
		for($index = 0; $index < $player->getInventory()->getSize(); $index ++){
			$Item = $player->getInventory()->getItem($index);
			if(in_array($Item->getId(),$Enchant->getItemId)){
				$List[] = $Item;
				$Old_Name = '';
				if(isset($Item->getNamedTag()['display']['Name'])){
				$Old_Name = $Item->getNamedTag()['display']['Name'];
				}
				$Strings = $Item->getNamedTag()['display']['strings'];
				$nbt = new CompoundTag('', [
				'display' => new CompoundTag('display', [
					'Name' => new StringTag('Name',"§c§l确定为此装备附魔?\n".$Old_Name ),
					"strings" => new StringTag("strings",$Strings)
				])
				]);
				$Item->setNamedTag($nbt);
				if($this->Main->Enchant->exists($Strings)){
				$enchantment = Enchantment::getEnchantment(-1);
				$enchantment->setLevel(1);
				$Item->addEnchantment($enchantment);
				$Item->setNamedTag($nbt);
				}
				$Item->slot = $index;
			}
		}
		if(!count($List)){
			$new = Item::get(388,0,1);
			$nbt = new CompoundTag('', [
				'display' => new CompoundTag('display', [
				'Name' => new StringTag('Name','§c§l未在您的背包内搜索到任何对'.$Enchant->name.'有效的装备!')
				])
			]);
			$new->setNamedTag($nbt);
			$List[] = $new;
		}
		return $List;
	}

	/**
	* 点击事件
	**/
	public function Click_Event($Name,$Data){
		$Info = $this->Main->Data[$Name];
		if($Info['Data'] == $Data){
			if($Info['Click'] == 1){
				$this->Main->Data[$Name]['Click'] = 0;
			$this->Main->Data[$Name]['Data'] = -1;
				return True;
			} else {
				$this->Main->Data[$Name]['Click'] += 1;
				return False;
			}
		} else {
			$this->Main->Data[$Name]['Click'] = 1;
			$this->Main->Data[$Name]['Data'] = $Data;
			return False;
		}
	}

	/**
	* 计算价格
	**/
	public function Money_Function($Name){
		$Shop_info = $this->Main->Data[$Name]['Shop'];
		$Price_LIst = $this->Main->Config['箱子商店']['价格表'];
		if(isset($Price_LIst[$Shop_info['Enchant_ID']])){
			$List = $Price_LIst[$Shop_info['Enchant_ID']];
			if(isset($List[$Shop_info['Enchant_LV']])){
				return $Shop_info['Enchant_LV'] * $List[$Shop_info['Enchant_LV']];
			} else {
				foreach($List as $key => $value){
					$na = explode('-',$key);
					if(isset($na[1])){
						if($Shop_info['Enchant_LV'] >= $na[0] and $Shop_info['Enchant_LV'] <= $na[1]){
							return $Shop_info['Enchant_LV'] * $value;
						}
					} else {
						$this->Main->getLogger()->warning("错误的{$List}->{$ksy}这可能会发生致命错误!");
					}
				}
			}
		} else {
			$this->Main->getLogger()->warning('丢失的附魔ID'.$Shop_info['Enchant_ID'].',执行错误!');
		}
	}

	/**
	* 获取自定义货币名
	**/
	public function getMoneyType(){
		$Main = $this->Main->Config['支付自定义'];
		return [
			$Main['金币'],
			$Main['附魔券'],
			$Main['等级'],
			$Main['经验'],
			$Main['物品']
		];
	}


	/**
	* 物品选择 点击事件处理
	**/
	function Select_Item($player,$Slot,$Win){
		$name = $player->getName();
		if(!$this->Click_Event($name,$Slot)) return;
		if(!isset($this->Inventory_Load($player)[$Slot]))return;
		$this->Main->Data[$name]['Shop']['Item'] = $this->Inventory_Load($player)[$Slot];
		$this->Main->Data[$name]['Shop']['Player'] = $player;
		$player->sendMessage($this->Main->Shop_Handling->Enchant_Hamdling($this->Main->Data[$name]['Shop']));
		$this->Inventory->DelPock($player,$Win);
	}
}
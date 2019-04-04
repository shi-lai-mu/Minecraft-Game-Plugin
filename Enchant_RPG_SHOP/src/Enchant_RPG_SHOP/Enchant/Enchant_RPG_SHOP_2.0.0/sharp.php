<?php
namespace Enchant_RPG_SHOP\PRO;
/*
	by shilaimu in qq 478889187
	如对以下属性产生疑问欢迎提出,已经尽量简化了
*/
class sharp
{
	public $name = '锋利';	//附魔名字
	public $id = 9;	//附魔ID[谨慎修改]
	public $pvp = True;	//pvp是否生效
	public $pve = True;	//pve是否生效
	public $independent = False;	//是否为独立属性
	public $destroyed = False;	//开服销毁此类装备
	public $discarded = False;	//是否可丢弃
	public $setname = False;	//是否可设置装备名字
	public $Food = False;	//此物品为食物
	public $info = '每级提升装备1.25攻击';	//附魔介绍
	public $gamemode = -1;	//允许被什么模式使用
	public $permissions = -1;	//允许被什么权限者使用
	/* RPG-自定义 */
	public $defense = 0;	//防御
	public $burning = 0;	//燃烧秒数
	public $CD = 0;	//CD时间
	public $CDtip = False;	//是否CD剩余提示
	public $Tip = False;	//击杀提示
	public $combo = 0;	//连续击杀提示
	public $scope = 0;	//伤害范围
	public $superposition = 0;	//连击叠加伤害
	public $Effect = 0;	//药水范围
	public $invincible = False;	//0耐久不销毁装备,但所有属性失效
	
	public function getScores($Level)//增加的分数
	{
		if($Level <= 30)
		{
			return 1.3 * $Level;
		}
		else
		{
			return 2 * $Level;
		}
	}
	
	public function getEnchantLevel()//附魔等级范围
	{
		return Array('min' => 0 , 'max' => 999999999);
	}
	
	public function getItemId()//附魔装备范围
	{
		return [268,272,267,283,276,271,275,258,286,279];
	}
	
	public function getDamage($Level)//攻击力
	{
		if($Level <= 30)
		{
			return 1.252 * $Level;
		}
		else
		{
			return 1.25 * $Level;
		}
	}
	
	public function addDMessage($entity)//给被攻击者提示
	{
		return Null;
	}
	
	public function addDEffect($entity)//给被攻击者药水BUFF
	{
		return Null;
	}
	
	public function addEMessage($entity)//给攻击者提示
	{
		return Null;
	}
	
	public function addEEffect($entity)//给攻击者药水BUFF
	{
		return Null;
	}
	
	public function NOTE()//特殊代码[谨慎修改]
	{
		return NULL;
	}
	
	public function CODE($Level)//[禁止修改]
	{
		if($Level < 1) return 0;
		return $this->getDamage($Level);
	}
	
}
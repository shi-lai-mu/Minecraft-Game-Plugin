<?php
namespace Enchant_RPG_SHOP\PRO;

class Fortune
{
	public $name = '时运';	//附魔名字
	public $id = 18;	//附魔ID[谨慎修改]
	public $pvp = False;	//true pvp | false pve | null all
	public $independent = False;	//是否为独立属性
	public $destroyed = False;	//玩家使用时销毁此类装备
	public $discarded = True;	//是否可丢弃
	public $setname = False;	//是否可设置装备名字
	public $Food = False;	//此属性为食物
	public $passive = False;	//此属性为被动
	public $protect = False;	//此属性护甲
	public $shooting = True;	//此属性为射击
	public $hand = True;	//此属性为手持品
	public $info = '掉落更多物品';	//附魔介绍
	public $gamemode = 0;	//允许被什么模式使用
	/* RPG-自定义 */
	public $damage = 0;	//能量
	public $burning = 0;	//燃烧秒数
	public $CD = 0;	//CD时间
	public $combo = 0;	//连续击杀提示
	public $scope = 0;	//伤害范围
	public $Effect = 0;	//药水范围
	public $vampire = 0;	//吸血
	public $superposition = 0;	//连击叠加伤害
	public $rebound_D = 0;//反弹攻击
	public $vampire_d = False;	//吸血额外伤害
	public $CDtip = False;	//是否CD剩余提示
	public $ai = True;	//是否覆盖原版附魔
	public $Tip = False;	//连击提示
	public $swim = False;//眩晕
	public $note = False;//特殊属性
	
	public function getdefense($Level)//防御
	{
		return 0;
	}
	
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
		return [256,257,258,269,270,271,273,274,275,277,278,279,284,285,286];
	}
	
	public function getDamage($Level)//攻击力
	{
		return 0;
	}
	
	public function addDMessage()//给攻击者提示
	{
		return Null;
	}
	
	public function addDEffect()//给攻击者药水BUFF
	{
		return Null;
	}
	
	public function addEMessage()//给被攻击者提示
	{
		return Null;
	}
	
	public function addEEffect()//给被攻击者药水BUFF
	{
		return Null;
	}
	
	public function NOTE()//特殊代码[谨慎修改]
	{
		return [
			16 => '263:0',
			56 => '264:0',
			21 => '351:4',
			73 => '311:0',
			89 => '348:0',
			129 => '388:0',
			153 => '406:0'
		];
	}
	
	public function CODE($Level)//[禁止修改]
	{
		if($Level < 1) return 0;
		return $this->getDamage($Level);
	}
}
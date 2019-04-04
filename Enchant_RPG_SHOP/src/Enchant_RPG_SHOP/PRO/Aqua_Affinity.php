<?php
namespace Enchant_RPG_SHOP\PRO;

class Aqua_Affinity
{
	public $name = '水下速掘';	//附魔名字
	public $id = 8;	//附魔ID[谨慎修改]
	public $pvp = Null;	//true pvp | false pve | null all
	public $independent = False;	//是否为独立属性
	public $destroyed = False;	//玩家使用时销毁此类装备
	public $discarded = True;	//是否可丢弃
	public $setname = False;	//是否可设置装备名字
	public $Food = False;	//此属性为食物击
	public $passive = False;	//此属性为被动
	public $protect = True;	//此属性护甲
	public $shooting = False;	//此属性为射
	public $hand = False;	//此属性为手持品
	public $info = '延长在水下呼吸的时间';	//附魔介绍
	public $gamemode = -1;	//允许被什么模式使用
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
	public $CDtip = False;	//是否CD剩余提示
	public $vampire_d = False;	//吸血额外伤害
	public $ai = False;	//是否覆盖原版附魔
	public $Tip = False;	//连击提示
	public $swim = False;//眩晕
	public $note = False;//特殊属性
	
	public function getdefense($Level)//防御
	{
		return Null;
	}
	
	public function getScores($Level)//增加的分数
	{
		if($Level <= 30)
		{
			return 1.4 * $Level;
		}
		else
		{
			return 2 * $Level;
		}
	}
	
	public function getEnchantLevel()//附魔等级范围
	{
		return Array('min' => 0 , 'max' => 3);
	}
	
	public function getItemId()//附魔装备范围
	{
		return [298,302,306,310,314];
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
		return NULL;
	}
	
	public function CODE()//[禁止修改]
	{
		return NULL;
	}
}
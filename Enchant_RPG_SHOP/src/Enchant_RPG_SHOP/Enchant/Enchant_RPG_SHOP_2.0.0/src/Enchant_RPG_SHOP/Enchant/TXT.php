<?php
namespace Enchant_RPG_SHOP\Enchant;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginLoader;
use pocketmine\plugin\PluginBase;

use Enchant_RPG_SHOP\Enchant_RPG_SHOP as Main;

class TXT
{
	private $Main;
	public function __construct(Main $Main)
	{
		$this->Main = $Main;
		$dir = $this->Main->getDataFolder();
		$this->start();
	}
	
	public function start()
	{
		$dir = $this->Main->getDataFolder();
		@mkdir($dir);
		@mkdir($dir . 'Prepaid/');
		$this->Prepaid_10 = new Config($dir . 'Prepaid/10.Prepaid',Config::YAML,[]);
		$this->Prepaid_30 = new Config($dir . 'Prepaid/30.Prepaid',Config::YAML,[]);
		$this->Prepaid_50 = new Config($dir . 'Prepaid/50.Prepaid',Config::YAML,[]);
		$this->Prepaid_75 = new Config($dir . 'Prepaid/75.Prepaid',Config::YAML,[]);
		$this->Prepaid_100 = new Config($dir . 'Prepaid/100.Prepaid',Config::YAML,[]);
		$this->Enchant = new Config($dir . 'Enchant_NBT.yml',Config::YAML,array('Enchant' => 0));
		$this->set = new Config($dir . 'set.yml',Config::YAML,[]);
		$this->b = new Config($dir . 'Config.yml',Config::YAML,[]);
		$this->signs = new Config($dir . 'signs.json',Config::YAML,[]);
		$this->Player = new Config($dir . 'Player.json',Config::YAML,array('attribute' => Array()));
		$this->info = $this->Player->get('attribute');
		$this->beibao = new Config($dir . 'beibao.json',Config::YAML,[]);
		$this->Money = new Config($dir . 'Money.json',Config::YAML,[]);
		$this->Command_Shop = new Config($dir . 'Command_Shop.yml',Config::YAML,array(
			'附魔' => Array(),
			'修复' => Array(),
			'强化' => Array(),
			'出售' => Array(),
			'回收' => Array(),
			'镶嵌' => Array(),
			'RPG' => Array(),
			'已下架' => Array(),
			'信息' => Array(
				'DATA' => 0
			),
		));
		$this->Prepaid = new Config($dir . 'Prepaid.yml',Config::YAML,array(
			'注释' => '请不要修改此文件内的数据,这会导致充值失败!',
			10 => Array(),
			30 => Array(),
			50 => Array(),
			75 => Array(),
			100 => Array()
		));
		$set1 = $this->getConfigTxT();
		if(!$this->set->exists('设置'))
		{
			$this->Main->getLogger()->info('§4正在写入配置信息[' . $set1['配置版本'] . ']');
			$this->set->set('设置',$set1);
			$this->set->save();
		}
		else
		{
			$set = $this->set->get('设置');
			if($set['配置版本'] != $set1['配置版本'])
			{
				$this->Main->getLogger()->info('§4发现旧版本配置文件§6[' . $set['配置版本'] . ']§4版本,正在§c智能覆盖§4此版本§6[' . $set1['配置版本'] . ']§4!');
				$this->Main->getLogger()->info('§e此版本因特殊原因，正在重置底部设置!');
				$this->Main->getLogger()->info('§4这会尽量保留原设置,从而加入§c新的设置...');
				$as = array_merge($set1,$set);
				$as['配置版本'] = $set1['配置版本'];
				$as['底部显示'] = "                                                                                 {动态线}
                                                                                 §4▍  §d{生命} / {生命上限} 生命 ஐ
                                                                                 §4▍  §9{魔法} / {魔法上限}  魔法 ✪
                                                                                 §4▍  §2+{物攻}  物攻 ➹
                                                                                 §4▍  §3+{物防}  物防 ♝
                                                                                 §4▍  §e+{暴击}  暴击 ☄
                                                                                 §4▍  §5+{格挡}  格挡 ☃
                                                                                 §4▍  §c+{抗暴}  抗暴 ♋
                                                                                 §4▍  §7+{魔防}  魔防 ♙
                                                                                 §4▍  §a+{魔攻}  魔攻 ☢
                                                                                 {动态线}









";
				$this->set->set('设置',$as);
				$this->set->save();
			}
		}
	}

	public function getConfigTxT()
	{
		return Array(
			'配置版本' => '2.0.5_1',
			'双击确认' => '开',
			'底部' => '开',
			'底部动态框' => '开',
			'白名单内成员才可创建商店' => '关',
			'后台才能执行附魔券操作' => '开',
			'在指定世界开启底部' => '关',
			'无限在未射中目标情况下也生效' => '关',
			'等级影响属性' => '开',
			'只开启血量和攻击属性' => '关',

			'底部世界' => ['世界一','世界二','按照这格式无限加',],
			'白名单' => Array(),
			'最大可扩展血量上限' => 100,
			'每级加血量上限' => 0,
			'每级加物攻' => 0,
			'双击冷却秒数' => 3,
			'卡密账号长度' => 10,
			'卡密密码长度' => 15,
			'等级上限' => 200000,
			'宝石ID' => 264,
			'底部方式' => 'Tip',
			
			'点券名称' => '点券',//D
			'附魔券名称' => '附魔券',//W
			'金币名称' => '金币',//M
			'经验名称' => '经验',//X
			'等级名称' => '等级',//L
			
			'底部显示' => "                                                                                 {动态线}
                                                                                 §4▍  §d{生命} / {生命上限} 生命 ஐ
                                                                                 §4▍  §9{魔法} / {魔法上限}  魔法 ✪
                                                                                 §4▍  §2+{物攻}  物攻 ➹
                                                                                 §4▍  §3+{物防}  物防 ♝
                                                                                 §4▍  §e+{暴击}  暴击 ☄
                                                                                 §4▍  §5+{格挡}  格挡 ☃
                                                                                 §4▍  §c+{抗暴}  抗暴 ♋
                                                                                 §4▍  §7+{魔防}  魔防 ♙
                                                                                 §4▍  §a+{魔攻}  魔攻 ☢
                                                                                 {动态线}









",

			'注释' => Array(
				'在下方内写入这些符号会被替换为文本' => 'true',
				'@ID' => '附魔ID名称',
				'@LV' => '附魔等级',
				'@MAX' => '附魔最高等级',
				'@MIX' => '附魔最低等级',
				'@Amount' => '花费的数量',
				'@MC' => '交换物名称',
				'@TS' => '附魔效果注释[仅附魔可用]',
				'@DA' => '附魔耐久变化'
			),

			'拆卸台坐标' => Array(),
			
			'附魔' => Array(
				'§5[§6附魔 §e"@ID"§6 商店§5]',
				'§a附魔等级§e @LV §2LV',
				'§2耗费§1 @Amount @MC§2 §a@DA',
				'@TS'
			),
			
			'强化' => Array(
				'§5[§6强化 §e"@ID"§6 商店§5]',
				'§3强化升§1 @MIX §3LV§1',
				'§2耗费§1 @Amount §2@MC §a@DA',
				'§2可强化上限§6 @MAX 级'
			),
			
			'回收' => Array(
				'§5[§6回收 §e"@ID"§6 商店§5]',
				'§a等级§e @MIX - @MAX §2LV',
				'§2获得§1 @Amount §2@MC',
				'§2装备耐久在§a @DA §2以上'
			),
			
			'修复' => Array(
				'§5[§6修复 §e""@ID"§6 商店§5]',
				'§a等级§e @MIX - @MAX §2LV',
				'§2耗费§1 @Amount §2@MC',
				'§2装备耐久恢复§a @DA'
			),
			
			'出售' => Array(
				'§5[§b出售§6 §e"@ID"§6 商店§5]',
				'§a附魔等级§e @LV §2LV',
				'§2耗费§1 @Amount §2@MC §a@DA',
				'@TS'
			)
		);
	}
}
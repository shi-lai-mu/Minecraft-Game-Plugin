<?php
/**
 * User: slm
 * Date: 2017/9/9
 * Time: 21:21
 * Version: 2.3.0
 */
namespace island;

use island\Main;
use island\Land;
use island\Command;
use island\Inventory;
use island\Data;
use pocketmine\utils\Config;
use onebone\economyapi\EconomyAPI;
class API
{
    //Main主类
    private $Main = Null;
    //Data接口
    public $Data = Null;
    //Land接口
    public $Land = Null;
    //Inventory接口
    public $Inventory = Null;
    //Land接口
    public $Land_List = [];
    //装杂类的盒子
    public $Box = [];
    //岛屿拓展列表
    private $File_List = [];
    public function __construct(Main $Main)
    {
        $this->Main = $Main;
        $this->error('发现岛屿接口被调用,已准备就绪...');
        $this->getDataFolder = $Main->getDataFolder();
        $this->Data = new Data($Main);
        $this->Land = new Land($Main);
        $this->Command = new Command($Main);
        $this->Inventory = new Inventory($Main);
    }
    /**
     * 插件版本号
     */
    public function getVersion()
    {
        return $this->Main->getDescription()->getVersion();
    }
    /**
     * 判断金币是否足够
     */
    public function FunMoney($Player,$Amout)
    {
        return EconomyAPI::getInstance()->myMoney($Player) > $Amout;
    }
    /**
     * 给予金币
     */
    public function addlMoney($Player,$Number)
    {
        $this->ExpMessage("系统在{$Player}钱包中存入{$Number}金币");
        EconomyAPI::getInstance()->addMoney($Player,$Number);
    }
    /**
     * 扣除金币
     */
    public function delMoney($Player,$Number)
    {
        $this->ExpMessage("系统从{$Player}钱包中取出{$Number}金币");
        EconomyAPI::getInstance()->reduceMoney($Player,$Number);
    }
    /**
     * 输出信息
     */
    function info(string $text)
    {
        $this->Main->getLogger()->info("$text");
    }
    /**
     * 输出类信息
     */
    function Log(string $text)
    {
        $this->Main->getLogger()->warning("API.php: {$text}");
    }
    /**
     * 输出警告信息
     */
    function error(string $text)
    {
        $this->Main->getLogger()->error("API.php: {$text}");
    }
    /**
     * 开发者信息
     */
    function ExpMessage(string $text)
    {
        if(!$this->Config_yml->get('Exploitation_Mode')) return;
        $this->Main->getLogger()->error("§cExploitation_Mode §6>§e $text");
    }
}
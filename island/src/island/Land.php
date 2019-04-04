<?php
/**
 * User: slm
 * Date: 2017/9/9
 * Time: 21:21
 * Version: 2.3.0
 */
namespace island;

use island\Main;
class Land
{
    //Main主类
    private $Main = Null;

    public function __construct(Main $Main)
    {
        $this->Main = $Main;
    }
    /**
     * 加载领地函数
     */
    public function Load_Land()
    {
        $this->ExpMessage('开始加载 领地数据...');
        $Nu = 0;
        foreach($this->API->Land_yml->getAll() as $Number => $Array)
        {
            $Box = [];
            foreach(explode(':',$Array) as $XZ)
            {
                $Box[] = $XZ;
            }
            unset($Box[count($Box) - 1]);
            $this->API->Land_List[$Number] = $Box;
            $Nu += 1;
        }
        $this->ExpMessage("领地数据 共加载[$Nu]条数据!");
    }
    /**
     * XZ获取领地编号
     */
    public function getXZ($XZ,$World)
    {
        foreach ($this->API->Land_List as $Numberin => $Land)
        {
            if(in_array($XZ,$Land)) return $Numberin;
        }
        return 0;
    }
    /**
     * 添加领地数据
     */
    public function AddLand(int $Numberin ,Array $AddLand)
    {
        if(!isset($this->API->Land_List[$Numberin])) $this->API->Land_List[$Numberin] = [];
        $this->API->Land_List[$Numberin] = array_merge($this->API->Land_List[$Numberin],$AddLand);
    }
    /**
     * 保存领地数据
     */
    public function Save_Land()
    {
        foreach($this->API->Land_List as $Numberin => $Land)
        {
            if(!is_numeric($Numberin)) continue;
            $Land_text = "";
            foreach($Land as $XZ)
            {
                $Land_text .= $XZ . ':';
            }
            $this->API->Land_yml->set($Numberin,$Land_text);
        }
        $this->API->Land_yml->save();
        $this->Log(' 领地数据保存完成!');
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
        $this->Main->getLogger()->warning("Land.php: {$text}");
    }
    /**
     * 输出警告信息
     */
    function error(string $text)
    {
        $this->Main->getLogger()->error("Land.php: {$text}");
    }
    /**
     * 开发者信息
     */
    function ExpMessage(string $text)
    {
        if(!$this->API->Config_yml->get('Exploitation_Mode')) return;
        $this->Main->getLogger()->error("§cExploitation_Mode §6>§e $text");
    }

}
<?php
namespace SuperXingKong\SuperTransfer;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\event\Listener;
use SuperXingKong\SuperTransfer\Transfer;
use pocketmine\command\{CommandSender,Command};
use pocketmine\scheduler\CallbackTask;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\EnchantParticle;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\particle\RedstoneParticle;
class SuperTransfer extends PluginBase implements Listener{
public $players = [];
public function onEnable(){
$this->getLogger()->info("§a---Copyright ©2017 SuperXingKong---");
$this->getLogger()->notice("倒卖必究!");
$this->getServer()->getPluginManager()->registerEvents($this,$this);
$this->getServer()->getScheduler()->scheduleRepeatingTask(new CallbackTask([$this,"transferTask"]),20);
}
public function onCommand(CommandSender $sender,Command $cmd,$label,array $args){
switch (strtolower($cmd->getName())){
case 'transfer':
if (count($args) == 2){
$sn = $sender->getName();
$this->players[$sn]['status'] = 'transfer';
$this->players[$sn]['ip'] = $args[0];
$this->players[$sn]['port'] = (int)$args[1];
$this->players[$sn]['time'] = 5;
return true;
}
}
}
public function transferTask(){
foreach ($this->players as $name=>$info){
$player = $this->getServer()->getPlayer($name);
if ($info['status'] === 'transfer'){
$this->setTask($player,5);
$this->players[$name]['status'] = 'task';
}else{
$this->setTask($player,$this->getTask($player) -1);
}
$task = $this->getTask($player);
$player->sendMessage("§a还有{$task}秒传送!");
$this->addTransferParticle($player);
if ($task === 0){
$this->transferPlayer($name,$info['ip'],$info['port']);
unset($this->players[$name]);
}
}
}
public function transferPlayer($player,$ip,$port){
if ($player instanceof Player){
$player = $player->getName();
}
$player = $this->getServer()->getPlayer($player);
switch ($player->getProtocol()){
case "101":
default:
$pk = new Version1();
break;
case "102":
$pk = new Version2();
break;
}
$pk->address = $ip;
$pk->port = $port;
$player->dataPacket($pk);
}
public function getTask($player){
if ($player instanceof Player){
$player = $player->getName();
}
$task = $this->players[$player]['task'];
return $task;
}
public function setTask($player,$num){
if ($player instanceof Player){
$player = $player->getName();
}
$this->players[$player]['task'] = (int)$num;
}
public function addTransferParticle($player){
if (!($player instanceof Player)){
$player = $this->getServer()->getPlayer($player);
}
$x = $player->getX();
$y = $player->getY();
$z = $player->getZ();
$level = $player->getLevel();
$r = 1.5;
$y2 = $y+2;
for($p=1;$p<=3;$p++){
for($i=1;$i<=5;$i=$i+1){
$level->addParticle(new RedstoneParticle(new Vector3($x,$y2+1,$z)));
}
for($i=1;$i<=30 ;$i++){
$xx=$x+$r*cos($i*3.1415926/15) ;
$zz=$z+$r*sin($i*3.1415926/15) ;
switch($r){
case 1.5:
$level->addParticle(new EnchantParticle(new Vector3($xx,$y2,$zz)));
case 3:
$level->addParticle(new PortalParticle(new Vector3($xx,$y2,$zz)));
case 4.5:
$level->addParticle(new InkParticle(new Vector3($xx,$y2,$zz)));
case 6:
$level->addParticle(new CriticalParticle(new Vector3($xx,$y2+4,$zz)));
}
}
}
}
}

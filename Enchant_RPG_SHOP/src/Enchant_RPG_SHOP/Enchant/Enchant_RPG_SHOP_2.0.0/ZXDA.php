<?php
namespace Enchant_RPG_SHOP\Enchant;

class ZXDA
{
		private static $_PID=false;
		private static $_TOKEN=false;
		private static $_PLUGIN=null;
		private static $_VERIFIED=false;
		private static $_API_VERSION=5012;

		public static function init($pid,$plugin)
		{
			if(!is_numeric($pid))
			{
				self::killit('参数错误,请传入正确的PID(0001)');
				exit();
			}
			self::$_PLUGIN=$plugin;
			if(self::$_PID!==false && self::$_PID!=$pid)
			{
				self::killit('非法访问(0002)');
				exit();
			}
			self::$_PID=$pid;
		}

		public static function checkKernelVersion()
		{
			if(self::$_PID===false)
			{
				self::killit('SDK尚未初始化(0003)');
				exit();
			}
			if(!class_exists('\\ZXDAKernel\\Main',false))
			{
				self::killit('请到 https://pl.zxda.net/ 下载安装最新版ZXDA Kernel后再使用此插件(0004)');
				exit();
			}
			$version=\ZXDAKernel\Main::getVersion();
			if($version<self::$_API_VERSION)
			{
				self::killit('当前ZXDA Kernel版本太旧,无法使用此插件,请到 https://pl.zxda.net/ 下载安装最新版后再使用此插件(0005)');
				exit();
			}
			return $version;
		}

		public static function isTrialVersion()
		{
			try
			{
				self::checkKernelVersion();
				return \ZXDAKernel\Main::isTrialVersion(self::$_PID);
			}
			catch(\Exception $err)
			{
				@file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',var_export($err,true));
				self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
			}
		}

		public static function requestCheck()
		{
			try
			{
				self::checkKernelVersion();
				self::$_VERIFIED=false;
				self::$_TOKEN=sha1(uniqid());
				if(!\ZXDAKernel\Main::requestAuthorization(self::$_PID,self::$_PLUGIN,self::$_TOKEN))
				{
					self::killit('请求授权失败,请检查PID是否已正确传入(0006)');
					exit();
				}
			}
			catch(\Exception $err)
			{
				@file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',var_export($err,true));
				self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
			}
		}

		public static function tokenCheck($key)
		{
			try
			{
				self::checkKernelVersion();
				self::$_VERIFIED=false;
				$manager=self::$_PLUGIN->getServer()->getPluginManager();
				if(!($plugin=$manager->getPlugin('ZXDAKernel')) instanceof \ZXDAKernel\Main)
				{
					self::killit('ZXDA Kernel加载失败,请检查插件是否已正常安装(0008)');
				}
				if(!$manager->isPluginEnabled($plugin))
				{
					$manager->enablePlugin($plugin);
				}
				$key=base64_decode($key);
				if(($token=\ZXDAKernel\Main::getResultToken(self::$_PID))===false)
				{
					self::killit('请勿进行非法破解(0009)');
				}
				if(self::rsa_decode(base64_decode($token),$key,768)!=sha1(strrev(self::$_TOKEN)))
				{
					self::killit('插件Key错误,请更新插件或联系作者(0010)');
				}
				self::$_VERIFIED=true;
			}
			catch(\Exception $err)
			{
				@file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',var_export($err,true));
				self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
			}
		}

		public static function isVerified()
		{
			return self::$_VERIFIED;
		}

		public static function getInfo()
		{
			try
			{
				self::checkKernelVersion();
				$manager=self::$_PLUGIN->getServer()->getPluginManager();
				if(!($plugin=$manager->getPlugin('ZXDAKernel')) instanceof \ZXDAKernel\Main)
				{
					self::killit('ZXDA Kernel加载失败,请检查插件是否已正常安装(0008)');
				}
				if(($data=\ZXDAKernel\Main::getPluginInfo(self::$_PID))===false)
				{
					self::killit('请勿进行非法破解(0009)');
				}
				if(count($data=explode(',',$data))!=2)
				{
					return array(
				'success'=>false,
				'message'=>'未知错误');
				}
				return array(
					'success'=>true,
					'version'=>base64_decode($data[0]),
					'update_info'=>base64_decode($data[1]));
			}
			catch(\Exception $err)
			{
				@file_put_contents(self::$_PLUGIN->getServer()->getDataPath().'0007_data.dump',var_export($err,true));
				self::killit('未知错误(0007),错误数据已保存到 0007_data.dump 中,请提交到群内获取帮助');
			}
		}

		public static function killit($msg)
		{
			if(self::$_PLUGIN===null)
			{
				echo('抱歉,插件授权验证失败[SDK:'.self::$_API_VERSION.']\n附加信息:'.$msg);
			}
			else
			{
				@self::$_PLUGIN->getLogger()->warning('§e抱歉,插件授权验证失败[SDK:'.self::$_API_VERSION.']');
				@self::$_PLUGIN->getLogger()->warning('§e附加信息:'.$msg);
				@self::$_PLUGIN->getServer()->forceShutdown();
			}
			exit();
		}


		//RSA加密算法实现
		public static function rsa_encode($message,$modulus,$keylength=1024,$isPriv=true){$result=array();while(strlen($msg=substr($message,0,$keylength/8-5))>0){$message=substr($message,strlen($msg));$result[]=self::number_to_binary(self::pow_mod(self::binary_to_number(self::add_PKCS1_padding($msg,$isPriv,$keylength/8)),'65537',$modulus),$keylength/8);unset($msg);}return implode('***&&&***',$result);}
		public static function rsa_decode($message,$modulus,$keylength=1024){$result=array();foreach(explode('***&&&***',$message) as $message){$result[]=self::remove_PKCS1_padding(self::number_to_binary(self::pow_mod(self::binary_to_number($message),'65537',$modulus),$keylength/8),$keylength/8);unset($message);}return implode('',$result);}
		private static function pow_mod($p,$q,$r){$factors=array();$div=$q;$power_of_two=0;while(bccomp($div,'0')==1){$rem=bcmod($div,2);$div=bcdiv($div,2);if($rem){array_push($factors,$power_of_two);}$power_of_two++;}$partial_results=array();$part_res=$p;$idx=0;foreach($factors as $factor){while($idx<$factor){$part_res=bcpow($part_res,'2');$part_res=bcmod($part_res,$r);$idx++;}array_push($partial_results,$part_res);}$result='1';foreach($partial_results as $part_res){$result=bcmul($result,$part_res);$result=bcmod($result,$r);}return $result;}
		private static function add_PKCS1_padding($data,$isprivateKey,$blocksize){$pad_length=$blocksize-3-strlen($data);if($isprivateKey){$block_type="\x02";$padding='';for($i=0;$i<$pad_length;$i++){$rnd=mt_rand(1,255);$padding .= chr($rnd);}}else{$block_type="\x01";$padding=str_repeat("\xFF",$pad_length);}return "\x00".$block_type.$padding."\x00".$data;}
		private static function remove_PKCS1_padding($data,$blocksize){assert(strlen($data)==$blocksize);$data=substr($data,1);if($data{0}=='\0'){return '';}assert(($data{0}=="\x01") or ($data{0}=="\x02"));$offset=strpos($data,"\0",1);return substr($data,$offset+1);}
		private static function binary_to_number($data){$radix='1';$result='0';for($i=strlen($data)-1;$i>=0;$i--){$digit=ord($data{$i});$part_res=bcmul($digit,$radix);$result=bcadd($result,$part_res);$radix=bcmul($radix,'256');}return $result;}
		private static function number_to_binary($number,$blocksize){$result='';$div=$number;while($div>0){$mod=bcmod($div,'256');$div=bcdiv($div,'256');$result=chr($mod).$result;}return str_pad($result,$blocksize,"\x00",STR_PAD_LEFT);}
}
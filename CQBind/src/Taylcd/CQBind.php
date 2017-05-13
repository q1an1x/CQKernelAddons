<?php

namespace Taylcd;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\Config;
use Taylcd\CQKernel\CQLib;
use Taylcd\CQKernel\plugin\CQKPlugin;

class CQBind extends CQKPlugin
{
    public $api = 1.3;

    private $codes = [];

    private static $obj;

    /**
     * @return CQBind
     */
    public static function getInstance()
    {
        return self::$obj;
    }

    public function onLoad()
    {
        self::$obj = $this;

        $this->saveDefaultConfig();
        $this->reloadConfig();

        foreach(['players', 'accounts'] as $folder)
            if(!is_dir($this->getDataFolder() . $folder)) @mkdir($this->getDataFolder() . $folder);

        $command = new PluginCommand($this->getConfig()->get('random-code-command', '随机码'), $this);
        $command->setDescription('获取用于绑定 QQ 账号的随机码');
        $this->getServer()->getCommandMap()->register($this->getConfig()->get('random-code-command', '随机码'), $command);
    }

    public function onEnable()
    {
        $this->registerCommand($this->getConfig()->get('bind-command', '绑定账号'));
        $this->registerCommand($this->getConfig()->get('unbind-command', '解绑账号'));
    }

    public function onCommand(CommandSender $sender, Command $command, $label, array $args)
    {
        $name = strtolower($sender->getName());
        if($qq = $this->getQQByName($name))
        {
            $sender->sendMessage('您的游戏账号已绑定至 QQ ' . $qq);
            return;
        }

        $this->codes[$name] = mt_rand(100000, 999999);
        $sender->sendMessage('您的随机码: ' . $this->codes[$name] . "\n请尽快到 QQ 机器人处绑定账号.");
    }

    public function commandProgress($command, array $args, $fromQQ, $fromGroup = null)
    {
        if($command == $this->getConfig()->get('bind-command', '绑定账号'))
        {
            if($ign = $this->getNameByQQ($fromQQ))
            {
                $this->sendMessage('你已经绑定了游戏账号: ' . $ign . "\n如需绑定其他账号请先发送 " . $this->getConfig()->get('unbind-command', '解绑账号') . " 进行解绑操作.", $fromQQ, $fromGroup);
                return;
            }
            if(!isset($args[1]))
            {
                $this->sendMessage('你输入了错误的指令!' . CQLib::RETURN . "用法: 绑定账号 <游戏名称> <随机码>\n请先在游戏中使用指令 /" . $this->getConfig()->get('random-code-command', '随机码') . ' 获取随机码再进行绑定', $fromQQ, $fromGroup);
                return;
            }
            $name = strtolower($args[0]);
            if($qq = $this->getQQByName($name))
            {
                $this->sendMessage('绑定失败: 该游戏账号已被绑定至QQ ' . CQLib::At($qq) . "($qq)", $fromQQ, $fromGroup);
                return;
            }
            unset($qq);
            if(!isset($this->codes[$name]))
            {
                $this->sendMessage('请先到游戏中使用指令 /' . $this->getConfig()->get('random-code-command', '随机码') . ' 获取随机码再发送 绑定账号 <游戏名称> <随机码> 进行绑定!', $fromQQ, $fromGroup);
                return;
            }
            if($args[1] != $this->codes[$name])
            {
                $this->sendMessage('错误的随机码!', $fromQQ, $fromGroup);
                return;
            }
            $config = new Config($this->getDataFolder() . 'accounts/' . $fromQQ . '.dat', Config::YAML);
            $config->set('in-game-name', $name);
            $config->save();
            $config = new Config($this->getDataFolder() . 'players/' . $name . '.dat', Config::YAML);
            $config->set('account', $fromQQ);
            $config->save();
            unset($this->codes[$name]);
            $this->getCQLogger()->info($fromQQ . ' 已绑定游戏账号: ' . $name);
            $this->sendMessage('绑定游戏账号 ' . $name . ' 成功!', $fromQQ, $fromGroup);

        } else
        {
            if(!($ign = $this->getNameByQQ($fromQQ)))
            {
                $this->sendMessage('你的 QQ 还没有绑定过任何游戏账号!', $fromQQ, $fromGroup);
                return;
            }
            $config = new Config($this->getDataFolder() . 'accounts/' . $fromQQ . '.dat', Config::YAML);
            $name = strtolower($config->get('in-game-name'));
            unset($config);
            @unlink($this->getDataFolder() . 'accounts/' . $fromQQ . '.dat');
            @unlink($this->getDataFolder() . 'players/' . $name . '.dat');
            $this->getCQLogger()->info($fromQQ . ' 已解绑游戏账号: ' . $name);
            $this->sendMessage('解绑游戏账号 ' . $name . ' 成功!', $fromQQ, $fromGroup);

        }
    }

    public function sendMessage($message, $fromQQ, $fromGroup = null)
    {
        if($fromGroup === null)
        {
            $this->getKernel()->sendPrivateMessage($fromQQ, $message);
            return;
        }
        $this->getKernel()->sendGroupMessage($fromGroup, CQLib::At($fromQQ) . $message);
    }

    public function commandFromGroup($command, array $args, $fromQQ, $fromGroup)
    {
        $this->commandProgress($command, $args, $fromQQ, $fromGroup);
    }

    public function commandFromPrivate($command, array $args, $fromQQ)
    {
        $this->commandProgress($command, $args, $fromQQ);
    }

    /**
     * @param $qq
     * @return bool|mixed
     */
    public function getNameByQQ($qq)
    {
        if(!is_file($this->getDataFolder() . 'accounts/' . $qq . '.dat')) return false;

        $config = new Config($this->getDataFolder() . 'accounts/' . $qq . '.dat', Config::YAML);
        return $config->get('in-game-name');
    }

    /**
     * @param $name
     * @return bool|mixed
     */
    public function getQQByName($name)
    {
        $name = strtolower($name);
        if(!is_file($this->getDataFolder() . 'players/' . $name . '.dat')) return false;

        $config = new Config($this->getDataFolder() . 'players/' . $name . '.dat', Config::YAML);
        return $config->get('account');
    }

}
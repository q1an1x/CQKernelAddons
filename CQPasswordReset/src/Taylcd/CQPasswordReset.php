<?php

namespace Taylcd;

use Taylcd\CQKernel\CQLib;
use Taylcd\CQKernel\plugin\CQKPlugin;

class CQPasswordReset extends CQKPlugin
{
    public $api = 1.3;

    private $plugin = null;

    public function onLoad()
    {
        $this->saveDefaultConfig();
        $this->reloadConfig();

        foreach(['SimpleAuth'] as $plugin)
        {
            if($this->getServer()->getPluginManager()->getPlugin($plugin) !== null)
            {
                $this->plugin = $plugin;
                $this->getLogger()->info('检测到 ' . $plugin . ' 插件，密码重置功能启用.');
            }
        }
        if($this->plugin == null)
        {
            $this->getLogger()->warning('未检测到任何兼容的登录插件，用户将无法进行密码重置!');
        }
    }

    public function onEnable()
    {
        $this->registerCommand('重置密码');
    }

    public function commandFromGroup($command, array $args, $fromQQ, $fromGroup)
    {
        $this->getKernel()->sendGroupMessage($fromGroup, CQLib::At($fromQQ) . '请使用私聊进行密码重置!');
    }

    public function commandFromPrivate($command, array $args, $fromQQ)
    {
        if(!isset($args[0]))
        {
            $this->getKernel()->sendPrivateMessage($fromQQ,"你输入了错误的指令!\n用法: 重置密码 <新密码>");
            return;
        }
        if(strlen($args[0]) < $this->getConfig()->get('min-password-length', 4))
        {
            $this->getKernel()->sendPrivateMessage($fromQQ,'密码重置失败! 密码最少应当多于 ' . $this->getConfig()->get('min-password-length', 4) . ' 个字符.');
            return;
        }
        if(strlen($args[0]) > $this->getConfig()->get('max-password-length', 20))
        {
            $this->getKernel()->sendPrivateMessage($fromQQ,'密码重置失败! 密码最多应当少于 ' . $this->getConfig()->get('max-password-length', 20) . ' 个字符.');
            return;
        }
        /** @var CQBind $CQBind */
        $CQBind = $this->getServer()->getPluginManager()->getPlugin('CQBind');
        if($CQBind === null)
        {
            $this->getLogger()->warning('无法获取 CQBind 插件，密码重置失败');
            $this->getKernel()->sendPrivateMessage($fromQQ,'无法获取 CQBind 插件，密码重置失败');
            return;
        }
        if(!$name = $CQBind->getNameByQQ($fromQQ))
        {
            $this->getKernel()->sendPrivateMessage($fromQQ,'你的 QQ 还未绑定任何游戏账号!');
            return;
        }
        $this->getCQLogger()->info($fromQQ . ' 已为绑定游戏账号 ' . $name . ' 进行密码重置');
        $this->getKernel()->sendPrivateMessage($fromQQ, $this->setPassword($name, $args[0]));
    }

    public function setPassword($name, $password)
    {
        switch($this->plugin)
        {
            case 'SimpleAuth':
                /** @var \SimpleAuth\SimpleAuth $SimpleAuth */
                $SimpleAuth = $this->getServer()->getPluginManager()->getPlugin('SimpleAuth');

                $player = $this->getServer()->getOfflinePlayer($name);
                if($player === null or !$SimpleAuth->getDataProvider()->isPlayerRegistered($player)) return "你绑定的游戏账号并没有被注册!";
                $SimpleAuth->getDataProvider()->unregisterPlayer($player);
                $SimpleAuth->getDataProvider()->registerPlayer($player, bin2hex(hash("sha512", $password . $name, true) ^ hash("whirlpool", $name . $password, true)));
                return "密码重置完成!";
            default:
                return "服务器密码重置功能暂时不可用!";
        }
    }
}
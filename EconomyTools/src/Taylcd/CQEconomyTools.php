<?php

namespace Taylcd;

use onebone\economyapi\EconomyAPI;
use Taylcd\CQKernel\CQHandler;
use Taylcd\CQKernel\CQLib;
use Taylcd\CQKernel\plugin\CQKPlugin;

class CQEconomyTools extends CQKPlugin
{
    public $api = 3.1;

    public function onLoad()
    {
        $this->saveDefaultConfig();
        $this->reloadConfig();
    }

    public function onEnable()
    {
        foreach($this->getConfig()->getAll() as $command)
        {
            $this->registerCommand($command);
        }
    }

    public function commandFromPrivate($command, array $args, $fromQQ)
    {
        $this->commandProgress($command, $args, $fromQQ);
    }

    public function commandFromGroup($command, array $args, $fromQQ, $fromGroup)
    {
        $this->commandProgress($command, $args, $fromQQ, $fromGroup);
    }

    public function sendMessage($message, $fromQQ, $fromGroup = null)
    {
        if($fromGroup === null)
        {
            CQHandler::sendPrivateMessage($fromQQ, $message);
            return;
        }
        CQHandler::sendGroupMessage($fromGroup, CQLib::At($fromQQ) . $message);
    }

    public function commandProgress($command, array $args, $fromQQ, $fromGroup = null)
    {
        /** @var EconomyAPI $EconomyAPI */
        $EconomyAPI = $this->getServer()->getPluginManager()->getPlugin('EconomyAPI');
        if($EconomyAPI == null)
        {
            $this->getLogger()->warning('经济数据查询失败: 未能获取到 EconomyAPI');
            $this->sendMessage('暂时无法进行经济数据查询!', $fromQQ, $fromGroup);
            return;
        }

        if($command == $this->getConfig()->get('inquire-command', '经济查询'))
        {
            if(!isset($args[0]))
            {
                /** @var CQBind $CQBind */
                $CQBind = $this->getServer()->getPluginManager()->getPlugin('CQBind');
                if($CQBind == null)
                {
                    $this->sendMessage('请发送 ' . $this->getConfig()->get('inquire-command', '经济查询') . ' <玩家名称> 来进行查询!', $fromQQ, $fromGroup);
                    return;
                }
                if(!($name = $CQBind->getNameByQQ($fromQQ)))
                {
                    $this->sendMessage("你并没有绑定任何游戏账号，请先进行绑定。\n如果你希望查询他人的经济信息，" . '请发送 ' . $this->getConfig()->get('inquire-command', '经济查询') . ' <玩家名称> 来进行查询!', $fromQQ, $fromGroup);
                    return;
                }
                unset($CQBind);
            } else {

                if(!$this->getConfig()->get('allow-check-others-economy', true) and !$this->getKernel()->isCQAdmin($fromQQ))
                {
                    $this->sendMessage("你不是管理员，无法查询他人的经济信息。\n" . '请进行账号绑定后进行经济查询!', $fromQQ, $fromGroup);
                    return;
                }
                $name = $args[0];
            }
            if(($money = $EconomyAPI->myMoney($name)) < 1)
            {
                $this->sendMessage($name . ' 未注册经济账户，查询失败!', $fromQQ, $fromGroup);
                return;
            }
            $this->sendMessage($name . ' 当前拥有 ' . $EconomyAPI->getMonetaryUnit() . $money . ' 余额。', $fromQQ, $fromGroup);
            return;
        }
        /** @var CQBind $CQBind */
        $CQBind = $this->getServer()->getPluginManager()->getPlugin('CQBind');
        if($CQBind == null)
        {
            $this->sendMessage('暂时无法进行转账操作!', $fromQQ, $fromGroup);
            return;
        }
        if(!($name = $CQBind->getNameByQQ($fromQQ)))
        {
            $this->sendMessage("你并没有绑定任何游戏账号，请先进行绑定。", $fromQQ, $fromGroup);
            return;
        }
        if(!isset($args[1]) or !is_numeric($args[1]))
        {
            $this->sendMessage("你发送了错误的指令!\n用法: " . $this->getConfig()->get('transfer-command', '转账') . ' <对方名称> <转账金额>', $fromQQ, $fromGroup);
            return;
        }
        if(strtolower($args[0]) == $name)
        {
            $this->sendMessage("你不能向自己转账!", $fromQQ, $fromGroup);
            return;
        }
        if($EconomyAPI->reduceMoney($name, $args[1]) === EconomyAPI::RET_INVALID)
        {
            $this->sendMessage("你的账户中没有足够的金额，转账失败!", $fromQQ, $fromGroup);
            return;
        }
        if($EconomyAPI->addMoney($args[0], $args[1]) === EconomyAPI::RET_NO_ACCOUNT)
        {
            $EconomyAPI->addMoney($name, $args[1]);
            $this->sendMessage("对方账户不存在，转账操作没有完成，金额已退回到你的账户中。", $fromQQ, $fromGroup);
            return;
        }
        $this->sendMessage("转账成功! " . $EconomyAPI->getMonetaryUnit() . $args[1] . ' 已转入 ' . $args[0] . ' 的账户中。', $fromQQ, $fromGroup);
        if($qq = $CQBind->getQQByName($args[0]))
        {
            CQHandler::sendPrivateMessage($qq, "$name(QQ: $fromQQ) 向你的游戏账户中转入了 " . $EconomyAPI->getMonetaryUnit() . $args[1] . '!');
        }
    }
}
<?php

namespace Taylcd;

use Taylcd\CQKernel\CQLib;
use Taylcd\CQKernel\plugin\CQKPlugin;

class CQCommandExecutor extends CQKPlugin
{
    public $api = 2.3;

    public function onLoad()
    {
        $this->saveDefaultConfig();
        $this->reloadConfig();
    }

    public function onEnable()
    {
        $this->registerCommand($this->getConfig()->get('execute-command', '执行指令'));
    }

    public function commandProgress(array $args, $fromQQ, $fromGroup = '')
    {
        if(!$this->getKernel()->isCQAdmin($fromQQ))
        {
            $this->sendMessage('您不是机器人的管理员, 无法执行指令!#{C}#' . $fromQQ . '#{C}#' . $fromGroup);
            return;
        }
        $commandLine = '';
        foreach($args as $arg)
        {
            $commandLine .= $arg . ' ';
        }
        $this->getServer()->dispatchCommand(new CQCommandSender($fromQQ, $fromGroup), $commandLine);
    }

    public function sendMessage($message)
    {
        $data = explode('#{C}#', $message);
        $message = array_shift($data);
        if(!$message) return;
        $fromQQ = array_shift($data);
        $fromGroup = array_shift($data);
        if($fromGroup == '')
        {
            $this->getKernel()->sendPrivateMessage($fromQQ, $message);
            return;
        }
        $this->getKernel()->sendGroupMessage($fromGroup, CQLib::At($fromQQ) . CQLib::RETURN_KEY . $message);
    }

    public function commandFromGroup($command, array $args, $fromQQ, $fromGroup)
    {
        $this->commandProgress($args, $fromQQ, $fromGroup);
    }

    public function commandFromPrivate($command, array $args, $fromQQ)
    {
        $this->commandProgress($args, $fromQQ);
    }
}
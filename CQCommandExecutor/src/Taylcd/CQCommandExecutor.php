<?php

namespace Taylcd;

use Taylcd\CQKernel\CQLib;
use Taylcd\CQKernel\plugin\CQKPlugin;

class CQCommandExecutor extends CQKPlugin
{
    public $api = 2.3;

    private $alias = [];

    public function onLoad()
    {
        $this->saveDefaultConfig();
        $this->reloadConfig();

        $this->alias = $this->getConfig()->get('alias');
    }

    public function onEnable()
    {
        $this->registerCommand($this->getConfig()->get('execute-command', '执行指令'));
        foreach($this->alias as $name => $info) $this->registerCommand($name);
    }

    public function commandProgress($command, array $args, $fromQQ, $fromGroup = '')
    {
        if(isset($this->alias[$command]))
        {
            if(isset($this->alias[$command]['permission']) and $this->alias[$command]['permission'] === false and !$this->getKernel()->isCQAdmin($fromQQ))
            {
                $this->sendMessage('该指令仅管理员可用!#{C}#' . $fromQQ . '#{C}#' . $fromGroup);
                return;
            }
            array_unshift($args, $this->alias[$command]['command']);
            $this->commandProgress($this->getConfig()->get('execute-command', '执行指令'), $args, $fromQQ, $fromGroup);
            return;
        } else
        {
            if(!$this->getKernel()->isCQAdmin($fromQQ))
            {
                $this->sendMessage('您不是机器人的管理员, 无法执行指令!#{C}#' . $fromQQ . '#{C}#' . $fromGroup);
                return;
            }
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
        $this->commandProgress($command, $args, $fromQQ, $fromGroup);
    }

    public function commandFromPrivate($command, array $args, $fromQQ)
    {
        $this->commandProgress($command, $args, $fromQQ);
    }
}
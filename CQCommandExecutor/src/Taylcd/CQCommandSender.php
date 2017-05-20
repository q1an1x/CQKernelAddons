<?php

namespace Taylcd;

use pocketmine\command\CommandSender;
use pocketmine\event\TextContainer;
use pocketmine\permission\PermissibleBase;
use pocketmine\permission\PermissionAttachment;
use pocketmine\plugin\Plugin;
use pocketmine\Server;

class CQCommandSender implements CommandSender
{
    private $perm, $fromQQ, $fromGroup;

    public function __construct($fromQQ, $fromGroup = '')
    {
        $this->fromQQ = $fromQQ;
        $this->fromGroup = $fromGroup;
        $this->perm = new PermissibleBase($this);
    }

    /**
     * @param \pocketmine\permission\Permission|string $name
     *
     * @return bool
     */
    public function isPermissionSet($name)
    {
        return $this->perm->isPermissionSet($name);
    }

    /**
     * @param \pocketmine\permission\Permission|string $name
     *
     * @return bool
     */
    public function hasPermission($name)
    {
        return $this->perm->hasPermission($name);
    }

    /**
     * @param Plugin $plugin
     * @param string $name
     * @param bool   $value
     *
     * @return \pocketmine\permission\PermissionAttachment
     */
    public function addAttachment(Plugin $plugin, $name = null, $value = null)
    {
        return $this->perm->addAttachment($plugin, $name, $value);
    }

    /**
     * @param PermissionAttachment $attachment
     *
     * @return void
     */
    public function removeAttachment(PermissionAttachment $attachment)
    {
        $this->perm->removeAttachment($attachment);
    }

    public function recalculatePermissions()
    {
        $this->perm->recalculatePermissions();
    }

    /**
     * @return \pocketmine\permission\PermissionAttachmentInfo[]
     */
    public function getEffectivePermissions()
    {
        return $this->perm->getEffectivePermissions();
    }

    /**
     * @return bool
     */
    public function isPlayer()
    {
        return false;
    }

    /**
     * @return \pocketmine\Server
     */
    public function getServer()
    {
        return Server::getInstance();
    }

    /**
     * @param string $message
     */
    public function sendMessage($message)
    {
        if($message instanceof TextContainer){
            $message = $this->getServer()->getLanguage()->translate($message);
        }else{
            $message = $this->getServer()->getLanguage()->translateString($message);
        }
        /** @var CQCommandExecutor $plugin */
        $plugin = $this->getServer()->getPluginManager()->getPlugin('CQCommandExecutor');
        $plugin->sendMessage($message . '#{C}#' . $this->fromQQ . '#{C}#' . $this->fromGroup);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "CQ";
    }

    /**
     * @return bool
     */
    public function isOp()
    {
        return true;
    }

    /**
     * @param bool $value
     */
    public function setOp($value)
    {

    }
}
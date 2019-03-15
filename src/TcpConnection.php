<?php

namespace Mix\Tcp;

use Mix\Core\Component\AbstractComponent;
use Mix\Core\Component\ComponentInterface;

/**
 * Class TcpConnection
 * @package Mix\Tcp
 * @author LIUJIAN <coder.keda@gmail.com>
 */
class TcpConnection extends AbstractComponent
{

    /**
     * 服务
     * @var \Swoole\Server
     */
    public $server;

    /**
     * 文件描述符
     * @var int
     */
    public $fd;

    /**
     * 前置初始化
     * @return void
     */
    public function beforeInitialize(\Swoole\Server $server, int $fd)
    {
        $this->server = $server;
        $this->fd     = $fd;
        // 设置组件状态
        $this->setStatus(ComponentInterface::STATUS_RUNNING);
    }

    /**
     * 前置处理事件
     */
    public function onBeforeInitialize()
    {
        // 移除设置组件状态
    }

    /**
     * 发送
     * @param string $data
     * @return bool
     */
    public function send(string $data)
    {
        return $this->server->send($this->fd, $data);
    }

    /**
     * 获取客户端信息
     * @return array|bool
     */
    public function getClientInfo()
    {
        return $this->server->getClientInfo($this->fd);
    }

    /**
     * 关闭连接
     * @param bool $reset
     */
    public function disconnect($reset = false)
    {
        $this->server->close($this->fd, $reset);
    }

}

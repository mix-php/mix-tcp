<?php

namespace Mix\Tcp;

use Mix\Core\Component\AbstractComponent;
use Mix\Core\Component\ComponentInterface;
use Mix\Helper\JsonHelper;

/**
 * Class Error
 * @package Mix\Tcp
 * @author LIUJIAN <coder.keda@gmail.com>
 */
class Error extends AbstractComponent
{

    /**
     * 协程模式
     * @var int
     */
    public static $coroutineMode = ComponentInterface::COROUTINE_MODE_REFERENCE;

    /**
     * 错误级别
     * @var int
     */
    public $level = E_ALL;

    /**
     * 异常处理
     * @param $e
     */
    public function handleException($e)
    {
        // 错误参数定义
        $statusCode = $e instanceof \Mix\Exception\NotFoundException ? 404 : 500;
        $errors = [
            'status'  => $statusCode,
            'code'    => $e->getCode(),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'type'    => get_class($e),
            'trace'   => $e->getTraceAsString(),
        ];
        // 日志处理
        if (!($e instanceof \Mix\Exception\NotFoundException)) {
            self::log($errors);
        }
        // 发送客户端
        self::send($errors);
        // 关闭连接
        self::close($errors);
    }

    /**
     * 写入日志
     * @param $errors
     */
    protected static function log($errors)
    {
        // 构造消息
        $message = "{$errors['message']}" . PHP_EOL;
        $message .= "[type] {$errors['type']} [code] {$errors['code']}" . PHP_EOL;
        $message .= "[file] {$errors['file']} [line] {$errors['line']}" . PHP_EOL;
        $message .= "[trace] {$errors['trace']}" . PHP_EOL;
        if (\Mix::$app->isRunning('request')) {
            $message .= '$SERVER' . substr(print_r(\Mix::$app->request->server(), true), 5);
            $message .= '$HEADER' . substr(print_r(\Mix::$app->request->header(), true), 5);
            $message .= '$GET' . substr(print_r(\Mix::$app->request->get(), true), 5);
            $message .= '$POST' . substr(print_r(\Mix::$app->request->post(), true), 5, -1);
        }
        if (\Mix::$app->isRunning('frame')) {
            $message .= '$FRAME' . substr(print_r((array)\Mix::$app->frame->getRawFrame(), true), 5);
        }
        // 写入
        $errorType = \Mix\Core\Error::getType($errors['code']);
        switch ($errorType) {
            case 'error':
                \Mix::$app->log->error($message);
                break;
            case 'warning':
                \Mix::$app->log->warning($message);
                break;
            case 'notice':
                \Mix::$app->log->notice($message);
                break;
        }
    }

    /**
     * 发送客户端
     * @param $errors
     */
    protected static function send($errors)
    {
        if (!\Mix::$app->isRunning('tcp')) {
            return;
        }
        $errors['trace'] = explode("\n", $errors['trace']);
        $statusCode = $errors['status'];
        if (!\Mix::$app->appDebug) {
            if ($statusCode == 404) {
                $errors = [
                    'status'  => 404,
                    'message' => $errors['message'],
                ];
            }
            if ($statusCode == 500) {
                $errors = [
                    'status'  => 500,
                    'message' => '服务器内部错误',
                ];
            }
        }
        $data = JsonHelper::encode($errors);
        \Mix::$app->tcp->send($data);
    }

    /**
     * 关闭连接
     * @param $errors
     */
    protected static function close($errors)
    {
        if (\Mix::$app->isRunning('tcp')) {
            \Mix::$app->tcp->disconnect();
        }
    }

}

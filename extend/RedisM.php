<?php

class RedisM
{

    protected $options = [
        'host'       => '127.0.0.1',
        'port'       => 6379,
        'password'   => '',
        'select'     => 0,
        'timeout'    => 0,
        'expire'     => 0,
        'persistent' => false,
        'prefix'     => '',
    ];

    protected $handler;

    public function __construct(array $options = [])
    {
        if (!extension_loaded('Redis')) {
            throw new \BadFunctionCallException('not support: redis');
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $func          = $this->options['persistent'] ? 'pconnect' : 'connect';
        $this->handler = new \Redis;
        $this->handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);

        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            $this->handler->select($this->options['select']);
        }
    }


    /**
     * 根据参数 count 的值，移除列表中与参数 value 相等的元素。
     * @param $key
     * @param $value
     */
    public function lrem($key, $value)
    {
        return $this->handler->lRem($key, $value, 1);
    }


    public function lpop($key)
    {
        return $this->handler->lPop($key);
    }


    public function rm($key)
    {
        return $this->handler->delete($key);
    }


    /**
     * 将一个或多个值 value 插入到列表 key 的表尾(最右边)。
     * @param $key
     * @param $value
     */
    public function rpush($key, $value)
    {
        return $this->handler->rPush($key, $value);
    }


    public function lrange($key)
    {
        return $this->handler->lRange($key, 0, -1);
    }

    public function get($key)
    {
        return $this->handler->get($key);
    }

    public function ttl($key)
    {
        return $this->handler->ttl($key);
    }

    public function expire($key, $seconds)
    {
        return $this->handler->expire($key, $seconds);
    }
}
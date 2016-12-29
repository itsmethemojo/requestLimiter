<?php

namespace Itsmethemojo;

use Redis;

class RequestLimiter
{

    private $redis = null;

    const BANNED_PREFIX = 'banned_';
    const MINUTE_COUNT_PREFIX = 'minute_';
    const ACTION_COUNT_PREFIX = 'action_';

    const DEFAULT_MINUTE_LIMIT = 5;
    const DEFAULT_BANN_TIME = 10;

    // no singleton pattern, so just instanciate this only once per request or use parameter
    public function __construct($isFirstUse = true, $redis = null)
    {

        if ($redis === null) {
            //start dirrty, maybe read config
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1');
        } else {
            $this->redis = $redis;
        }

        if ($isFirstUse) {
            $this->setMinuteCount();
        }
    }

    public function isLimitReached()
    {
        $bannedKey = $this->getBannedKey();
        if ($this->redis->exists($bannedKey)) {
            return true;
        }
        return false;
    }

    // if you want to bann clients for specific bad actions
    public function countLimtedAction($actionName, $actionIntervall, $actionLimit, $bannTime)
    {
        return $this->countAction(
            $this::ACTION_COUNT_PREFIX . $this->getIncomingIp() . $actionName,
            $actionIntervall,
            $actionLimit,
            $bannTime
        );
    }

    // retrieve IP, check also for forwarded/proxied requests
    private function getIncomingIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    // returns the key who marks if an ip is banned or not
    private function getBannedKey()
    {
        return $this::BANNED_PREFIX . $this->getIncomingIp();
    }

    private function countAction($key, $keyLifetime, $limit, $bannTime)
    {
        $bannedKey = $this->getBannedKey();

        // first call, create counter with lifetime
        if (!$this->redis->exists($key)) {
            $this->redis->incr($key);
            $this->redis->expire($key, $keyLifetime);
            return;
        }

        // is rate limit reached, create banned key with lifetime
        if (intval($this->redis->get($key)) >= $limit) {
            $this->redis->incr($bannedKey);
            $this->redis->expire($bannedKey, $bannTime);
            return;
        }

        // normal call, increment counter
        $this->redis->incr($key);
    }


    private function setMinuteCount()
    {
        return $this->countAction(
            $this::MINUTE_COUNT_PREFIX . $this->getIncomingIp() . (round(time()/60)),
            65,
            $this::DEFAULT_MINUTE_LIMIT,
            $this::DEFAULT_BANN_TIME
        );
    }
}

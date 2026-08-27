<?php

declare(strict_types=1);

namespace Misaf\LaravelAuthifyLog\Support;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class CacheStoreResolver
{
    public function __construct(
        protected CacheManager $cache,
        protected ConfigRepository $config,
    ) {}

    public function store(): CacheRepository
    {
        $store = $this->config->get('authify-log.cache');

        return $this->cache->store(is_string($store) ? $store : null);
    }
}

<?php
/*
 * Copyright (c) 2012,2020 Satoshi Fukutomi <info@fuktommy.com>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHORS AND CONTRIBUTORS ``AS IS'' AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED.  IN NO EVENT SHALL THE AUTHORS OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS
 * OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF
 * SUCH DAMAGE.
 */
namespace Fuktommy\DaniRss\Model;

use Fuktommy\DaniRss\Entity\Series;
use Fuktommy\Db\Cache;
use Fuktommy\WebIo\Resource;


class WebPageFetcher
{
    /** @var Fuktommy\WebIo\Resource */
    private $resource;

    public function __construct(Resource $resource)
    {
        $this->resource = $resource;
    }

    /**
     * @return Fuktommy\DaniRss\Entity\Series[]
     */
    public function fetch()
    {
        $url = $this->resource->config['series_list_url'];
        $cacheTime = $this->resource->config['cache_time'];
        $feedSize = $this->resource->config['feed_size'];

        $log = $this->resource->getLog('fetcher');

        $cache = new Cache($this->resource);
        $cache->setUp();
        try {
            $cache->expire();
        } catch (\Exception $e) {
            $log->warning("failed to expire cache: {$e->getMessage()}");
        }
        
        $html = $cache->get($url);
        if ($html === null) {
            $log->info("fetching $url");
            $html = file_get_contents($url);
            if (empty($html)) {
                $log->warning('failed to fetch: ' . implode('; ', $http_response_header));
                $html = '';
            }
            $cache->set($url, $html, $cacheTime);
        }
        $cache->commit();

        $serieses = [];
        $lines = explode("\n", $html);
        foreach ($lines as $line) {
            if (preg_match('/\A<a href="([^"]+)">([^<]+)<\/a>\r*\z/', $line, $matches)) {
                $serieses[] = new Series([
                    'url' => htmlspecialchars_decode($matches[1]),
                    'title' => htmlspecialchars_decode($matches[2]),
                    'date' => '',
                ]);
            }
        }

        $seriesList = new SeriesList($this->resource);
        $seriesList->setUp();

        try {
            $seriesList->append($serieses);
        } catch (\Exception $e) {
            $log->warning("failed to append series: {$e->getMessage()}");
        }
        $seriesList->commit();

        return $seriesList->getRecent($feedSize);
    }
}
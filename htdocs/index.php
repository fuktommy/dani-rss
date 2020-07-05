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
namespace Fuktommy\DaniRss;

require_once __DIR__ . '/../libs/Fuktommy/Bootstrap.php';
use Fuktommy\Bootstrap;
use Fuktommy\Db\Cache;
use Fuktommy\WebIo;


class IndexAction implements WebIo\Action
{
    /**
     * @param Fuktommy\WebIo\Context $context
     */
    public function execute(WebIo\Context $context)
    {
        $url = $context->config['series_list_url'];
        $cacheTime = $context->config['cache_time'];
        $feedSize = $context->config['feed_size'];

        $cache = new Cache($context->getResource());
        $cache->setUp();
        $cache->expire();
        
        $html = $cache->get($url);
        if ($html === null) {
            $html = file_get_contents($url);
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

        $seriesList = new SeriesList($context->getResource());
        $seriesList->setUp();
        $seriesList->append($serieses);
        $seriesList->commit();

        $recentSerieses = $seriesList->getRecent($feedSize);

        $smarty = $context->getSmarty();
        $smarty->assign('config', $context->config);
        $smarty->assign('serieses', $recentSerieses);

        $smarty->display('atom.tpl');
    }
}


(new Controller())->run(new IndexAction(), Bootstrap::getContext());

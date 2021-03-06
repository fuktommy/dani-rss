<?php
/*
 * Copyright (c) 2020 Satoshi Fukutomi <info@fuktommy.com>.
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
use Fuktommy\Db\Migration;
use Fuktommy\WebIo\Resource;

class SeriesList
{
    /**
     * @var PDO
     */
    private $db;

    /**
     * Constructor.
     * @param \Fuktommy\WebIo\Resource $resource
     * @throws PDOException
     */
    public function __construct(Resource $resource)
    {
        $this->db = $resource->getDb();
    }

    /**
     * Set up storage.
     *
     * Call it first.
     */
    public function setUp()
    {
        $this->db->beginTransaction();
        $migration = new Migration($this->db);
        $migration->execute(
            "CREATE TABLE `series`"
            . " (`title` CHAR PRIMARY KEY NOT NULL,"
            . "  `url` CHAR NOT NULL,"
            . "  `date` CHAR NOT NULL)"
        );
        $migration->execute(
            "CREATE UNIQUE INDEX `series_url` ON `series` (`url`)"
        );
        $migration->execute(
            "CREATE INDEX `series_date` ON `series` (`date`)"
        );
    }

    /**
     * Select recent items.
     * @param int $size
     * @return Fuktommy\DaniRss\Entity\Series[]
     * @throws PDOException
     */
    public function getRecent($size)
    {
        $state = $this->db->prepare(
            "SELECT `title`, `url`, `date` FROM `series`"
            . " ORDER BY `date` DESC"
            . " LIMIT :size"
        );
        $state->execute(array('size' => $size));
        return array_map(function ($r) { return new Series($r); },
                         $state->fetchAll(\PDO::FETCH_ASSOC));
    }

    private function timeFormat($unixtime)
    {
        return gmstrftime('%FT%TZ', $unixtime);
    }

    /**
     * Append serieses to list.
     * @param Fuktommy\DaniRss\Entity\Series[]
     * @throws PDOException
     */
    public function append($serieses)
    {
        $date = $this->timeFormat(time());

        // for corrected titles
        $state = $this->db->prepare(
            "UPDATE `series`"
            . " SET `title` = :title, `date` = :date"
            . " WHERE `url` = :url AND `title` <> :title"
        );
        foreach ($serieses as $seriese) {
            $state->execute([
                'title' => $seriese->title,
                'url' => $seriese->url,
                'date' => $date,
            ]);
        }

        // for updated urls
        // do not update date. it is weekly operation.
        $state = $this->db->prepare(
            "UPDATE `series`"
            . " SET `url` = :url"
            . " WHERE `title` = :title AND `url` <> :url"
        );
        foreach ($serieses as $seriese) {
            $state->execute([
                'title' => $seriese->title,
                'url' => $seriese->url,
            ]);
        }

        // for newarrival contents
        $state = $this->db->prepare(
            "INSERT OR IGNORE INTO `series`"
            . " (`title`, `url`, `date`)"
            . " VALUES (:title, :url, :date)"
        );
        foreach ($serieses as $seriese) {
            $state->execute([
                'title' => $seriese->title,
                'url' => $seriese->url,
                'date' => $date,
            ]);
        }
    }

    /**
     * Commit transaction.
     *
     * Call it after append()
     */
    public function commit()
    {
        $this->db->commit();
    }
}

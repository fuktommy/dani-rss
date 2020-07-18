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

namespace Fuktommy\Db;
use Fuktommy\Db\Migration;

class Cache
{
    /**
     * @var PDO
     */
    private $db;

    /**
     * Constructor.
     * @throws PDOException
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
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
            "CREATE TABLE `cache`"
            . " (`id` CHAR PRIMARY KEY NOT NULL,"
            . "  `value` TEXT NOT NULL,"
            . "  `expired` INTEGER NOT NULL)"
        );
        $migration->execute(
            "CREATE INDEX `cache_expired` ON `cache` (`expired`)"
        );
    }

    /**
     * Delete expired records.
     * @throws PDOException
     */
    public function expire()
    {
        $state = $this->db->prepare(
            "DELETE FROM `cache`"
            . " WHERE `expired` < :expired"
        );
        $state->execute(['expired' => time()]);
    }

    /**
     * Select record
     * @param string $id
     * @return string|null
     * @throws PDOException
     */
    public function get($id)
    {
        $state = $this->db->prepare(
            "SELECT `value` FROM `cache`"
            . " WHERE `id` = :id"
            . "   AND `expired` >= :expired"
        );
        $state->execute(['id' => $id, 'expired' => time()]);
        $record = $state->fetch(\PDO::FETCH_ASSOC);
        if ($record === false) {
            return null;
        } else {
            return $record['value'];
        }
    }

    /**
     * Delete record.
     * @param string $id
     * @throws PDOException
     */
    public function delete()
    {
        $state = $this->db->prepare(
            "DELETE FROM `cache`"
            . " WHERE `id` = :id"
        );
        $state->execute(['id' => $id]);
    }

    /**
     * Set value.
     * @param string $id
     * @param string $value
     * @param int $cacheTime
     * @throws PDOException
     */
    public function set($id, $value, $cacheTime)
    {
        $state = $this->db->prepare(
            "INSERT OR REPLACE INTO `cache`"
            . " (`id`, `value`, `expired`)"
            . " VALUES (:id, :value, :expired)"
        );
        $state->execute([
            'id' => $id,
            'value' => $value,
            'expired' => time() + $cacheTime,
        ]);
    }

    /**
     * Commit transaction.
     */
    public function commit()
    {
        $this->db->commit();
    }
}

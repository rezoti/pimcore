<?php
/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Model\Dao;

use Pimcore\Cache;
use Pimcore\Db;

abstract class AbstractDao implements DaoInterface
{
    use DaoTrait;

    const CACHEKEY = 'system_resource_columns_';

    /**
     * @var \Pimcore\Db\ConnectionInterface
     */
    public $db;

    public function configure()
    {
        $this->db = Db::get();
    }

    public function beginTransaction(bool $isNested = false)
    {
        // MySQL/MariaDB do not support nested transaction, so we just ignore the transaction if
        // the transaction is started within another transaction (e.g. when deleting elements)
        if($isNested === false) {
            $this->db->beginTransaction();
        }
    }

    public function commit(bool $isNested = false)
    {
        if($isNested === false) {
            $this->db->commit();
        }
    }

    public function rollBack(bool $isNested = false)
    {
        if($isNested === false) {
            $this->db->rollBack();
        }
    }

    /**
     * @param string $table
     * @param bool $cache
     *
     * @return array|mixed
     */
    public function getValidTableColumns($table, $cache = true)
    {
        $cacheKey = self::CACHEKEY . $table;

        if (\Pimcore\Cache\Runtime::isRegistered($cacheKey)) {
            $columns = \Pimcore\Cache\Runtime::get($cacheKey);
        } else {
            $columns = Cache::load($cacheKey);

            if (!$columns || !$cache) {
                $columns = [];
                $data = $this->db->fetchAll('SHOW COLUMNS FROM ' . $table);
                foreach ($data as $d) {
                    $columns[] = $d['Field'];
                }
                Cache::save($columns, $cacheKey, ['system', 'resource'], null, 997);
            }

            \Pimcore\Cache\Runtime::set($cacheKey, $columns);
        }

        return $columns;
    }

    /** Clears the column information for the given table.
     * @param $table
     */
    public function resetValidTableColumnsCache($table)
    {
        $cacheKey = self::CACHEKEY . $table;
        \Pimcore\Cache\Runtime::getInstance()->offsetUnset($cacheKey);
        Cache::clearTags(['system', 'resource']);
    }
}

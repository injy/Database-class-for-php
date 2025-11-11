<?php
/**
 * @license MIT
 * @copyright KC
 */

namespace Kc\Database\Core;

/**
 * 表字段缓存管理
 */
class TableColumnCache
{
    private static array $cache = [];
    
    /**
     * 获取表字段列表（带缓存）
     */
    public static function get(int $tableId, InjectablePdo $pdo): array
    {
        if (!isset(self::$cache[$tableId])) {
            self::$cache[$tableId] = self::loadColumns($tableId, $pdo);
        }
        return self::$cache[$tableId];
    }
    
    /**
     * 清除缓存
     */
    public static function clear(int $tableId = null): void
    {
        if ($tableId === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$tableId]);
        }
    }
    
    /**
     * 加载表字段
     */
    private static function loadColumns(int $tableId, InjectablePdo $pdo): array
    {
        $columns = $pdo->sql_getTableColumns($tableId);
        return array_column($columns, 'COLUMN_NAME');
    }
}
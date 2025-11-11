<?php
namespace Kc\Database\Core;

use Kc\Database\Config\DatabaseConfigInterface;

class InjectablePdo
{
    protected const log_type = self::class;
    private static array $pdoPool = [];
    private DatabaseConfigInterface $config;

    /** 默认允许的操作符列表 */
    public const DEFAULT_ALLOWED_OPERATORS = ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'IN', 'NOT IN', 'IS NULL', 'IS NOT NULL'];

    public function __construct(DatabaseConfigInterface $config)
    {
        $this->config = $config;
    }

    /** 根据 tableId 获取 dbid（首位数字） */
    public function dbidFromTableId(int $tableId): int
    {
        return (int)substr((string)$tableId, 0, 1);
    }

    /** 获取 PDO 连接 */
    public function getPdo(int $dbid): \PDO
    {
        if (!isset(self::$pdoPool[$dbid])) {
            $dbInfo = $this->config->getDbInfo($dbid);
            $dbname = $this->config->getDbName($dbid);
            
            $dsn = "mysql:host={$dbInfo['host']};port={$dbInfo['port']};dbname={$dbname};charset=utf8mb4";
            self::$pdoPool[$dbid] = new \PDO($dsn, $dbInfo['user'], $dbInfo['pw'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }
        return self::$pdoPool[$dbid];
    }

    /** 根据 tableId 获取 PDO */
    public function pdoByTableId(int $tableId): \PDO
    {
        return $this->getPdo($this->dbidFromTableId($tableId));
    }

    /** 根据 tableId 获取表名 */
    public function tableNameFromId(int $tableId): string
    {
        return $this->config->getTableName($tableId);
    }

    /** 获取表字段（自动缓存） */
    public function getTableColumns(int $tableId): array
    {
        return TableColumnCache::get($tableId, $this);
    }

    /** 获取表字段（直接查询） */
    public function sql_getTableColumns(int $tableId): array
    {
        $sql = "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table";
        $pdo = $this->pdoByTableId($tableId);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':db' => $this->config->getDbName($this->dbidFromTableId($tableId)),
            ':table' => $this->tableNameFromId($tableId),
        ]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** 过滤字段，只保留表中存在的列 */
    public function filterFields(int $tableId, array $data): array
    {
        $cols = $this->getTableColumns($tableId);
        $out = [];
        foreach ($data as $k => $v) {
            $field = is_int($k) ? $v : $k;
            if (!is_string($field) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $field)) {
                continue;
            }
            if (in_array($field, $cols, true)) {
                if (is_int($k)) {
                    $out[] = $field;
                } else {
                    $out[$k] = trim($v);
                }
            }
        }
        return $out;
    }

    /** 规范化条件 */
    public function normalizeCondition(int $tableId, $key, $value, array $allowedOperators = self::DEFAULT_ALLOWED_OPERATORS): ?array
    {
        $field = is_string($key) ? $key : (is_array($value) && isset($value[0]) ? $value[0] : null);

        if (!is_string($field) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $field)) {
            return null;
        }

        $cols = $this->getTableColumns($tableId);
        if (!in_array($field, $cols, true)) {
            write_log("[warning] 在表{$tableId}中，字段名未找到: {$key}", self::log_type);
            return null;
        }

        if (is_array($value) && count($value) === 3 && is_string($value[0]) && is_string($value[1])) {
            $operator = strtoupper(trim($value[1]));
            if (!in_array($operator, $allowedOperators, true)) {
                $operator = '=';
            }
            return [
                'field' => $value[0],
                'operator' => $operator,
                'value' => $value[2]
            ];
        } elseif (is_array($value) && isset($value['field']) && isset($value['value'])) {
            $operator = strtoupper(trim($value['operator'] ?? '='));
            if (!in_array($operator, $allowedOperators, true)) {
                $operator = '=';
            }
            return [
                'field' => $value['field'],
                'operator' => $operator,
                'value' => $value['value']
            ];
        } else {
            return [
                'field' => $key,
                'operator' => '=',
                'value' => $value
            ];
        }
    }

    /** 验证并规范化排序参数 */
    public function validateOrder(int $tableId, ?string $order): string
    {
        $columns = $this->getTableColumns($tableId);

        if ($order === null) {
            if (empty($columns)) {
                write_log("[warning] 表 {$tableId} 的字段列表为空，请检查表是否存在或字段查询是否正常", self::log_type);
                return "";
            }
            $firstColumn = $columns[0];
            return "ORDER BY `{$firstColumn}` DESC";
        }

        $orderParts = explode(' ', trim($order));
        if (count($orderParts) !== 2 || !in_array(strtoupper($orderParts[1]), ['ASC', 'DESC'], true)) {
            write_log("[warning] 无效的排序参数: {$order}", self::log_type);
            $firstColumn = !empty($columns) ? $columns[0] : 'id';
            return "ORDER BY `{$firstColumn}` DESC";
        }

        $field = trim($orderParts[0], '`');
        if (!in_array($field, $columns, true)) {
            write_log("[warning] 排序字段未找到: {$field}", self::log_type);
            $firstColumn = !empty($columns) ? $columns[0] : 'id';
            return "ORDER BY `{$firstColumn}` DESC";
        }

        return "ORDER BY `{$field}` {$orderParts[1]}";
    }
}
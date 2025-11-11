<?php
namespace Kc\Database\Core;

use Kc\Database\Config\DatabaseConfigInterface;

class InjectableSql
{
    protected const log_type = self::class;
    private InjectablePdo $pdo;
    private DatabaseConfigInterface $config;

    public function __construct(InjectablePdo $pdo, DatabaseConfigInterface $config)
    {
        $this->pdo = $pdo;
        $this->config = $config;
    }

    /** INSERT */
    public function insert(int $tableId, array $data): bool
    {
        $table = $this->pdo->tableNameFromId($tableId);
        $data = $this->pdo->filterFields($tableId, $data);
        if (!$data) return false;

        $cols = array_keys($data);
        $ph   = array_map(fn($c) => ":$c", $cols);
        $sql  = "INSERT INTO `{$table}` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $ph) . ")";

        $stmt = $this->pdo->pdoByTableId($tableId)->prepare($sql);
        $bind = [];
        foreach ($data as $k => $v) $bind[":$k"] = $v;
        $res = $stmt->execute($bind);

        if ($res) write_log("INSERT {$table}: " . json_encode($data), self::log_type);
        return $res;
    }

    /** DELETE */
    public function delete(int $tableId, array $where): bool
    {
        $table = $this->pdo->tableNameFromId($tableId);
        $where = $this->pdo->filterFields($tableId, $where);
        if (!$where) return false;

        $parts = []; $bind = []; $i = 0;
        foreach ($where as $k => $v) {
            $i++; $p = ":w$i"; $parts[] = "`$k`=$p"; $bind[$p] = $v;
        }
        $sql  = "DELETE FROM `{$table}` WHERE " . implode(' AND ', $parts);
        $stmt = $this->pdo->pdoByTableId($tableId)->prepare($sql);
        $res  = $stmt->execute($bind);

        if ($res) write_log("DELETE {$table}: " . json_encode($where), self::log_type);
        return $res;
    }

    /** UPDATE */
    public function update(int $tableId, array $data, array $where): bool
    {
        $table = $this->pdo->tableNameFromId($tableId);
        $data  = $this->pdo->filterFields($tableId, $data);
        $where = $this->pdo->filterFields($tableId, $where);
        if (!$data || !$where) return false;

        $set = []; $bind = []; $i = 0;
        foreach ($data as $k => $v) { $i++; $p = ":s$i"; $set[] = "`$k`=$p"; $bind[$p] = $v; }
        $whr = [];
        foreach ($where as $k => $v) { $i++; $p = ":w$i"; $whr[] = "`$k`=$p"; $bind[$p] = $v; }

        $sql  = "UPDATE `{$table}` SET " . implode(',', $set) . " WHERE " . implode(' AND ', $whr);
        $stmt = $this->pdo->pdoByTableId($tableId)->prepare($sql);
        $res  = $stmt->execute($bind);

        if ($res) write_log("UPDATE {$table}: data=" . json_encode($data) . " where=" . json_encode($where), self::log_type);
        return $res;
    }

    /** SELECT 多行 */
    public function select(int $tableId, array $fields = ['*'], array $where = [], string $order = null, int $limit = 1000): array
    {
        $table = $this->pdo->tableNameFromId($tableId);
        $where = $this->pdo->filterFields($tableId, $where);
        $filteredFields = $fields === ['*'] ? ['*'] : $this->pdo->filterFields($tableId, $fields);

        $cols = $filteredFields === ['*'] ? '*' : ('`' . implode('`,`', $filteredFields) . '`');

        $bind = []; $parts = []; $i = 0;
        foreach ($where as $k => $v) {
            $i++; $p = ":w$i";
            $parts[] = "`$k` = $p";
            $bind[$p] = $v;
        }
        $sqlWhere = $parts ? ' WHERE ' . implode(' AND ', $parts) : '';

        $orderClause = $this->pdo->validateOrder($tableId, $order);

        $sql = "SELECT {$cols} FROM `{$table}` {$sqlWhere} {$orderClause} LIMIT {$limit}";
        $stmt = $this->pdo->pdoByTableId($tableId)->prepare($sql);
        $stmt->execute($bind);
        return $stmt->fetchAll();
    }

    /** SELECT 带操作符支持 */
    public function select_open(int $tableId, array $fields = ['*'], array $where = [], string $order = null, int $limit = 1000): array
    {
        $table = $this->pdo->tableNameFromId($tableId);
        $filteredFields = $fields === ['*'] ? ['*'] : $this->pdo->filterFields($tableId, $fields);

        $normalizedWhere = [];
        foreach ($where as $key => $value) {
            $condition = $this->pdo->normalizeCondition($tableId, $key, $value);
            if ($condition !== null) {$normalizedWhere[] = $condition;}
        }

        if (!empty($where) && empty($normalizedWhere)) {
            write_log("[warning] 表{$tableId}所有输入条件均无效", self::log_type);
            return [];
        }

        $cols = $filteredFields === ['*'] ? '*' : ('`' . implode('`,`', $filteredFields) . '`');

        $bind = []; $parts = []; $i = 0;
        foreach ($normalizedWhere as $condition) {
            $field = $condition['field'];
            $operator = $condition['operator'];
            $value = $condition['value'];

            if (($operator === 'IN' || $operator === 'NOT IN') && is_array($value)) {
                $placeholders = [];
                foreach ($value as $index => $item) {
                    $i++; $p = ":w{$i}_{$index}";
                    $placeholders[] = $p;
                    $bind[$p] = $item;
                }
                $parts[] = "`$field` $operator (" . implode(', ', $placeholders) . ")";
            } else {
                $i++; $p = ":w$i";
                $parts[] = "`$field` $operator $p";
                $bind[$p] = $value;
            }
        }
        $sqlWhere = $parts ? ' WHERE ' . implode(' AND ', $parts) : '';

        $orderClause = $this->pdo->validateOrder($tableId, $order);

        $sql = "SELECT {$cols} FROM `{$table}` {$sqlWhere} {$orderClause} LIMIT {$limit}";
        $stmt = $this->pdo->pdoByTableId($tableId)->prepare($sql);
        $stmt->execute($bind);
        return $stmt::fetchAll();
    }

    /** SELECT 单行 */
    public function selectOne(int $tableId, array $where = [], array $fields = ['*']): ?array
    {
        $rows = $this->select($tableId, $where, $fields, 1);
        return $rows[0] ?? null;
    }

    /** SELECT 模糊查询 */
    public function selectLike(int $tableId, array $like, array $fields = ['*'], int $limit = 10): array
    {
        $like = $this->pdo->filterFields($tableId, $like);
        if (!$like) return [];
        $table = $this->pdo->tableNameFromId($tableId);

        $cols = $fields === ['*'] ? '*' : ('`' . implode('`,`', $fields) . '`');

        $parts = []; $bind = []; $i = 0;
        foreach ($like as $k => $v) {
            $i++; $p = ":l$i"; $parts[] = "`$k` LIKE {$p}"; 
            $bind[$p] = "%$v%";
        }
        $sqlWhere = $parts ? ' WHERE ' . implode(' AND ', $parts) : '';
        $sql = "SELECT {$cols} FROM `{$table}`{$sqlWhere} ORDER BY `id` ASC LIMIT {$limit}";

        $stmt = $this->pdo->pdoByTableId($tableId)->prepare($sql);
        $stmt->execute($bind);
        return $stmt->fetchAll();
    }
}
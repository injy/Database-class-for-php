<?php
namespace Kc\Database\Core;

use Kc\Database\Config\DatabaseConfigInterface;

class InjectableDb
{
    protected const log_type = self::class;
    private InjectableSql $sql;
    private DatabaseConfigInterface $config;

    public function __construct(InjectableSql $sql, DatabaseConfigInterface $config)
    {
        $this->sql = $sql;
        $this->config = $config;
    }

    /**
     * 动态调用数据库分组（如 web）
     */
    public function __get(string $dbName)
    {
        // 从配置中查找对应的数据库ID
        $dbMapping = $this->config->getConfig()['db_mapping'] ?? [];
        $dbId = array_search($dbName, $dbMapping);
        
        if ($dbId === false) {
            write_log("数据库分组 {$dbName} 不存在", self::log_type);
            return null;
        }
        
        return new InjectableDbGroup($this->sql, $dbId, $this->config);
    }
}

class InjectableDbGroup
{
    protected const log_type = InjectableDb::class;
    private InjectableSql $sql;
    private int $dbId;
    private DatabaseConfigInterface $config;

    public function __construct(InjectableSql $sql, int $dbId, DatabaseConfigInterface $config)
    {
        $this->sql = $sql;
        $this->dbId = $dbId;
        $this->config = $config;
    }

    /**
     * 动态调用表名（如 domain）
     */
    public function __get(string $tableName)
    {
        // 查找对应的表ID（表ID的第一位是数据库ID）
        $tables = $this->config->getConfig()['tables'] ?? [];
        
        foreach ($tables as $tableId => $name) {
            if ($name === $tableName && (int)substr((string)$tableId, 0, 1) === $this->dbId) {
                return new InjectableDbTable($this->sql, $tableId);
            }
        }
        
        write_log("表 {$tableName} 在分组 {$this->dbId} 中不存在", self::log_type);
        return null;
    }
}

class InjectableDbTable
{
    protected const log_type = InjectableDb::class;
    private InjectableSql $sql;
    private int $tableId;

    public function __construct(InjectableSql $sql, int $tableId)
    {
        $this->sql = $sql;
        $this->tableId = $tableId;
    }

    // 添加记录
    public function add(array $data)
    {
        return $this->sql->insert($this->tableId, $data);
    }

    // 删除记录
    public function delete(array $search_arr = [])
    {
        return $this->sql->delete($this->tableId, $search_arr);
    }

    // 更新记录
    public function update(array $data, array $search_arr = [])
    {
        return $this->sql->update($this->tableId, $data, $search_arr);
    }

    // 获取记录
    public function get(array $search_arr = [])
    {
        return $this->sql->select($this->tableId, $search_arr);
    }
}
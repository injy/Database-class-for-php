<?php
namespace Kc\Database;

use Kc\Database\Config\DatabaseConfigInterface;
use Kc\Database\Core\InjectablePdo;
use Kc\Database\Core\InjectableSql;
use Kc\Database\Core\InjectableDb;

class Database
{
    private DatabaseConfigInterface $config;
    private InjectablePdo $pdo;
    private InjectableSql $sql;
    private InjectableDb $db;
    
    public function __construct(DatabaseConfigInterface $config)
    {
        $this->config = $config;
        $this->initializeComponents();
    }
    
    private function initializeComponents(): void
    {
        // 依赖注入链：Config → Pdo → Sql → Db
        $this->pdo = new InjectablePdo($this->config);
        $this->sql = new InjectableSql($this->pdo, $this->config);
        $this->db = new InjectableDb($this->sql, $this->config);
    }
    
    /**
     * 魔术方法 - 直接访问数据库分组
     * $database->user->user->add()
     */
    public function __get(string $dbName)
    {
        return $this->db->$dbName;
    }
    
    /**
     * 获取数据库分组对象（兼容原有方式）
     */
    public function db(): InjectableDb
    {
        return $this->db;
    }
    
    /**
     * 获取SQL操作对象
     */
    public function sql(): InjectableSql
    {
        return $this->sql;
    }
    
    /**
     * 获取查询构建器
     */
    public function query(int $tableId): InjectableSqlQuery
    {
        return new InjectableSqlQuery($tableId, $this->sql);
    }
    
    /**
     * 获取配置对象
     */
    public function getConfig(): DatabaseConfigInterface
    {
        return $this->config;
    }
    
    /**
     * 静态方法创建实例（工厂模式）
     */
    public static function createFromArray(array $config): self
    {
        $configObj = new \Kc\Database\Config\ArrayDatabaseConfig($config);
        return new self($configObj);
    }
    
    /**
     * 从环境变量创建实例
     */
    public static function createFromEnv(string $envPrefix = 'DB_'): self
    {
        $configObj = new \Kc\Database\Config\EnvDatabaseConfig($envPrefix);
        return new self($configObj);
    }
}
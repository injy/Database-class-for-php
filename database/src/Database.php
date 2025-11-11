<?php
/**
 * @license MIT
 * @copyright KC
 */

namespace Kc\Database;

use Kc\Database\Config\DatabaseConfigInterface;
use Kc\Database\Core\InjectablePdo;
use Kc\Database\Core\InjectableSql;
use Kc\Database\Core\InjectableDb;
use Kc\Database\Core\InjectableSqlQuery;

/**
 * 数据库操作主类
 * 
 * 提供统一的数据库操作接口，支持链式调用和传统调用两种方式
 * 
 * @example
 * // 链式调用
 * $database->web->domain->add(['name' => 'example.com']);
 * 
 * // 传统调用
 * $database->sql()->insert(101, ['name' => 'example.com']);
 */
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
        // 初始化日志系统
        $this->initializeLogging();
        
        // 依赖注入链：Config → Pdo → Sql → Db
        $this->pdo = new InjectablePdo($this->config);
        $this->sql = new InjectableSql($this->pdo, $this->config);
        $this->db = new InjectableDb($this->sql, $this->config);
    }
    
    private function initializeLogging(): void
    {
        // 如果配置中有日志设置，自动初始化日志系统
        if (method_exists($this->config, 'getConfig')) {
            $configArray = $this->config->getConfig();
            if (isset($configArray['logging'])) {
                \Kc\Database\Utils\Logger::initFromConfig($configArray);
            }
        }
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
     * 开始事务
     */
    public function beginTransaction(int $dbId): bool
    {
        return $this->pdo->getPdo($dbId)->beginTransaction();
    }
    
    /**
     * 提交事务
     */
    public function commit(int $dbId): bool
    {
        return $this->pdo->getPdo($dbId)->commit();
    }
    
    /**
     * 回滚事务
     */
    public function rollback(int $dbId): bool
    {
        return $this->pdo->getPdo($dbId)->rollBack();
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
    
    /**
     * 获取最后插入的ID
     */
    public function lastInsertId(int $dbId): string
    {
        return $this->pdo->getPdo($dbId)->lastInsertId();
    }
}
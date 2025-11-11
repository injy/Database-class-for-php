<?php
namespace Kc\Database\Config;

class ArrayDatabaseConfig implements DatabaseConfigInterface
{
    private array $config;
    
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->validate();
    }
    
    public function getDbInfo(int $dbId): array
    {
        if (!isset($this->config['databases'][$dbId])) {
            throw new \InvalidArgumentException("数据库ID {$dbId} 未配置");
        }
        return $this->config['databases'][$dbId];
    }
    
    public function getTableName(int $tableId): string
    {
        if (!isset($this->config['tables'][$tableId])) {
            throw new \InvalidArgumentException("表ID {$tableId} 未配置");
        }
        return $this->config['tables'][$tableId];
    }
    
    public function getDbName(int $dbId): string
    {
        if (!isset($this->config['db_mapping'][$dbId])) {
            throw new \InvalidArgumentException("数据库分组ID {$dbId} 未配置");
        }
        return $this->config['db_mapping'][$dbId];
    }
    
    public function validate(): bool
    {
        $requiredSections = ['databases', 'tables', 'db_mapping'];
        foreach ($requiredSections as $section) {
            if (!isset($this->config[$section]) || !is_array($this->config[$section])) {
                throw new \InvalidArgumentException("配置缺少必要部分: {$section}");
            }
        }
        return true;
    }
    
    /**
     * 获取完整配置（用于调试）
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
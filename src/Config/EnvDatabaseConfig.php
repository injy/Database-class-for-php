<?php
namespace Kc\Database\Config;

class EnvDatabaseConfig implements DatabaseConfigInterface
{
    private array $config;
    
    public function __construct(string $envPrefix = 'DB_')
    {
        $this->config = $this->loadFromEnv($envPrefix);
        $this->validate();
    }
    
    private function loadFromEnv(string $prefix): array
    {
        $config = [
            'databases' => [],
            'tables' => [],
            'db_mapping' => []
        ];
        
        // 从环境变量加载数据库配置
        foreach ($_ENV as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $this->parseEnvKey($key, $value, $config);
            }
        }
        
        return $config;
    }
    
    private function parseEnvKey(string $key, string $value, array &$config): void
    {
        $parts = explode('_', str_replace('DB_', '', $key));
        
        if (count($parts) >= 3) {
            $type = strtolower($parts[0]);
            $id = (int)$parts[1];
            $field = strtolower($parts[2]);
            
            switch ($type) {
                case 'db':
                    $config['databases'][$id][$field] = $value;
                    break;
                case 'table':
                    if ($field === 'name') {
                        $config['tables'][$id] = $value;
                    }
                    break;
                case 'mapping':
                    if ($field === 'name') {
                        $config['db_mapping'][$id] = $value;
                    }
                    break;
            }
        }
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
        // 基础验证
        if (empty($this->config['databases'])) {
            throw new \RuntimeException('未配置任何数据库连接');
        }
        return true;
    }
}
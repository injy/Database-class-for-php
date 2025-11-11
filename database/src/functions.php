<?php
/**
 * @license MIT
 * @copyright KC
 */

use Kc\Database\Utils\Logger;

/**
 * 全局日志函数
 * 
 * 为现有代码提供兼容性，建议在类中直接使用 Logger::write()
 */
if (!function_exists('write_log')) {
    function write_log(string $message, string $type = 'Database'): void
    {
        Logger::write($message, $type);
    }
}

/**
 * 获取数据库实例的便捷函数（推荐使用 Database 类的静态方法）
 * 
 * @param array|null $config 可选配置数组，为null时尝试从环境变量创建
 * @return \Kc\Database\Database
 * @throws RuntimeException 当配置不存在时抛出异常
 * 
 * @example
 * // 使用环境变量配置
 * $db = db();
 * 
 * // 使用数组配置
 * $db = db($configArray);
 */
if (!function_exists('db')) {
    function db(?array $config = null): \Kc\Database\Database
    {
        static $instances = [];
        $configKey = $config ? md5(serialize($config)) : 'env';
        
        if (!isset($instances[$configKey])) {
            try {
                if ($config) {
                    $instances[$configKey] = \Kc\Database\Database::createFromArray($config);
                } else {
                    $instances[$configKey] = \Kc\Database\Database::createFromEnv();
                }
            } catch (Exception $e) {
                throw new RuntimeException(
                    '数据库配置未找到。请提供配置数组或设置环境变量: ' . $e->getMessage()
                );
            }
        }
        
        return $instances[$configKey];
    }
}

/**
 * 快速数据库操作函数（简化常用操作）
 */
if (!function_exists('db_insert')) {
    function db_insert(int $tableId, array $data, ?array $config = null): bool
    {
        return db($config)->sql()->insert($tableId, $data);
    }
}

if (!function_exists('db_select')) {
    function db_select(int $tableId, array $where = [], ?array $config = null): array
    {
        return db($config)->sql()->select($tableId, $where);
    }
}
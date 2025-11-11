<?php
/**
 * @license MIT
 * @copyright KC
 */

namespace Kc\Database\Config;

interface DatabaseConfigInterface
{
    /**
     * 获取数据库连接配置
     */
    public function getDbInfo(int $dbId): array;
    
    /**
     * 获取表名映射
     */
    public function getTableName(int $tableId): string;
    
    /**
     * 获取数据库分组映射
     */
    public function getDbName(int $dbId): string;
    
    /**
     * 验证配置完整性
     */
    public function validate(): bool;
    
    /**
     * 获取完整配置
     */
    public function getConfig(): array;
    
    /**
     * 检查配置中是否包含特定部分
     */
    public function hasSection(string $section): bool;
}
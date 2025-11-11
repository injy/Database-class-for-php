<?php
/**
 * @license MIT
 * @copyright KC
 */

/**
 * 数据库日志配置示例
 * 
 * 启用数据库日志功能需要：
 * 1. 创建日志表结构
 * 2. 配置表ID映射
 * 3. 设置Logger使用数据库日志
 */

return [
    'databases' => [
        // 主数据库
        1 => [
            'host' => 'localhost',
            'port' => 3306,
            'user' => 'root',
            'pw' => 'password'
        ]
    ],
    
    'tables' => [
        // 业务表
        101 => 'users',
        102 => 'products',
        
        // 日志表（重要：表ID需要对应数据库）
        103 => 'system_logs'  // 系统日志表
    ],
    
    'db_mapping' => [
        1 => 'main'
    ]
];

/**
 * 日志表SQL结构（需要在数据库中执行）：
 * 
 * CREATE TABLE system_logs (
 *     id BIGINT AUTO_INCREMENT PRIMARY KEY,
 *     timestamp DATETIME NOT NULL,
 *     type VARCHAR(50) NOT NULL COMMENT '日志类型: DEBUG, INFO, WARNING, ERROR',
 *     level VARCHAR(20) NOT NULL COMMENT '日志级别',
 *     message TEXT NOT NULL COMMENT '日志内容',
 *     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 * 
 * CREATE INDEX idx_timestamp ON system_logs(timestamp);
 * CREATE INDEX idx_type ON system_logs(type);
 * CREATE INDEX idx_level ON system_logs(level);
 */

/**
 * 启用数据库日志的代码示例：
 * 
 * <?php
 * require_once 'vendor/autoload.php';
 * 
 * // 加载配置
 * $config = require 'config/database-logging.php';
 * $database = \Kc\Database\Database::createFromArray($config);
 * 
 * // 启用数据库日志（表ID 103 对应 system_logs 表）
 * \Kc\Database\Utils\Logger::setDatabaseLogTable(103);
 * 
 * // 可选：设置自定义日志文件路径
 * \Kc\Database\Utils\Logger::setLogFile('/var/log/myapp/database.log');
 * 
 * // 现在所有数据库操作日志都会同时保存到文件和数据库
 * $database->main->users->add(['name' => 'test']);
 */
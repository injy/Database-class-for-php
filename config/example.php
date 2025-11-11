<?php
/**
 * @license MIT
 * @copyright KC
 */

/**
 * 数据库配置示例文件
 * 
 * 使用说明：
 * 1. 复制此文件为 config.php
 * 2. 修改实际数据库连接信息
 * 3. 在项目中引入：$database = Database::createFromArray(require 'config.php');
 * 
 * 配置结构说明：
 * - databases: 数据库连接配置，键为数据库ID
 * - tables: 表名映射，键为表ID（格式：数据库ID + 表编号）
 * - db_mapping: 数据库分组映射，用于链式调用
 * 
 * 表ID命名规则：
 * - 3位数字：首位为数据库ID，后两位为表编号
 * - 示例：101 = 数据库1的第1个表，201 = 数据库2的第1个表
 * 
 * @example
 * // 链式调用：$database->main->users->add(['name' => '张三'])
 * // 传统调用：$database->sql()->insert(101, ['name' => '张三'])
 */

return [
    // 数据库连接配置
    'databases' => [
        // 主数据库 (ID: 1)
        1 => [
            'host' => 'localhost',     // 数据库主机
            'port' => 3306,           // 数据库端口
            'user' => 'root',         // 用户名
            'pw' => 'password'        // 密码
        ],
        
        // 日志数据库 (ID: 2) - 可选，用于读写分离或专用数据库
        2 => [
            'host' => '192.168.1.100',
            'port' => 3306,
            'user' => 'log_user',
            'pw' => 'log_password'
        ]
    ],
    
    // 表名映射配置
    'tables' => [
        // 数据库1的表 (ID以1开头)
        101 => 'users',           // 用户表
        102 => 'products',        // 商品表
        103 => 'orders',          // 订单表
        
        // 数据库2的表 (ID以2开头)
        201 => 'access_logs',     // 访问日志表
        202 => 'error_logs'       // 错误日志表
    ],
    
    // 数据库分组映射（用于链式调用语法）
    'db_mapping' => [
        1 => 'main',              // 主数据库分组 -> $database->main
        2 => 'logs'               // 日志数据库分组 -> $database->logs
    ],
    
    // 日志系统配置（可选）
    'logging' => [
        'enabled' => true,                    // 是否启用日志
        'file_path' => '/var/log/database.log', // 日志文件路径（为空使用默认路径）
        'database_table_id' => 103            // 数据库日志表ID（为空则不保存到数据库）
    ]
];
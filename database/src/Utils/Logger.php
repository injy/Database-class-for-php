<?php
/**
 * @license MIT
 * @copyright KC
 */

namespace Kc\Database\Utils;

/**
 * 日志工具类
 * 
 * 提供统一的日志记录功能，支持多种输出方式
 */
class Logger
{
    private static ?string $logFile = null;
    private static bool $enabled = true;
    private static ?int $databaseLogTableId = null;
    
    // 默认日志文件路径
    private const DEFAULT_LOG_FILE = __DIR__ . '/../../logs/database.log';
    
    /**
     * 从配置初始化日志系统
     */
    public static function initFromConfig(array $config): void
    {
        $loggingConfig = $config['logging'] ?? [];
        
        // 启用/禁用日志
        self::$enabled = $loggingConfig['enabled'] ?? true;
        
        // 设置日志文件路径
        if (isset($loggingConfig['file_path'])) {
            self::$logFile = $loggingConfig['file_path'];
        }
        
        // 设置数据库日志表ID
        if (isset($loggingConfig['database_table_id'])) {
            self::$databaseLogTableId = (int)$loggingConfig['database_table_id'];
        }
    }
    
    /**
     * 设置日志文件路径（兼容旧版本）
     */
    public static function setLogFile(string $filePath): void
    {
        self::$logFile = $filePath;
    }
    
    /**
     * 设置数据库日志表ID（兼容旧版本）
     */
    public static function setDatabaseLogTable(int $tableId): void
    {
        self::$databaseLogTableId = $tableId;
    }
    
    /**
     * 启用/禁用日志（兼容旧版本）
     */
    public static function setEnabled(bool $enabled): void
    {
        self::$enabled = $enabled;
    }
    
    /**
     * 写入日志
     */
    public static function write(string $message, string $type = 'Database'): void
    {
        if (!self::$enabled) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
        
        // 1. 输出到标准输出（开发环境）
        if (php_sapi_name() === 'cli' || defined('STDIN')) {
            echo $logMessage;
        }
        
        // 2. 写入文本文件（默认或自定义路径）
        $logFile = self::$logFile ?: self::DEFAULT_LOG_FILE;
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // 3. 写入数据库（如果配置了数据库日志表）
        if (self::$databaseLogTableId && function_exists('db')) {
            try {
                db_insert(self::$databaseLogTableId, [
                    'timestamp' => $timestamp,
                    'type' => $type,
                    'message' => $message,
                    'level' => self::getLogLevel($type)
                ]);
            } catch (Exception $e) {
                // 数据库日志失败时，记录到文件但不中断程序
                $errorMessage = "[{$timestamp}] [Logger-Error] 数据库日志写入失败: " . $e->getMessage() . PHP_EOL;
                file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);
            }
        }
    }
    
    /**
     * 根据日志类型获取日志级别
     */
    private static function getLogLevel(string $type): string
    {
        $levelMap = [
            'DEBUG' => 'debug',
            'WARNING' => 'warning', 
            'ERROR' => 'error'
        ];
        
        return $levelMap[$type] ?? 'info';
    }
    
    /**
     * 记录调试信息
     */
    public static function debug(string $message): void
    {
        self::write($message, 'DEBUG');
    }
    
    /**
     * 记录警告信息
     */
    public static function warning(string $message): void
    {
        self::write($message, 'WARNING');
    }
    
    /**
     * 记录错误信息
     */
    public static function error(string $message): void
    {
        self::write($message, 'ERROR');
    }
}
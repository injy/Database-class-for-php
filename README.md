# PHP Database Class

一个现代化的PHP数据库操作类库，提供简洁、安全、高效的数据库操作接口。

## 特性

- 🚀 **链式调用**：支持 `$database->main->users->add()` 的优雅语法
- 🔒 **安全防护**：自动字段验证、SQL注入防护、操作符白名单
- 📊 **连接池**：PDO连接池管理，支持连接健康检查
- 🔧 **配置灵活**：支持数组配置和环境变量配置
- 📝 **查询构建器**：流畅的查询构建接口
- 💾 **事务支持**：完整的事务管理功能
- 📈 **性能优化**：字段缓存、智能查询优化
- 🐛 **详细日志**：完整的操作日志记录

## 安装

### Composer安装

```bash
composer require kc/database
```

### 手动安装

1. 下载项目文件
2. 包含自动加载文件：

```php
require_once 'database/src/functions.php';
```

## 快速开始

### 1. 基本配置

```php
<?php

// 使用数组配置
$config = [
    'databases' => [
        1 => [
            'host' => 'localhost',
            'port' => 3306,
            'user' => 'root',
            'pw' => 'password'
        ]
    ],
    'tables' => [
        101 => 'users',
        102 => 'products'
    ],
    'db_mapping' => [
        1 => 'main'
    ]
];

$database = \Kc\Database\Database::createFromArray($config);
```

### 2. 环境变量配置

设置环境变量：

```bash
# 数据库连接
DB_DB_1_HOST=localhost
DB_DB_1_PORT=3306
DB_DB_1_USER=root
DB_DB_1_PW=password

# 表映射
DB_TABLE_101_NAME=users
DB_TABLE_102_NAME=products

# 数据库分组
DB_MAPPING_1_NAME=main
```

使用环境变量创建实例：

```php
$database = \Kc\Database\Database::createFromEnv();
```

### 3. 便捷函数

```php
// 使用全局函数（自动从环境变量创建）
$database = db();
```

## 使用方法

### 链式调用（推荐）

```php
// 添加记录
$database->main->users->add([
    'name' => '张三',
    'email' => 'zhangsan@example.com'
]);

// 查询记录
$users = $database->main->users->get(['status' => 1]);

// 更新记录
$database->main->users->update(
    ['status' => 0], 
    ['id' => 1]
);

// 删除记录
$database->main->users->delete(['id' => 1]);
```

### 传统调用

```php
// 插入
$database->sql()->insert(101, [
    'name' => '李四',
    'email' => 'lisi@example.com'
]);

// 查询
$users = $database->sql()->select(101, ['*'], ['status' => 1]);

// 带操作符的查询
$users = $database->sql()->select_open(101, ['*'], [
    'age' => ['>', 18],
    'name' => ['LIKE', '%张%']
]);
```

### 查询构建器

```php
// 创建查询构建器
$query = $database->query(101);

// 链式构建查询
$users = $query->select(['id', 'name', 'email'])
    ->where(['status' => 1])
    ->andWhere('age', 18, '>')
    ->orderBy('id DESC')
    ->limit(10)
    ->get();

// 统计数量
$count = $query->where(['status' => 1])->count();

// 检查存在
$exists = $query->where(['email' => 'test@example.com'])->exists();
```

### 事务管理

```php
try {
    $database->beginTransaction(1);
    
    $database->main->users->add(['name' => '王五']);
    $database->main->products->add(['name' => '商品A']);
    
    $database->commit(1);
} catch (Exception $e) {
    $database->rollback(1);
    throw $e;
}
```

## 配置说明

### 表ID规则

表ID采用3位数字编码：
- 第1位：数据库ID
- 第2-3位：表编号

例如：
- `101`：数据库1的第1个表
- `201`：数据库2的第1个表

### 环境变量命名规则

```
DB_{TYPE}_{ID}_{FIELD}
```

- `TYPE`: db（数据库）、table（表）、mapping（分组）
- `ID`: 数字ID
- `FIELD`: 配置字段

## 高级功能

### 自定义日志

```php
// 使用自定义日志类
class CustomLogger {
    public static function write($message, $type) {
        // 自定义日志逻辑
    }
}

// 重写全局函数
function write_log($message, $type) {
    CustomLogger::write($message, $type);
}
```

### 字段缓存管理

```php
// 清除特定表的字段缓存
\Kc\Database\Core\TableColumnCache::clear(101);

// 清除所有缓存
\Kc\Database\Core\TableColumnCache::clear();
```

## 最佳实践

1. **使用链式调用**：代码更简洁易读
2. **合理设计表ID**：便于管理和维护
3. **使用环境变量**：提高配置安全性
4. **启用事务**：保证数据一致性
5. **监控日志**：及时发现和解决问题

## 故障排除

### 常见问题

1. **连接失败**：检查数据库配置和网络连接
2. **表不存在**：验证表ID配置是否正确
3. **字段验证失败**：检查字段名拼写和数据类型

### 日志查看

日志文件位于：`database/logs/database.log`

## 许可证

MIT License

## 贡献

欢迎提交Issue和Pull Request！
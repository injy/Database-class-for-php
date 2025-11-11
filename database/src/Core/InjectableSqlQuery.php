<?php
/**
 * @license MIT
 * @copyright KC
 */

namespace Kc\Database\Core;

/**
 * 查询构建器
 */
class InjectableSqlQuery
{
    private int $tableId;
    private InjectableSql $sql;
    private array $fields = ['*'];
    private array $where = [];
    private ?string $order = null;
    private ?int $limit = null;
    private ?int $offset = null;
    private array $joins = [];
    
    public function __construct(int $tableId, InjectableSql $sql)
    {
        $this->tableId = $tableId;
        $this->sql = $sql;
    }
    
    /**
     * 设置查询字段
     */
    public function select(array $fields): self
    {
        $this->fields = $fields;
        return $this;
    }
    
    /**
     * 添加WHERE条件
     */
    public function where(array $conditions): self
    {
        $this->where = array_merge($this->where, $conditions);
        return $this;
    }
    
    /**
     * 添加单个WHERE条件
     */
    public function andWhere(string $field, $value, string $operator = '='): self
    {
        $this->where[$field] = [$field, $operator, $value];
        return $this;
    }
    
    /**
     * 设置排序
     */
    public function orderBy(string $order): self
    {
        $this->order = $order;
        return $this;
    }
    
    /**
     * 设置限制
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    /**
     * 设置偏移量
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }
    
    /**
     * 添加JOIN
     */
    public function join(string $table, string $on, string $type = 'INNER'): self
    {
        $this->joins[] = [
            'table' => $table,
            'on' => $on,
            'type' => strtoupper($type)
        ];
        return $this;
    }
    
    /**
     * 执行查询
     */
    public function get(): array
    {
        return $this->sql->select_open(
            $this->tableId, 
            $this->fields, 
            $this->where, 
            $this->order, 
            $this->limit ?? 1000
        );
    }
    
    /**
     * 获取单条记录
     */
    public function first(): ?array
    {
        $this->limit = 1;
        $result = $this->get();
        return $result[0] ?? null;
    }
    
    /**
     * 统计数量
     */
    public function count(): int
    {
        $result = $this->sql->select_open($this->tableId, ['COUNT(*) as count'], $this->where);
        return (int)($result[0]['count'] ?? 0);
    }
    
    /**
     * 检查是否存在
     */
    public function exists(): bool
    {
        return $this->count() > 0;
    }
}
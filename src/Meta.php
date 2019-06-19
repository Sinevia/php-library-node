<?php
namespace Sinevia\Nodes;

class Meta extends \Sinevia\ActiveRecord
{
    public static $keys = ['Id'];
    public static $table = 'snv_nodes_meta';

    public static function getTable()
    {
        return static::getDatabase()->table(static::getTableName());
    }

    public static function getDatabase()
    {
        return db();
    }
    
    public function beforeInsert()
    {
        $this->set('CreatedAt', date('Y-m-d H:i:s'));
        $this->set('UpdatedAt', date('Y-m-d H:i:s'));
    }

    public function beforeUpdate()
    {
        $this->set('UpdatedAt', date('Y-m-d H:i:s'));
    }

    public static function findByNodeAndKey($nodeId, $key)
    {
        $row = static::getTable()
            ->where('NodeId', '=', $nodeId)
            ->where('Key', '=', $key)
            ->selectOne();

        if (is_null($row)) {
            return null;
        }

        $o = new static;
        $o->data = $row;
        return $o;
    }

    public static function findContains($key, $value)
    {
        $row = static::getTable()
            ->where('Key', '=', $key)
            ->where('Value', 'LIKE', '%' . $value . '%')
            ->selectOne();

        if (is_null($row)) {
            return null;
        }

        $o = new static;
        $o->data = $row;
        return $o;
    }

    public static function findEquals($key, $value)
    {
        $row = static::getTable()
            ->where('Key', '=', $key)
            ->where('Value', '=', $value)
            ->selectOne();

        if (is_null($row)) {
            return null;
        }

        $o = new static;
        $o->data = $row;
        return $o;
    }

    public function isJson()
    {
        $json = json_decode($this->get('Value'));
        return $json && $str != $json;
    }

    /**
     * Create table
     *
     * @return void
     */
    public static function tableCreate()
    {
        if (static::getDatabase()->table(static::$table)->exists()) {
            echo "Table '" . static::$table . "' already exists skipped...";
            return true;
        }

        static::getDatabase()->table(static::$table)
            ->column('Id', 'STRING')
            ->column('NodeId', 'STRING')
            ->column('Key', 'STRING')
            ->column('Value', 'TEXT')
            ->column('CreatedAt', 'STRING')
            ->column('UpdatedAt', 'STRING')
            ->column('DeletedAt', 'STRING')
            ->create();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public static function tableDelete()
    {
        if (static::getDatabase()->table(static::$table)->exists() == false) {
            echo "Table '" . static::$table . "' already exists deleted...";
            return true;
        }
        static::getDatabase()->table(static::$table)->drop();
    }
}

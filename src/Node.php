<?php
namespace Sinevia\Nodes;

class Node extends \Sinevia\ActiveRecord
{
    public static $keys = ['Id'];
    public static $table = 'snv_nodes_node';

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

    public static function createWithMetas($type, $metas)
    {
        static::getDatabase()->transactionBegin();

        try {
            $new = new static();
            $new->set('Id', \Sinevia\Uid::microUid());
            $new->set('Type', $type);
            $isSaved = $new->save();

            if ($isSaved == false) {
                return null;
            }

            foreach ($metas as $key => $value) {
                $new->setMeta($key, $value);
            }

            static::getDatabase()->transactionCommit();
        } catch (\Exception $e) {
            static::getDatabase()->transactionRollBack();
            return null;
        }

        return $new;
    }

    public static function updateWithMetas($id, array $metas)
    {
        static::getDatabase()->transactionBegin();

        try {
            $node = static::find($id);
            if ($node == null) {
                throw new \RuntimeException('Node ' . $id . ' not found');
                return false;
            }

            if (isset($metas['Memo'])) {
                $node->set('Memo', $metas['Memo']);
            }

            foreach ($metas as $key => $value) {
                $node->setMeta($key, $value);
            }

            $isSaved = $node->save();

            if ($isSaved == false) {
                return false;
            }

            foreach ($metas as $key => $value) {
                $node->setMeta($key, $value);
            }

            static::getDatabase()->transactionCommit();
        } catch (\Exception $e) {
            static::getDatabase()->transactionRollBack();
            return false;
        }

        return true;
    }

    public static function findByType($type, $id)
    {
        return static::where('type', $type)->where('Id', $id)->first();
    }

    public static function findByMetaContains($key, $value)
    {
        $meta = Meta::findContains($key, $value);
        if (is_null($meta)) {
            return null;
        }
        return static::find($meta->NodeId);
    }

    public static function findByMetaEquals($key, $value)
    {
        $meta = Meta::findEquals($key, $value);
        if (is_null($meta)) {
            return null;
        }
        return static::find($meta->get('NodeId'));
    }

    /**
     * Returns the meta value, or default (null) if not found
     * @param string $key
     * @return string|null|any
     */
    public function getMeta($key, $default = null)
    {
        $meta = Meta::findByNodeAndKey($this->get('Id'), $key);

        if (is_null($meta)) {
            return $default;
        }

        if ($meta->isJson()) {
            return json_decode($meta->Value);
        }

        return $meta->get('Value');
    }

    public function getMetas()
    {
        $rows = Meta::getTable()
            ->where('NodeId', '=', $this->get('Id'))
            ->select();

        $metas = [];

        foreach ($rows as $row) {
            $isJson = $this->isJson($row['Value']);
            $metas[$row['Key']] = $isJson ? json_decode($row['Value']) : $row['Value'];
        }

        return $metas;
    }

    /**
     * Sets key-value pair
     * @param string $key
     * @param any $value
     * @return boolean
     */
    public function setMeta($key, $value)
    {
        $meta = Meta::findByNodeAndKey($this->get('Id'), $key);

        if (is_null($meta)) {
            $meta = new Meta();
            $meta->set('Id', \Sinevia\Uid::microUid());
            $meta->set('NodeId', $this->get('Id'));
            $meta->set('Key', $key);
            $meta->set('CreatedAt', date('Y-m-d H:i:s')); // Update CreatedAt timestamp
        }

        $meta->set('Value', is_array($value) ? json_encode($value) : $value);

        $meta->set('UpdatedAt', date('Y-m-d H:i:s')); // Update UpdatedAt timestamp

        return $meta->save() === true ? true : false;
    }

    /**
     * Sets key-value pairs
     * @param string $key
     * @param any $value
     * @return boolean
     */
    public function setMetas($metas)
    {
        static::getDatabase()->transactionBegin();

        try {
            foreach ($metas as $key => $value) {
                $this->setMeta($key, $value);
            }
            static::getDatabase()->transactionCommit();
        } catch (\Exception $e) {
            static::getDatabase()->transactionRollBack();
            return false;
        }

        return true;
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
            ->column('Type', 'STRING')
            ->column('Memo', 'STRING')
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

    private function isJson($value)
    {
        $json = json_decode($value);
        return $json && $value != $json;
    }
}

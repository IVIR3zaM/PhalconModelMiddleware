<?php
namespace IVIR3zaM\PhalconModelMiddleware;

trait ModelMiddleware
{
    protected $_haveDatabaseTable = null;
    protected $_classProperties = null;
    protected $_classParent = null;
    protected $_skippedColumns = array();
    protected $_allowEmpties = array();

    public function getSource()
    {
        if ($this->haveDatabaseTable()) {
            $table = parent::getSource();
        } else {
            $parent = $this->calculateParent();
            $obj = new $parent();
            $table = $obj->getSource();
        }

        return $table;
    }

    protected function haveDatabaseTable()
    {
        if ($this->_haveDatabaseTable !== null) {
            return $this->_haveDatabaseTable;
        }
        $class = get_class($this);
        $list = self::calculateProperties();

        if (!isset($list[$class]) || empty($list[$class])) {
            $this->_haveDatabaseTable = false;
        } else {
            $this->_haveDatabaseTable = true;
        }
        return $this->_haveDatabaseTable;
    }

    protected static function staticHaveDatabaseTable()
    {
        $class = get_called_class();
        $list = self::staticCalculateProperties();

        if (!isset($list[$class]) || empty($list[$class])) {
            return false;
        } else {
            return true;
        }
    }

    public static function getUniqueField()
    {
        return 'id';
    }

    protected static function staticCalculateProperties()
    {
        $list = array(get_called_class());
        $list = array_merge($list, class_parents(get_called_class()));
        $list = array_reverse($list);
        $classes = $temp = array();
        $first = true;
        foreach ($list as $class) {
            $properties = get_class_vars('\\' . $class);
            if ($first) {
                $first = false;
                $temp = $properties;
                continue;
            }
            if (in_array($class, [__CLASS__])) {
                $temp = array_merge($temp, $properties);
                continue;
            }
            $properties_final = array();
            foreach ($properties as $index => $value) {
                if ($index != static::getUniqueField() && !array_key_exists($index, $temp)) {
                    $properties_final[$index] = $value;
                }
            }
            if (!empty($properties_final)) {
                $classes[$class] = $properties_final;
            }
            $temp = array_merge($temp, $properties);
        }
        return $classes;
    }

    public function calculateProperties()
    {
        if ($this->_classProperties == null) {
            $this->_classProperties = self::staticCalculateProperties();
        }
        return $this->_classProperties;
    }

    public function skipAttributes(array $attributes)
    {
        if (!is_array($attributes)) {
            $attributes = array($attributes);
        }
        $this->_skippedColumns = $attributes;
    }

    public function allowEmptyStringValues(array $attributes)
    {
        if (!is_array($attributes)) {
            $attributes = array($attributes);
        }
        $this->_allowEmpties = array_merge($this->_allowEmpties, $attributes);
    }

    public function initialize()
    {
        if (method_exists($this->calculateParent(), 'initialize')) {
            parent::initialize();
        }

        if ($this->haveDatabaseTable()) {
            $list = $this->calculateProperties();
            foreach ($list as $class => $properties) {
                if ($class != get_class($this)) {
                    $this->_skippedColumns = array_merge($this->_skippedColumns, $properties);
                }
            }
            $this->_allowEmpties[] = static::getUniqueField();
        }
        if (!empty($this->_skippedColumns)) {
            parent::skipAttributes($this->_skippedColumns);
        }
        if (!empty($this->_allowEmpties)) {
            parent::allowEmptyStringValues($this->_allowEmpties);
        }
    }

    public function beforeValidation()
    {
        if (!empty($this->_allowEmpties)) {
            parent::allowEmptyStringValues($this->_allowEmpties);
        }
    }

    public function beforeValidationOnCreate()
    {
        if (method_exists($this->calculateParent(), 'beforeValidationOnCreate')) {
            if (parent::beforeValidationOnCreate() === false) {
                return false;
            }
        }
        if ($this->haveDatabaseTable()) {
            $id = static::getUniqueField();
            if (!$this->$id) {
                $this->$id = 0;
            }
        }
    }

    public function beforeDelete()
    {
        if (method_exists($this->calculateParent(), 'beforeDelete')) {
            if (parent::beforeDelete() === false) {
                return false;
            }
        }
        if ($this->haveDatabaseTable()) {
            $list = $this->calculateProperties();
            $idField = static::getUniqueField();
            foreach ($list as $class => $properties) {
                if ($class != get_class($this)) {
                    $object = $class::findFirst([
                        "{$idField} = :id:",
                        'bind' => [
                            'id' => $this->$idField,
                        ],
                    ]);
                    if ($object && $object->delete() === false) {
                        return false;
                    }
                }
            }
        }
    }

    public function beforeCreate()
    {
        if (method_exists($this->calculateParent(), 'beforeCreate')) {
            if (parent::beforeCreate() === false) {
                return false;
            }
        }
        if ($this->haveDatabaseTable()) {
            $list = $this->calculateProperties();
            $id = null;
            $idField = static::getUniqueField();
            $custom_fields = array();
            if (method_exists(get_called_class(), 'getCustomFields')) {
                $custom_fields = $this->getCustomFields();
            }
            foreach ($list as $class => $properties) {
                if ($class != get_class($this)) {
                    $class = '\\' . $class;
                    $object = new $class();

                    foreach ($properties as $index => $value) {
                        $object->$index = $this->$index;
                    }
                    if (is_array($custom_fields)) {
                        foreach ($custom_fields as $index => $value) {
                            if (array_key_exists($index, $properties)) {
                                $object->$index = $value;
                            }
                        }
                    }
                    if ($id) {
                        $object->$idField = $id;
                    }
                    if (!$object->save()) {
                        return false;
                    }
                    $id = $object->$idField;
                }
            }
            if ($id) {
                $this->$idField = $id;
            }
        } elseif (method_exists(get_called_class(), 'getCustomFields')) {
            $custom_fields = $this->getCustomFields();
            if (is_array($custom_fields)) {
                foreach ($custom_fields as $index => $value) {
                    $this->$index = $value;
                }
            }
        }
        return true;
    }

    public function beforeUpdate()
    {
        if (method_exists($this->calculateParent(), 'beforeUpdate')) {
            if (parent::beforeUpdate() === false) {
                return false;
            }
        }
        if ($this->haveDatabaseTable()) {
            $idField = static::getUniqueField();
            $list = $this->calculateProperties();
            $custom_fields = array();
            $class = get_called_class();
            if (method_exists($class, 'getCustomFields')) {
                $custom_fields = $class::getCustomFields();
            }
            foreach ($list as $class => $properties) {
                if ($class != get_class($this)) {
                    $class = '\\' . $class;
                    $object = $class::findFirst([
                        "{$idField} = :id:",
                        'bind' => [
                            'id' => $this->$idField,
                        ],
                    ]);
                    if (empty($object)) {
                        return false;
                    }

                    foreach ($properties as $index => $value) {
                        $object->$index = $this->$index;
                    }
                    if (is_array($custom_fields)) {
                        foreach ($custom_fields as $index => $value) {
                            if (array_key_exists($index, $properties)) {
                                $object->$index = $value;
                            }
                        }
                    }

                    if (!$object->save()) {
                        return false;
                    }
                }
            }
        } elseif (method_exists(get_called_class(), 'getCustomFields')) {
            $custom_fields = $this->getCustomFields();
            if (is_array($custom_fields)) {
                foreach ($custom_fields as $index => $value) {
                    $this->$index = $value;
                }
            }
        }
        return true;
    }

    protected static function staticCalculateParent()
    {
        $parents = class_parents(get_called_class());
        if (empty($parents)) {
            return get_called_class();
        }
        foreach ($parents as $class) {
            if ($class != __CLASS__) {
                return $class;
            }
        }
        return get_called_class();
    }

    protected function calculateParent()
    {
        if ($this->_classParent === null) {
            $this->_classParent = self::staticCalculateParent();
        }
        return $this->_classParent;
    }

    public function toArray($columns = null)
    {
        if (!$this->haveDatabaseTable()) {
            return parent::toArray($columns);
        }
        if (!is_array($columns)) {
            $columns = array();
        }
        $array = array();
        $list = $this->calculateProperties();
        $idField = static::getUniqueField();
        if (empty($columns) || in_array($idField, $columns)) {
            $array[$idField] = $this->$idField;
        }
        foreach ($list as $cl => $values) {
            foreach ($values as $field => $tmp) {
                if (empty($columns) || in_array($idField, $columns)) {
                    $array[$field] = $this->$field;
                }
            }
        }
        return $array;
    }


    public function afterFetch()
    {
        if (method_exists($this->calculateParent(), 'afterFetch')) {
            parent::afterFetch();
        }
        if ($this->haveDatabaseTable()) {
            $class = get_called_class();
            $list = $this->calculateProperties();
            $find = false;
            foreach ($list as $cl => $values) {
                if ($cl != $class) {
                    foreach ($values as $field => $tmp) {
                        $find = true;
                        break;
                    }
                }

            }
            if ($find) {
                $idField = static::getUniqueField();
                $item = parent::findFirst(self::calculateParams([
                    "{$idField} = :value:",
                    'bind' => [
                        'value' => $this->$idField,
                    ],
                    'all_columns' => true,
                ]));
                foreach ($list as $cl => $values) {
                    if ($cl != $class) {
                        foreach ($values as $field => $tmp) {
                            if (property_exists($item, $field)) {
                                $this->$field = $item->$field;
                            }
                        }
                    }

                }
            }
        }
    }

    protected static function realColumnName($column = '')
    {
        $name = '';
        for ($i = 0; $i < strlen($column); $i++) {
            $char = substr($column, $i, 1);
            if ($i > 0 && preg_match('/[A-Z]/', $char)) {
                $char = '_' . $char;
            }
            $name .= strtolower($char);
        }
        return $name;
    }

    protected static function calculateParams($parameters = array())
    {
        $class = get_called_class();
        $list = self::staticCalculateProperties();

        $custom_fields = array();
        if (method_exists($class, 'getCustomFields')) {
            $custom_fields = $class::getCustomFields();
        }

        $parameters = (array)$parameters;
        $params = $parameters;
        if (array_key_exists(0, $params)) {
            unset($params[0]);
        }
        $conditions = array_key_exists('conditions', $parameters) ? $parameters['conditions'] : (array_key_exists(0,
            $parameters)
            ? $parameters[0] : null);

        $all_columns = false;
        if (isset($params['all_columns'])) {
            if ($params['all_columns']) {
                $all_columns = true;
            }
            unset($params['all_columns']);
        }

        $custom_query = array();
        if (!isset($params['bind']) || !is_array($params['bind'])) {
            $params['bind'] = array();
        }
        foreach ($custom_fields as $name => $value) {
            $custom_query[] = "{$name} = :_custom{$name}:";
            $params['bind']["_custom{$name}"] = $value;
        }
        $custom_query = implode(' AND ', $custom_query);
        if ($custom_query) {
            if ($conditions) {
                $conditions = "({$custom_query}) AND {$conditions}";
            }else {
                $conditions = $custom_query;
            }
        }

        if (!self::staticHaveDatabaseTable()) {
            $params['conditions'] = $conditions;
            return $params;
        }


        $idField = static::getUniqueField();
        if (!array_key_exists('columns', $params)) {
            $columns = array("{$class}.{$idField}");
            foreach ($list as $cl => $values) {
                if ($cl != $class) {
                    $joins[] = $cl;
                    $params['joins'][] = array($cl, "{$cl}.{$idField} = {$class}.{$idField}", null, null);
                }
                foreach ($values as $field => $tmp) {
                    if ($all_columns || !array_key_exists($field, $custom_fields)) {
                        $columns[] = "{$cl}.{$field}";
                    }
                }
            }
            $params['columns'] = $columns;
        } elseif (($key = array_search($idField, $params['columns'])) !== false) {
            $params['columns'][$key] = preg_replace("/(^|\\s|[^:\\w]){$idField}([^:\\w]|\\s|$)/",
                "\$1{$class}.{$idField}\$2",
                $params['columns'][$key]);
        }


        $conditions = preg_replace("/(^|\\s|[^:\\w]){$idField}([^:\\w]|\\s|$)/", "\$1{$class}.{$idField}\$2", $conditions);
        $params['conditions'] = $conditions;

        foreach (['order', 'column', 'group'] as $field) {
            if (isset($params[$field])) {
                $params[$field] = preg_replace("/(^|\\s|[^:\\w]){$idField}([^:\\w]|\\s|$)/", "\$1{$class}.{$idField}\$2",
                    $params[$field]);
            }
        }
        return $params;
    }

    public static function toObject($item)
    {
        $class = get_called_class();
        $record = new $class();
        if (is_a($item, '\Phalcon\Mvc\Model\Row')) {
            foreach ($item->toArray() as $field => $value) {
                $record->$field = $value;
            }
        }
        return $record;
    }

    public static function findFirst($arguments = array())
    {
        if (!self::staticHaveDatabaseTable()) {
            $arguments = self::calculateParams($arguments);
        }
        return forward_static_call_array(array(parent::class, 'findFirst'), array($arguments));
    }

    public static function find($arguments = array())
    {
        if (!self::staticHaveDatabaseTable()) {
            $arguments = self::calculateParams($arguments);
        }
        return forward_static_call_array(array(parent::class, 'find'), array($arguments));
    }

    public static function count($arguments = array())
    {
        if (!self::staticHaveDatabaseTable()) {
            $arguments = self::calculateParams($arguments);
        }
        return forward_static_call_array(array(parent::class, 'count'), array($arguments));
    }

    public static function sum($arguments = array())
    {
        if (!self::staticHaveDatabaseTable()) {
            $arguments = self::calculateParams($arguments);
        }
        return forward_static_call_array(array(parent::class, 'sum'), array($arguments));
    }

    public static function average($arguments = array())
    {
        if (!self::staticHaveDatabaseTable()) {
            $arguments = self::calculateParams($arguments);
        }
        return forward_static_call_array(array(parent::class, 'average'), array($arguments));
    }

    public static function maximum($arguments = array())
    {
        if (!self::staticHaveDatabaseTable()) {
            $arguments = self::calculateParams($arguments);
        }
        return forward_static_call_array(array(parent::class, 'maximum'), array($arguments));
    }

    public static function minimum($arguments = array())
    {
        if (!self::staticHaveDatabaseTable()) {
            $arguments = self::calculateParams($arguments);
        }
        return forward_static_call_array(array(parent::class, 'minimum'), array($arguments));
    }

    public static function __callStatic($name = '', $arguments = array())
    {
        if ($name == 'fullFind') {
            return parent::find(self::calculateParams(current($arguments)));
        } elseif ($name == 'fullFindFirst') {
            return parent::findFirst(self::calculateParams(current($arguments)));
        } elseif (preg_match('/^fullFindFirstBy(?P<column>[A-z][\w]+)$/', $name, $match)) {
            $column = self::realColumnName($match['column']);
            return parent::findFirst(self::calculateParams([
                "{$column} = :value:",
                'bind' => [
                    'value' => current($arguments),
                ],
            ]));
        } elseif (in_array($name, ['fullCount', 'fullSum', 'fullAverage', 'fullMaximum', 'fullMinimum'])) {
            $name = strtolower(substr($name, 4));
            return parent::$name(self::calculateParams(current($arguments)));
        } elseif (!self::staticHaveDatabaseTable() && preg_match('/^findFirstBy(?P<column>[A-z][\w]+)$/', $name,
                $match)
        ) {
            $column = self::realColumnName($match['column']);
            return parent::findFirst(self::calculateParams([
                "{$column} = :value:",
                'bind' => [
                    'value' => current($arguments),
                ],
            ]));
        }
        return forward_static_call_array(array(parent::class, $name), $arguments);
    }
}
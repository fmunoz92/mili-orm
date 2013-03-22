<?php

class Config
{
    private $vars;
    private static $instance;

    private function __construct()
    {
        $this->vars = array();
    }

    //Con set vamos guardando nuestras variables.
    public function set($name, $value)
    {
        if(!isset($this->vars[$name]))
        {
            $this->vars[$name] = $value;
        }
    }

    //Con get('nombre_de_la_variable') recuperamos un valor.
    public function get($name)
    {
        if(isset($this->vars[$name]))
        {
            return $this->vars[$name];
        }
    }

    public static function singleton()
    {
        if (!isset(self::$instance)) {
            $c = __CLASS__;
            self::$instance = new $c;
        }

        return self::$instance;
    }
}

final class SPDO extends PDO
{
    static private $instance;

    public function __construct()
    {
      try
      {
          $config = Config::singleton();
          parent::__construct("mysql:host={$config->get('dbhost')}; dbname={$config->get('dbname')}",
                              $config->get('dbuser'),
                              $config->get('dbpass'));
          $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      }
      catch (PDOException $e)
      {
          echo 'Connection failed: ' . $e->getMessage();
      }
    }

    static public function getInstance()
    {
        if (!isset(self::$instance))
          self::$instance = new SPDO();
        return self::$instance;
    }
}

/**
* Responsabilidad: generar consultas select en modo string
* quienes hereden deben implementar el metodo "run" que la ejecuta en alguna bd
*/
abstract class Query
{
    private $_query = array(
        "from"   => null, // FROM parts
        "group"  => null, // GROUP parts
        "join"   => null, // JOIN parts
        "limit"  => null, // LIMIT parts
        "order"  => null, // ORDER BY parts
        "select" => null, // SELECT parts, proyeccion
        "where"  => null  // WHERE parts
    );

    abstract protected function run();

    protected static function create()
    {
        return new Query();
    }

    protected function getQuery()
    {
        return  ( $this->_query["select"] ? $this->_query["select"] : "SELECT *" )
                . $this->_query["from"]
                . $this->_query["join"]
                . $this->_query["where"]
                . $this->_query["group"]
                . $this->_query["order"]
                . $this->_query["limit"]
                . ";";
    }

    public function from($table = null, $alias = null)
    {
        // set alias
        $alias = $alias ? " AS {$alias}" : null;

        // check if FROM clause set
        if(!$this->_query["from"])
            $this->_query["from"] = " FROM {$table}{$alias}";
        else
            $this->_query["from"] .= ", {$table}{$alias} ";

        return $this;
    }

    public function group($field = null, $asc = true)
    {
        // check if GROUP BY set
        if(!$this->_query["group"])
            $this->_query["group"] = " GROUP BY ";
        else
            $this->_query["group"] .= ", ";

        $this->_query["group"] .= $field;

        return $this;
    }

    public function join($table = null, $alias = null, $on = null)
    {
        $this->_query["join"] .= " JOIN {$table} " . ( $alias ? "AS {$alias} " : null)
            . " " . ( $on ? "ON {$on} " : null );

        return $this;
    }

    public function joinLeft($table = null, $alias = null, $on = null)
    {
        $this->_query["join"] .= " LEFT JOIN " . $table . ( $alias ? " AS {$alias} " : null)
            . " " . ( $on ? "ON {$on} " : null );

        return $this;
    }

    public function limit($row_count, $offset = 0)
    {

        $this->_query["limit"] = " LIMIT " . ( $offset ? "{$offset}, " : null ) . "{$row_count} ";

        return $this;
    }

    public function order($field, $asc = true)
    {
        if(!$this->_query["order"])
            $this->_query["order"] = " ORDER BY ";
        else
            $this->_query["order"] .= ", ";

        // add ORDER BY field, add DESC if not ASC
        $this->_query["order"] .= $field . ( $asc ? null : " DESC" );

        return $this;
    }

    public function select($fields = null, $alias = null, $distinct = false)
    {
        // set alias
        $alias = $alias ? " AS '{$alias}'" : null;

        if(!$this->_query["select"])
            // add SELECT keywords, add DISTINCT keyword if needed
            $this->_query["select"] = "SELECT " . ( $distinct ? "DISTINCT " : null ) . " {$fields} {$alias} ";
        else
            // add fields
            $this->_query["select"] .= ", {$fields} {$alias} ";

        return $this;
    }

    public function selectDistinct($fields = null)
    {
        return $this->select($fields, true);
    }

    public function where($field, $value, $operator = "=", $pre_keyword = null)
    {
        if(!$this->_query["where"])
            $this->_query["where"] = " WHERE";

        $this->_query["where"] .= " {$pre_keyword} {$field} {$operator} '{$value}'";

        return $this;
    }

    public function whereEqual($field = null, $value = null, $pre_keyword = null)
    {
        return $this->where($field, $value, "=", $pre_keyword );
    }

    public function andWhereEqual($field = null, $value = null)
    {
        return $this->where($field, $value, "=", "AND");
    }

    public function orWhereEqual($field = null, $value = null)
    {
        return $this->where($field, $value, "=", "OR");
    }

    public function andWhereNotEqual($field = null, $value = null)
    {
        return $this->where($field, $value, "!=", "AND");
    }

    public function orWhereNotEqual($field = null, $value = null)
    {
        return $this->where($field, $value, "!=", "OR");
    }

    public function andWhere($field = null, $value = null, $operator = "=")
    {
        return $this->where($field, $value, $operator, "AND");
    }

    public function orWhere($field = null, $value = null, $operator = "=")
    {
        return $this->where($field, $value, $operator, "OR");
    }
}

/**
* Resposabilidad ejecutar una consulta Query y devolver un array asociativo con los resultados
*/
class AdvancedQuery extends Query
{

    protected function __construct()
    {
        $this->db = SPDO::getInstance();
    }

    public static function create()
    {
        return new AdvancedQuery();
    }

    /*
     *
     * Retorna un arreglo asociativo "Campo" => "Valor"
     * no retorna objetos-modelos ya que estas consultas pueden tener joins, proyecciones etc.
     */
    public function run()
    {
        $result = array();

        $query = $this->getQuery();
        $sth = $this->db->query($query);
        $sth->setFetchMode(PDO::FETCH_ASSOC);

        while($row = $sth->fetch())
            array_push($result, $row);

        return $result;
    }

    public function first()
    {
        $ar = $this->run();
        return $ar[0];
    }
}

/*
 *
 * Responsabilidad: Encapsular valores y acceso a campos dados.
 *
 */
class SetAndGet
{
    private $values;//array para acceder directamente a cada var
    public $model_fields;

    protected function __construct()
    {
        $this->values = array();

        foreach ($this->model_fields as $field)
            $this->values[$field->getName()] = $field;
    }

    public function set($field, $value)
    {
        $this->values[$field]->setValue($value);
    }

    public function get($field)
    {
        return $this->values[$field]->getValue();
    }
}

/*
 *
 * Responsabilidad: Determinar el estado del modelo con sus campos y segun el estado
 *                  utilizar los metodos abstractos insertar o actualizar o borrar.
 *
 */
abstract class ModelBase extends SetAndGet
{
    public $model_table_name;

    protected $changed;
    protected $unsaved;

    public function saved()
    {
        $this->unsaved = false;
    }

    protected function __construct()
    {
        parent::__construct();
        $this->unsaved = true;
        $this->chaged  = false;
    }

    abstract protected function insert();

    abstract protected function update();

    abstract protected function delete();

    public function set($field, $value)
    {
        parent::set($field, $value);
        $this->chaged = true;
    }

    public function save()
    {
        try
        {
            if($this->unsaved)
                $this->insert();
            else if ($this->chaged)
                $this->update();

            $this->unsaved = false;
            $this->chaged = false;
        }
        catch(Exception $e)
        {
            return false;
        }

        return true;
    }


    public function destroy()
    {
        if(!$this->unsaved)
        {
            $this->delete();
            //reset
            $this->unsaved = true;
            $this->chaged = true;
        }
    }
}


abstract class Exec
{
    protected $db;
    protected function __construct()
    {
        $this->db = SPDO::getInstance();
    }

    abstract public function getSQL();

    public function run()
    {
        $sql = $this->getSQL();
        $query = $this->db->prepare($sql);
        $query->execute();
    }

    public function lastInsertId()
    {
        return $this->db->lastInsertId();
    }
}

class CreateTable extends Exec
{
    private $fields;
    private $engine;
    private $table_name;

    public static function create()
    {
        return new CreateTable();
    }  

    public function table($table_name)
    {
        $this->table_name = "CREATE TABLE IF NOT EXISTS ". $table_name;

        return $this;
    }
    
    public function engine($engine)
    {
        $this->engine = $engine;
        return $this;
    }
    
    public function field($name, $type)
    {
        if (!$this->fields)
            $this->fields =  " ( " . $name . " " . $type;
        else
            $this->fields .= ", " . $name . " " . $type;
        return $this;
    }
    
    public function fields($fields)
    {
        foreach ($fields as $field)
            $this->field($field->getName(), $field->getType());
        return $this;
    }

    public function getSQL()
    {
        $engine = ($this->engine) ? $this->engine : "InnoDB";

        $sql = $this->table_name . " " . $this->fields . ") ENGINE = ".$engine.";";

        return $sql;
    }
}

/**
*
*/
class Insert extends Exec
{
    private $insert = null;
    private $fields = null;
    private $values = null;

    public static function create()
    {
        return new Insert();
    }

    public function table($table)
    {
        $this->insert = "INSERT INTO {$table} ";

        return $this;
    }

    public function field($field, $value='')
    {
        if (!$this->fields)
        {
            $this->fields = "(";
            $this->values = ") VALUES (";
        }
        else
        {
            $this->fields .= ", ";
            $this->values .= ", ";
        }

        $this->fields .= $field;
        $this->values .= "'{$value}'";

        return $this;
    }

    public function getSQL()
    {
        return $this->insert . $this->fields . $this->values . ");";
    }
}

/**
*
*/
class Update extends Exec
{
    private $update = null;
    private $fields = null;
    private $where = null;

    public static function create()
    {
        return new Update();
    }

    public function table($table)
    {
        $this->update = "UPDATE {$table} SET ";

        return $this;
    }

    public function field($field, $value='')
    {
        if (!$this->fields)
            $this->fields = "(";
        else
            $this->fields .= ", ";

        $this->fields .= $field . " = " . $value;

        return $this;
    }

    public function fields($fields)
    {
        foreach ($fields as $field)
            $this->field($field);

        return $this;
    }

    public function where($field = "id", $value, $operator = "=")
    {
        if(!$this->where)
            $this->where = "WHERE";

        $this->where .= "{$field} {$operator} '{$value}' ";

        return $this;
    }

    public function id($id)
    {
        return $this->where("id", $id, "=");
    }

    public function getSQL()
    {
        return $this->update . $this->fields . ") " . $this->where . ";";
    }
}

class Delete extends Exec
{
    private $delete = null;
    private $where = null;

    public static function create()
    {
        return new Delete();
    }

    public function table($table)
    {
        $this->delete = "DELETE FROM {$table} ";

        return $this;
    }

    public function where($field, $value, $operator = "=", $pre_keyword = null)
    {
        if(!$this->where)
            $this->where = " WHERE";

        $this->where .= " {$pre_keyword} {$field} {$operator} {$value}";

        return $this;
    }

    public function id($id)
    {
        return $this->where("id", $id, "=");
    }

    public function getSQL()
    {
        return $this->delete . $this->where . ";";
    }
}

/**
*
* Tener instancia de la coneccion a la base de datos,
* generar y ejecutar las inserciones, actualizaciones y borrados del modelo
*/
class Model extends ModelBase
{
    protected $db;
    protected function __construct()
    {
        parent::__construct();
        $this->db = SPDO::getInstance();
    }

    protected function insert()
    {
        $insert = Insert::create()->table($this->model_table_name);

        foreach ($this->model_fields as $field)
        {
            $field_value = $this->get($field->getName());
            if(!empty($field_value))
                $insert->field($field->getName(), $field_value);
        }

        $insert->run();
        $this->set("id", $insert->lastInsertId());
    }

    protected function delete()
    {
        Delete::create()->table($this->model_table_name)->id($this->get("id"))->run();
    }

    protected function update()
    {
        Update::create()->table($this->model_table_name)->fields($this->fields)->id($this->get("id"))->run();
    }
}


/**
* Responsabilidad: ejecutar consultas que devuelvan los Objetos correspondientes a tal modelo
* TODO: poner como privado el from, group y el select
*/
class ModelQuery extends Query
{
    protected $db;
    protected $class_name;
    protected $table_name;
    protected $fields;//TODO: usarlos para el select sin *

    function __construct($class_name, $fields, $table_name)
    {
        $this->db = SPDO::getInstance();
        $this->class_name = $class_name;
        $this->fields = $fields;
        $this->table_name = $table_name;

        $this->from($table_name);

        foreach ($fields as $field)
            $this->select($field->getName());
    }

    private function creator($sth, &$object)
    {
        $result = false;

        if($row = $sth->fetch())
        {
            $object = new $this->class_name(false);

            foreach ($this->fields as $field)
                $object->set($field->getName(), $row[$field->getName()]);

            $object->saved();
            $result = true;
        }

        return $result;
    }

    public function run()
    {
        $query = $this->getQuery();
        $query;
        $sth = $this->db->query($query);
        $sth->setFetchMode(PDO::FETCH_ASSOC);

        $result = array();
        $object = null;

        while($this->creator($sth,$object))
        {
            array_push($result, $object);
            $object = null;
        }

        return $result;
    }

    public function first()
    {
        $ar = $this->run();
        return $ar[0];
    }
}

abstract class TypeField
{
    protected $type;

    function getType()
    {
       return $this->type;
    }
}

abstract class Field extends TypeField
{
    protected $name;
    protected $value;

    function getName()
    {
        return $this->name;
    }

    function getValue()
    {
        return $this->value;
    }

    function setValue($value)
    {
        $this->value = $value;
    }
}

class IdField extends Field
{
    function __construct()
    {
        $this->name = "id";
        $this->type = "INT NOT NULL AUTO_INCREMENT PRIMARY KEY";
    }
}

class ForeignKeyField extends Field
{
    private $obj;
    function __construct($name, $model = null, $notNull = false)
    {
        $this->name = $name;
        $this->model = $model;
        $this->notNull = $notNull;
        $this->type = "INT UNIQUE";
        $this->type .= ($notNull)? " NOT NULL" : "";
        $this->obj = null;
    }

    function getValue()
    {
        if($this->obj == null)
            $this->obj = $model::$objects->find("id", parent::getValue());
        return $this->obj;
    }

    function setValue($value)
    {
        parent::setValue($value);
        $this->obj = null;
    }
}

class CharField extends Field
{
    function __construct($name, $length = 100, $notNull = false, $unique = false)
    {
        $this->name = $name;
        $this->length = $length;
        $this->notNull = $notNull;
        $this->unique = $unique;
        $this->type = "VARCHAR(".$this->length.")" ;
        $this->type .= ($this->notNull)? " NOT NULL" : "";
        $this->type .= ($this->unique)? " UNIQUE" : "";
    }
}

class IntField extends Field
{
    function __construct($name, $length = 100, $notNull = false, $unique = false)
    {
        $this->name = $name;
        $this->length = $length;
        $this->notNull = $notNull;
        $this->unique = $unique;
        $this->type = "int(".$this->length.")" ;
        $this->type .= ($this->notNull)? " NOT NULL" : "";
        $this->type .= ($this->unique)? " UNIQUE" : "";
    }
}

class DateField extends CharField
{
    function __construct($name, $length = 100, $notNull = false, $unique = false)
    {
        parent::__construct($name, $length, $notNull, $unique);
    }
};

class URLField extends CharField
{
    function __construct($name, $length = 100, $notNull = false, $unique = false)
    {
        parent::__construct($name, $length, $notNull, $unique);
    }
};

class BooleanField extends IntField
{
    function __construct($name, $length = 1, $notNull = false, $unique = false)
    {
        parent::__construct($name, $length, $notNull, $unique);
    }

    function getValue()
    {
        if(parent::getValue() == 0)
            return false;
        else
            return true;
    }

    function setValue($value)
    {
        $valInt = ($value)? 1 : 0;
        parent::setValue($valInt);
    }
};

/*
 *
 * Responsabilidad: Dar acceso a la creacion de consultas y ejecucion de consultas basicas.
 *
 */
class Objects
{
    protected $db;
    protected $class_name;
    protected $table_name;
    protected $fields;

    function __construct($class_name, $fields, $table_name)
    {
        $this->db = SPDO::getInstance();
        $this->class_name = $class_name;
        $this->fields = $fields;
        $this->table_name = $table_name;
    }

    public function find($column = "id", $value)
    {
        $ar = $this->createQuery()->where($column, $value,"=")->limit(1)->run();

        if (count($ar) == 0)
            return null;
        else
            return $ar[0];
    }

    public function getAll($order="id", $asc = true)
    {
        $ar = $this->createQuery()->order($order, $asc)->run();

        return $ar;
    }

    public function createQuery()
    {
        return new ModelQuery($this->class_name, $this->fields, $this->table_name);
    }
   
    public function getSQLModel()
    {
        return CreateTable::create()->table($this->table_name)->fields($this->fields)->getSQL();
    }
}

/*
 *
 * Responsabilidad: inicializar las variables estaticas correspondientes al modelo dado
 *
 */
function REGISTER_MODEL($model)
{
    $tmp = new $model();
    $model::$objects = new Objects($model, $tmp->model_fields, $tmp->model_table_name);
}

?>

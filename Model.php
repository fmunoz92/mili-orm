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
		{
			self::$instance = new SPDO();
        }

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
     * no retorna objetos ya que estas consultas pueden tener joins, proyecciones etc.
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
    private $values;//solo accesibles a traves de set y get
    protected $fields;

    protected function __construct(&$fields)
    {
        $this->values = array();
        $this->fields = &$fields;
        
        foreach ($fields as $field)
            $this->values[$field] = "";
    }

    public function set($field, $value)
    {
        $this->values[$field] = $value;
    }

    public function get($field)
    {
        return $this->values[$field];
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
    protected $table_name;

    protected $changed;
    protected $unsaved;

    public function saved()
    {
        $this->unsaved = false;
    }
    protected function __construct($table_name, $fields = array()) 
    {
        parent::__construct($fields);
        $this->table_name = $table_name;
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

    public function getSQL()
    {
        return $this->delete . $this->where . ";";
    }
}


/**
* 
* Tener instancia de la coneccion a la base de datos, 
* generar y ejecutar las inserciones, actualizaciones y borrados del modelo
* TODO: quitar responsabilidad de generar, hacer una clase similar a query para insercion, borrado y actualizacion
*/
class Model extends ModelBase
{
    protected $db;
    protected function __construct($table_name, $fields = array())
    {
        parent::__construct($table_name, $fields);
        $this->db = SPDO::getInstance();
    }

    protected function insert()
    {
       
        $insert = Insert::create()->table($this->table_name);

        foreach ($this->fields as $field) 
        {
            $field_value = $this->get($field);
            if(!empty($field_value))
                $insert->field($field, $field_value);
        }
        
        $insert->run();
        $this->set("id", $insert->lastInsertId());
    }

    protected function delete()
    {
        $delete = Delete::create()->table($this->table_name);
        $delete->where("id", $this->get("id"))->run();
    }

    /**
        TODO: HACER CLASE UPDATE
    */    
    protected function update()
    {
        $sql = "UPDATE {$this->table_name} SET ";

        foreach ($this->fields as $field)
            $sql .= $field . " = ?, "; 

        $sql = substr($sql, 0, (-2));

        $sql .= " WHERE id = {$this->get('id')};";
        
        $q = $this->db->prepare($sql);
        $values = array();

        foreach ($this->fields as $field)
            array_push($values, $this->get($field));

        $q->execute($values);
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
            $this->select($field);
    }

    private function creator($sth, &$object) 
    {
        $result = false;

        if($row = $sth->fetch())
        {
            $object = new $this->class_name(false);
            
            foreach ($this->fields as $field)
                $object->set($field, $row[$field]);
            
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
}

/*
 *
 * Responsabilidad: inicializar las variables estaticas correspondientes al modelo dado
 *
 */
function REGISTER_MODEL($model)
{
    $model::$objects = new Objects($model, $model::$model_fields, $model::$model_table_name);
}


/*
 *----------------------------------------------------------------------------------
 *
 *
 *         Documentacion
 *
 *
 *----------------------------------------------------------------------------------
 */

/*
 *
 * Plantilla
 *
 */
/*

Setear estos valores antes de incluir la libreria
$config = Config::singleton();
$config->set('dbhost', 'localhost');
$config->set('dbname', '');
$config->set('dbuser', 'root');
$config->set('dbpass', '');

class ModelName extends Model
{   
    //Atributos obligatorios:
    static $model_table_name = ""; //nombre de la tabla
    static $model_fields = array('id');//campos, siempre deben tener un campo integer id como clave
    static $objects;//RegisterModel la inicializara, solo declararla.

    function __construct() 
    {
        parent::__construct(self::$model_table_name, self::$model_fields);//Llamada oboligatoria, no cambia nunca
    }
}
REGISTER_MODEL("ModelName");//Registro obligatorio
*/

?>

<?php
require '../Model.php';
require '../Form.php';

$config = Config::singleton();
$config->set('dbhost', 'localhost');
$config->set('dbname', 'example');
$config->set('dbuser', 'root');
$config->set('dbpass', '1');

class Ambiente extends Model
{
    static $objects;
    function __construct()
    {
        $this->model_table_name = "ambiente";
        $this->model_fields     = array(new IdField(),
                                        new BooleanField('mode360'),
                                        new ImageField('imagen'), 
                                        new ForeignKeyField('mapa', 'Mapa'));
        parent::__construct();
    }
};
REGISTER_MODEL("Ambiente");


class Mapa extends Model
{
    static $objects;
    function __construct()
    {
        $this->model_table_name = "mapa";
        $this->model_fields     = array(new IdField(),
                                        new CharField('titulo'),
                                        new CharField('descripcion'));
        parent::__construct();
    }

    function __toString()
    {
        return $this->get("titulo") . " " . $this->get("id");
    }
};
REGISTER_MODEL("Mapa");

class Area extends Model
{
    static $objects;
    function __construct()
    {
        $this->model_table_name = "area";
        $this->model_fields     = array(new IdField(),
                                        new IntField('x1'),
                                        new IntField('x2'),
                                        new IntField('y1'),
                                        new IntField('y2'),
                                        new CharField('texto'),
                                        new ForeignKeyField('idAmbiente','Ambiente'));
        parent::__construct();
    }

    function __toString()
    {
        return $this->get("texto") . " " . $this->get("id");
    }
}
REGISTER_MODEL("Area");

/*
 * Creado de las tablas, si ya existen no las crea
 * Hay que borrarlas manualmente
 * */
Ambiente::$objects->createTable();
Mapa::$objects->createTable();
Area::$objects->createTable();

/*
 * Si queremos ver el sql de la tabla
 */
//echo Ambiente::$objects->getSQLModel();
//echo Mapa::$objects->getSQLModel();
//echo Area::$objects->getSQLModel();

/*Ejemplo de uso de los modelos para la carga*/
$am = new Ambiente();
$am->set("mode360", false);
$am->set("imagen", "http://www.google.com/images/dcasc.png");

$map = new Mapa();
$map->set("titulo", "TITULO");
$map->set("descripcion", "DEScripcion");
$map->save();

//$am->set("mapa", 5); asi no funciona
$am->set("mapa", $map); //asi funciona
/*$am->save();*/

$am1 = Ambiente::$objects->find("id", 1);
if ($am1 != null)
{
  $am->set("mode360", true);
  $am->set("imagen", "asf");

  $map = new Mapa();
  $map->set("titulo", "TITULO");
  $map->set("descripcion", "DEScripcion");
  $map->save();
  $am->set("mapa", $map);
  $map = null;

  $am->save();
  
  $map = $am->get("mapa");
  $map->set("titulo", "Nuevo titulo");
  $map->save();
}

$m1 = new Mapa();
$m1->set("titulo", "TITULO");
$m1->set("descripcion", "DEScripcion");
/*$m1->save();*/

/* Form example */
$htmlT = <<<EOT
<!DOCTYPE html>
<html lang="es">
	<head>
		<meta charset="utf-8">
		<title>Example</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/css/bootstrap-combined.min.css" rel="stylesheet">
		<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
		<script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.2.1/js/bootstrap.min.js"></script>
	</head>
	<body>
EOT;
$htmlF = <<<EOT
  </body>
</html>
EOT;

echo $htmlT;

$form = new FormModel($am);
$form ->render();

echo $htmlF;
?>

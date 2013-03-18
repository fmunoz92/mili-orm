Documentacion

/*
 *
 * Plantilla
 *
 */

```php

//Setear estos valores antes de incluir la libreria
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
```

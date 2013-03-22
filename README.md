Documentación

ORM minimalista, permite tener un manipulación completa de una base de datos mysql
con muy pocas lineas de código con solo incluir un archivo.

Reglas:
- Cada tabla debe tener un campo autoincrementado id.
- Un modelo por cada tabla.
- Cada modelo se representa con una clase que hereda de Model y debe definir
  las variables estáticas:

```php
    static $model_table_name = ""; //nombre de la tabla
    static $model_fields = array('id');//campos, siempre deben tener un campo integer id como clave
    static $objects;//RegisterModel la inicializara, solo declararla.
```

Plantilla y ejemplo de uso:
```php

require 'Model.php';

$config = Config::singleton();
$config->set('dbhost', 'localhost');
$config->set('dbname', '');
$config->set('dbuser', 'root');
$config->set('dbpass', '');


class ModelName extends Model
{
    static $objects;
    function __construct()
    {
        $this->model_table_name = "tableName";
        $this->model_fields     = array(new IdField(),
                                        new BooleanField('bool'),
                                        new IntField('x'),
                                        new URLField('url'),
                                        new CharField('text'),
                                        new ForeignKeyField('otherModel', 'OtherModel'));
        parent::__construct();
    }
};
REGISTER_MODEL("ModelName");
```

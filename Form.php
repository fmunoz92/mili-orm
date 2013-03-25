<?php
/* TODO: no usar PFBC, y que todo entre en este archivo! 
   TODO: Recibir post y retornar un objeto actualizado
   foreach fields as field
     $model->set(field, _$POST[field]);
   TODO: validar
    $f = Form::fromPost(_$POST)
    if ($f->isValid())
       -
   TODO: ver campos obligatorios
   TODO: en claves foraneas dar opcion a agregar nuevo objeto abriendo una nueva ventana con
         el form de el modelo correspondiente.
 */
session_start();//TODO: Cuando no se use PFBC no va a hacer falta, es para la serializacion de los post en las sessions

//http://www.imavex.com/pfbc3.x-php5/index.php
include 'vendor/PFBC/Form.php';
use PFBC\Form;
use PFBC\Element;

class FormModel
{
  private $model;
  private $form;

  function __construct($model, $action = "")
  {
      $this->form = new Form($model);
      $this->action = $action;
      $this->model = $model;
      $this->setUp();
  }

  function render()
  {
    $this->form->render();
  }

  /*TODO: FEO, cada field deberia saber que elemento en el formulario le corresponde */
  function getElementByField($field)
  {
    $element = null;
    $required = 1;//TODO: poner en los modelos cual es requerido o no
    $arrayOptions = array("required" => $required, "value" => $field->getValue());
    switch (get_class($field)) {
      case "IdField":
        break;
      case "FileField":
        $element = new Element\File($field->getLabel(), $field->getName(), $arrayOptions);
        break;
      case "ImageField":
        $element = new Element\File($field->getLabel(), $field->getName(), $arrayOptions);
        break;
      case "ForeignKeyField":
        $M = $field->model;
        $all = $M::$objects->getAll();

        $options = array();

        foreach($all as $obj)
          $options[$obj->get("id")] = (string)$obj;

        $arrayOptions["value"] = $field->getValue()->get("id");
        $element = new Element\Select($field->getLabel(), $field->getName(), $options, $arrayOptions);
        break;
      case "CharField":
        $element = new Element\Textbox($field->getLabel(), $field->getName(), $arrayOptions);
        break;
      case "TextField":
        $element = new Element\Textarea($field->getLabel(), $field->getName(), $arrayOptions);
        break;
      case "IntField":
        $element = new Element\Number($field->getLabel(), $field->getName(), $arrayOptions);
        break;
      case "DateField":
        $element = new Element\Date($field->getLabel(), $field->getName(), $arrayOptions);
        break;
      case "URLField":
        $element = new Element\Url(
                                  $field->getLabel(), 
                                   $field->getName(), 
                                   $arrayOptions);
        break;
      case "BooleanField":
        $options = array($field->getName() => "");
        $element = new Element\Checkbox($field->getLabel(), $field->getName(), $options ,$arrayOptions);
        break;
    }
    return $element;
  }

  function setUp()
  {
    $this->form->configure(array(
        "prevent" => array("bootstrap", "jQuery"),
        "action" => $this->action,
        "method" => "post"
    ));

    $this->form->addElement(new Element\Hidden("form", "sidebyside"));

    foreach ($this->model->model_fields as $field)
    {
      $element = $this->getElementByField($field);
      if($element != null)
          $this->form->addElement($element);
    }

    $this->form->addElement(new Element\Button("Guardar"));
  }
}



/*
abstract class Tag
{
  protected $class = "";
  protected $id = "";
  protected $text = "";

  abstract function begin();
  abstract function end();

  function insert($text)
  {
    $this->text = $text;
  }

  function getClass()
  {
      return $this->class;
  };
  function getId()
  {
      return $this->id;
  };
  function setClass($class)
  {
      $this->class = $class;
  }
  function setId($id)
  {
      $this->id = $id;
  }
}

class Form extends Tag
{
  function begin()
  {
    return "<form class='bs-docs-example'>";
  }
  function end()
  {
    return "</form>";
  }
}
class FieldSet extends Tag
{
  function begin()
  {
    return "<fieldset>";
  }
  function end()
  {
    return "</fieldset>";
  }
}
class Legend extends Tag
{
  function begin()
  {
    return "<legend>";
  }
  function end()
  {
    return "</legend>";
  }
}
class Span extends Tag
{
  function begin()
  {
    return "<span>";
  }
  function end()
  {
    return "</span>";
  }
}
class Label extends Tag
{
  function begin()
  {
    return "<label>";
  }
  function end()
  {
    return "</label>";
  }
}
class Button extends Tag
{
  function begin()
  {
    return "<button>";
  }
  function end()
  {
    return "</button>";
  }
}
abstract class Input extends Tag
{
  abstract function getType();
  function begin()
  {
    return "<input type='". $this->getType() ."'>";
  }
  function end()
  {
    return "";
  }
}
class TextInput extends Input
{}
class CharInput extends Input
{}
class IntInput extends Input
{}
class ForeignKeyInput extends Input
{}

$html = new HTMLPart();
$html->addthis()->addFieldSet()->addHTMLPart()->addLegend()->addLabel()->addInput()->addSpan()->addHTMLPart()->addLabel()->addInput();


addX() -> (grafo,IDlista)
tiene que poner el nuevo elemento y retornar un objeto que contenga el grafo y el id de la lista a la
que debe agregarse, si es null entonces es un nuevo objetos.

[this1]  = (Field1)
[field1] = (legent1) -> (label1) -> (input1) -> (span1)
[legent1] = []
[label1] = []
[input1] = []
[span1]  = (label2) -> (input2)
*/
?>

# Image
Libreria adaptada para symfony **3.x**, **4.x**
Recorta imagenes que se obtienen desde los ficheros temporales(/tmp) del servidor.En esta carpeta se suele guardar los datos pasados por formularios, etc. Recorta las miniaturas de la imagen centrada segun los parametros que se le pase y retorna un array con la ruta donde se guardaron y genera minuaturas. Estas imagenes son guardadas en una carpeta alojada en "/web" o "/public".

## Índice
* [¿Como funciona?](#how-work-image)

[SearchGloom](/src/searchGloom.md)


## ¿Como funciona?
<a name="how-work-image"></a> 
 - Primero: Obtienes la ruta donde se encuentra la imagen temporal.
 - Segundo: Instancia la clase **Imagen()**
 - Tercero: Inserta el nombre de la imagen con el metodo setName()
 - Cuardo: Inserta la imagen temporal, el nombre de la carpeta donde se guarda y el callBack donde llamar las funciones de Thumbnail
 - Quinto: **save()**  retorna un array con los datos meta de la imagen guardada.
 
Llama la libreria
```php 
use Bitsystem\Backend\Image;
```
 
```php
//Obtiene la imagen pasada por un formulario
$imageBinary = $request->files->get('image', FALSE);

//Instancia la clase
$image = new Image();
$image->setName($request->request->get('name', NULL));
$image->insertImage($imageBinary, 'course',function($e){
    $e->thumbnail(40,40);
    $e->thumbnail(364,205);
    $e->thumbnail(1080,720);
});

//Retorna un array
$imageSave = $image->save();

//Recorre el array para obtener el path de la imagen
foreach ($imageSave['thumbnails'] as $value){
    $value['route'];
    $value['width'];
    $value['height'];
}
```


# Search Pro

Search pro es una libreria pensada para mostrar las tablas de una base de datos nombrando la tabla y las columnas que necesitamos visualizar. Ademas puedes filtrar las tablas por las columnas. Incluye un sistema de paginacion que busca los resultados de la tabla por Ajax segun los filtros seleccionados.

## Índice    
* [¿Como funciona?](#how-work)
  * [Twig](#twig)
  * [Php](#php)
* [Parametros](#params)
  * [Paginacion](#paging)
  * [Info](#info)
  * [setTable](#table)([string](http://php.net/manual/es/language.types.string.php))
  * [addFilter](#add-filter)([array](http://php.net/manual/es/language.types.array.php))
  * [addColum](#add-colum)([array](http://php.net/manual/es/language.types.array.php))
  * [addColumForeignKey](#colum-foreign-key)([array](http://php.net/manual/es/language.types.array.php))
  * [selectRow](#select-row)([array](http://php.net/manual/es/language.types.array.php))
  * [search](#search)([string, string](http://php.net/manual/es/language.types.string.php))
  * [getResult()](#result)
* [Estructura de Tablas](#params)

<a name="add-colum"></a>
<a name="add-filter"></a>
<a name="colum-foreign-key"></a>
<a name="select-row"></a>
<a name="search"></a>
<a name="result"></a> 


## ¿Como funciona?
<a name="how-work"></a> 
Accedemos a la terminal y instalamos con composer la libreria en el composer.json de nuestro proyecto Symfony
```composer
composer require bitsystem/searchpro
```
<a name="table"></a> 
### Twig
<a name="twig"></a> 
Necesitamos agregar primero el select2.js necesario para poder visualizar los filtros de la tabla. Despues tableList.js al final.
```twig
{% blocks javascripts %}
    //Dependencias
    <script type="text/javascript" src="{{ asset('assets/select2/dist/js/select2.min.js') }}"></script>
    //Plugin para generar la tabla
    <script src="{{asset('js/tableList.js')}}"></script>
{% endblocks %}
```
## Php
Primero incluimos la libreria en el controlador.
```php
use Bitsystem\SearchPro\Search as Search;
```
Recogemos el texto a buscar y el filtro a buscar. Instancion la clase y usas los metodos que necesites para mostrar las columnas.
```php
//Campo de busqueda
$textSearch = $request->request->get('q','');

//Parametros del filtro
$filter = $request->request->get('filter','');
$search = new Search($this->getDoctrine()->getManager(), $request);
$result = $search->setTable('Clientes')
    ->addColum([
                'id' => 'id',
                'nombre' => 'nombre'
    ])
    ->selectRow([	1 => 1,
                    10 => [ 10, true ],
                    20 => 20,
                    30 => 30,
                    40 => 40,
                    'Todo' => 'all'
                ])
    ->search('nombre',$textSearch)
    ->getResult();
    return $result;
```
## Parametros
<a name="params"></a>
### Paginación
<a name="paging"></a> 
Si necesitamos ocultar la paginacion de la tabla solo tenemos que añadir la opcion paging. Por defecto el valor de paging es 
```TRUE``` 
```
paging: false
```
### Numero de resultados
<a name="info"></a> 
El numero de resultados totales de columnas de la tabla se muestra debajo de la paginacion. Por defecto el valor de info es ```TRUE``` 
Si necesitamos ocultar la informacion solo tenemos que añadir la opcion info.
```
info: false
```

### addColum
<a name="add-colum"></a> 
```php
->addColum([
            'nombre variable' => 'Nombre columna Tabla'
])
```

<a name="table"></a> 
Metodo encargado de obtener el nombre de la tabla principal para poder hacer las consultas. La tabla principal se relaciona con otras tablas con los metodos [addColumForeignKey()](#colum-foreign-key) , [addColumForeignKey()](#colum-foreign-key)
```php
->setTable('Nombre tabla')
```

<a name="colum-foreign-key"></a>
```php
->addColumForeignKey([
          'POPULATION' => [
                          'type' => 'select',
                          'table' => 'MUNICIPALITY',
                          'columReference' => 'MUNICIPALITY',
                          'colum' => 'POPULATION'
                      ]
     ])
```
Para implementarlo necesitamos insertar en nuestro controlador y en la vista twig algunos archivos necesarios.


Estructura basica html
```html
<div class="search-pro">
    <div class="card">
        <div class="card-body">
            
            <div class="row">
                <div class="col-xl-10 col-sm-10">
                    <label>Buscar:</label>
                    <input type="search" id="find" class="form-control">
                </div>  
                <div class="col-xl-2 col-sm-2">
                    <button id="find-ajax" class="btn btn-primary waves-effect waves-light">Buscar</button> 
                </div>
            </div>
            <div class="row">
                {% if rowList|length %}
                    <div class="col-sm-4 col-md-2">
                        <div class="form-group">
                            <label class="col-sm-12 control-label col-md-12">Resultados: </label>
                            <div class="col-sm-8 col-md-12">
                                <select class="form-control" name="row-result">
                                {% for key,selector in rowList if rowList %}
                                    {% if selector is iterable and selector['active'] %}
                                        {% set selector = selector[0] %}
                                        {% set check = 'selected' %}
                                    {% else %}
                                        {% set selector = selector %}
                                        {% set check = '' %}
                                    {% endif %}
                                    <option value="{{selector}}" {{check}}>{{key}}</option>
                                {% endfor %}                                
                                </select>
                            </div>
                        </div>
                    </div>
                {% endif %}
                {% for key,selector in filter %}
                    {% if selector is defined %}
                        <div class="col-xl-2 col-sm-4 filter">
                            <div class="form-group">
                                <div class="form-group row">
                                    <label class="control-label">{{selector.label}}</label>
                                    <select class="select2 {{key}} form-control select2-multiple" name="{{key}}"></select>
                                </div>
                            </div>
                        </div>
                    {% endif %}
                {% endfor %}
            </div>

            <div class="table-rep-plugin">
                <div class="table-responsive">
                    <table class="table table-condensed m-b-none">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                    <h6 class="text-center info-count"></h6>                    
                </div>
            </div>
        </div>
    </div>
</div>
```

```javascript
$("#search-pro").search({
    textSend: $('#find'),
    buttonSend: $('#find-ajax'),
    paging: false,
    info: false,
    colums: ['Id','Nombre'],
    tableRow: function(data){
        var row = "";
        $.each(data.result, function(k, v){
            row += "<tr data-href='"+v.id+"'>\
                        <th>"+v.id+"</th>\
                            <th>\
                            <a>"+v.nombre+"</a>\
                        </th>\
                    </tr>";
        });
        return row;
    }
});
```
```npm install```

#### NOTA
Esta librera utiliza los metodos GET y POST debemos asegurarnos de  que tenemos habilitada la request de ellas 

### Resultado

Vista de como se ve la tabla segun los parametros indicados


![tabla](https://user-images.githubusercontent.com/24224731/43127144-08474f72-8f2f-11e8-8feb-4f778e01f2b0.JPG)

```
Give the example
```

And repeat

```
until finished
```

End with an example of getting some data out of the system or using it for a little demo

## Running the tests

Explain how to run the automated tests for this system

### Break down into end to end tests

Explain what these tests test and why

```
Give an example
```

### And coding style tests

Explain what these tests test and why

```
Give an example
```

## Deployment

Add additional notes about how to deploy this on a live system

## Built With

* [Dropwizard](http://www.dropwizard.io/1.0.2/docs/) - The web framework used
* [Maven](https://maven.apache.org/) - Dependency Management
* [ROME](https://rometools.github.io/rome/) - Used to generate RSS Feeds

## Contributing

Please read [CONTRIBUTING.md](https://gist.github.com/PurpleBooth/b24679402957c63ec426) for details on our code of conduct, and the process for submitting pull requests to us.

## Versioning

We use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Autor

* **Julian Garcia Garcia** - *Creador* - [Juliangarciaga3](https://github.com/juliangarciaga3)

Consulte también la lista de [colaboradores](https://github.com/juliangarciaga3/SearchPro/graphs/contributors) que participaron en este proyect.

## Licencia

Este proyecto está licenciado bajo la Licencia MIT - ver el [LICENSE.md](LICENSE.md) archivo para detalles

## Inspirado

* [Datatables](https://datatables.net/)
* [Bootstrap 3.3.7](https://getbootstrap.com/)

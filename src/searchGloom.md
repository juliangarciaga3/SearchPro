Añadir la libreria a nuestro controlador
```php
use Bitsystem\Backend\SearchGloom
```

Un ejemplo de uso
```php
//Instancia el objeto
$search = new SearchGloom();

//Pagina a mostrar
$search->setPage($request->request->get('page',1));

//Establece la conexion
$search->setConnection($em);

$search->setQuery("SELECT b.id as id,
        CASE
          WHEN b.master IS NOT NULL then (select c.name from burgers c WHERE c.id = b.master)
          WHEN b.master IS NULL then b.name
          END AS name
        FROM burgers AS b
        LEFT JOIN restaurant_burgers ON restaurant_burgers.burger = b.id
        LEFT JOIN burgers AS m ON m.id = b.master
        LEFT JOIN restaurant ON restaurant.id = restaurant_burgers.id_r
        WHERE restaurant.id=:id
        AND (m.name LIKE :value OR b.name LIKE :value)");

$search->setQueryCount("SELECT count(b.id) as total
          FROM burgers AS b
          LEFT JOIN restaurant_burgers ON restaurant_burgers.burger = b.id
          LEFT JOIN burgers AS m ON m.id = b.master
          LEFT JOIN restaurant ON restaurant.id = restaurant_burgers.id_r
          WHERE restaurant.id=:id
          AND (m.name LIKE :value OR b.name LIKE :value)");

$search->bindValue("id", (string)$restaurant->getId(),"int");
$search->bindValue("value", "%".$request->request->get("q","")."%");


$search->subQueryColum("nobre columna","");
$search->limit($request->request->get('per_page',10));
$search->selectRow([	1 => 1,
                  10 => [ 10, true ],
                        20 => 20,
                        30 => 30,
                        40 => 40,
                        'Todo' => 'all'
                    ]);
                    
if($request->isXmlHttpRequest()){
    return $search->getResultJson();
}else{
    return [
        'listBurger' => $search->getResult()
    ];
}        
                    
```

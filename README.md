# Project Title

One Paragraph of project description goes here

## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

What things you need to install the software and how to install them

```
Give examples
```

### Installing

A step by step series of examples that tell you how to get a development env running

Say what the step will be

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

## Authors

* **Billie Thompson** - *Initial work* - [PurpleBooth](https://github.com/PurpleBooth)

See also the list of [contributors](https://github.com/your/project/contributors) who participated in this project.

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details

## Acknowledgments

* Hat tip to anyone whose code was used
* Inspiration
* etc




```php
$resultSearch = $search->setTable('BURGERS')
    ->addColum([
                'id'=>'id',
                'NAME'=>'NAME',
                'DELUXE'=>'DELUXE'
    ])
    ->addColumForeignKey([
        'CATEGORY' => [
                        'type' => 'selectOneToMany',
                        'table' => [ 
                                        'to' => 'CATEGORY',
                                        'to_colum' => 'id_c',
                                        'many' => 'CATEGORY_BURGERS',
                                        'many_colum' => 'id_b',
                                    ],
                        'colum' => 'NAME'
                    ],
        'RESTAURANT' => [
                        'type' => 'selectOneToMany',
                        'table' => [ 
                                        'to' => 'RESTAURANT',
                                        'to_colum' => 'id_r',
                                        'many' => 'RESTAURANT_BURGERS',
                                        'many_colum' => 'id_b',
                                    ],
                        'colum' => 'NAME'
                    ]
    ])
    ->addFilter([
        'restaurant' => [
                        'visible' => false,
                        'label' => 'Restaurante',
                        'type' => 'selectOneToMany',
                        'param' => $id,
                        'table' => 'RESTAURANT',	//Tabla foranea
                        'colum' => 'NAME'	//Tabla foranea
                    ],
        'category' => [
                        'label' => 'Categoria',
                        'type' => 'selectOneToMany',
                        'param' => @$filter['category'],
                        'table' => 'CATEGORY',	//Tabla foranea
                        'colum' => 'NAME'	//Tabla foranea
                    ],
        'deluxe' => [
                        'label' => 'Deluxe',
                        'type' => 'boolean',	//Tipo de busqueda
                        'param' => @$filter['deluxe'],
                        'colum' => 'DELUXE'	//Columna de la tabla principal
                    ]
    ])
    ->selectRow([	1 => 1,
                    10 => [ 10, true ],
                    20 => 20,
                    30 => 30,
                    40 => 40,
                    'Todo' => 'all'
                ])
    ->search('NAME',$textSearch)
    ->getResult();
```
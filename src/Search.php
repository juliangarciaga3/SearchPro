<?php
namespace Bitsystem\SearchPro;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

//Libreria necesaria para detectar la desconexion por ajax 
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class Search{

    protected $em;
    protected $request;
    private $id = 'id';
    private $table;
    private $page = 1;
    private $per_page;
    private $start;
    private $results;
    private $numPage;
    private $numberTotalRow;
    private $numberRow;
    private $baseurl;
    private $query;
    private $filter = [];
    private $queryColums = "";
    private $queryImage = "";
    private $queryFilter = "";
    private $querySelectFilter = "";
    private $rowList = [];
    private $leftJoin = '';

    private $image = FALSE;
    private $queryColumForeignKey = "";

    public function __construct(EntityManager $em, RequestStack $requestStack){
        $this->em = $em;
        $this->db = $this->em->getConnection();
        $this->request = $requestStack->getCurrentRequest();
        $this->per_page = $this->request->get('per_page',10);
        $this->page = $this->request->get('page',1);
        $this->baseurl = $this->request->getScheme().'://'.$this->request->getHttpHost().$this->request->getBasePath();
    }
    public function setNameId($id){
        $this->id = $id;
    }
    
    public function getNameId(){
        return $this->id;
    }   
    public function setTable($table){
        $this->table = $table;
        return $this;
    }
    
    public function getTable(){
        return $this->table;
    }

    public function addFilter($array = NULL){
        try {
            if($array){
                foreach($array as $name => $element){
                    if($this->request->get('filter_ajax')){
                        try{
                            $parameter = $this->request->get('q');
                            $query = "SELECT id, ".$element['colum']." as NAME ";
                            $query .= " FROM ".$element['table'];
                            $query .= " WHERE ".$element['colum'];
                            $query .= " LIKE :name";
                            
                            $list = $this->db->prepare($query);
                            $list->execute([
                                            'name' => "%".$parameter."%"
                                        ]);
                            if($name != $this->request->get('filter_ajax')){
                                continue;
                            }            
                            echo json_encode($list->fetchAll());
                            exit();
                        }catch(\Exception $exception){
                            echo json_encode([
                                'success' => false,
                                'code'    => $exception->getCode(),
                                'message' => $exception->getMessage(),
                            ]);
                            exit();
                        }
                    }

                    switch($element['type']){
                        case 'select':

                            if($element['param'] != ''){
                                $this->queryFilter .= " AND ".$element['id_colum']." = ".$element['param'];
                            }else{
                                $this->querySelectFilter .= "SELECT id, ".$element['colum']." as NAME FROM ".$element['table']; 
                                $filter = $this->db->prepare($this->querySelectFilter);
                                $filter->execute();
                                $this->filter[$name]['result'] = $filter->fetchAll();
                                $this->filter[$name]['type'] = $element['type'];
                                $this->filter[$name]['label'] = $element['label'];
                                $this->querySelectFilter = "";
                            }
                            break;
                        case 'selectOneToMany':

                            if($element['param'] != ''){
                                $this->queryFilter .= " AND ".$element['table'].".id = ".$element['param'];
                            }else{
                                $this->filter[$name]['type'] = 'select';
                                $this->filter[$name]['label'] = $element['label'];
                                $this->querySelectFilter = "";

                            }
                            break;
                        case 'boolean':
                            if($element['param'] == 1){
                                $this->queryFilter .= " AND ".$this->table.".".$element['colum']." >= 1";
                            }
                                $this->filter[$name]['type'] = $element['type'];
                                $this->filter[$name]['label'] = $element['label'];
                                $this->querySelectFilter = "";
                            
                            break;                            
                        default:
                            break;
                    }
                }
            }
            return $this;
        }catch(\Exception $e){
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function addColum($array = NULL){
        try {
            if(is_array($array)){
                foreach($array as $name => $element){
                    $this->queryColums .= $this->table.".".$element." as ".$name.",";
                }
            }
            $this->queryColums = substr($this->queryColums, 0, -1);
            return $this;            
        }catch(\Exception $e){
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }

    public function addColumForeignKey($array = NULL){
        try {
            if($array){
                if(!empty($this->queryColums)){
                    $this->queryColumForeignKey .= ',';
                }
                foreach($array as $name => $element){
                    switch($element['type']){
                        case 'image':
                            if(!$element['width'] || !$element['height']){
                                throw new Exception('Define el ancho y el alto');
                            }
                            $this->image = TRUE;
                            $this->queryColumForeignKey .= "(SELECT THUMBNAIL.ROUTE";
                            $this->queryColumForeignKey .= " FROM ".$element['colum'];
                            $this->queryColumForeignKey .= " LEFT JOIN RESTAURANT_GALERY_THUMBNAIL as THUMBNAIL ON THUMBNAIL.ID_GALERY = RESTAURANT_GALERY.id";
                            $this->queryColumForeignKey .= " WHERE RESTAURANT_GALERY.RESTAURANT = ".$this->table.".id";
                            $this->queryColumForeignKey .= " AND THUMBNAIL.WIDTH = ".$element['width'];
                            $this->queryColumForeignKey .= " AND THUMBNAIL.HEIGHT = ".$element['height'];
                            $this->queryColumForeignKey .= " LIMIT 1) as $name,";                    
                            break; 
                        case 'select':
                            $this->queryColumForeignKey .= "(SELECT ".$element['columReference'];
                            $this->queryColumForeignKey .= " FROM ".$element['table'];
                            $this->queryColumForeignKey .= " WHERE ".$element['table'].".id = ".$this->table.".".$element['colum'];
                            $this->queryColumForeignKey .= ") as $name,";                    
                            break;
                        case 'selectOneToMany':
                            $to_colum = $element['table']['to_colum'];
                            $many_colum = $element['table']['many_colum'];
                            $many = $element['table']['many'];
                            $to = $element['table']['to'];

                            $this->queryColumForeignKey .= " ".$to.".".$element['colum']." as ".$name.",";

                            $this->columMany = " ".$many.".".$to_colum;
                            $this->leftJoin .= " LEFT JOIN ".$many." ON ".$many.".".$many_colum." = ".$this->table.".id";
                            $this->leftJoin .= " LEFT JOIN ".$to." ON ".$to.".id = ".$many.".".$to_colum;

                            // $this->queryColumForeignKey .= "(SELECT IFNULL(".$to.".".$element['colum'].", '')";
   

                            break;
                        default:
                            break;
                    }
                }
                if(count($array) > 0){
                    $this->queryColumForeignKey = substr($this->queryColumForeignKey, 0, -1);
                }
            }
            return $this;            
        }catch(\Exception $e){
            echo 'Excepción capturada: ',  $e->getMessage(), "\n";
        }
    }
    public function selectRow($array = NULL){
        foreach($array as $row => $value){
            $this->rowList[$row] = $value;
            if(is_array($this->rowList[$row])){
                $this->rowList[$row]['active'] = true;
            }
        }
        return $this;
    }
    public function search($field = 'NAME', $param = NULL){
        $this->per_page = (is_null($this->per_page))? 10 : $this->per_page;
        $this->start = ($this->page != 1)?($this->page-1) * $this->per_page : 0;

        $this->count = "SELECT COUNT(".$this->table.".id) as total".$this->queryColumForeignKey;
        $this->query = "SELECT ".$this->queryColums.$this->queryColumForeignKey;

        $this->query .= " FROM ".$this->table;
        $this->count .= " FROM ".$this->table;

        $this->query .= $this->leftJoin;
        $this->count .= $this->leftJoin;


        $this->query .= " WHERE ".$this->table.".".$field." LIKE :find";
        $this->count .= " WHERE ".$this->table.".".$field." LIKE :find";

        $this->query .= $this->queryFilter;
        $this->count .= $this->queryFilter;

        // Obtengo el numero total
        $totalQuery = $this->db->prepare($this->count);
        $totalQuery->execute(['find' => "%".$param."%"]);

        $this->query .=" LIMIT ".$this->start.", ".$this->per_page;

        // Obtengo la lista de la tabla
        $search = $this->db->prepare($this->query);
        $search->execute(['find' => "%".$param."%"]);

        $resultQuery = $search->fetchAll(\PDO::FETCH_ASSOC);
        $this->numberTotalRow = $totalQuery->fetch()['total'];
        $this->numberRow = count($resultQuery);


        
        // Numero de pagina seleccionada
        $this->numPage = ceil($this->numberTotalRow / $this->per_page);

        if($this->image){
            $package = new Package(new EmptyVersionStrategy());
            foreach($resultQuery as $row){
                if($row['IMAGE']){
                    $link = $this->baseurl.'/'.$row['IMAGE'];            
                    $row['IMAGE'] = $link;               
                }else{
                    $link = $this->baseurl.'/'.$package->getUrl('/backend/images/default.jpg');
                    $row['IMAGE'] = $link;                  
                }
                $this->results[] = $row;
            }
        }else{
            $this->results = $resultQuery;
        }



        return $this;
    }
    
    public function getResult(){
        if($this->request->isXmlHttpRequest()){
            $resultJson = json_encode([
                'result' => $this->results,   //Filas de la tabla
                'numPage' => $this->numPage,    //Numero de pagina seleccionada
                'numberRow' => $this->numberRow,   //Numero de filas a mostrar
                'numberTotalRow' => $this->numberTotalRow,  //Numero de filas totales
                'debug' => [
                                'query' => $this->query
                            ]
            ]);
            header('Content-type:application/json;charset=utf-8');
            echo $resultJson;
            exit();

        }else{
            return [
                    'filter' => $this->filter,          //Filtro para buscar
                    'rowList' => $this->rowList     //Lista de filas as mostrar
                    ];
        }
    }
    public function jsonResponse($result){

    }

    /**
     * Handles security related exceptions.
     *
     * @param GetResponseForExceptionEvent $event An GetResponseForExceptionEvent instance
     */
    public function onCoreException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();
        $request = $event->getRequest();
 
        if ($request->isXmlHttpRequest()) {
            if ($exception instanceof AuthenticationException || $exception instanceof AccessDeniedException) {
                $event->setResponse(new Response('', 403));
            }
        }
    }


   
}
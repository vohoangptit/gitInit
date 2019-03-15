<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;
use Zend\View\Model\JsonModel;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\ResultSet\ResultSet;


class GoogleController extends AbstractRestfulController
{


    protected $conn;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        $this->conn = new \Zend\Db\Adapter\Adapter([
            'driver' => 'Mysqli',
            'database' => 'post',
            'username' => 'root',
            'password' => '',
            'hostname' => 'localhost',
            'charset' => 'utf8'
        ]);
    }

    public function get($id)
    {
        try{
            $end = $this->params()->fromQuery('end');
            if($end == "" || $end == null){
                http_response_code(400);
                throw new \InvalidArgumentException('Param : end not Exist or Invalid');
            }
            $select = "select * from `analytics` where (`created_date` BETWEEN '{$id}' and '{$end}') order by `created_date` desc ";
            $statement = $this->conn->createStatement($select);
            $result = $statement->execute();
            $data = $this->_transform($result);
            http_response_code(200);
            return new JsonModel([
                'data' => $data
            ]);
        }catch (\Exception $e){
            http_response_code(500);
            throw new \Exception($e->getMessage());
        }

    }

    protected function _transform($result)
    {
        $rows = array();
        if ($result instanceof ResultInterface && $result->isQueryResult()) {
            $resultSet = new ResultSet;
            $resultSet->initialize($result);

            $rows = $resultSet->toArray();

            if (!empty($rows)) {
                foreach ($rows as &$value) {
                    $value = array_change_key_case($value, CASE_LOWER);
                }
            }
            unset($resultSet);
        }
        return $rows;
    }
}

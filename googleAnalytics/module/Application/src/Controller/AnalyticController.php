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
use Analytics\Google;


class AnalyticController extends AbstractRestfulController
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

    public function getList()
    {
        $type = $this->params()->fromQuery('type');
        switch ($type)
        {
            case 'getDataAnalytic':
                $userId = $this->params()->fromQuery('userId');
                $start = $this->params()->fromQuery('start');
                $end = $this->params()->fromQuery('end');
                return $this->getDataAnalyticByViewIdAction($userId,$start,$end);
                break;
        }
    }

    public function create($data)
    {
        $type = $this->params()->fromQuery('type');
        switch ($type)
        {
            case 'createShareAccount':
                return $this->accountAction();
        }
    }

    public function accountAction()
    {
        $client = new Google();
        $account = $client->getAccount();

        foreach ($account as $item) {
            $viewId = $item['webProperties'][0]['profiles'][0]['id'];
            $accountId = $item['id'];
            $userId = strtolower($item['name']);
            $sql = "insert into account(account_id,user_id,view_id) values('{$accountId}','{$userId}','{$viewId}') on duplicate key update
                    updated_date=current_timestamp();";
            $statement = $this->conn->createStatement($sql);
            $statement->execute();
        }
        return new JsonModel([
                'data' => true
        ]
        );
    }

    public function getDataAnalyticByViewIdAction($userId,$start,$end)
    {
        try {
            $select = "select * from account where user_id = '{$userId}'";
            $statement = $this->conn->createStatement($select);
            $result = $statement->execute();
            $data = null;
            while ($result->valid()) {
                $data = $result->current();
                $result->next();
            }
            $viewId = (string)$data['view_id'];
            if($viewId=="" || $viewId==null){
                http_response_code(400);
                throw new \InvalidArgumentException("userId : {$userId} not exists");
            }
            $init = new Google();
            $response = $init->getReport($start, $end, $viewId);
            $dataAnalytics = $init->printResults($response);
            http_response_code(200);
            return new JsonModel([
                'data' => $dataAnalytics
            ]);
        } catch (\Exception $e) {
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

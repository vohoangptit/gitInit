<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Analytics\Google;
use Analytics\GoogleDriver;
use Analytics\GoogleSheet;
use Datetime;
use Zend\View\Model\JsonModel;


class IndexController extends AbstractActionController
{


    protected $conn;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        $this->conn = new \Zend\Db\Adapter\Adapter([
            'driver' => 'Mysqli',
            'database' => 'analytics',
            'username' => 'root',
            'password' => '',
            'hostname' => 'localhost',
            'charset' => 'utf8'
        ]);
    }

    public function indexAction()
    {
        $date = $this->params()->fromQuery('date');
        $init = new Google();
        $response = $init->getReport($date, $date);
        $result = $init->printResults($response);
        foreach ($result as $key => $item) {

            $sql = "insert into analytics(source_medium,users,new_users,session,bounce_rate,pages_session,
                    avg_session_duration,ecommerce_conversion_rate,transactions,revenue,created_date) values('{$key}','{$item['ga:users']}','{$item['ga:newUsers']}','{$item['ga:sessions']}'
                    ,'{$item['ga:bounceRate']}','{$item['ga:pageviewsPerSession']}','{$item['ga:avgSessionDuration']}'
                    ,'{$item['ga:transactionsPerSession']}','{$item['ga:transactions']}','{$item['ga:transactionRevenue']}','{$date}') on duplicate key update
                    users = '{$item['ga:users']}',new_users ='{$item['ga:newUsers']}' ,session='{$item['ga:sessions']}',bounce_rate='{$item['ga:bounceRate']}',pages_session='{$item['ga:pageviewsPerSession']}',
                    avg_session_duration='{$item['ga:avgSessionDuration']}',ecommerce_conversion_rate='{$item['ga:transactionsPerSession']}',transactions='{$item['ga:transactions']}',revenue='{$item['ga:transactionRevenue']}'";
            $statement = $this->conn->createStatement($sql);
            $statement->execute();
        }
    }

    public function accountAction(){
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
    }


    public function getDataAction(){
        try{
            $select = "select * from account";
            $statement = $this->conn->createStatement($select);

            $result = $statement->execute();
            $data = [];
            while($result->valid())
            {
                $data[] = $result->current();
                $result->next();
            }
            $dataAnalytics = [];
            $start = $this->params()->fromQuery('start');
            $end = $this->params()->fromQuery('end');
            $init = new Google();
            foreach ($data as $item){
                $response = $init->getReport($start, $end,$item['view_id']);
                $dataAnalytics[] = $init->printResults($response);
            }
            return new JsonModel([
                'data' => $dataAnalytics
            ]);
        }catch (\Exception $e){
            echo "<pre>";
            print_r($e->getMessage());
            echo "</pre>";
            exit();
        }

    }

    public function getDataAnalyticByViewIdAction(){
        try{
            $userId = $this->params()->fromQuery('userId');
            $start = $this->params()->fromQuery('start');
            $end = $this->params()->fromQuery('end');
            $select = "select * from account where user_id = '{$userId}'";
            $statement = $this->conn->createStatement($select);
            $result = $statement->execute();
            $data = null;
            while($result->valid())
            {
                $data = $result->current();
                $result->next();
            }
            $viewId = (string)$data['view_id'];
            $init = new Google();
            $response = $init->getReport($start,$end,$viewId);
            $dataAnalytics = $init->printResults($response);
            return new JsonModel([
                'data' => $dataAnalytics
            ]);
        }catch (\Exception $e){
            echo "<pre>";
            print_r($e->getMessage());
            echo "</pre>";
            exit();
        }
    }

    public function filterAction()
    {
        $start = $this->params()->fromQuery('start');
        $end = $this->params()->fromQuery('end');
        $begin = new DateTime($start);
        $finish = new DateTime($end);
        $init = new Google();
        for ($i = $begin; $i <= $finish; $i->modify('+1 day')) {
            $date = $i->format("Y-m-d");
            $response = $init->getReport($date, $date);
            $result = $init->printResults($response);
            foreach ($result as $key => $item) {
                $sql = "insert into analytics(source_medium,users,new_users,session,bounce_rate,pages_session,
                    avg_session_duration,ecommerce_conversion_rate,transactions,revenue,created_date) values('{$key}','{$item['ga:users']}','{$item['ga:newUsers']}','{$item['ga:sessions']}'
                    ,'{$item['ga:bounceRate']}','{$item['ga:pageviewsPerSession']}','{$item['ga:avgSessionDuration']}'
                    ,'{$item['ga:transactionsPerSession']}','{$item['ga:transactions']}','{$item['ga:transactionRevenue']}','{$date}') on duplicate key update 
                    users = '{$item['ga:users']}',new_users ='{$item['ga:newUsers']}' ,session='{$item['ga:sessions']}',bounce_rate='{$item['ga:bounceRate']}',pages_session='{$item['ga:pageviewsPerSession']}',
                    avg_session_duration='{$item['ga:avgSessionDuration']}',ecommerce_conversion_rate='{$item['ga:transactionsPerSession']}',transactions='{$item['ga:transactions']}',revenue='{$item['ga:transactionRevenue']}'";
                $statement = $this->conn->createStatement($sql);
                $statement->execute();
            }
        }
    }

    public function uploadAction()
    {
        $request = $this->getRequest();
        $request->getPost()->toArray();
        $postFiles = $request->getFiles();
        $init = new GoogleDriver();
        $client = $init->getClient();
        $init->uploadFile($client,$postFiles['files']['tmp_name'], $postFiles['files']['type'], "hoang");
    }

    public function updateAction()
    {
//        $init = new GoogleDriver();
//        $client = $init->getClient();
//        $init->updateFile($client);
    }

    public function createSheetAction()
    {
        $ten = $_POST['ten'];
        $email = $_POST['email'];
        $linkCV = $_POST['link_cv'];
        $cv = $_FILES['file']['tmp_name'];

        $note = $_POST['note'];
        try{
//            $title = $this->params()->fromQuery('title');
            $title = "CV Interview";
            $init = new GoogleSheet();
            $client = $init->getClient();
            $sheetId = $init->uploadSheet($client,$title);
            $init->appendSheet($client,$sheetId,$ten,$email,$linkCV,$cv,$note);
            echo ("<script>
                        alert('Success');
                        setTimeout(
                            function() {
                              window.location.href = 'http://localhost:8080/application/update';
                            }, 1000
                        )
                   </script>");
        } catch (\Exception $e){
            throw new $e->getMessage();
        }

    }

    public function appendAction()
    {
        try{
            $ten = $_POST['ten'];
            $email = $_POST['email'];
            $linkCV = $_POST['link_cv'];
            $cv = $_FILES['file']['name'];
            $note = $_POST['note'];
            $sheetId = $this->params()->fromQuery('sheetId');
            $init = new GoogleSheet();
            $client = $init->getClient();
            $init->appendSheet($client,$sheetId,$ten,$email,$linkCV,$cv,$note);
        } catch (\Exception $e){
            throw new $e->getMessage();
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

    protected function fetchData($result){
        if($result->count() > 0) {
            $returnArr = array();
            while ($result->valid()) {
                $returnArr[] = $result->current();
                $result->next();
            }
            if(count($returnArr) > 0) {
                return $returnArr;
            }
        }
        return [];
    }
}

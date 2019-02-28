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
            'database' => 'post',
            'username' => 'root',
            'password' => '',
            'hostname' => 'localhost',
            'charset' => 'utf8'
        ]);
    }

    public function indexAction()
    {
//        $date = $this->params()->fromQuery('date');
//        $today = date("Y/m/d");
        $client = new Google();
        $client->getAccount();
//        $response = $init->getReport($date, $date);
//        $result = $init->printResults($response);
//        foreach ($result as $key => $item) {
//
//            $sql = "insert into analytics(source_medium,users,new_users,session,bounce_rate,pages_session,
//                    avg_session_duration,ecommerce_conversion_rate,transactions,revenue,created_date) values('{$key}','{$item['ga:users']}','{$item['ga:newUsers']}','{$item['ga:sessions']}'
//                    ,'{$item['ga:bounceRate']}','{$item['ga:pageviewsPerSession']}','{$item['ga:avgSessionDuration']}'
//                    ,'{$item['ga:transactionsPerSession']}','{$item['ga:transactions']}','{$item['ga:transactionRevenue']}','{$date}') on duplicate key update
//                    users = '{$item['ga:users']}',new_users ='{$item['ga:newUsers']}' ,session='{$item['ga:sessions']}',bounce_rate='{$item['ga:bounceRate']}',pages_session='{$item['ga:pageviewsPerSession']}',
//                    avg_session_duration='{$item['ga:avgSessionDuration']}',ecommerce_conversion_rate='{$item['ga:transactionsPerSession']}',transactions='{$item['ga:transactions']}',revenue='{$item['ga:transactionRevenue']}'";
//            $statement = $this->conn->createStatement($sql);
//            $statement->execute();
//        }
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
}

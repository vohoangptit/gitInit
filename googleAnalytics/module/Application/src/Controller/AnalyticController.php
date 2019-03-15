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
use Elasticsearch\ClientBuilder;


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
            'database' => 'analytics',
            'username' => 'root',
            'password' => '',
            'hostname' => 'localhost',
            'charset' => 'utf8'
        ]);
    }

    public function get($id)
    {
        switch ($id) {
            case '1':
                return $this->mappingIndex();
                break;
            case 'getDataAnalytic':
                echo "das";
                exit();
                $userId = $this->params()->fromQuery('userId');
                $date = $this->params()->fromQuery('date');
                return $this->getDataAnalyticByViewIdAction($userId, $date);
                break;
            case 'dimension':
                $type = $this->params()->fromQuery('dimensionType');
                return $this->isSearchAction($type);
                break;
            case 'query':
                $data = $this->params()->fromQuery('data');
                return $this->startSearchAction($data);
                break;
            case 'contain':
                $data = $this->params()->fromQuery('data');
                return $this->containSearchAction($data);
                break;
        }
    }

    public function create($data)
    {
        $type = $this->params()->fromQuery('type');
        switch ($type) {
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

    public function getDataAnalyticByViewIdAction($userId, $date)
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
            if ($viewId == "" || $viewId == null) {
                http_response_code(400);
                throw new \InvalidArgumentException("userId : {$userId} not exists");
            }
            $init = new Google();
            $response = $init->getReport($date, $date, $viewId);
            $dataAnalytics = $init->printResults($response);
            $this->saveDataAndPutElasticSearch($userId, $viewId, $date, $dataAnalytics);
            http_response_code(200);
            return new JsonModel([
                'data' => $dataAnalytics
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            throw new \Exception($e->getMessage());
        }
    }

    public function mappingIndex()
    {
        $client = ClientBuilder::create()->build();
//        $client->indices()->delete(['index' => 'utm_data_1']);
        $params = [
            'index' => 'utm_data_1',
            'body' => [
                'settings' => [
                    'number_of_shards' => 3,
                    'number_of_replicas' => 2,
                ],
                'mappings' => [
                    'data' => [
                        'properties' => [
                            'users' => [
                                'type' => 'integer',
                            ],
                            'new_users' => [
                                'type' => 'integer'
                            ],
                            'session' => [
                                'type' => 'integer',
                            ],
                            'bounce_rate' => [
                                'type' => 'double'
                            ],
                            'pages_session' => [
                                'type' => 'double',
                            ],
                            'avg_session_duration' => [
                                'type' => 'double'
                            ],
                            'ecommerce_conversion_rate' => [
                                'type' => 'double',
                            ],
                            'transactions' => [
                                'type' => 'integer'
                            ],
                            'revenue' => [
                                'type' => 'double'
                            ],
                            'created_date' => [
                                'type' => 'keyword',
                            ],
                            'user_id' => [
                                'type' => 'keyword',
                            ],
                            'view_id' => [
                                'type' => 'integer',
                            ],
                            'source' => [
                                'type' => 'keyword',
//                                'analyzer' => 'standard'
                            ],
                            'medium' => [
                                'type' => 'keyword',
//                                'analyzer' => 'standard'
                            ],
                            'campaign' => [
                                'type' => 'keyword',
//                                'analyzer' => 'standard'
                            ]
                        ]
                    ]
                ],
            ]
        ];
        try {
            $response = $client->indices()->create($params);
        } catch (\Exception $e) {
            print_r($e->getMessage());
            die();
        }
        echo "<pre>";
        print_r($response);
        echo "</pre>";
        exit();
    }

    public function saveDataAndPutElasticSearch($userId, $viewId, $date, $result)
    {
        try {
            foreach ($result as $item) {
                $sql = "insert into analytics(
                    users,
                    new_users,
                    session,
                    bounce_rate,
                    pages_session,
                    avg_session_duration,
                    ecommerce_conversion_rate,
                    transactions,
                    revenue,
                    created_date,
                    user_id,
                    view_id,
                    source,
                    medium,
                    campaign) 
                    values(
                    {$item['ga:users']}
                    ,{$item['ga:newUsers']}
                    ,{$item['ga:sessions']}
                    ,{$item['ga:bounceRate']}
                    ,{$item['ga:pageviewsPerSession']}
                    ,{$item['ga:avgSessionDuration']}
                    ,{$item['ga:transactionsPerSession']}
                    ,{$item['ga:transactions']}
                    ,{$item['ga:transactionRevenue']}
                    ,'{$date}'
                    ,'$userId'
                    ,$viewId
                    ,'{$item['ga:source']}'
                    ,'{$item['ga:medium']}'
                    ,'{$item['ga:campaign']}') 
                    on duplicate key update 
                    users = {$item['ga:users']}
                    ,new_users ={$item['ga:newUsers']}
                    ,session={$item['ga:sessions']}
                    ,bounce_rate={$item['ga:bounceRate']}
                    ,pages_session={$item['ga:pageviewsPerSession']}
                    ,avg_session_duration={$item['ga:avgSessionDuration']}
                    ,ecommerce_conversion_rate={$item['ga:transactionsPerSession']}
                    ,transactions={$item['ga:transactions']}
                    ,revenue={$item['ga:transactionRevenue']}";
                $statement = $this->conn->createStatement($sql);
                $statement->execute();
            }
            $client = ClientBuilder::create()->build();

            foreach ($result as $item) {
                $params = [
                    'index' => 'utm_data_1',
                    'type' => 'data',
                    'body' => [
                        'users' => $item['ga:users'],
                        'new_users' => $item['ga:newUsers'],
                        'session' => $item['ga:sessions'],
                        'bounce_rate' => $item['ga:bounceRate'],
                        'pages_session' => $item['ga:pageviewsPerSession'],
                        'avg_session_duration' => $item['ga:avgSessionDuration'],
                        'ecommerce_conversion_rate' => $item['ga:transactionsPerSession'],
                        'transactions' => $item['ga:transactions'],
                        'revenue' => $item['ga:transactionRevenue'],
                        'created_date' => $date,
                        'user_id' => $userId,
                        'view_id' => $viewId,
                        'source' => $item['ga:source'],
                        'medium' => $item['ga:medium'],
                        'campaign' => $item['ga:campaign']
                    ]
                ];
                $client->index($params);
            }
        } catch (\Exception $ex) {
            echo "<pre>";
            print_r($ex->getMessage());
            echo "</pre>";
            exit();
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

    public function isSearchAction($data)
    {
        try {
            $client = ClientBuilder::create()->build();
            $params = [
                'index' => 'utm_data_1',
                'type' => 'data',
                'body' => [
                    'query' => [
                        'bool' => [
                            'should' => [
                                ['match' => ['source' => $data]],
                                ['match' => ['medium' => $data]],
                                ['match' => ['campaign' => $data]],
                            ]

                        ]
                    ]
                ]
            ];

            $results = $client->search($params);
        } catch (\Exception $ex) {
            echo "<pre>";
            print_r($ex->getMessage());
            echo "</pre>";
            exit();
        }
        return new JsonModel([
            'data' => $results['hits']['hits']
        ]);
    }

    public function startSearchAction($data)
    {
        try {
            $client = ClientBuilder::create()->build();
            $params = [
                'index' => 'utm_data_1',
                'type' => 'data',
                'body' => [
                    'query' => [
                        'bool' => [
                            'should' => [
                                ['prefix' => ['source' => $data]],
                                ['prefix' => ['medium' => $data]],
                                ['prefix' => ['campaign' => $data]],
                            ]
                        ]
                    ]
                ]
            ];

            $results = $client->search($params);
        } catch (\Exception $ex) {
            echo "<pre>";
            print_r($ex->getMessage());
            echo "</pre>";
            exit();
        }
        return new JsonModel([
            'data' => $results['hits']['hits']
        ]);
    }

    public function containSearchAction($data)
    {
        try {
            $client = ClientBuilder::create()->build();
            $params = [
                'index' => 'utm_data_1',
                'type' => 'data',
                'body' => [
                    'query' => [
                        'bool' => [
                            'should' => [
                                ['wildcard' => ['medium' => "*" . $data . "*",]],
                                ['wildcard' => ['source' => "*" . $data . "*"]],
                                ['wildcard' => ['campaign' => "*" . $data . "*"]]
                            ]
                        ]

                    ]
                ]
            ];
            $results = $client->search($params);
        } catch (\Exception $ex) {
            echo "<pre>";
            print_r($ex->getMessage());
            echo "</pre>";
            exit();
        }
        return new JsonModel([
            'data' => $results['hits']['hits']
        ]);
    }
}

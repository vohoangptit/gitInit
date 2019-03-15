<?php

namespace Analytics;

session_start();

class Google
{
    public $analytics;

    public $client;

    /**
     * index constructor.
     */
    public function __construct()
    {
        $client = new \Google_Client();
        $client->setAuthConfig(__DIR__ . '/../../client_secrets.json');
        $client->addScope(\Google_Service_Analytics::ANALYTICS_READONLY);
        try {
            if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
                // Set the access token on the client.
                $client->setAccessToken($_SESSION['access_token']);

                // Create an authorized analytics service object.
                $this->analytics = new \Google_Service_AnalyticsReporting($client);
//                $this->client = new \Google_Service_Analytics($client);
            } else {
                $redirect_uri = 'http://' . $_SERVER['HTTP_HOST'] . '/oauth2callback.php';
                header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
            }
        } catch (\Exception $mess) {
            echo "<pre>";
            print_r($mess->getMessage());
            echo "</pre>";
            exit();
        }
    }

    function getAccount(){
        try {
            $account = $this->client->management_accountSummaries->listManagementAccountSummaries([
                'start-index' => 1,
                'max-results' => 100
            ]);
            return $accountId = $account->getItems();
        } catch (\Google_Service_Exception $e) {
            print 'There was an Analytics API service error '
                . $e->getCode() . ':' . $e->getMessage();

        }
        catch (\Google_Exception $e) {
            print 'There was a general API error '
                . $e->getCode() . ':' . $e->getMessage();
        }
    }

    function getLinkAccount($accountId){
        try {
            $account = $this->client->management_accountUserLinks->listManagementAccountUserLinks($accountId);
            return $account;
        } catch (\Google_Service_Exception $e) {
            print 'There was an Analytics API service error '
                . $e->getCode() . ':' . $e->getMessage();

        }
        catch (\Google_Exception $e) {
            print 'There was a general API error '
                . $e->getCode() . ':' . $e->getMessage();
        }
    }

    /**
     * Queries the Google Reporting API V4.
     *
     * @param start.
     * @param end.
     * @param viewId.
     * @return The Google Reporting API V4 response.
     */

    function getReport($start, $end, $viewId)
    {
/*        Dimension : ga:medium, ga:source, ga:campaign, ga:sourceMedium
*/
        // Replace with your view ID, for example XXXX.
//        $VIEW_ID = "179699466";

        // Create the DateRange object.
        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($start);
        $dateRange->setEndDate($end);

        //create dimension
        $browser = new \Google_Service_AnalyticsReporting_Dimension();
        $browser->setName('ga:source');
        $browser1 = new \Google_Service_AnalyticsReporting_Dimension();
        $browser1->setName('ga:medium');
        $browser2 = new \Google_Service_AnalyticsReporting_Dimension();
        $browser2->setName('ga:campaign');

        // Create the Metrics object.
        $sessions1 = new \Google_Service_AnalyticsReporting_Metric();
        $sessions1->setExpression("ga:users");

        $sessions2 = new \Google_Service_AnalyticsReporting_Metric();
        $sessions2->setExpression("ga:newUsers");

        $sessions3 = new \Google_Service_AnalyticsReporting_Metric();
        $sessions3->setExpression("ga:sessions");

        $sessions4 = new \Google_Service_AnalyticsReporting_Metric();
        $sessions4->setExpression("ga:bounceRate");

        $sessions5 = new \Google_Service_AnalyticsReporting_Metric();
        $sessions5->setExpression("ga:pageviewsPerSession");

        $sessions6 = new \Google_Service_AnalyticsReporting_Metric();
        $sessions6->setExpression("ga:avgSessionDuration");

        $sessions7 = new \Google_Service_AnalyticsReporting_Metric();
        $sessions7->setExpression("ga:transactionsPerSession");

        $sessions8 = new \Google_Service_AnalyticsReporting_Metric();
        $sessions8->setExpression("ga:transactions");

        $sessions9 = new \Google_Service_AnalyticsReporting_Metric();
        $sessions9->setExpression("ga:transactionRevenue");
//    $sessions->setAlias("sessions");

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewId);
        $request->setDateRanges($dateRange);
        $request->setDimensions(array($browser,$browser1,$browser2));
        $request->setMetrics(array($sessions1, $sessions2,
            $sessions3, $sessions4, $sessions5, $sessions6,
            $sessions7, $sessions8, $sessions9));

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests(array($request));

        return $this->analytics->reports->batchGet($body);
    }


    function printResults($reports)
    {
        $results = array();
        for ($reportIndex = 0; $reportIndex < count($reports); $reportIndex++) {
            $report = $reports[$reportIndex];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();
            for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $data = array();
                $row = $rows[$rowIndex];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                for ($j = 0; $j < count($metrics); $j++) {

                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        $data[$dimensionHeaders[0]] = $dimensions[0];
                        $data[$dimensionHeaders[1]] = $dimensions[1];
                        $data[$dimensionHeaders[2]] = $dimensions[2];
                        $data[$entry->getName()] = $values[$k];
                    }
                }
                $results[] = $data;
            }
        }
        return $results;
    }
}


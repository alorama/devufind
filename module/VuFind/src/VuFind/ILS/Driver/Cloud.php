<?php
/**
http://winbu/vufind/Upgrade/FixDatabase
grep -Hrn --include '*.php' 'MyStringToSearch' .
style.css background: #619144;  Green RoyalBlue is #1B72DE
datemodified:‎4/‎24/‎2014 .. ‎5/‎9/‎2014  type:*.php
*/ 

namespace VuFind\ILS\Driver;
use  VuFind\Exception\ILS as ILSException,
     VuFind\Auth\Manager as AuthManager,
     Zend\Session\Container as SessionContainer;
class cloud extends AbstractBase 
{
    protected $port; 
    protected $host;
    protected $dateFormat;
    protected $dateConverter;
    protected $AuthManager;
    public $validLocations;   
    protected $session;
    // protected $storageRetrievalRequests = true;
    /**
     * Constructor
     *
     * @param \VuFind\Date\Converter $dateConverter Date converter object
     * @param SearchService          $ss            Search service
  */ 
   public function __construct(\VuFind\Date\Converter $dateConverter,  AuthManager $AuthManager
    ) {
        $this->dateConverter = $dateConverter;
        $this->AuthManager = $AuthManager;
   }

//   public function __construct(AuthManager $AuthManager )
//   {
//        $this->AuthManager = $AuthManager;
//   }

//   public function __construct(\VuFind\Auth\ILSAthenticator $AuthManager )
//   {
//        $this->AuthManager = $AuthManager;
//   }


//    public function __construct(\VuFind\Config\PluginManager $configLoader,  \VuFind\Auth\ILSAuthenticator $ilsAuth  )
//   {
//        $this->configLoader = $configLoader;
//        $this->ilsAuth = $ilsAuth;
//    }

/**
     * Initialize the driver.
     * Validate configuration and perform all resource-intensive tasks needed to
     * make the driver active.
     * @throws ILSException
     * @return void
     */
    public function init()
    { 
       
        if (empty($this->config))  {
            throw new ILSException('Configuration needs to be set.');       
        }    
           
        // Define cloud parameters
        $this->host    = $this->config['Catalog']['host'];
        $this->port    = $this->config['Catalog']['port'];   
        $this->defaultPickUpLocation
            = isset($this->config['Holds']['defaultPickUpLocation'])
            ? $this->config['Holds']['defaultPickUpLocation'] : null;

        $this->session = new SessionContainer('CloudDriver');             
    //    $this->validLocations = $this->config['Locations']; 
    //   error_log(print_r($this->validLocations, true), 3, '/tmp/clocations.vuf');       
            
    }  
     /**
     * HTTP service
     * @var \VuFindHttp\HttpServiceInterface
     protected $httpService = null;
    /**
     * Set the HTTP service to be used for HTTP requests.
     *
     * @param HttpServiceInterface $service HTTP service
     *
     * @return void
  
    public function setHttpService(\VuFindHttp\HttpServiceInterface $service)
    {
        $this->httpService = $service;
    }
     /**
     * Retrieve the IDs of items recently added to the catalog.
     *
     * @param int $page    Page number of results to retrieve (counting starts at 1)
     * @param int $limit   The size of each page of results to retrieve
     * @param int $daysOld The maximum age of records to retrieve in days (max. 30)
     * @param int $fundId  optional fund ID to use for limiting results (use a value
     * returned by getFunds, or exclude for no limit); note that "fund" may be a
     * misnomer - if funds are not an appropriate way to limit your new item
     * results, you can return a different set of values from getFunds. The
     * important thing is that this parameter supports an ID returned by getFunds
     * whatever that may mean.
     *
     * @return array       Associative array with 'count' and 'results' keys
     * @access public
     */
    public function getNewItems($page, $limit, $daysOld, $fundId = null)
    {
        $library = $_COOKIE['mylib'] ;
//        $locCode = $this->layout()->userLoc ;
     //   error_log(__FILE__ . " line " . __LINE__ . ' getnewitems library=' . $library . '  $locCode' .  $locCode ,0);
        // don't forget to lookf $library being empty could be very very bad 
        $params = 'newitems?homelib=' . trim($library) .'&page='. trim($page) . '&limit=' . trim($limit) . '&daysold=' . trim($daysOld);
    //    error_log(__FILE__ . " line " . __LINE__ . ' getnewitems params=' . $params ,0);
        $response = $this->search_cloud($params,false);

         $xml = $response;
        // $xml = simplexml_load_string($response);

        $justids = array();
        foreach($xml as $item){
          $justids[] = trim($item->titleurl);
        } 
        $retVal = array('count' => count($justids), 'results' => array());
        foreach ($justids as $result) {
            $retVal['results'][] = array('id' => $result);
        }
        return $retVal;
    } 
    /**
     * Get Holding
     * This is responsible for retrieving the holding information of a certain
     * record.
     * @param string $id The record id to retrieve the holdings for
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber, duedate,
     * number, barcode; on failure, a PEAR_Error.
     * @access
     */
     public function getHolding($id, array $patron = null)    
    {
      //  error_log(__FILE__ . " line " . __LINE__ . ' getHolding $id='. $id ,0);
      //  error_log(print_r($patron, true), 3, '/tmp/' . rand(1,999) . '.vuf');
        return $this->getStatus($id);
    }    
    /**
     * Get Status
     * This is responsible for retrieving the status information of a certain
     * record.
     * @param string $id The record id to retrieve the holdings for
     * @return mixed     On success, an associative array with the following keys:
     * id, availability (boolean), status, location, reserve, callnumber; on
     * failure, a PEAR_Error.
     * @access public
     */
    public function getStatus($id)
    {
        $getrequest = 'getstatus?id=' . $id;
  //      error_log(__FILE__ . " line " . __LINE__ . ' Call getStatus with ' . $getrequest,0);
        $response = $this->search_cloud($getrequest,false);
        $xml = $response;
          $items = array();
          foreach($xml as $item){
            $items[] = array(
            'id'           => trim($item->id),
            'number'       => trim($item->number),
            'barcode'      => trim($item->barcode),
            'availability' => trim($item->availability),
            'status'       => trim($item->status),
            'location'     => trim($item->location),
            'reserve'      => trim($item->reserve),
            'callnumber'   => trim($item->callnumber),       
            'duedate'      => trim($item->duedate),
            'is_holdable'  => true,          
            'addLink'      => trim($item->addlink)
            );
	  }
//        error_log(print_r($items, true), 3, '/tmp/items.vuf');
        return $items ;
    }
    /**
     * Get Statuses
     * This is responsible for retrieving the status information for a
     * collection of records.
     * @param array $ids The array of record ids to retrieve the status for
     * @return mixed     An array of getStatus() return values on success,
     */
    public function getStatuses($ids)
    {
          $procrequest = 'getpage' ;
          $params      = $ids;
          $xmltopost = $this->buildXML($ids);
          // error_log(__FILE__ . " line " . __LINE__ . ' xmltopost=' . $xmltopost ,0);
          $postresult = $this->hrequest($procrequest, $params,"POST", $xmltopost);
          $itemvalue = -1;  // init counter to negative 1
          $idvalue   = '';  // init $idvalue to blank
          $items = array();
          $justids = array();
          foreach($postresult as $item){
           if ($idvalue != trim($item->id)) {
             $idvalue = trim($item->id);       // change save title number
             $itemvalue++;                     // bump up array index because title changed
           }
            $items[$itemvalue][] = array(
            'id'           => trim($item->id),
            'number'       => trim($item->number),
            'barcode'      => trim($item->barcode),
            'availability' => trim($item->availability),
            'status'       => trim($item->status),
            'location'     => trim($item->location),
            'reserve'      => trim($item->reserve),
            'callnumber'   => trim($item->callnumber),
            'duedate'      => trim($item->duedate)			
            );
    //     $justids[$itemvalue] = trim($item->id);
	       }        
          return $items;       
    }
     /**
     * Make Request
     * @param string  $procrequest string of name of procedure to call
     * the request (set value to false to inject a non-paired value).
     * @param array  $params    A keyed array of query data
     * @param string $mode      The http request method to use (Default of GET)
     * @param string $xml       An optional XML string to send to the API
     * @return obj  A Simple XML Object loaded with the xml data returned by the API
     * @access private
     */
    protected function hrequest($procrequest, $params = false, $mode = "GET",$xml = false) {
//  	    error_log(__FILE__ . " line " . __LINE__ . 'hrequest procrequest=' . $procrequest ,0);
        $urlParams = "http://{$this->host}:{$this->port}/" .$procrequest;
//        error_log(__FILE__ . " line " . __LINE__ . 'hrequest mode=' . $mode ,0);
        foreach ($params as $key => $param) {
//          error_log(__FILE__ . " line " . __LINE__ . ' mode=' . $mode . ' urlenc ' .  urlencode($param) ,0);  
          $queryString[] = $key. "=" . urlencode($param); 
        }
        if ($mode == "POST") {
            $header  = "Content-type: text/xml \r\n";
            $header .= "Content-length: ".strlen($xml)." \r\n";
            $header .= "Content-transfer-encoding: text \r\n";
            $header .= "Connection: close \r\n\r\n";
            $context = stream_context_create(array(
             'http' => array(
             'method' => 'POST',
             'header' => $header,   //'Content-Type: application/xml',
             'content' => $xml
             )
             ));
             $xmlResponse = file_get_contents($urlParams, false, $context);
        } else {
//            error_log(print_r($queryString, true), 3, '/tmp/queryString.vuf');
            $urlParams .= "?" . implode("&", $queryString); // this sometimes gets doubled up with data 
//            error_log(__FILE__ . " line " . __LINE__ . ' $urlParams=' . $urlParams ,0);
//            $client = new Proxy_Request($urlParams);
//            $client->setMethod(HTTP_REQUEST_METHOD_GET);
//            $client->sendRequest();
//            $xmlResponse = $client->getResponseBody();
             $xmlResponse = file_get_contents($urlParams);            
        }
        $oldLibXML = libxml_use_internal_errors();
        libxml_use_internal_errors(true);
        $simpleXML = simplexml_load_string($xmlResponse);

        // $debug = true; 
        //if ($debug !== false) {
        //  $flattenxmlarray = $this->flattenxmlarray($simpleXML);
        //   error_log(print_r($flattenxmlarray, true), 3, '/tmp/debug/' . $procrequest . '.vuf');
        //}


        libxml_use_internal_errors($oldLibXML);
        if ($simpleXML === false) {
            return false;
        }
        return $simpleXML;
    }
    /**   
     * Build Basic XML
     * Builds a simple xml string to send to the API
     * @param array $xml A keyed array of xml node names and data
     * @return string    An XML string
     * @access protected
     */
 protected function buildXML($xml)
    {
        $cnt = 0;
        $xmlString = "<queue>";
            foreach ($xml as $val) {
                $xmlString .= "<item>";
                $xmlString .= "<qid>" . $cnt . "</qid>";
                $xmlString .= "<qtitle>" . $val . "</qtitle>";
                $xmlString .= "</item>";
                $cnt +=1;
            }
        $xmlString .= "</queue>";
        $xmlComplete = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . $xmlString;
        return $xmlComplete;
    }   
    /**
     * Get Purchase History
     * This is responsible for retrieving the acquisitions history data for the
     * specific record (usually recently received issues of a serial).
     * @param string $id The record id to retrieve the info for
     * @return mixed     An array with the acquisitions data on success, PEAR_Error
     * on failure
     * @access public
     */
    public function getPurchaseHistory($id)
    {
        return array();
    }
     /**
     * Patron Login
     * This is responsible for authenticating a patron against the catalog.
     * @param string $barcode  The patron barcode
     * @param string $password The patron password
     * @return mixed           Associative array of patron info on successful login,
     * null on unsuccessful login, PEAR_Error on error.
     * @access public
     */
    public function patronLogin($barcode, $password)
    {
       $user = $this->AuthManager->isLoggedIn();
       $home_library = $user->home_library;
       $username     = $user->username;

       $username     = urlencode($username); // Fixes the problem of having spaces in your username urlencode alpat 08-04-2014

       $email        = $user->email;
       $mainpassword = $user->password;
       // try and set the home library into the session and change mylib to home_library if they are different
       if (!isset($this->session->home_library)) {
         $this->session->home_library = $home_library;              //  ;
         // error_log(__FILE__ . " line " . __LINE__ . ' 0 patronLogin $home_library=' . $home_library,0); 
       } else {
         if (strlen($home_library) > 0) { 
           // error_log(__FILE__ . " line " . __LINE__ . ' 1 patronLogin $home_library=' . $home_library,0); 
           if ($this->session->home_library != $_COOKIE['mylib']) {
              // error_log(__FILE__ . " line " . __LINE__ . ' 2  patronLogin $home_library=' . $home_library . '  this home=' . $this->session->home_library ,0);            
              $this->session->home_library = $home_library;
              setcookie('mylib', $home_library, null, '/');
           }
         }
       }
      // $params = 'patronlogin?homelib='.$home_library.'&barcode='. trim($barcode) .'&password='. trim($password) ;
      $params = 'patronlogin?homelib='.$home_library.'&barcode='. trim($barcode) . '&username=' . trim($username) .'&password='. trim($mainpassword) . '&email=' . trim($email); 
      // error_log(__FILE__ . " line " . __LINE__ . ' $params=' . $params,0);
      $response = $this->search_cloud($params,false);
      $xml = $response;
      // error_log(print_r($xml,true), 3, '/tmp/patronloginresponse.vuf');
	 $user = array();
	 foreach($xml as $item){
           $user[] = array(
            'id'           => trim($item->id),
            'firstname'    => trim($item->firstname),
            'lastname'     => trim($item->lastname),
            'cat_username' => trim($item->barcode),
            'cat_password' => trim($item->password),
            'email'        => trim($item->email),
            'pat_id'       => trim($item->pat_id),
            'pat_class'    => trim($item->pat_class),
            'home_library' => trim($item->home_library),
            'reg_date'     => trim($item->reg_date)
         );		 
         if (trim($item->id) == 'NF') {
          return NULL;
         }          
	  }	
      return $user;
    }
    /**
     * Get Patron Profile
     */
    public function getMyProfile($patron)
    {
    //   error_log(print_r($patron,true), 3, '/tmp/getMyProfileIn.vuf');
//      if (!isset($this->session->home_library)) {
//        $this->session->home_library =  $_COOKIE['mylib'];
//      }
      $home_library = $this->session->home_library;
	     
      $pat_id = $patron[0]['pat_id'];
      $params = 'getmyprofile?pat_id=' . $pat_id . '&homelib=' . $home_library; 
      $response = $this->search_cloud($params,false);
      $xml = $response;
      // $xml = simplexml_load_string($response);	
      $user = array();
	 foreach($xml as $item){
           $user = array(
            'firstname'    => trim($item->firstname),
	        'lastname'     => trim($item->lastname),
            'address1'     => trim($item->address1),
            'address2'     => trim($item->address2),
            'zip'          => trim($item->zip),
            'phone'        => trim($item->phone),
            'group'        => trim($item->pgroup)
		);	
 	  }
      return $user;
    }
    /**
     * Get Patron Fines
     * This is responsible for retrieving all fines by a specific patron.
     * @param array $patron The patron array from patronLogin
     * @return mixed        Array of the patron's fines on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyFines($patron)
    {
//        error_log(print_r($patron,true), 3, '/tmp/getMyFinesIn.vuf');
        $home_library = $patron[0]['home_library'];
        $pat_id = $patron[0]['pat_id'];
        $params = 'getmyfines?id=' . $pat_id  . '&homelib=' . $home_library ;		
        $response = $this->search_cloud($params,false);
        $xml = $response;
        //$xml = simplexml_load_string($response);	
        $items = array();
	    foreach($xml as $item){
          $items[] = array(
            'amount'  => trim($item->amount),
	        'checkout'=> trim($item->checkout),
            'fine'    => trim($item->fine),  
            'balance' => trim($item->balance), 
            'duedate' => trim($item->duedate),
            'id'      => trim($item->id)			
            );		 
	 }
//         error_log(print_r($patron,true), 3, '/tmp/getMyFinesOut.vuf');
         return $items;
    }
    /**
     * Get Patron Holds
     * This is responsible for retrieving all holds by a specific patron.
     * @param array $patron The patron array from patronLogin
     * @return mixed        Array of the patron's holds on success, PEAR_Error
     * otherwise.
     * @access public
     */
    public function getMyHolds($patron)
    {
 
    $home_library = $patron[0]['home_library'];
    $pat_id = $patron[0]['pat_id'];
    $params = 'getmyholds?pat_id=' . $pat_id  . '&homelib=' . $home_library ;
    $response = $this->search_cloud($params,false);
    $xml = $response;
    // $xml = simplexml_load_string($response);
    $items = array();	
	foreach($xml as $item){
         $items[] = array(
          'id'        => trim($item->id),
	      'location'  => trim($item->location),
          'expire'    => trim($item->expire),  
          'create'    => trim($item->create), 
          'reqnum'    => trim($item->reqnum)			
         );		 
	 }			
  	return $items;
    }
    /**
     * Get Patron Transactions
     *
     * This is responsible for retrieving all transactions (i.e. checked out items)
     * by a specific patron.
     *
     * @param array $patron The patron array from patronLogin
     *
     * @return mixed        Array of the patron's transactions on success,
     * PEAR_Error otherwise.
     * @access public
     */
    public function getMyTransactions($patron)
    {
        $items = array();
        $home_library = $patron[0]['home_library'];
	    $pat_id = $patron[0]['pat_id'];	
	    $params = 'getmytransactions?id=' . $pat_id  . '&homelib=' . $home_library  ;		
        $response = $this->search_cloud($params,false);
        $xml = $response;  // was already here ????????????????????????????????????????????????????????????
        // $xml = simplexml_load_string($response);	
	    foreach($xml as $item){
           $items[] = array(
	         'duedate'      => trim($item->duedate),
	         'barcode'      => trim($item->barcode),
             'renew'        => trim($item->renew),   // not exactly sure whate 
             'id'           => trim($item->id),
             'item_id'      => trim($item->item_id),
             'renewable'    => trim($item->renewable)   // if equal to false then no interface shows up with which to renew the titles
            );
	    }
        return $items;
    }
    
    protected function search_cloud($params,$debug = false) 
    {
        $url = $this->build_query($params);
        $response = file_get_contents($url);
        $response = simplexml_load_string($response);
        // $debug = true; // want output from every call
        if ($debug !== false) {
          $flattenxmlarray = $this->flattenxmlarray($response);
          error_log(print_r($flattenxmlarray, true), 3, '/tmp/debug/' . $params . '.vuf');
        }

        return $response;
    }
    
    protected function build_query($params)  
    {
    //	error_log(__FILE__ . " line " . __LINE__ . ' func14' ,0);
        $url = $this->host;
        if ($this->port) {
            $url =  "http://" . $url . ":" . $this->port . "/" ;
        } else {
            $url =  "http://" . $url . "/" ;
        }
        $url = $url . $params;
        return $url;
    }

   protected function flattenxmlarray($xml)
   {
   $arr = array();
   foreach ($xml->children() as $r) {
            // $c += 1;
            //echo "ccc= $c \n";
            if (count($r->children()) == 0) {
                  $arr[$r->getName()] = strval($r);
                  //$test1 = $r->getName();
                  //$test2 = strval($r);
                  //echo "c=$c  $test1 = $test2  \n";
            }
             else
            {
                 $arr[$r->getName()][] = $this->flattenxmlarray($r);
                // $test1 = $r->getName();
                // $test2 = xml2array($r);
                // echo "c=$c cnot0  $test1 = $test2 \n";
            } 
  }
      return $arr;
  }

 ////////////////////////////////   adding cloudrestful below here   
     /**
     * Public Function which retrieves renew, hold and cancel settings from the
     * driver ini file.
     *
     * @param string $function The name of the feature to be checked
     *
     * @return array An array with key-value pairs.
     * @access public
     */
    public function getConfig($function)
    {
        // error_log(__FILE__ . " line " . __LINE__ . ' public function getConfig($function)' ,0);
        if (isset($this->config[$function]) ) {
            $functionConfig = $this->config[$function];
        } else {
            $functionConfig = false;
        }
       // error_log(print_r($functionConfig, true), 3, '/tmp/functionConfig.vuf');
        return $functionConfig;
    }
 
    /**
     * Check Account Blocks
     * Checks if a user has any blocks against their account which may prevent them
     * performing certain operations
     * @param string $patronId A Patron ID
     * @return mixed           A boolean false if no blocks are in place and an array
     * of block reasons if blocks are in place
     * @access private
     */
    private function checkCloudAccountBlocks($patronId)  // private function _checkAccountBlocks($patronId)
    {
       // error_log(__FILE__ . " line " . __LINE__ . ' checkCloudAccountBlocks $patronId=' . $patronId,0);
        $blockReason = false;
       // $user = $this->AuthManager->isLoggedIn();
       // $home_library = $user->home_library;
        if (!isset($this->session->home_library)) {
          $this->session->home_library =  $_COOKIE['mylib'];
        }
        $home_library = $this->session->home_library;
	 // Build Hierarchy
        $procrequest = 'checkaccountblocks';
        $params = array(
                "RTYPE"          => 'BLOCKS',
                "ORG"            => $home_library,
                "PATRON_NUM"     => $patronId
            );
        $result = $this->hrequest($procrequest, $params);	
  	    // error_log(print_r($result, true), 3, '/tmp/_checaccountblocs.vuf');
            if ($result) {
                $node  = $result->children();
                $reply = (string)$node[0]->replytext;
                $note  = (string)$node[0]->note;  //$patronId = $renewDetails['patron'][0]['pat_id']; // was 'id' simple
		        $note2 = $node[0]->note2;
                // Valid Response
                if ($reply == "ok" && $note == "good") {
                   $blockReason = false; 
                } else {
                   $reason[]  = $note;
                   $blockReason = $reason;
                }
            }
        return $blockReason;
    }
    /**
     * Renew My Items
     *
     * Function for attempting to renew a patron's items.  The data in
     * $renewDetails['details'] is determined by getRenewDetails().
     *
     * @param array $renewDetails An array of data required for renewing items
     * including the Patron ID and an array of renewal IDS
     *
     * @return array              An array of renewal information keyed by item ID
     * @access public
     */
    public function renewMyItems($renewDetails)
    {
        $renewProcessed = array();
        $renewResult    = array();
        $failIDs        = array();
        $patronId     = $renewDetails['patron'][0]['pat_id']; // was 'id'
 	    $home_library = $renewDetails['patron'][0]['home_library'];
    
     //   error_log(__FILE__ . " line " . __LINE__ . ' Before $this->checkCloudAccountBlocks($patronId) ' . $patronId ,0);

        $finalResult['blocks'] = $this->checkCloudAccountBlocks($patronId);

     //   error_log(print_r($finalResult, true), 3, '/tmp/1finalResult.vuf');

        if ($finalResult['blocks'] === false) {
            // Add Items and Attempt Renewal
            foreach ($renewDetails['details'] as $renewID) {
                // Build an array of item ids which may be of use in the template
                $failIDs[$renewID] = "";
                // Create Rest API Renewal Key
                //   $restRenewID = $this->ws_dbKey. "|" . $renewID;
                //    $procrequest[$restRenewID] = false;
                //   error_log(__FILE__ . " line " . __LINE__ . ' $renewID=' . $renewID,0);
            $procrequest = 'renew_item';
             // Add Required Params
	        $renew = 'REQUESTRENEW';
            $params = array(
                "RTYPE"     => $renew,
                "PATRON_ID" => $patronId,
                "ORG"       => $home_library,
                "ITEM_ID"   => $renewID
            );				
                // Attempt Renewal
                $renewalObj = $this->hrequest($procrequest, $params);
                $process = $this->processCloudRenewals($renewalObj);
                // Process Renewal
                $renewProcessed[] = $process;
            }
            // Place Successfully processed renewals in the details array

       //     error_log(print_r($renewProcessed, true), 3, '/tmp/renewProcessed.vuf');

            foreach ($renewProcessed as $renewal) {
                if ($renewal !== false) {
                    $finalResult['details'][$renewal['item_id']] = $renewal;
                    unset($failIDs[$renewal['item_id']]);
                }
            }
            // Deal with unsuccessful results
            foreach ($failIDs as $id => $junk) {
                $finalResult['details'][$id] = array(
                    "success" => false,
                    "new_date" => false,
                    "item_id" => $id,
                    "sysMessage" => ""
                );
              //  error_log(__FILE__ . " line " . __LINE__ . ' inside foreach 2 unsuccessful results $id=' . $id ,0);
            }

        }
        //error_log(__FILE__ . " line " . __LINE__ . ' Right before return from renewMyItems' ,0); 
        //error_log(print_r($finalResult, true), 3, '/tmp/2finalResult.vuf');
        return $finalResult;
    }
    /**
     * Process Renewals
     * A support method of renewMyItems which determines if the renewal attempt
     * was successful
     * @param object $renewalObj A simpleXML object loaded with renewal data
     * @return array             An array with the item id, success, new date (if
     * available) and system message (if available)
     * @access private
     */
    private function processCloudRenewals($renewalObj)
    {
    	//error_log(__FILE__ . " line " . __LINE__ . ' func19' ,0);
        $node = $renewalObj->children();
 
        //error_log(print_r($node, true), 3, '/tmp/node-processCloudRenewals.vuf');

	    $note  = (string)$node[0]->note;
	    $note1 = (string)$node[0]->note1;
        $itemId                 = (string)$node[0]->id;
        $dueDate                = (string)$node[0]->duedate;
        $response['item_id']    = $itemId;
        $response['sysMessage'] = $note . ' ' .$note1;  // $renewalStatus ;
	    $response['new_date']   = $dueDate;        
        // Valid Response
        if ($note == "ok") {
          $response['success'] = true;
        } else {
          $response['success']    = false;
        }
        
        // error_log(print_r($response, true), 3, '/tmp/response-processCloudRenewals.vuf');

        return $response;
    }
    /**
     * Check Item Requests
     * Determines if a user can place a hold or recall on a specific item
     * @param string $bibId    An item's Bib ID
     * @param string $patronId The user's Patron ID
     * @param string $request  The request type (hold or recall)
     * @param string $itemId   An item's Item ID (optional)
     * @return boolean         true if the request can be made, false if it cannot
     * @access private
     */
    private function checkItemCloudRequests($bibId, $patronId, $request, $itemId = false)
    { 

      error_log(__FILE__ . " line " . __LINE__ . 'checkItemCloudRequests patronId=' . $patronId . ' request=' . $request . ' $bibId=' . $bibId . ' $itemId=' . $itemId,0);

      if (!isset($this->session->home_library)) {
        $this->session->home_library =  $_COOKIE['mylib'];
      }
      $home_library = $this->session->home_library;
    
        // was   if (!empty($bibId) && !empty($patronId) && !empty($request) ) {
        // For now I don't see where request type matters    
        if (!empty($bibId) && !empty($patronId)) {
            $procrequest = 'checkitemrequest';
         //   $home_library = $_COOKIE['mylib'];  
   
            $params = array(
                "RTYPE"     => $request,
                "PATRON_ID" => $patronId,
                "homelib"   => $home_library,
                "BIB_ID"    => $bibId,
                "ITEM_ID"   => $itemId
            );
            
            $result = $this->hrequest($procrequest, $params, "GET", false);

            if ($result) {
                $node = $result->children();
                 // $note  = (string)$node[0]->note; 
                 //error_log(print_r($node, true), 3, '/tmp/node-checkItemCloudRequests.vuf');

                $reply = $node[0]->replytext;
                $note  = $node[0]->note;
                // Valid Response
                if ($reply == "ok" && $note == "good") {
                      return "ok";
                }
                return $note; // Reserve block and returning the reason why
            }
        }

        return "Patron=$patronId Title=$bibId";
        //return false;
    }

    /**
     * 
     * Make Item Requests
     * Places a Hold or Recall for a particular item
     * @param string $bibId       An item's Bib ID
     * @param string $patronId    The user's Patron ID
     * @param string $request     The request type (hold or recall)
     * @param array  $requestData An array of data to submit with the request,
     * may include comment, lastInterestDate and pickUpLocation
     * @param string $itemId      An item's Item ID (optional)
     * @return array             An array of data from the attempted request
     * including success, status and a System Message (if available)
     * @access private
     */

    private function makeItemRequests($bibId, $patronId, $request,$requestData, $itemId = false) {

         //error_log(__FILE__ . " line " . __LINE__ . ' $patronId=' . $patronId,0);

    	  //$user = $this->AuthManager->isLoggedIn();
          //$home_library = $user->home_library;

         if (!isset($this->session->home_library)) {
           $this->session->home_library =  $_COOKIE['mylib'];
         }

         $home_library = $this->session->home_library; 
   //     $lastinterestdate = clarion deformat @d2-
        $urlencodedcomment = urlencode(trim($requestData['comment']));
       
        $params = 'get_rsv?HOMELIB='.$home_library.'&PATRON_NUM='. trim($patronId)
                . '&TITLE_NUM=' . trim($bibId) .'&RTYPE=TRY&RSV_ID=0&ITEM_NUM='
                . trim($itemId) . '&COMMENT=' . $urlencodedcomment
                . '&LID=' . trim($requestData['lastInterestDate']) . '&pickup=1' ;
        
        $result = $this->search_cloud($params,false);
        // $result = simplexml_load_string($result);

        if ($result) {
                $node = $result->children();
                $reply = $node[0]->replytext;
                $note = $node[0]->note;
                if ($reply == "ok") {
                    $response['success'] = true;
                    $response['status'] = "hold_success";
                } else {
                    $response['sysMessage'] = $note;
                }
            }
        return $response;
    }

    /**
     * Hold Error
     * Returns a Hold Error Message
     * @param string $msg An error message string
     * @return array An array with a success (boolean) and sysMessage key
     * @access private
     */
    private function _holdError($msg)
    {
       error_log(__FILE__ . " line " . __LINE__ . ' $msg=' . $msg ,0);
       // $msg = 'what just happened';
        return array(
                    "success" => false,
                    "sysMessage" => $msg
        );
    }

    /**
     * Get Pick Up Locations
     * This is responsible for gettting a list of valid library locations for
     * holds / recall retrieval
     * @param array $patron Patron information returned by the patronLogin method.
     * @return array        An keyed array where libray id => Library Display Name
     * @access public
     * 
       If I show more than one library the patron can change the home_library in the database
       I am not sure this will be possible the way library numbers are set up
     */
    public function getPickUpLocations($patron = false)
    {
    //   error_log(print_r($patron, true), 3, '/tmp/getPickUpLocations.vuf');
       $home_library  = $patron[0]['home_library'];
 
       $UntilIGrokIt[] = array('locationID' => $home_library,'locationDisplay' => 'Why This Library of Course!');

       return $UntilIGrokIt;
    }
    /**
     * Get Default Pick Up Location
     * Returns the default pick up location set in cloudrest.ini
     * @param array $patron Patron information returned by the patronLogin method.
     * @return array        An keyed array where libray id => Library Display Name
     * @access public
     */
    public function getDefaultPickUpLocation($patron = false)
    {
     //  error_log(print_r($patron, true), 3, '/tmp/getDefaultpickuplocation.vuf');
       $home_library  = $patron[0]['home_library'];
     //  error_log(__FILE__ . " line " . __LINE__ . ' getDefaultPickUpLocation $home_library=' . $home_library ,0);
      //return 'LIB'; // This is currently dependant on how cloudrest.ini is setup
        return $home_library ;
    }    
    /**
     * Place Hold
     * Attempts to place a hold or recall on a particular item and returns
     * an array with result details or a PEAR error on failure of support classes
     *     * @param array $holdDetails An array of item and patron data
     * @return mixed An array of data on the request including
     * whether or not it was successful and a system message (if available) or a
     * PEAR error on failure of support classes
     * @access public
     */
    public function placeHold($holdDetails)
    {
        $patron = $holdDetails['patron'][0]['pat_id'];
    //    error_log(__FILE__ . " line " . __LINE__ . ' placeHold  patron=' . $patron ,0);

        error_log(print_r($holdDetails, true), 3, '/tmp/holds.vuf');

        $pickUpLocation = !empty($holdDetails['pickUpLocation'])
        ? $holdDetails['pickUpLocation'] : $this->defaultPickUpLocation;

        $itemId = $_GET['number'];
        // $itemId = $holdDetails['item_id'];  // was  $itemId = $holdDetails['item_id'];
//        $comment = $holdDetails['comment'];

        $bibId = $holdDetails['id'];

        // get last interest date from Display Format to cloud required format 
//        $lastInterestDate  = $holdDetails['requiredBy'];     
        // Make Sure Pick Up Library is Valid

/*
        $pickUpValid = false;
        $patronarray = $holdDetails['patron'];  // Took a while to get this right
        $pickUpLibs = $this->getPickUpLocations($patronarray); // Was not passing it anything before
        // error_log(print_r($pickUpLibs, true), 3, '/tmp/pickUpLibs.vuf');
        foreach ($pickUpLibs as $location) {
            if ($location['locationID'] == $pickUpLocation) {
                $pickUpValid = true;
            }
        }
        if (!$pickUpValid) {
            // Invalid Pick Up Point
            return $this->_holdError("hold_invalid_pickup");
        }
        // Build Request Data
*/

       $comment = '';
       $pickUpLocation =  $holdDetails['patron'][0]['home_library'];
       $lastInterestDate = date('m-d-Y' , strtotime('+60 days'));
       error_log(__file__ . ' line = ' . __line__ . ' $lastInterestDate= ' . $lastInterestDate,0);

        $requestData = array(
            'pickupLocation' => $pickUpLocation,
            'lastInterestDate' => $lastInterestDate,
            'comment' => $comment
        );

        $type = 'mylibcloud';

        $ok = $this->checkItemCloudRequests($bibId, $patron, $type, $itemId);

    //    error_log(__FILE__ . " line " . __LINE__ . ' ok=' . $ok ,0);
        if ($ok == "ok") {
            // Attempt Request

            $result = $this->makeItemRequests($bibId, $patron, $type, $requestData, $itemId);
            
//            error_log(print_r($result, true), 3, '/tmp/resultplacehold1.vuf');	
             
            if ($result) {
                return $result;
            }
        }

        return $this->_holdError($ok); // if ok is not equal to "ok" then it was blocked and we are sending why
    }
    /**
     * Cancel Holds
     * Attempts to Cancel a hold or recall on a particular item. The
     * data in $cancelDetails['details'] is determined by getCancelHoldDetails().
     * @param array $cancelDetails An array of item and patron data
     * @return array               An array of data on each request including
     * whether or not it was successful and a system message (if available)
     * @access public
     */
    public function cancelHolds($cancelDetails)
    {
        $details  = $cancelDetails['details'];
        $patron   = $cancelDetails['patron'][0];
//        $user = UserAccount::isLoggedIn();
//        $home_library = $user->home_library;
        $count    = 0;
        $response = array();
        foreach ($details as $cancelDetails) {
            list($itemId, $cancelCode) = explode("|", $cancelDetails);
            // Create Rest API Cancel Key
            // $cancelID = $this->ws_dbKey. "|" . $cancelCode;
            $cancelID =  $cancelCode;
            // Build Hierarchy
           $procrequest = 'request_rsv' ;
            // Add Required Params
            $params = array(
                "RTYPE"          => 'CANCEL',
                "ORG"            => $patron['home_library'],
                "PATRON_NUM"     => $patron['pat_id'],
                "TITLE_NUM"      => $itemId,
                "RSV_ID"         => $cancelID
            );
            $cancel = $this->hrequest($procrequest, $params, "GET");
            if ($cancel) {
                // Process Cancel
                $cancel = $cancel->children();
                $node = "reply-text";
                $reply = (string)$cancel->$node;
                $count = ($reply == "ok") ? $count+1 : $count;
                $response[$itemId] = array(
                    'success' => ($reply == "ok") ? true : false,
                    'status' => ($result[$itemId]['success'])
                        ? "hold_cancel_success" : "hold_cancel_fail",
                    'sysMessage' => ($reply == "ok") ? false : $reply,
                );
            } else {
                $response[$itemId] = array(
                    'success' => false, 'status' => "hold_cancel_fail"
                );
            }
        }
        $result = array('count' => $count, 'items' => $response);
        return $result;
    }
    /**
     * Get Cancel Hold Details
     * In order to cancel a hold, cloud requires the patron details an item ID
     * and a recall ID. This function returns the item id and recall id as a string
     * separated by a pipe, which is then submitted as form data in Hold.php. This
     * value is then extracted by the CancelHolds function.
     * @param array $holdDetails An array of item data
     * @return string Data for use in a form field
     * @access public
     */
    public function getCancelHoldDetails($holdDetails)
    {
    
        $cancelDetails = $holdDetails['id']."|".$holdDetails['reqnum'];
        return $cancelDetails;
    }
    /**
     * Get Renew Details
     * In order to renew an item, cloud requires the patron details and an item
     * id. This function returns the item id as a string which is then used
     * as submitted form data in checkedOut.php. This value is then extracted by
     * the RenewMyItems function.
     * @param array $checkOutDetails An array of item data
     * @return string Data for use in a form field
     * @access public
     */
    public function getRenewDetails($checkOutDetails)  // I do not understand how this works with the 
    {
       // when  a patron is blocked in lb_pat I get when trying to renew  PHP Notice:  Undefined index: details in /usr/local/vufind2/module/VuFind/src/VuFind/Controller/Plugin/Renewals.php
       // error_log(__FILE__ . " line " . __LINE__ . 'get  RenewDetails' ,0);
       // error_log(print_r($checkOutDetails, true), 3, '/tmp/checkOutDetails.vuf');  // goodby
        $renewDetails = $checkOutDetails['id'];
        return $renewDetails;
    }   
}

?>

<?php

/**
 * GpsSoap class
 */
class GpsSoap {
    /**
     * Success error code
     */
    const ERR_OK = 'ok';

    /**
     * State logged out
     */
    const LOGGED_OUT = 0;
    /**
     * State login_1
     */
    const LOGIN_1 = 1;
    /**
     * State logged in
     */
    const LOGGED_IN = 2;

    /**
     * Webservice host
     */
    const HOST = '85.118.26.16';
    /**
     * Webservice port
     */
    const PORT = '8081';

    /**
     * Webservice path of login request
     */
    const PATH_LOGIN = 'soap/gps/login';
    /**
     * Webservice path of login continue request
     */
    const PATH_LOGIN_CONTINUE = 'soap/gps/logincontinue';
    /**
     * Webservice path of logout request
     */
    const PATH_LOGOUT = 'soap/gps/logout';
    /**
     * Webservice path of get last location request
     */
    const PATH_LAST_LOCATION = 'soap/gps/getlastposition';
    /**
     * Webservice path of set tracking frequency request
     */
    const PATH_TRACKING_FREQUENCY = 'soap/gps/settrackingfrequency';

    /**
     * GpsSoapTransport instance
     * @var GpsSoapTransport GpsSoapTransport instance
     */
    protected $_transport;
    /**
     * GpsSoapXml instance
     * @var GpsSoapXml GpsSoapXml instance
     */
    protected $_xml;

    /**
     * Application ID
     * @var mixed string or int
     */
    public $application_id;
    /**
     * Last error code or status
     * @var string
     */
    public $err_code;
    /**
     * URI
     * @var string
     */
    public $uri;
    /**
     * Auth type
     * @var string
     */
    public $auth_type;
    /**
     * Auth
     * @var string
     */
    public $auth;
    /**
     * Nonce
     * @var mixed string/int
     */
    public $nonce;
    /**
     * Opaque
     * @var mixed string/int
     */
    public $opaque;
    /**
     * Qop
     * @var string
     */
    public $qop;
    /**
     * Cnonce
     * @var mixed string/int
     */
    public $cnonce;
    /**
     * Realm
     * @var string
     */
    public $realm;
    /**
     * Request timestamp
     * @var mixed string/int
     */
    public $current_time;
    /**
     * Last returned longitude
     * @var mixed int/string
     */
    public $longitude;
    /**
     * Last returned latitude
     * @var mixed int/string
     */
    public $latitude;
    /**
     * Last returned frequency
     * @var mixed int/string
     */
    public $frequency;
    /**
     * Last returned expiration
     * @var mixed int/string
     */
    public $expiration;

    /**
     * Current request id
     * @var int
     */
    public $request_id = 1;
    /**
     * Current login state
     * @var int
     */
    public $state = self::LOGGED_OUT;

    /**
     * Constructor - initialize transport and xml instances
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize transport and xml instances
     */
    public function init()
    {
        $this->_transport = new GpsSoapTransport(self::HOST, self::PORT);
        $this->_xml = new GpsSoapXml($this);
    }

    /**
     * Send login request
     * @param string $username service username
     * @param string $password service password
     * @return boolean true - logged in, false - logged out
     */
    public function login($username, $password) {
        $loginRequest = $this->_xml->getLoginRequest($username);
        $loginResponse = $this->_transport->request(self::PATH_LOGIN, $loginRequest);
        $return = $this->_xml->parseLoginResponse($loginResponse);
        
        if ($return) {
            $loginContinueRequest = $this->_xml->getLoginContinueRequest($this->application_id, $this->uri, $password);
            $loginContinueResponse = $this->_transport->request(self::PATH_LOGIN_CONTINUE, $loginContinueRequest);
            $return = $this->_xml->parseLoginContinueResponse($loginContinueResponse);
        }

        return $return;
    }

    /**
     * Send logout request
     * @return boolean true - logged out, false - logged in
     */
    public function logout() {
        $this->_checkLoggedIn(__METHOD__);

        $logoutRequest = $this->_xml->getLogoutRequest($this->application_id);
        $logoutResponse = $this->_transport->request(self::PATH_LOGOUT, $logoutRequest);
        $return = $this->_xml->parseLogoutReponse($logoutResponse);

        return $return;
    }

    /**
     * Get last position of the user
     * @param mixed $user string/int user identificator
     * @return array associative array with these keys: status, timestamp, longitude, latitude
     */
    public function getLastPosition($user) {
        $this->_checkLoggedIn(__METHOD__);

        $getLastPositionRequest = $this->_xml->getLastPositionRequest($this->application_id, $this->request_id, $user);
        $getLastPositionResponse = $this->_transport->request(self::PATH_LAST_LOCATION, $getLastPositionRequest);
        $return = $this->_xml->parseGetLastPositionResponse($getLastPositionResponse);

        $this->request_id++;
        $return['user_id'] = $user;

        return $return;
    }

    /**
     * Get mutiple positions at once
     * @param array $users associative array of users username => user id
     * @return array associative array of username => associative array with keys: status, timestamp, longitude, latitude
     */
    public function getLastPositions($users)
    {
        $return = array();

        foreach($users as $user => $id) {
            $return[$user] = $this->getLastPosition($id);
        }

        return $return;
    }

    /**
     * Set tracking frequency request
     * @param mixed $user string/int user id
     * @param int $frequency frequency of tracking in seconds
     * @param int $expiration expiration of this request in seconds
     * @return array associative array with keys frequency, expiration
     */
    public function setTrackingFrequencyRequest($user, $frequency, $expiration) {
        $this->_checkLoggedIn(__METHOD__);

        $setTrackingFrequencyRequest = $this->_xml->getTrackingFrequencyRequest($this->application_id, $this->request_id, $user, $frequency, $expiration);
        $setTrackingFrequencyResponse = $this->_transport->request(self::PATH_TRACKING_FREQUENCY, $setTrackingFrequencyRequest);
        $return = $this->_xml->parseSetTrackingFrequencyResponse($setTrackingFrequencyResponse);

        $this->request_id++;

        return $return;
    }

    /**
     * Check if state is logged in for certain requests
     * @param string $function function/method name
     * @throws Exception exception if not logged in
     */
    protected function _checkLoggedIn($function)
    {
        if ($this->state <> self::LOGGED_IN) {
            throw new Exception('You can\'t call function GpsSoap::' . $function . '(), because you aren\'t logged in.');
        }
    }
}

/**
 * GpsSoapXml - generate and parse XML requests and responses
 */
class GpsSoapXml
{

    /**
     * Request login body
     */
    const REQUEST_LOGIN = '<?xml version="1.0" encoding="utf-8"?>
<env:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://www.mobiletornado.com/iprs/gps/soap" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:SOAP-ENC="http://www.w3.org/2003/05/soap-encoding">
 <env:Body>
  <ns1:RequestLogin xmlns:ns1="http://www.mobiletornado.com/iprs/gps/soap">
   <ns1:Name>%username%</ns1:Name>
   <ns1:OrgId>0</ns1:OrgId>
   <ns1:LoginEntityType>dispatcher</ns1:LoginEntityType>
   <ns1:AuthType>simple</ns1:AuthType>
  </ns1:RequestLogin>
 </env:Body>
</env:Envelope>
';

    /**
     * Request login continue body
     */
    const REQUEST_LOGIN_CONTINUE = '<?xml version="1.0" encoding="utf-8"?>
<env:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://www.mobiletornado.com/iprs/gps/soap" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:SOAP-ENC="http://www.w3.org/2003/05/soap-encoding">
 <env:Header>
  <ns1:Request>LoginContinue</ns1:Request>
 </env:Header>
 <env:Body>
  <ns1:RequestLoginContinue xmlns:ns1="http://www.mobiletornado.com/iprs/gps/soap">
   <ns1:ApplicationID>%application_id%</ns1:ApplicationID>
   <ns1:URI>%uri%</ns1:URI>
   <ns1:Auth>null</ns1:Auth>
   <ns1:Nonce>null</ns1:Nonce>
   <ns1:Opaque>null</ns1:Opaque>
   <ns1:Cnonce>null</ns1:Cnonce>
   <ns1:Realm>null</ns1:Realm>
   <ns1:Response>%password%</ns1:Response>
  </ns1:RequestLoginContinue>
 </env:Body>
</env:Envelope>
';

    /**
     * Request logout body
     */
    const REQUEST_LOGOUT = '<?xml version="1.0" encoding="utf-8"?>
<env:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://www.mobiletornado.com/iprs/gps/soap" xmlns:xsd="http
://www.w3.org/2001/XMLSchema" xmlns:SOAP-ENC="http://www.w3.org/2003/05/soap-encoding">
 <env:Header>
  <ns1:Request>Logout</ns1:Request>
 </env:Header>
 <env:Body>
  <ns1:RequestLogout xmlns:ns1="http://www.mobiletornado.com/iprs/gps/soap">
   <ns1:ApplicationID>%application_id%</ns1:ApplicationID>
  </ns1:RequestLogout>
 </env:Body>
</env:Envelope>
';

    /**
     * Request last position body
     */
    const REQUEST_LAST_POSITION = '<?xml version="1.0" encoding="utf-8"?>
<env:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://www.mobiletornado.com/iprs/gps/soap" xmlns:xsd="http
://www.w3.org/2001/XMLSchema" xmlns:SOAP-ENC="http://www.w3.org/2003/05/soap-encoding">
 <env:Header>
  <ns1:Request>GetLastPosition</ns1:Request>
 </env:Header>
 <env:Body>
  <ns1:RequestGetLastPosition xmlns:ns1="http://www.mobiletornado.com/iprs/gps/soap">
   <ns1:ApplicationID>%application_id%</ns1:ApplicationID>
   <ns1:RequestID>%request_id%</ns1:RequestID>
   <ns1:DataSpecifier>
    <ns1:Entity>
     <ns1:User>%user%</ns1:User>
    </ns1:Entity>
    <ns1:GpsDataMask>
     <ns1:Type>
      <ns1:GpsDataType>longitude</ns1:GpsDataType>
      <ns1:GpsDataType>latitude</ns1:GpsDataType>
     </ns1:Type>
    </ns1:GpsDataMask>
   </ns1:DataSpecifier>
  </ns1:RequestGetLastPosition>
 </env:Body>
</env:Envelope>
';

    /**
     * Request set tracking frequency
     */
    const REQUEST_TRACKING_FREQUENCY = '<?xml version="1.0" encoding="utf-8"?>
<env:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:env="http://www.w3.org/2003/05/soap-envelope" xmlns:ns1="http://www.mobiletornado.com/iprs/gps/soap" xmlns:xsd="http
://www.w3.org/2001/XMLSchema" xmlns:SOAP-ENC="http://www.w3.org/2003/05/soap-encoding">
 <env:Header>
  <ns1:Request>SetTrackingFrequency</ns1:Request>
 </env:Header>
 <env:Body>
  <ns1:RequestSetTrackingFrequency xmlns:ns1="http://www.mobiletornado.com/iprs/gps/soap">
   <ns1:ApplicationID>%application_id%</ns1:ApplicationID>
   <ns1:RequestID>%request_id%</ns1:RequestID>
   <ns1:User>%user%</ns1:User>
   <ns1:FrequencySecs>%frequency%</ns1:FrequencySecs>
   <ns1:ExpirationSecs>%expiration%</ns1:ExpirationSecs>
  </ns1:RequestSetTrackingFrequency>
 </env:Body>
</env:Envelope>
';

    /**
     * preg_match universal pattern
     */
    const PATTERN = '/%s>([\d\w\.\-\-@\,]+)/i';

    /**
     * GpsSoap instance
     * @var GpsSoap GpsSoap instance
     */
    protected $_gps_soap;

    /**
     * Constructor - set GpsSoap instance
     * @param GpsSoap $gps_soap GpsSoap instance
     */
    public function __construct(GpsSoap &$gps_soap)
    {
        $this->_gps_soap = $gps_soap;
    }

    /**
     * Generate login request
     * @param string $username login username
     * @return string xml login request
     */
    public function getLoginRequest($username)
    {
        return $this->_replace(array('username' => $username), self::REQUEST_LOGIN);
    }

    /**
     * Generate continue login request
     * @param mixed $application_id application id (string or int)
     * @param string $uri uri, but it's actually login username
     * @param string $password password for the login username
     * @return string xml login continue request
     */
    public function getLoginContinueRequest($application_id, $uri, $password)
    {
        return $this->_replace(array('application_id' => $application_id, 'uri' => $uri, 'password' => $password), self::REQUEST_LOGIN_CONTINUE);
    }

    /**
     * Generate logout request
     * @param mixed $application_id application_id (string or int)
     * @return string xml logout request
     */
    public function getLogoutRequest($application_id)
    {
        return $this->_replace(array('application_id' => $application_id), self::REQUEST_LOGOUT);
    }

    /**
     * Generate last position request
     * @param mixed $application_id application_id (string or int)
     * @param mixed $request_id request id (string or int)
     * @param string $user tracked user id
     * @return string xml get last position request
     */
    public function getLastPositionRequest($application_id, $request_id, $user)
    {
        return $this->_replace(array('application_id' => $application_id, 'request_id' => $request_id, 'user' => $user), self::REQUEST_LAST_POSITION);
    }

    /**
     * Generate set tracking frequency request
     * @param mixed $application_id application_id (string or int)
     * @param mixed $request_id request id (string or int)
     * @param string $user tracked user id
     * @param int $frequency frequency in seconds
     * @param int $expiration request expiration in seconds
     * @return string xml set tracking frequency request
     */
    public function getTrackingFrequencyRequest($application_id, $request_id, $user, $frequency, $expiration)
    {
        return $this->_replace(array('application_id' => $application_id, 'request_id' => $request_id, 'user' => $user, 'frequency' => $frequency, 'expiration' => $expiration), self::REQUEST_TRACKING_FREQUENCY);
    }

    /**
     * Parse XML login response, and set $state for the GpsSoap instance
     * @param string $loginResponse xml response of the login request
     * @return boolean
     */
    public function parseLoginResponse($loginResponse)
    {
        $match = array(
            'application_id' => 'ApplicationID',
            'err_code' => 'ErrCode',
            'uri' => 'URI',
            'auth_type' => 'AuthType',
            'auth' => 'Auth',
            'nonce' => 'Nonce',
            'opaque' => 'Opaque',
            'qop' => 'Qop',
            'realm' => 'Realm'
        );

        $this->_match($match, $loginResponse);

        if ($this->_gps_soap->err_code == GpsSoap::ERR_OK) {
            $this->_gps_soap->state = GpsSoap::LOGIN_1;
            return true;
        }

        return false;
    }

    /**
     * Parse XML login continue response, and set $err_code, and $state for the GpsSoap instance
     * @param string $loginContinueResponse xml response of the login continue request
     * @return boolean
     */
    public function parseLoginContinueResponse($loginContinueResponse)
    {
        $match = array(
            'application_id' => 'ApplicationID',
            'err_code' => 'ErrCode',
            'current_time' => 'CurrentTime'
        );

        $this->_match($match, $loginContinueResponse);

        if ($this->_gps_soap->err_code == GpsSoap::ERR_OK) {
            $this->_gps_soap->state = GpsSoap::LOGGED_IN;
            return true;
        } else {
            $this->_gps_soap->state = GpsSoap::LOGGED_OUT;
            return false;
        }
    }

    /**
     * Parse XML logout response, and set $err_code, and $state for the GpsSoap instance
     * @param string $logoutResponse xml response of the logout request
     * @return boolean
     */
    public function parseLogoutReponse($logoutResponse)
    {
        $match = array(
            'application_id' => 'ApplicationID',
            'err_code' => 'ErrCode'
        );

        $this->_match($match, $logoutResponse);

        if ($this->_gps_soap->err_code == GpsSoap::ERR_OK) {
            $this->_gps_soap->state = GpsSoap::LOGGED_OUT;
            return true;
        } else {
            $this->_gps_soap->state = GpsSoap::LOGGED_IN;
            return false;
        }
    }

    /**
     * Parse XML get last position response, and set $status, $timestamp, $langitude, $latitude for the GpsSoap instance
     * @param string $getLastPositionResponse xml response of the get last position request
     * @return array associative array with keys: status, timestamp, longitude, latitude
     */
    public function parseGetLastPositionResponse($getLastPositionResponse)
    {
        $match = array(
            'application_id' => 'ApplicationID',
            'err_code' => 'Status',
            'uri' => 'RequestID',
            'timestamp' => 'TimeStamp',
            'longitude' => 'longitude',
            'latitude' => 'latitude'
        );

        $this->_match($match, $getLastPositionResponse);

        return array(
            'status' => $this->_gps_soap->err_code,
            'timestamp' => $this->_gps_soap->timestamp / 1000,
            'longitude' => $this->_gps_soap->longitude / 1000000,
            'latitude' => $this->_gps_soap->latitude / 1000000
        );
    }

    /**
     * Parse XML set tracking frequency response, and set $frequency, $expiration for the GpsSoap instance
     * @param string $setTrackingFrequencyResponse xml response of set tracking frequency request
     * @return array associative array with keys: frequency, expiration
     */
    public function parseSetTrackingFrequencyResponse($setTrackingFrequencyResponse)
    {
        $match = array(
            'application_id' => 'ApplicationID',
            'err_code' => 'Status',
            'frequency' => 'FrequencySecs',
            'expiration' => 'ExpirationSecs'
        );

        $this->_match($match, $setTrackingFrequencyResponse);
        
        return array(
            'frequency' => $this->_gps_soap->frequency,
            'expiration' => $this->_gps_soap->expiration
        );
    }

    /**
     * Replace multiple patterns in string/xml
     * @param array $patterns associative array of pattern_key => xml tag name
     * @param string $subject string/xml of the request
     * @return string string/xml with replaced patterns
     */
    protected function _replace($patterns, $subject)
    {
        $search = array();
        $replace = array();

        foreach ($patterns as $_search => $_replace) {
            $search[] = '%' . $_search . '%';
            $replace[] = $_replace;
        }

        return str_replace($search, $replace, $subject);
    }

    /**
     * Match multiple patterns in string/xml response
     * @param array $patterns associative array of the key => xml tag name
     * @param string $subject string/xml response
     * @return array associative array of matched key => value
     */
    protected function _match($patterns, $subject)
    {
        $return = array();
        
        foreach ($patterns as $key => $pattern) {
            $matches = array();
            $pattern = sprintf(self::PATTERN, $pattern);
            preg_match($pattern, $subject, $matches);
            $return[$key] = isset($matches[1]) ? $matches[1] : NULL;
            
            $this->_gps_soap->$key = $return[$key];
        }

        return $return;
    }

}

/**
 * GpsSoapTransport - transport layer using cURL extension
 */
class GpsSoapTransport
{
    /**
     * Enabled or disable debugging
     */
    const DEBUG = false;

    /**
     * Default content type
     */
    const CONTENT_TYPE = 'Content-Type: application/soap+xml; charset="utf-8"';
    /**
     * Default user agent
     */
    const USER_AGENT = 'User-Agent: PhpGpsSoap1.0';
    /**
     * URL pattern
     */
    const URL = 'http://%s:%d/%s';
    /**
     * HTTP status code success
     */
    const CODE_SUCCESS = 200;
    /**
     * cURL default timeout
     */
    const TIMEOUT = 5;
    /**
     * HTTP end of line
     */
    const EOL = "\r\n";

    /**
     * Protected cURL instance
     * @var curl protected cURL instance
     */
    protected $_curl;

    /**
     * Protected host
     * @var string protected host name of the request
     */
    protected $_host;
    /**
     * Protected port
     * @var mixed protected port number (can be both string or integer) of the request
     */
    protected $_port;
    /**
     * Protected path
     * @var string protected path of the request
     */
    protected $_path;
    /**
     * Protected url
     * @var string protected url of the request (composed from host, port, and path)
     */
    protected $_url;

    /**
     * Constructor - prepare transportation layer, and init cURL instance
     * @param string $host request host
     * @param mixed $port request port
     * @see init()
     */
    public function __construct($host, $port)
    {
        $this->init($host, $port);
    }

    /**
     * Destructor - close cURL instance
     */
    public function __destruct()
    {
        curl_close($this->_curl);
    }

    /**
     * Init cURL extension
     * @param string $host request host
     * @param mixed $port request port
     */
    public function init($host, $port)
    {
        // set host and port
        $this->_host = $host;
        $this->_port = $port;

        // create cURL instance
        $this->_curl = curl_init();

        // set return body and headers
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->_curl, CURLOPT_HEADER, true);

        // set request type to post
        curl_setopt($this->_curl, CURLOPT_POST, true);

        // set track response headers
        curl_setopt($this->_curl, CURLINFO_HEADER_OUT, true);

        if (self::DEBUG) {
            // verbose cURL output
            curl_setopt($this->_curl, CURLOPT_VERBOSE, true);
        }

        // set curl timeouts
        curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT, self::TIMEOUT);
        curl_setopt($this->_curl, CURLOPT_DNS_CACHE_TIMEOUT, self::TIMEOUT);
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, self::TIMEOUT);
    }

    /**
     * Send $request to $path and previously set $host and $port -> $url
     * @param string $path path without leading /
     * @param string $request body of the request
     * @return string body of the response
     * @throws Exception exception if http status code is not success, or empty body
     */
    public function request($path, $request)
    {
        // create url and prepare empty exception
        $url = $this->_getUrl($path);
        $exception = false;

        // set url, post body, and headers of the request
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, $request);
        curl_setopt($this->_curl, CURLOPT_HTTPHEADER, array(self::CONTENT_TYPE, 'Content-Length: ' . strlen($request)));

        // get result (headers, body), and additional information
        $result = curl_exec($this->_curl);
        $status = curl_getinfo($this->_curl);

        // parse result and get headers and body
        $result = explode(self::EOL . self::EOL, $result, 2);
        $headers = isset($result[0]) ? $result[0] : NULL;
        $body = isset($result[1]) ? $result[1] : NULL;

        // check http status code
        if ($status['http_code'] <> self::CODE_SUCCESS) {
            $exception = 'GpsTransport::request() failed with http error code: ' . $status['http_code'] . '.';
        }

        // check response content
        if (empty($body)) {
            $exception = 'GpsTransport::request() failed and returned empty body.';
        }

        // print debug information
        $this->_debug($request,$headers,$body,$status);

        // if exception message, throw it
        if ($exception) {
            throw new Exception($exception);
        }

        // return response body
        return $body;
    }

    /**
     * Get cURL instance
     * @return curl
     */
    public function getCurl()
    {
        return $this->_curl;
    }

    /**
     * Set cURL instance
     * @param curl $curl
     */
    public function setCurl($curl)
    {
        $this->_curl = $curl;
    }

    /**
     * Compile URL from $host, $port and $path
     * @param string $path path of the URL without leading /
     * @return string compiled URL
     */
    protected function _getUrl($path)
    {
        $this->_path = $path;
        $this->_url = sprintf(self::URL, $this->_host, $this->_port, $this->_path);
        return $this->_url;
    }

    /**
     * Print debug information for request with URL, request headers and body,
     * AND response headers and body
     * @param string $request request body
     * @param string $headers response headers
     * @param string $body response body
     * @param array $status additional information
     * @see DEBUG
     */
    protected function _debug($request, $headers, $body, $status)
    {
        if (self::DEBUG) {
            echo 'REQUEST DEBUG: ' . PHP_EOL . $this->_url . PHP_EOL . PHP_EOL;

            echo 'REQUEST HEADERS: ' . PHP_EOL . $status['request_header'] . PHP_EOL . PHP_EOL;
            echo 'REQUEST BODY: ' . PHP_EOL . $request . PHP_EOL . PHP_EOL;

            echo 'RESPONSE HEADERS: ' . PHP_EOL . ( empty($headers) ? 'EMPTY' : $headers ) . PHP_EOL . PHP_EOL;
            echo 'RESPONSE HEADERS: ' . PHP_EOL . ( empty($body) ? 'EMPTY' : $body ) . PHP_EOL . PHP_EOL;

            echo '---------------------------------------------------------------' . PHP_EOL . PHP_EOL;
        }
    }
}

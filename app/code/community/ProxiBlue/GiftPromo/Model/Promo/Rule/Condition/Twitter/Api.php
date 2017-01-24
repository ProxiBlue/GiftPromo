<?php

class ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Twitter_Api extends Mage_Rule_Model_Condition_Abstract
{

    private $_oauth_access_token;
    private $_oauth_access_token_secret;
    private $_consumer_key;
    private $_consumer_secret;
    private $_API_Url = 'https://api.twitter.com/1.1/';
    private $_oauth;
    private $_url;
    private $_postfields;
    private $_endpoint;

    public function __construct()
    {
        parent::__construct();
        $this->_oauth_access_token = mage::getStoreConfig('giftpromo/twitter/access_token');
        $this->_oauth_access_token_secret = mage::getStoreConfig('giftpromo/twitter/access_token_secret');
        $this->_consumer_key = mage::getStoreConfig('giftpromo/twitter/consumer_key');
        $this->_consumer_secret = mage::getStoreConfig('giftpromo/twitter/consumer_secret');
    }

    /**
     * Build the Oauth object using params set in construct and additionals
     * passed to this method. For v1.1, see: https://dev.twitter.com/docs/api/1.1
     *
     * @param string $url           The API url to use. Example: https://api.twitter.com/1.1/search/tweets.json
     * @param string $requestMethod Either POST or GET
     *
     * @return \TwitterAPIExchange Instance of self for method chaining
     */
    public function buildOauth($endPoint, $requestMethod)
    {
        $url = $this->_API_Url . $endPoint;

        $this->_endpoint = $endPoint;

        if (!in_array(strtolower($requestMethod), array('post', 'get'))) {
            throw new Exception('Request method must be either POST or GET');
        }

        $consumer_key = $this->_consumer_key;
        $consumer_secret = $this->_consumer_secret;
        $oauth_access_token = $this->_oauth_access_token;
        $oauth_access_token_secret = $this->_oauth_access_token_secret;

        $oauth = array(
            'oauth_consumer_key'     => $consumer_key,
            'oauth_nonce'            => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token'            => $oauth_access_token,
            'oauth_timestamp'        => time(),
            'oauth_version'          => '1.0'
        );

        $getfield = $this->getGetfield();

        if (!is_null($getfield)) {
            $getfields = str_replace('?', '', explode('&', $getfield));
            foreach ($getfields as $g) {
                $split = explode('=', $g);
                $oauth[$split[0]] = $split[1];
            }
        }

        $base_info = $this->buildBaseString($url, $requestMethod, $oauth);
        $composite_key = rawurlencode($consumer_secret) . '&' . rawurlencode($oauth_access_token_secret);
        $oauth_signature = base64_encode(hash_hmac('sha1', $base_info, $composite_key, true));
        $oauth['oauth_signature'] = $oauth_signature;

        $this->_url = $url;
        $this->_oauth = $oauth;

        return $this;
    }

    /**
     * Private method to generate the base string used by cURL
     *
     * @param string $baseURI
     * @param string $method
     * @param array  $params
     *
     * @return string Built base string
     */
    private function buildBaseString($baseURI, $method, $params)
    {
        $return = array();
        ksort($params);

        foreach ($params as $key => $value) {
            $return[] = "$key=" . $value;
        }

        return $method . "&" . rawurlencode($baseURI) . '&' . rawurlencode(implode('&', $return));
    }

    /**
     * Perform the actual data retrieval from the API
     *
     * @param boolean $return If true, returns data.
     *
     * @return string json If $return param is true, returns json data.
     */
    public function performRequest($return = true, $additional_cache_key = '')
    {
        if ($result = $this->fetchCachedEndpointResult($additional_cache_key) == false) {
            if (!is_bool($return)) {
                throw new Exception('performRequest parameter must be true or false');
            }

            $header = array($this->buildAuthorizationHeader($this->_oauth), 'Expect:');

            $getfield = $this->getGetfield();
            $postfields = $this->getPostfields();

            $options = array(
                CURLOPT_HTTPHEADER     => $header,
                CURLOPT_HEADER         => false,
                CURLOPT_URL            => $this->_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
            );

            if (!is_null($postfields)) {
                $options[CURLOPT_POSTFIELDS] = $postfields;
            } else {
                if ($getfield !== '') {
                    $options[CURLOPT_URL] .= $getfield;
                }
            }

            mage::log('requesting data');

            $feed = curl_init();
            curl_setopt_array($feed, $options);
            $json = curl_exec($feed);
            curl_close($feed);

            $this->cacheEndpointResult($json, $additional_cache_key);
        }

        if ($return) {
            return $json;
        }
    }

    private function fetchCachedEndpointResult($additional_cache_key)
    {
        $cache = Mage::getSingleton('checkout/session')->getTwitterCache();
        if (is_array($cache)) {
            try {
                $time = time();
                if (mage::getStoreConfig('giftpromo/twitter/use_cache')
                    && array_key_exists(
                        $this->_endpoint . $additional_cache_key, $cache
                    )
                    && $time - $cache[$this->_endpoint . $additional_cache_key]['timestamp'] < mage::getStoreConfig(
                        'giftpromo/twitter/cache_period'
                    )
                ) {
                    return $cache[$this->_endpoint . $additional_cache_key]['result'];
                }
            } catch (Exception $e) {
                mage::logException($e);
            }
        }

        return false;
    }

    /**
     * Private method to generate authorization header used by cURL
     *
     * @param array $oauth Array of oauth data generated by buildOauth()
     *
     * @return string $return Header used by cURL for request
     */
    private function buildAuthorizationHeader($oauth)
    {
        $return = 'Authorization: OAuth ';
        $values = array();

        foreach ($oauth as $key => $value) {
            $values[] = "$key=\"" . rawurlencode($value) . "\"";
        }

        $return .= implode(', ', $values);

        return $return;
    }

    /**
     * Get postfields array (simple getter)
     *
     * @return array $this->postfields
     */
    public function getPostfields()
    {
        return $this->_postfields;
    }

    /**
     * Set postfields array, example: array('screen_name' => 'J7mbo')
     *
     * @param array $array Array of parameters to send to API
     *
     * @return TwitterAPIExchange Instance of self for method chaining
     */
    public function setPostfields(array $array)
    {
        if (!is_null($this->getGetfield())) {
            throw new Exception('You can only choose get OR post fields.');
        }

        if (isset($array['status']) && substr($array['status'], 0, 1) === '@') {
            $array['status'] = sprintf("\0%s", $array['status']);
        }

        $this->_postfields = $array;

        return $this;
    }

    /**
     * Get getfield string (simple getter)
     *
     * @return string $this->getfields
     */
    public function getGetfield()
    {
        return $this->_getfield;
    }

    private function cacheEndpointResult($result, $additional_cache_key)
    {
        $cache = Mage::getSingleton('checkout/session')->getTwitterCache();
        if (!is_array($cache)) {
            $cache = array();
        }
        $cache[$this->_endpoint] = array('result' => $result, 'timestamp' => time());
        Mage::getSingleton('checkout/session')->setTwitterCache($cache);
    }

    protected function getUserInfo($user)
    {
        $url = 'users/show.json';
        $getfield = '?screen_name=' . $user;
        $requestMethod = 'GET';
        $result = $this->setGetfield($getfield)
            ->buildOauth($url, $requestMethod)
            ->performRequest(true, 'aa');

        return json_decode($result);
    }

    /**
     * Set getfield string, example: '?screen_name=J7mbo'
     *
     * @param string $string Get key and value pairs as string
     *
     * @return \TwitterAPIExchange Instance of self for method chaining
     */
    public function setGetfield($string)
    {
        if (!is_null($this->getPostfields())) {
            throw new Exception('You can only choose get OR post fields.');
        }

        $search = array('#', ',', '+', ':');
        $replace = array('%23', '%2C', '%2B', '%3A');
        $string = str_replace($search, $replace, $string);

        $this->_getfield = $string;

        return $this;
    }

    protected function getFollowers($userInfo)
    {
        $followersCount = $userInfo->followers_count;
        $slurpedInFollowers = array();
        $url = 'followers/ids.json';
        $cursor = -1;
        while (count($slurpedInFollowers) < $followersCount) {
            $getfield = '?screen_name='
                . ProxiBlue_GiftPromo_Model_Promo_Rule_Condition_Twitter_Conditions::getTwitterHandle()
                . '&cursor=' . $cursor;
            $requestMethod = 'GET';
            $result = $this->setGetfield($getfield)
                ->buildOauth($url, $requestMethod)
                ->performRequest(true, $cursor);
            $result = json_decode($result);
            $slurpedInFollowers = array_merge($slurpedInFollowers, $result->ids);
            //mage::log($followersCount . '->' .count($slurpedInFollowers));
            $cursor = $result->next_cursor;
        }

        return $slurpedInFollowers;
    }

    private function getCurentFollowersCount()
    {

    }

}

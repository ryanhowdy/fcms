<?php
/**
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  This is class written to work with Instagram OAuth methods. It 
 *  authorizes a user and will let you make requests to Instagram API.
 *
 *  Last Edit: May 28th, 2011
 *  @author Mike Helmick (mikeh@ydekproductions.com)
 *  @version 1.0
 *
**/

class Instagram {
    private $apiBase = 'https://api.instagram.com/';
    private $apiUrl = 'https://api.instagram.com/v1/';
    
    protected $client_id;
    protected $client_secret;
    protected $access_token;
    
    public function accessTokenUrl()  { return $this->apiBase.'oauth/access_token/'; }
    public function authorizeUrl($redirect_uri, $scope = array('basic'), $response_type = 'code'){
        return $this->apiBase.'oauth/authorize/?client_id='.$this->client_id.'&redirect_uri='.$redirect_uri.'&response_type='.$response_type.'&scope='.implode('+', $scope);
    }
    
    public function __construct($client_id='', $client_secret='', $access_token = '')
    {
        if(empty($client_id) || empty($client_secret)){
            throw new Exception('You need to configure your Client ID and/or Client Secret keys.');
        }
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->access_token = $access_token;
    }
    
    private function urlEncodeParams($params)
    {
        $postdata = '';
        if(!empty($params)){
            foreach($params as $key => $value)
            {
                $postdata .= '&'.$key.'='.urlencode($value);
            }
        }
        
        return $postdata;
    }
    
    public function http($url, $params, $method)
    {
        $c = curl_init();
        
        // If they are authenticated and there is a access token passed, send it along with the request
        // If the access token is invalid, an error will be raised upon the request
        if($this->access_token){
            $url = $url.'?access_token='.$this->access_token;
        }
        
        // If the request is a GET and we need to pass along more params, "URL Encode" them.
        if($method == 'GET'){
            $url = $url.$this->urlEncodeParams($params);
        }
        
        curl_setopt($c, CURLOPT_URL, $url);
        
        if($method == 'POST'){
            curl_setopt($c, CURLOPT_POST, True);
            curl_setopt($c, CURLOPT_POSTFIELDS, $params);
        }
        
        if($method == 'DELETE'){
            curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        
        curl_setopt($c, CURLOPT_RETURNTRANSFER, True);
        
        $r = json_decode(curl_exec($c));
        
        // Throw an error if maybe an access token expired or wasn't right
        // or if an ID doesn't exist or something
        if(isset($r->meta->error_type)){
            throw new InstagramApiError('Error: '.$r->meta->error_message);
        }
        return $r;
    }
    
    // Giving you some easy functions (get, post, delete)
    public function get($endpoint, $params=array(), $method='GET'){
        return $this->http($this->apiUrl.$endpoint, $params, $method);
    }
    
    public function post($endpoint, $params=array(), $method='POST'){
        return $this->http($this->apiUrl.$endpoint, $params, $method);
    }
    
    public function delete($endpoint, $params=array(), $method='DELETE'){
        return $this->http($this->apiUrl.$endpoint, $params, $method);
    }
    
    public function getAccessToken($code, $redirect_uri, $grant_type = 'authorization_code'){
        $rsp = $this->http($this->accessTokenUrl(), array('client_id' => $this->client_id, 'client_secret' => $this->client_secret, 'grant_type' => $grant_type, 'redirect_uri' => $redirect_uri, 'code' => $code), 'POST');
        
        return $rsp;
    }
}

class InstagramApiError extends Exception {}
?>

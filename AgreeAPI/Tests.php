<?php
	class Test {
		//private $apiUrl = "danielalfred.com/agree/api/";
		private $apiUrl = "localhost:8888/";

		public function __construct($testName = "Untitled",$args = array("none")){
			echo "<h1>Test: <em>".$testName."</em></h1>";
			echo "<p>Conducted ".date("M d, y \a\\t H:i:s",time())."</p>";
			if(!is_array($args)){
				die("Your arguments must be sent as a single array.");
			}
			echo "<p>Beginning test.</p>";
			echo "<h2>Parameters:</h2>";
			$args["token"] = $this->getToken();
			echo "<table border=1><thead><tr><th>Key</th><th>Value</th></tr></thead><tbody>";
			foreach($args as $key=>$val){
				echo "<tr><td>".$key."</td><td>".$val."</td></tr>";
			}
			echo "</tbody></table>";
			echo "<h2>Response</h2>";
			$response = $this->makeRequest($args);
			var_dump($response);
		}
		private function getToken(){
			$response = $this->makeRequest(array("action"=>"login","email"=>"test0001","password"=>"xWfDsmjWoYDYMZplH"));
			$responseJson = json_decode($response,true);
			$token = $responseJson["User"]["Token"];
			return $token;
		}
		
		private function getArgsAsQueryString($args){
			$queryString = "?";
			foreach($args as $key=>$val){
				$queryString .= $key."=".urlencode($val)."&";
			}
			return rtrim($queryString,"&");
		}

		public function makeRequest($args){
			// Sets our destination URL
			$endpoint_url = $this->apiUrl.$this->getArgsAsQueryString($args);
			echo "<p>Sending to ".$endpoint_url."</p>";
			// Sets our options array so we can assign them all at once
			$options = [
				CURLOPT_URL        => $endpoint_url,
				CURLOPT_POST       => true,
				CURLOPT_USERAGENT  => "API Tests",
				CURLOPT_RETURNTRANSFER => true
			];

			// Initiates the cURL object
			$curl = curl_init();

			// Assigns our options
			curl_setopt_array($curl, $options);

			// Executes the cURL POST
			$results = curl_exec($curl);

			// Be kind, tidy up!
			curl_close($curl);
			return $results;
		}
	}
	$test = new Test("Get Questions",array("action"=>"getQuestions"));
	$test = new Test("Create Question",array("action"=>"createQuestion","title"=>"Do you like my test question?"));
?>
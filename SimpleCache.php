<?php 
	/*
		Date: 2017-11-24
		Author: skkong@nate.com
		Version: 1.0

		목적: 
			프로그램 내에서 캐시 기능을 구현할 때 연관배열만 사용해도 충분하다.
			하지만, 대용량 데이터를 처리하는 정산프로그램과 같은 경우는 연관배열을 이용할 경우
			프로그램이 사용하는 메모리량이 많이 커질 수 있다.
			이와 같은 경우, cache 처럼 사용할 수 있게 간단한 캐시 라이브러리를 구현한다.
			
			프로그램이 길게 실행되고, 연관배열 사용 시 메모리를 많이 사용할 경우에
			아래 라이브러리를 사용하면 된다.
			
		모든 캐시 클래스는 최소한의 메소드만 구현한다. (setValue(), getValue())

		refer:
			https://paulund.co.uk/sort-multi-dimensional-array-value <- uasort() 사용법
	*/

	error_reporting(E_ALL);
	ini_set("display_errors", 1);

	
	// 아래 함수가 연관배열에서는 잘 동작하지 않는다.
	function cmpByDate($a, $b)
	{	
		return $a['ttl'] - $b['ttl'];	// 순차정렬
		//return $b['ttl'] - $a['ttl'];	// TODO: 역순정렬
	}


	class ArrayCache
	{
		private $hash_data = array();			// hash 저장소
		private $max_size = 5;				// hash 최대 사이즈 50000
		private $delete_count = 0;				// 삭제건수
		private $errormsg = "";				// 에러 메시지

		public function __construct()
		{
			try
			{

			}
			catch (Exception $err)
			{
				die("ArrayCache ERROR: " . $err->getMessage());
			}	
		}

		// 키, 값 저장, expired date
		public function setValue($key, $value, $ttl = 86400)
		{
			$date = date("Y-m-d H:i:s",strtotime("+$ttl second"));
	
			try
			{
				$this->hash_data[$key]['value'] = $value;
				$this->hash_data[$key]['ttl'] = $date;		

				$this->cleanCache();
			} 
			catch (Exception $err) 
			{
				//_log("[ERROR] ".$err->getMessage(), __FILE__, __FUNCTION__, __LINE__);
			}
			
			return true;
		}
		
		// 값 조회
		public function getValue($key)
		{
			$result = "";
			
			if(empty($this->hash_data[$key]))
				return false;

			return $this->hash_data[$key]['value'];
		}


		// 특정 일자의 캐시를 지운다.
		// 가장 오래된 캐시를 지운다.
		private function cleanCache()
		{
			// 삭제 1단계: 캐시 사이즈를 초과하면 가장 오래된 캐시 항목을 지운다.
			$total_count = count($this->hash_data);
			$diff_count = $total_count - $this->max_size;

			if($diff_count > 0)
			{
				$this->errmsg = "max size 초과: $diff_count \n"; 
				echo $this->errormsg;
				error_log($this->errmsg, 3, "php://stderr");


				// 배열 값에 의한 정렬로 키를 재배열하지 않음, 역순정렬은 arsort()
				// XXX: usort: 연관배열의 키를 없앤다. uasort를 사용해야 한다.
				uasort($this->hash_data, "cmpByDate");	

				// XXX: array_pop()은 맨 뒤에 요소를 삭제하고 array_shift()는 맨 앞의 요소를 삭제한다.
				$i = 0;
				while($i++ < $diff_count)
				{
					array_shift($this->hash_data);
					$this->delete_count++;
				}
			}


			// 삭제 2단계: 특정 일자가 지난 캐시 항목을 지운다.
			$now = date("Y-m-d H:i:s");	// 현재시간
			$arr_delete_key = array();

			// 삭제될 키를 찾는다.
			foreach($this->hash_data as $key => $value)
			{
				$ttl = $value['ttl'];
	
				if($ttl < $now)
				{
					$arr_delete_key[] = $key;	
				}
			}

			// 위에서 조회한 키값으로 삭제한다.
			foreach($arr_delete_key as $key)
			{
				unset($this->hash_data[$key]);
				$this->delete_count++;
			}

		}

		// 큭정 해시 항목을 삭제한다.
		public function deleteCache($key)
		{
			unset($this->hash_data[$key]);
		}

		// 모든 캐시를 비운다.
		public function flush()
		{
			unset($this->hash_data);
		}

		// 캐시 내용을 출력한다.
		public function printCache()
		{
			print_r($this->hash_data);
		}

		// 캐시 통계를 보여준다.
		public function printStatics()
		{
			$total_count = count($this->hash_data);		// 전체 카운트

			echo "전체 항목 수: $total_count \n";
			echo "삭제 항목 수: $this->delete_count \n";	
		}
	}
	
	// mongo, redis 등에 대한 객체를 생성하고 반환한다.
	class CacheFactory
	{
		public static function create($model)
		{
			if($model == "mongo")
				return false;
			else if($model == "redis")
				return false;	
			else if($model == "array")
				return new ArrayCache();
			else 
				return false;	
		}
	}


	// 사용 예제
	$cache = CacheFactory::create("array");
	// echo "문자열 캐시 test \n";
	$cache->setValue("name1", "skkong");
	$result = $cache->getValue("name1");

	if($result == 'skkong')
		echo "TEST 01 OK. \n";
	else
		echo "TEST 01 Fail. \n";


	// echo "배열 캐시 test \n";
	$cache->setValue("name2", array("skkong", "hongkildong"));
	$result = $cache->getValue("name2");

	if($result[0] == 'skkong')
		echo "TEST 02 OK. \n";
	else
		echo "TEST 02 Fail. \n";


	//echo "연관배열 캐시 test \n";
	$cache->setValue("name2", array("skkong" => 46, "gugim" => 36), 60);
	$result = $cache->getValue("name2");

	if($result['skkong'] == 46)
		echo "TEST 03 OK. \n";
	else
		echo "TEST 03 Fail. \n";

	for($i = 0; $i < 14; $i++)
	{
		$cache->setValue("test-".$i, $i+1);
		sleep(1);
	}

	$cache->printCache();
	$cache->printStatics();
?>


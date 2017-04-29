<?php namespace App\Providers\Api;

use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Exceptions\ApiException;

class ApiProvider {

	public function __construct() {
	}

	public function __call($method, $parameters)
	{
		$host = Config::get('api.idevops_host');
		$apis = Config::get('api.items');
		$data = $parameters[0];

		foreach ($apis as $name=>$api) {

			if ($name == $method) {

				$url = trim($host, '/') . '/' . $api['path'];

				if (isset($api['type']) && $api['type'] == 'REST' && isset($data['id'])) {
					$url .= '/' . $data['id'];
					unset($data['id']);
				}

				return do_request($url, $api['method'], $data);
				 
			} 
		}
		throw new ApiException("Api $method not found");
	}
}

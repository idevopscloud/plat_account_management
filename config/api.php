<?php
return [
		'idevops_host' => 'http://192.168.99.101:8080',
		'items' => [
				'usersSync' => [
						'path'=>'v1/user/sync',
						'method'=>'POST',
				],
				'getDeployEnv' => [
						'path' => 'third/app/app/envs',
						'method' => 'GET',
						'type' => 'REST'
				],
				'createDeployEnv' => [
						'path' => 'third/app/app/envs',
						'method' => 'POST'
				],
				'getUserInfo' => [
					'path'=>'v1/user/mime',
					'method'=>'GET'
				],
				'deleteDeployEnv' => [
						'path' => 'third/app/app/envs',
						'method' => 'DELETE',
						'type' => 'REST'
				],
				'getApp' => [
						'path'=>'third/app/apps',
						'method'=>'GET',
						'type' => 'REST'
				],
				'deleteApp' => [
						'path'=>'third/app/apps',
						'method'=>'DELETE',
						'type' => 'REST'
				],
				'getRegistry' => [
						'path'=>'third/registry/registries',
						'method'=>'GET',
						'type' => 'REST'
				],
				'getOLImage' => [
						'path'=>'third/registry/registries',
						'method'=>'GET',
						'type' => 'REST'
				],
				'deleteOLImage' => [
						'path'=>'third/registry/registries',
						'method'=>'DELETE',
						'type' => 'REST'
				],
		]
	];

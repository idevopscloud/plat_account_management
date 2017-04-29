<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Response;
use App\Company;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Facades\Api;
use App\Providers\Api\ApiProvider;

class CompanyController extends Controller
{
    public function index(Request $request) {
    	$user = Api::getUserInfo(['token'=>$request->core_token]);
    	$builder = Company::with('users')->select ( 'companies.*',
    			 'users.nickname as contact_user_nickname',
    			 'users.position as contact_user_position',
    			 'users.name as contact_user_name',
    			 'users.telephone as contact_user_telephone'
    	);
    	if ($request->q) {
    		$builder->where('companies.name', 'like', "%{$request->q}%");
    	}

    	if ($user['id'] != 1) { // not admin
    		$builder->where('companies.id', $user['company_id']);
    	}

    	$companies = $builder->leftJoin('users', 'users.id', '=', 'companies.contact_user_id')->orderBy('id', 'desc')->paginate();
    	foreach ($companies as &$company) {
    		$envs = with(new ApiProvider())->getDeployEnv(['token'=>$request->core_token, 'company_id'=>$company->id, 'company_name'=>$company->name]);
    		$company['envs'] = $envs['envs'];
    	}
    	return Response::Json($companies);
    }
    
    public function show($id) {
    	$company = Company::with('users')->findOrFail($id);
    	return Response::Json($company);
    }
    
    /**
     * 创建企业 <br>
     * 企业信息，负责人信息，[私有集群信息]
     * 
     * @param Request $request
     * @throws \Exception
     */
    public function store(Request $request) {
    	$company = null;
    	$validator = Validator::make($request->all(), [
    			'name' => 'required|unique:companies,name',
    			'contact_user_nickname' => 'required',
    			// 'contact_user_telephone' => 'required|regex:/^1[34578]\d{9}$/',
    			// 'contact_user_position' => 'required',
    			'contact_user_password' => 'required|min:6',
				'contact_user_name' => 'required|unique:users,name',
				'mem_limit' => 'required' 
		] );
		
		if ($validator->fails ()) {
			throw new \Exception ( $validator->errors () );
		}
		DB::beginTransaction ();
		try {
			$companyData = [ 
					'id' => gen_uuid (),
					'name' => $request->name,
					'mem_limit' => $request->mem_limit 
			];
			$company = new Company ( $companyData );
			$company->save ();
			
			$userData = [ 
					'nickname' => $request->contact_user_nickname,
    				'position' => $request->input('contact_user_position', ""),
    				'telephone' => $request->input('contact_user_telephone', ""),
    				'password' => Hash::make($request->contact_user_password),
    				'name' => $request->contact_user_name,
    				'company_id' => $company->id
    		];
    		$user = User::firstOrNew($userData);
    		$user->save();
    		
    		$company->contact_user_id = $user->id;
    		$company->save();
    		
    		// 创建私有集群
    		if (isset($request->is_private_res)) {
    			with(new ApiProvider())->createDeployEnv(['token'=>$request->core_token, 'company_id'=>$company->id, 'company_name'=>$company->name]);
    		}
    		
    	} catch (\Exception $e) {
    		DB::rollBack();
    		throw $e;
		}
		DB::commit ();
		
		// sync user to platform
		$token = JWTAuth::getToken();
		Api::usersSync(['account_token' => (string)$token, 'token'=>$request->core_token, 'users'=>[$user->id]]);
		
		return Response::Json ( $company );
	}
	
	
	public function update($id, Request $request) {
		if ($request->action && $request->action = 'add') {
			$company = Company::findOrFail($id);
			$availiable = ($company->mem_limit - $company->mem_usage)*128;
			$company->mem_usage += (int)$request->mem;
			if ($company->mem_usage > $company->mem_limit) {
				
				throw new \Exception("超出限额，目前剩余:{$availiable}MB");
			}
			$company->save();
			return Response::Json($company);
		} else if ($request->action && $request->action = 'sync') {
			$company = Company::findOrFail($id);
			$availiable = ($company->mem_limit - $company->mem_usage)*128;
			$company->mem_usage = (int)$request->mem;
			if ($company->mem_usage > $company->mem_limit) {
				throw new \Exception("超出限额，目前剩余:{$availiable}MB");
			}
			$company->save();
			return Response::Json($company);
		}
		$validator = Validator::make ( $request->all (), [ 
				'name' => "required|unique:companies,name,$id,id",
    	]);
    	 
    	if ($validator->fails()) {
    		throw new \Exception($validator->errors());
    	}
    	$company = Company::findOrFail($id);
    	DB::beginTransaction();
    	try {
    		$validator = Validator::make ( $request->all (), [
    				'contact_user_nickname' => "required",
    				// 'contact_user_telephone' => 'required|regex:/^1[34578]\d{9}$/',
    				// 'contact_user_position' => 'required',
    				'contact_user_password' => 'min:6',
    				'contact_user_name' => 'required',
    				'mem_limit' => 'required',
    		]);
    		
    		if ($validator->fails()) {
    			throw new \Exception($validator->errors());
    		}
    		
    		$userData = [
    				'nickname' => $request->contact_user_nickname,
    				'position' => $request->input('contact_user_position', ""),
    				'telephone' => $request->input('contact_user_telephone',""),
    				'name' => $request->contact_user_name,
    		];
    		if ( !empty($request->contact_user_password) ) {
    			$userData['password'] = Hash::make($request->contact_user_password);
    		} 
    		$user = User::where('name', $request->contact_user_name)->find($company->contact_user_id);
    		if (!$user) {
    			$user = new User($userData);
    			$user->company_id = $id;
    			$user->save();
    			$company->contact_user_id = $user->id;
    			
    		} else {
    			$user->fill($userData);
    			$user->save();
    		}
    		$company->mem_limit = $request->mem_limit;
    		$company->name = $request->name;
    		$company->save();
    		
    		// manage cluster
    		$envs = with(new ApiProvider())->getDeployEnv(['token'=>$request->core_token, 'company_id'=>$company->id, 'company_name'=>$company->name]);
    		if (isset($request->is_private_res)) {
    			if (count($envs['envs']) == 0) {
    				with(new ApiProvider())->createDeployEnv(['token'=>$request->core_token, 'company_id'=>$company->id, 'company_name'=>$company->name]);
    			}
    		} else {
    			if (count($envs['envs']) > 0) {
	    			foreach ($envs['envs'] as $env) {
		    			with(new ApiProvider())->deleteDeployEnv(['token'=>$request->core_token, 'id'=>$env['id']]);
		    		}
    			}
    		}
    		
    	} catch (\Exception $e) {
    		DB::rollBack();
    		throw $e;
    	}
    	DB::commit();
    	$token = JWTAuth::getToken();
    	Api::usersSync(['account_token' => (string)$token, 'token'=>$request->core_token, 'users'=>[$user->id]]);
    	
    	return Response::Json($company);
    }
    
    public function destroy($id, Request $request) {
    	DB::beginTransaction();
    	try {
	    	$company = Company::findOrFail($id);
	    	$users = $company->users()->get()->lists('id', 'id')->toArray();
	    	$company->delete();// Note:先删除再同步
	    	
	    	// delete apps
	    	$data = Api::getApp(['token' => $request->core_token, 'action'=>'team', 'company_id'=>$company->id]);
	    	if (isset($data['apps'])) {
	    		foreach ($data['apps'] as $app) {
	    			Api::deleteApp(['token' => $request->core_token, 'id'=>$app['id']]);
	    		}
	    	}
	    	
	    	// delete private cluster
	    	$envs = with(new ApiProvider())->getDeployEnv(['token'=>$request->core_token, 'company_id'=>$company->id, 'company_name'=>$company->name]);
	    	if (count($envs['envs']) > 0) {
	    		foreach ($envs['envs'] as $env) {
	    			with(new ApiProvider())->deleteDeployEnv(['token'=>$request->core_token, 'id'=>$env['id']]);
	    		}
	    	}
	    	
	    	//delete OL images
	    	$registries = Api::getRegistry(['token' => $request->core_token, 'name'=>'platform']);
	    	if (isset ( $registries [0] )) {
				$registry = $registries [0];
				$registryData = Api::getOLImage ( [ 
						'token' => $request->core_token,
						'id' => $registry ['id'],
						'action' => 'images',
						'type' => 'app_base',
						'app_name' => $company->id 
				] );
				if (isset ( $registryData ['images'] )) {
					foreach ( $registryData ['images'] as $image ) {
						foreach ($image['tags'] as $tag) {
							Api::deleteOLImage ( [ 
									'token' => $request->core_token,
									'id' => $registry ['id'],
									'type' => 'app_base',
									'app_name' => $company->id,
									'name' => $image ['short_name'],
									'version' => $tag['name'] 
							] );
						}
					}
				}
			}
		} catch (\Exception $e) {
			DB::rollBack();
			throw $e;
		}
		DB::commit();
    	$token = JWTAuth::getToken();
    	Api::usersSync(['account_token' => (string)$token, 'token'=>$request->core_token, 'users'=>array_values($users)]);
	    	
    	return Response::Json([]);
    }
}

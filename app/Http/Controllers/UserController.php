<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\User;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\App;
use App\Facades\Api;
use App\Exceptions\ValidateException;

class UserController extends Controller
{
    public function index(Request $request) {
    	$user = Api::getUserInfo(['token'=>$request->core_token]);
    	if ($user['id'] != 1) {
    		$users = User::with('company')->where('company_id', $user['company_id'])->paginate();
    	} else {
    		$users = User::with('company')->orderBy('company_id', 'desc')->paginate();
    	}
    	return Response::Json($users);
    }
    
    public function show($id) {
    	$user = User::with('company')->findOrFail($id);
    	return Response::Json($user);
    }
    
    
    public function store(Request $request) {
    	$validator = Validator::make($request->all(), [
    			'name' => 'required|unique:users,name',
    			'telephone' => ['regex:/(^(([0\+]\d{2,3}-)?(0\d{2,3})-)(\d{7,8})(-(\d{3,}))?$)|(^0{0,1}1[3|4|5|6|7|8|9][0-9]{9}$)/'],
    			'password' => 'required|min:6',
    			'nickname' => 'required',
    			'company_id' => 'required',
    	],[
				'name.unique'=> trans("validation.unique", ['attribute'=>trans('validation.attributes.login_name')]) //登录名唯一
    	]);
    	 
    	if ($validator->fails()) {
    		throw new \Exception($validator->errors());
    	}
    	DB::beginTransaction();
    	try {
    		$userData = [
    				'name' => $request->name,
    				'nickname' => $request->nickname,
    				'password' => Hash::make($request->password),
    				'position' => $request->position,
    				'telephone' => $request->telephone,
    				'company_id' => $request->company_id,
    		];
    		$user = new User($userData);
    		$user->save();
    	} catch (\Exception $e) {
    		DB::rollBack();
    		throw $e;
    	}
    	DB::commit ();
    	$token = JWTAuth::getToken();
    	Api::usersSync(['account_token' => (string)$token, 'token'=>$request->core_token, 'users'=>[$user->id]]);
    	return Response::Json ( $user );
    }
    
    public function update($id, Request $request) {
    	$validator = Validator::make($request->all(), [
    			'name' => "unique:users,name,{$id},id",
    			'telephone' => ['regex:/(^(([0\+]\d{2,3}-)?(0\d{2,3})-)(\d{7,8})(-(\d{3,}))?$)|(^0{0,1}1[3|4|5|6|7|8|9][0-9]{9}$)/'],
    	],[
				'name.unique'=> trans("validation.unique", ['attribute'=>trans('validation.attributes.login_name')]) //登录名唯一
    	]);
    
    	if ($validator->fails()) {
    		throw new \Exception($validator->errors());
    	}
    	DB::beginTransaction();
    	try {
    		$userData = $request->all();
    		if ( !empty($request->password) ) {
    			if (strlen($request->password) < 6) {
    				throw new ValidateException(trans("validation.min.string", ['attribute'=>trans('validation.attributes.password'), 'min'=>6]));
    			}
    			$userData['password'] = Hash::make($request->password);
    		} else {
    			unset($userData['password']);
    		}
    		$user = User::findOrFail($id);
    		$user->fill($userData);
    		$user->save();
    	} catch (ValidateException $e) {
    		throw $e;
    	} catch (\Exception $e) {
    		logger($e);
    		DB::rollBack();
    	}
    	DB::commit ();
    	
    	$token = JWTAuth::getToken();
    	Api::usersSync(['account_token' => (string)$token, 'token'=>$request->core_token, 'users'=>[$user->id]]);
    	return Response::Json ( $user );
    }
    
    public function destroy($id, Request $request) {
    	$userInCore = Api::getUserInfo(['token'=>$request->core_token]);
    	$user = User::findOrFail($id);
    	if ($userInCore['id'] == $id) {
    		throw new \Exception(trans('exception.destroy_self_not_allow'));
    	}
    	$user->delete();
    	$token = JWTAuth::getToken();
    	Api::usersSync(['account_token' => (string)$token, 'token'=>$request->core_token, 'users'=>[$user->id]]);
    	return Response::Json ( $user );
    }
}

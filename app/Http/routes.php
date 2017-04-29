<?php
use App\User;
use Illuminate\Support\Facades\Input;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
Route::group(['middleware' => 'jwt.auth'], function () {
	Route::resource('companies', 'CompanyController');
	Route::resource('users', 'UserController');
});
	
/* Route::post ( '/signup', function () {
	$credentials = Input::only ( 'name', 'password' );
	
	try {
		$credentials['password'] = Hash::make($credentials['password']);
		$credentials['company_id'] = 1;
		$user = User::create ( $credentials );
	} catch ( Exception $e ) {
		return Response::json ( [ 
				'error' => 'User already exists.' 
		], \Symfony\Component\HttpFoundation\Response::HTTP_CONFLICT );
	}
	
	$token = JWTAuth::fromUser ( $user );
	
	return Response::json ( compact ( 'token' ) );
} ); */


Route::post('/signin', ['middleware'=>'response.filter', function () {
	$credentials = Input::only('name', 'password');
	
	if ( ! $token = JWTAuth::attempt($credentials)) {
		return Response::json(['msg'=> 'username or password error'], \Symfony\Component\HttpFoundation\Response::HTTP_UNAUTHORIZED);
	}
	
	$user = JWTAuth::toUser($token);
	return Response::json(compact('token') + $user->toArray());
}]);
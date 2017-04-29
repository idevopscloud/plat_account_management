<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	if (User::where('name', 'admin')->exists() == false) {
	    	$company = DB::table('companies')->first();
	    	DB::table('users')->insert([
		    	'name' => 'admin',
		    	'password' => Hash::make('platform!@#.com'),
		    	'nickname' => '平台管理员',
		    	'telephone' => '',
		    	'position' => '管理',
		    	'company_id' => $company->id,
		    	'created_at' => date('Y-m-d H:i:s'),
		    	'updated_at' => date('Y-m-d H:i:s'),
	    	]);
    	}
    }
}

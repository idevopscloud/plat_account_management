<?php

use Illuminate\Database\Seeder;
use App\Company;

class CompaniesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
    	if (Company::where('name', 'platform')->exists() == false) {
	    	DB::table('companies')->insert([
	    		'id' => gen_uuid(),
		    	'name' => 'platform',
		    	'contact_user_id' => 1,
		    	'mem_limit' => 10,
		    	'created_at' => date('Y-m-d H:i:s'),
		    	'updated_at' => date('Y-m-d H:i:s'),
	    	]);
    	}
    }
}

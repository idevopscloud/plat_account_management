<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::table('companies', function ($table) {
	    	$table->integer('mem_limit')->unsigned()->after('contact_user_id');
	    	$table->integer('mem_usage')->unsigned()->after('mem_limit');
    	});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    	Schema::table('companies', function ($table) {
    		$table->dropColumn('mem_limit');
    		$table->dropColumn('mem_usage');
    	});
    }
}

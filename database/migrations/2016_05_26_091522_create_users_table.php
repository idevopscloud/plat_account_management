<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
    	Schema::create('users', function (Blueprint $table) {
    		$table->increments('id');
    		$table->string('name')->unique();
    		$table->string('nickname');
    		$table->string('password');
    		$table->string('telephone');
    		$table->string('position');
    		$table->integer('company_id')->unsigned();
    		$table->rememberToken();
    		$table->timestamps();
    		$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
    	});
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    	Schema::drop('users');
    }
}

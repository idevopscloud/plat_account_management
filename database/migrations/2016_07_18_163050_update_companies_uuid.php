<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateCompaniesUuid extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function ($table) {
			$table->dropForeign('users_company_id_foreign');
		});
		Schema::table('users', function ($table) {
			$table->string('company_id', 36)->change();
		});
		Schema::table('companies', function ($table) {
			$table->string('id', 36)->change();
		});
		Schema::table('users', function ($table) {
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
		Schema::table('users', function ($table) {
			$table->dropForeign('users_company_id_foreign');
		});
		Schema::table('companies', function ($table) {
			$table->increments('id')->change();
		});
		Schema::table('users', function ($table) {
			$table->integer('company_id')->unsigned()->change();
			$table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
		});
	}
}

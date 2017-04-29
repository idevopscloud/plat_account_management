<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
	
	protected $table = 'companies';
	
	public $incrementing = false;
	
	protected $dates = ['deleted_at'];
	
	protected $fillable = [
			'id', 'name', 'contact_user_id', 'mem_limit', 'mem_usage'
	];
	
	public function users()
	{
		return $this->hasMany('App\User');
	}
	
}

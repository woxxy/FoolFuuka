<?php

namespace Model;

class UsersWrongId extends \FuelException {}

class Users extends \Model
{
	/**
	 * Gets single user database row by selected row
	 *
	 * @param  int  $id
	 * @return object
	 */
	public static function get_user_by($field, $id)
	{
		$query = \DB::select()->from(\Config::get('foolauth.table_name'))
			->where($field, $id)
			->as_object()
			->execute(\Config::get('foolauth.db_connection'));

		if ( ! count($query))
		{
			throw new UsersWrongId;
		}

		return $query->current();
	}
}

/* end of file user.php */
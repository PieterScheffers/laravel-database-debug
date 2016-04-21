<?php

namespace pisc\LaravelDBDebug;

require_once __DIR__ . '/helper.php';

use DB;

class DBDebug {

	/**
	 * getSql
	 *
	 * Usage:
	 * getSql( DB::table("users") )
	 * 
	 * Get the current SQL and bindings
	 * 
	 * @param  mixed  $query  Relation / Eloquent Builder / Query Builder
	 * @return array          Array with sql and bindings or else false
	 */
	public static function getSql($query)
	{
		if( $query instanceof Illuminate\Database\Eloquent\Relations\Relation )
		{
			$query = $query->getBaseQuery();
		}

		if( $query instanceof Illuminate\Database\Eloquent\Builder )
		{
			$query = $query->getQuery();
		}

		if( $query instanceof Illuminate\Database\Query\Builder )
		{
			return [ 'query' => $query->toSql(), 'bindings' => $query->getBindings() ];
		}

		return false;
	}

	/**
	 * logQuery
	 *
	 * Get the SQL from a query in a closure
	 *
	 * Usage:
	 * logQueries(function() {
	 *     return User::first()->applications;
	 * });
	 * 
	 * @param  closure $callback              function to call some queries in
	 * @return Illuminate\Support\Collection  Collection of queries
	 */
	public static function logQueries(closure $callback, $construct = false) 
	{
		// check if query logging is enabled
		$logging = DB::logging();

		// Get number of queries
		$numberOfQueries = count(DB::getQueryLog());

		// if logging not enabled, temporarily enable it
		if( !$logging ) DB::enableQueryLog();

		$query = $callback();

		$lastQuery = static::getSql($query);

		// Get querylog
		$queries = new Illuminate\Support\Collection( DB::getQueryLog() );

		// calculate the number of queries done in callback
		$queryCount = $queries->count() - $numberOfQueries;

		// Get last queries
		$lastQueries = $queries->take(-$queryCount);

		// disable query logging
		if( !$logging ) DB::disableQueryLog();

		// if callback returns a builder object, return the sql and bindings of it
		if( $lastQuery )
		{
			$lastQueries->push($lastQuery);
		}

		if( $construct )
		{
			return static::constructQueries($lastQueries);
		}

		return $lastQueries;
	}

	/**
	 * constructQueries
	 *
	 * Pass the result of logQueries to this function to get the query with the question marks replaced by the bindings
	 * 
	 * @param  array  $queries Array of queries (Query = [ 'query' => '', 'bindings' => [] ])
	 * @return string          
	 */
	public static function constructQueries($queries)
	{
		if( $queries instanceof Illuminate\Support\Collection )
		{
			$queries = $queries->all();
		}

		return array_map(function($q) {

			$query = $q['query'];
			$bindings = $q['bindings'];

			foreach( $bindings as $key => $binding ) {

				if( is_object($binding) )
				{
					if( $binding instanceof DateTime )
					{
						$binding = $binding->format("Y-m-d H:i:s");
					}
					else
					{
						$binding = $binding->__toString();
					}			
				}

				if( !is_numeric($binding) )
				{
					$binding = "'{$binding}'";
				}

				$query = str_replace_limit('?', $binding, $query, 1);
			}

			return $query;

		}, $queries);
	}

}





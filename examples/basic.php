<?php
// copy this file to your controller folder
// rename it to feed.php
class Controller_Feed extends Controller
{
	private $feed_builder;

	public function before()
	{
		Package::load('fuelphp-feeds');
		
		// set required data
		// you could have a config file named feeds.php 
		// if you want to have a default config
		$config = array(			
			'title' 		=> 'Chuck Norris Blog',
			'site_url' 		=> 'blog',
			'author_name' 	=> 'Chuck Norris'
		);
		$this->feed_builder = Feeds_Builder::forge($config);


		// Retrieve 10 random Chuck Norris Fact
		// Thanks to http://api.icndb.com :)
		$result = @file_get_contents('http://api.icndb.com/jokes/random/10?limitTo=[nerdy]');
		$result = Format::forge($result, 'json')->to_array();
		if(Arr::get($result, 'type') != 'success')
			return;

		// add items to the feeds
		foreach ($result['value'] as $item) {
			$this->feed_builder->items[] = array(
				'url' 			=> 'http://api.icndb.com/jokes/'.$item['id'],
				'title' 		=> 'Chuck Norris fact #'.$item['id'],
				'updated_at' 	=> mt_rand(735346800, 1116457200), // during "Walker, Texas Ranger" diff ;)
				'content' 		=> $item['joke']
			);
		}
	}

	public function action_atom()
	{
		// get xml string
		$atom = $this->feed_builder->get_atom();
		$header = array('Content-Type' => 'application/xml');

		// serve it
		return Response::forge($atom, 200, $header);
	}

	public function action_rss()
	{
		// get xml string
		$atom = $this->feed_builder->get_rss();
		$header = array('Content-Type' => 'application/xml');

		// serve it
		return Response::forge($atom, 200, $header);
	}
}
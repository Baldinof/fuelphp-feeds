<?php
// A full example for a blog with Model_Post
// Adapt it to fit with your models
class Controller_Feed extends Controller
{
	// just serve the stored file, no database queries
	public function action_atom()
	{
		if( ! file_exists(APPPATH.'feeds/atom.xml'))
			throw new HttpNotFoundException;

		$atom = File::read(APPPATH.'feeds/atom.xml', true);
		$header = array('Content-Type' => 'application/xml');
		
		return Response::forge($atom, 200, $header);
	}

	public function action_rss()
	{
		if( ! file_exists(APPPATH.'feeds/rss.xml'))
			throw new HttpNotFoundException;

		$rss = File::read(APPPATH.'feeds/rss.xml', true);
		$header = array('Content-Type' => 'application/xml');

		return Response::forge($rss, 200, $header);
	}

	// Rebuild feeds on each Model_Post modifications
	public static function build()
	{
		Package::load('fuelphp-feeds');
		
		$feed_builder = Feeds\Builder::forge(array(
			'title' 		=> 'Your blog title',
			'site_url' 		=> 'blog',
			'author_name' 	=> 'John Doe',
		));

		// retrieve data
		$posts = Model_Post::find('all');

		// add to the feed
		foreach ($posts as $post) {
			$item = array(
				'title' 		=> $post->title,
				'url'			=> 'blog/post/'.$post->id,
				'updated_at' 	=> $post->date,
				'content' 		=> $post->content
			);
			$feed_builder->add_item($item);
		}

		// save the feed in file
		if($feed_builder->is_valid())
		{
			$folder = APPPATH.'/feeds/';
			File::update($folder, 'atom.xml', $feed_builder->get_atom());
			File::update($folder, 'rss.xml', $feed_builder->get_rss());
		}
	}
}


Fuelphp-feeds
=============

A FuelPHP package to easily create RSS and Atom feeds.

Installation
------------
1. Go to fuel/packages/
2. Clone (`git clone git://github.com/Baldinof/fuelphp-feeds`) / [download](https://github.com/Baldinof/fuelphp-feeds/zipball/master)
3. Optionally create a fuel/app/config/feeds.php fiel (to set some default values)
5. Optionally add 'fuelphp-feeds' to the 'always_load/packages' array in app/config/config.php 
   (or call `Package::load('fuelphp-feeds')` whenever you want to use it).
6. Enjoy :)

Introduction
------------
The package has one class called `Feeds_Builder`. 

The class helps you to easily generate valid XML strings 
according to RSS and Atom specs.

You just have to pass array containing required fields.

Basic Usage
-----------
First you have to create a Feeds_Builder instance with some fields.
```PHP	
	$config = array(
		'title' 		=> 'Your blog', // required
		'site_url' 		=> 'blog',		// required
		'author_name' 	=> 'John Doe' 	// recommended for Atom
	);

	$builder = Feeds_Builder::forge($config);
```

Direct assignment is also available
```PHP
	$builder->title 	= 'Your blog';
	$builder->site_url 	= 'blog';
```

Then you will add items you want to be available in feeds.
```PHP
	// these 3 fields a required
	$builder->add_item(array(
		'title' 		=> 'A post',
		'url' 			=> 'blog/permalink_to_your_post',
		'updated_at' 	=> array('21/03/1987', '%d/%m/%Y')
	));

	// or direct push on the items array
	$builder->items[] = array(/* ... */); 
```

So data are ok, you can build the xml now!
```PHP
	$rss_xml = $builder->get_rss();
	$atom_xml = $builder->get_atom();
```

All available fields for feeds
------------------------------
```PHP
	$config	= array(
		/* Required */
		'title' 		=> 'the title of this feeds',		
		'site_url' 		=> 'your/url/',

		/* Optional */		
		'updated_at' 	=> 'now',	// A valid date (see below) or 'now'. Default = 'now'

		'author_name' 	=> 'the author', 	// recommended for Atom
		'author_email' 	=> 'you@domain.com',
		'author_url' 	=> 'your_url',	

		'self_atom'		=> 'url/to/atom',	// recommended for Atom
		'self_rss'		=> 'url/to/rss', 	

		'description'	=> 'a description',	// Required in RSS (if null, the title will be used)
		
		'copyright'		=> '© 2013',
		'categories'	=> array('Developpement', 'Fuel-PHP'),

		'items' => array(),

		'use_CDATA' => false, // if true items content will be in CDATA element. Default: false
	);
```

There are different ways to set these properties:

- Pass it as array to the constructor or via the static method `Feeds_Builder::forge()`
- Direct assignment on a Feeds_Builder instance via properties
- Have a config file named `feeds.php` (or a 'feeds' key in fuel/app/config/config.php)


All available fields on items
-----------------------------
```PHP
	$item = array(
		// Required
		'url' 			=> 'the_item_permalink_on_the_site',
		'title' 		=> 'the_item_title',
		'updated_at' 	=> '', // A valid date, see below

		// optional
		'author_name' 	=> 'author',
		'author_email'	=> 'email',
		'author_url'	=> 'email',
		'content' 		=> 'the_item_content',	// could be html !
		'link_alt' 		=> 'alternate_permalink_to_item',
		'categories' 	=> array('foo', 'bar'),
		'summary'		=> 'the_item_summary'
	);
```

About date
----------
All 'updated_at' fields accepts 3 form of date:

- A timestamp
- A Fuel\Core\Date instance - [see documentation](http://fuelphp.com/docs/classes/date.html)
- An array :  `array('date_as_string', 'date_format')`
  The builder will use `Date::create_from_string()` method so 
  you could refer to the [Fuel documentation](http://fuelphp.com/docs/classes/date.html#/method_create_from_string).

An item MUST have a date.

You can omit `updated_at` on the root config, the current date will be used.

About Url
---------
All url could be relative or absolute.


About CDATA
-----------
The field `content` of an item could contain HTML. 
By default HTML code is encoded. Then feeds reader 
will decode it to present the item to the user.

There is no influence on using CDATA or not for the feeds reader. 
The html will still be interpreted.

Use CDATA could just make the XML file more human readable.


Exception
---------
When you try to retrieve the xml string, the builder verify 
the presence of required fields.

If a required field is missing or a date is not valid the 
builder will throw a `InvalidDataException` and will tell 
you which field are wrong.

If you want to be sure all data are valid you can call `is_valid()`.
```PHP
	$builder = Feeds_Builder::forge();

	if($builder->is_valid()) 
		$xml = $builder->get_atom();
```


Caching into files
------------------
Usually feeds are builded with data stored in database.

If you setup a controller that retrieves last posts in 
database, then serve the XML string, you will have 
performance issue.

Feeds reader will call this controller very frequently.

I recommend to store the XML in a file, and rebuild it 
only when a post are saved, modified or deleted.

Have a look to the [example](https://github.com/Baldinof/fuelphp-feeds/tree/master/examples) folder.

See also
--------

- [Atom specifications](http://www.atomenabled.org/developers/syndication/atom-format-spec.php)
- [RSS2 specifications](http://cyber.law.harvard.edu/rss/rss.html)
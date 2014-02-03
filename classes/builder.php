<?php
/**
 * @package    Feeds
 * @version    1.0
 * @author     BaldinoF
 * @license    MIT License
 */

namespace Feeds;

class InvalidDataException extends \FuelException {  }

/**
* Build rss and atom xml by passing data
*/
class Feeds_Builder
{   
    protected static $config = array(
        /*
            REQUIRED
        */      
        'title'         => null,    // the title of this feeds      
        'site_url'      => null,    // url where the content of this feeds could be finded      
        'updated_at'    => 'now',   // A timestamp, a Fuel\Core\Date object, array like array('date_str', 'pattern') accepted by Date::create_from_string(). Default 'now'. 
        

        /*
            Recommended for Atom
        */
        'self_atom'     => null,    // the action where the atom feeds is available
        'author_name'   => null,

        /*
            Optional
        */
        'description'   => null,    // optionnal in Atom but required in RSS (if null, the title will be used)
        'self_rss'      => null,    // the action where the rss feeds is available
        'author_email'  => null,
        'author_url'    => null,
        'copyright'     => null,
        'categories'    => array(), // a list of terms wich define the feeds

        /*
            Required

            Here is all item wich will be available on the feeds.
            It must be an array containing items like this: 
                $item = array(
                        // Required
                        'url'           => 'the_item_permalink_on_the_site',
                        'title'         => 'the_item_title',
                        'updated_at'    => '', // item timestamp

                        // optional
                        'author_name'   => 'author',
                        'author_email'  => 'email',
                        'author_url'    => 'email',
                        'content'       => 'the_item_content',
                        'link_alt'      => 'alternate_permalink_to_item',
                        'categories'    => array('foo', 'bar'),
                        'summary'       => 'the_item_summary'
                );
        */
        'items' => array(),

        'use_CDATA' => false,
    );
    
    /**
     * All feeds data
     * @var array
     */
    protected $data;
    
    /**
     * Forge a new builder
     * @param array $config Data wich will be used to form the feeds xml
     * @return Builder
     */
    public static function forge($config = array())
    {
        return new static($config);
    }

    function __construct($config = array())
    {
        \Config::load('feeds', 'feeds');
        // update config with given and predefined in config folders
        $this->data = \Arr::merge(static::$config, \Config::get('feeds', array()), $config);
    }

    public function & __get($key = '')
    {
        if (array_key_exists($key, $this->data)) {
            return $this->data[$key];
        }
    }

    public function __set($key = '', $value)
    {
        if (array_key_exists($key, $this->data)) {
            $this->data[$key] = $value;
        }
    }

    /**
     * Add an item to the feeds.
     * The given array have 3 required field:
     * <code>
     *  $item = array(
     *      // Required
     *      'url'           => 'the_item_permalink_on_the_site',
     *      'title'         => 'the_item_title',
     *      'updated_at'    => '', // item timestamp
     *
     *      // optional
     *      'author_name'   => 'author',
     *      'content'       => 'the_item_content',
     *      'link_alt'      => 'alternate_permalink_to_item',
     *      'categories'    => array('foo', 'bar'),
     *      'summary'       => 'the_item_summary'
     *  );
     * </code
     * @param array $item the array containing all data of this item
     * @return Builder
     */
    public function add_item($item = array())
    {
        $this->data['items'][] = $item;
        return $this;
    }

    /**
     * Transform all date fields into timestamp
     */
    private function _process_date()
    {
        $transform = function($date) {
            if(empty($date))
                return null;
    
            if(is_string($date) && strtolower($date) == 'now')
                return \Date::time()->get_timestamp();

            // Date object return timestamp
            if($date instanceof \Date)
                return $date->get_timestamp();

            // array, create date from string and format
            if(is_array($date))
            {
                if(array_key_exists(0, $date) && array_key_exists(1, $date))
                {
                    $dateStr = $date[0];
                    $pattern = $date[1];
                    try {
                        $date = \Date::create_from_string($dateStr, $pattern);
                        return $date->get_timestamp();
                    } catch (\Exception $e) {
                        return null;
                    }
                }
                return null;
            }
            // default : it should be a timestamp
            try {
                $date = \Date::forge($date);
                $date->format();

                return $date->get_timestamp();
            } catch (\Exception $e) {
                return null;
            }
        }; // end closure

        // process each date;
        $this->data['updated_at'] = $transform(\Arr::get($this->data, 'updated_at'));

        if(is_array(\Arr::get($this->data, 'items')))
        {
            foreach ($this->data['items'] as $key => $item) {
                $date = \Arr::get($item, 'updated_at');
                $this->data['items'][$key]['updated_at'] = $transform($date);
            }
        }
    }

    /**
     * Verify the validity of the config array
     * @return bool
     */
    private function _validate()
    {
        $this->_process_date();

        // check base required fields
        if( ! \Arr::get($this->data, 'title'))
            throw new InvalidDataException("The field 'title' is required to build a feed.");
            
        if( ! \Arr::get($this->data, 'site_url')) 
            throw new InvalidDataException("The field 'site_url' is required to build a feed.");

        if( ! \Arr::get($this->data, 'updated_at'))
            throw new InvalidDataException("The field 'updated_at' is required to build a feed. It must be a Date object, a timestamp, an array like: array('string_date', 'pattern').");


        // check items validity
        $items = \Arr::get($this->data, 'items');
        if ( ! is_array($items))
            throw new InvalidDataException("The field 'items' must be an array.");

        foreach ($items as $item) {
            if( ! is_array($item))
                throw new InvalidDataException("The field 'items' must contain only array");

            // check required fields
            if( ! \Arr::get($item, 'url'))
                throw new InvalidDataException("The field 'url' is required for each array in the field 'items'.");

            if( ! \Arr::get($item, 'title'))
                throw new InvalidDataException("The field 'title' is required for each array in the field 'items'.");

            if( ! \Arr::get($item, 'updated_at'))
                throw new InvalidDataException("The field 'updated_at' is required for each array in the field 'items' and must be a Date object, a timestamp, an array like: array('string_date', 'pattern').");
        }

        // sort by date
        usort($this->data['items'], function($a, $b) {
            // desc sort
            $res = $a['updated_at'] > $b['updated_at'] ? -1 : 1;

            $a['updated_at'] == $b['updated_at'] and $res = 0;

            return $res;
        });

        return true;
    }

    public function is_valid()
    {
        try {
            $this->_validate(); 
            return true;        
        } catch (\InvalidDataException $e) {
            return false;
        }
    }

    /**
     * Build the XML string, according to Atom specs
     * @param bool $throw false will return null if data is invalid, true throw exception
     * @return string|null the xml string, null if $throw = false and an error occur
     */
    public function get_atom($throw = true)
    {
        if($throw) {
            $this->_validate();
        }
        else if( ! $this->is_valid()) {
            return null;
        }

        $feed = new \SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><feed xmlns="http://www.w3.org/2005/Atom"></feed>');


        $build_date = new \DateTime();
        $build_date->setTimestamp($this->data['updated_at']);


        // Required Atom data
        $feed->id           = \Uri::create( $this->data['site_url'] );
        $feed->title        = $this->data['title'];
        $feed->updated      = $build_date->format(\DateTime::ATOM);


        // optional data
        if($this->data['description'])
            $feed->subtitle = $this->data['description'];

        // the feeds link
        if($this->data['self_atom'])
        {
            $feed->link['rel']  = 'self';
            $feed->link['href'] = \Uri::create($this->data['self_atom']);
        }
        
        // author info
        $author_name    = $this->data['author_name'];
        $author_url     = $this->data['author_url'];
        $author_email   = $this->data['author_email'];
        if($author_name || $author_url || $author_email)
        {
            $author = $feed->addChild('author');
            $author_name    and $author->name   = $author_name;
            $author_url     and $author->uri    = $author_url;
            $author_email   and $author->email  = $author_email;
        }

        // feeds categories
        foreach ($this->data['categories'] as $c) {
            $feed->addChild('category')['term'] = $c;
        }

        $this->data['copyright'] and $feed->rights = $this->data['copyright'];


        // add items to feeds
        foreach ($this->data['items'] as $item) {
            $entry = $feed->addChild('entry');

            $date = date_create()->setTimestamp($item['updated_at']);

            $url = \Uri::create( $item['url'] );

            // required
            $entry->id      = $url;
            $entry->title   = $item['title'];
            $entry->updated = $date->format(\DateTime::ATOM);
            $link           = $entry->addChild('link');
            $link['href']   = $url;

            // optionnals

            // author info
            \Arr::get($item, 'author_name') and $entry->author->name = $item['author_name'];
            \Arr::get($item, 'author_url') and $entry->author->url = $item['author_url'];
            \Arr::get($item, 'author_email') and $entry->author->email = $item['author_email'];

            // alternative link
            if(\Arr::get($item, 'link_alt'))
            {
                $link           = $entry->addChild('link');
                $link['href']   = \Uri::create( $item['link_alt'] );
                $link['rel']    = 'alternate';
            }

            // categories
            if(is_array(\Arr::get($item, 'categories')))
            {
                foreach ($item['categories'] as $c) {
                    $entry->addChild('category')['term'] = $c;
                }
            }

            // summary
            \Arr::get($item, 'summary') and $entry->summary = $item['summary'];

            // content
            if(\Arr::get($item, 'content'))
            {
                if($this->data['use_CDATA'])
                {   
                    $entry->content = null;
                    $node = dom_import_simplexml($entry->content); 
                    $content_cdata = $node->ownerDocument->createCDATASection($item['content']); 
                    $node->appendChild($content_cdata);
                    // $node and $entry share the same xml element
                }
                else
                {
                    $entry->content = $item['content'];
                }
                $entry->content['type'] = 'html';
            }
        } // end foreach items

        return $feed->asXML();
    }

    /**
     * Build the XML string, according to RSS specs
     *
     * @todo Support of guid on item
     *
     * @param bool $throw false will return null if data is invalid, true throw exception
     * @return string|null the xml string, null if $throw = false and an error occur
     */
    public function get_rss($throw = true)
    {
        if($throw) {
            $this->_validate();
        }
        else if( ! $this->is_valid()) {
            return null;
        }

        $atomNs = 'http://www.w3.org/2005/Atom';

        $rss = new \SimpleXMLElement('<?xml version="1.0"?><rss version="2.0" xmlns:atom="'.$atomNs.'"><channel></channel></rss>');
        $rss->registerXPathNamespace('atom', $atomNs);
        $channel = $rss->channel;

        $build_date = new \DateTime();
        $build_date->setTimestamp($this->data['updated_at']);

        // required RSS data
        $channel->link          = \Uri::create( $this->data['site_url'] );
        $channel->title         = $this->data['title'];
        $channel->description   = $this->data['description'] ? : $this->data['title'];
        $channel->lastBuildDate = $build_date->format(\DateTime::RSS);

        if(\Arr::get($this->data, 'self_rss')) {
            $source = $channel->addChild('link', '', $atomNs);
            $source['href'] = $this->data['self_rss'];
            $source['rel']  = 'self';
            $source['type'] = 'application/rss+xml';
        }

        // author
        $this->data['author_email'] and $channel->managingEditor = $this->data['author_email'];

        //copyright
        $this->data['copyright'] and $channel->copyright = $this->data['copyright'];

        // categories
        foreach ($this->data['categories'] as $c) {
             $channel->addChild('category', $c);
        }

        // item data
        foreach ($this->data['items'] as $item) {
            $itemNode = $channel->addChild('item');

            $date = date_create()->setTimestamp($item['updated_at']);

            // required
            $itemNode->link     = \Uri::create( $item['url'] );
            $itemNode->title    = $item['title'];
            $itemNode->pubDate  = $date->format(\DateTime::RSS);

            if(\Arr::get($item, 'content'))
            {
                if($this->data['use_CDATA'])
                {   
                    $itemNode->description = null;
                    $node = dom_import_simplexml($itemNode->description); 
                    $content_cdata = $node->ownerDocument->createCDATASection($item['content']); 
                    $node->appendChild($content_cdata);
                    // $node and $entry share the same xml element
                }
                else
                {
                    $itemNode->description = $item['content'];
                }
            }
            else if (\Arr::get($item, 'summary'))
            {
                $itemNode->description = $item['summary'];
            }

            if(\Arr::get($this->data, 'self_rss'))
            {
                $source = $itemNode->addChild('source', $this->data['title']);
                $source['url'] = \Uri::create($this->data['self_rss']);
            }

            \Arr::get($item, 'author_email') and $itemNode->author = $item['author_email'];
        }

        return $rss->asXML();   
    }

}

<?php
/**
 * Ionize
 *
 * @package		Ionize
 * @author		Ionize Dev Team
 * @license		http://ionizecms.com/doc-license
 * @link		http://ionizecms.com
 * @since		Version 0.9.8
 *
 */


/**
 * Article TagManager 
 *
 */
class TagManager_Article extends TagManager
{
	public static $tag_definitions = array
	(
		'articles' => 				'tag_articles',
		'articles:article' => 		'tag_articles_article',

		'article' => 				'tag_article',
		'article:content' => 		'tag_simple_content',
		'article:active_class' => 	'tag_simple_value',
		'article:view' => 			'tag_simple_value',
		'article:next' => 			'tag_next_article',
		'article:prev' => 			'tag_prev_article',
	);


	// ------------------------------------------------------------------------
	

	/**
	 * Get Articles
	 * @TODO : 	Write local cache
	 *
	 * @param	FTL_Binding
	 * @return	Array
	 *
	 * 1. Try to get the articles from a special URI
	 * 2. Get the articles from the current page
	 * 3. Filter on the article name if the article name is in URI segment 1
	 *
	 */
	public static function get_articles(FTL_Binding $tag)
	{
		// Page. 1. Local one, 2. Current page (should never arrived except if the tag is used without the 'page' parent tag)
		$page = $tag->get('page');

		// Only get all articles (no limit to one page) if asked.
		// Filter by current page by default
		if (empty($page) && $tag->getAttribute('all') == NULL)
			$page = self::registry('page');

		// Set by Page::get_current_page()
		$is_current_page = isset($page['__current__']) ? TRUE : FALSE;

		// Pagination
		$tag_pagination = $tag->getAttribute('pagination');
		$ionize_pagination = $page['pagination'];

		// Type filter, limit, SQL filter
		$type = $tag->getAttribute('type');
		$nb_to_display = $tag->getAttribute('limit', 0);
		$filter = $tag->getAttribute('filter');

		// URL based process of special URI only allowed on current page
		$special_uri_array = self::get_special_uri_array();

		if ($is_current_page)
		{
			// Special URI process
			if (! is_null($special_uri_array))
			{
				foreach($special_uri_array as $_callback => $args)
				{
					if (method_exists(__CLASS__, 'add_articles_filter_'.$_callback))
						call_user_func(array(__CLASS__, 'add_articles_filter_'.$_callback), $tag, $args);
				}
			}
			// Deactivate "limit" if one pagination is set
			if ($tag_pagination OR $ionize_pagination) $nb_to_display = 0;
		}
		else
		{
			// Deactivate Ionize pagination (Only available of the current page)
			$ionize_pagination = NULL;

			// Deactivate limit if the "pagination" attribute is set
			if ($tag_pagination) $nb_to_display = 0;
		}

		// If pagination is only set by the tag : Call the pagination filter
		if ($tag_pagination)
		{
			if ( is_null($special_uri_array) OR ! array_key_exists('pagination', $special_uri_array))
				self::add_articles_filter_pagination($tag);
		}

		// from categories ?
		// @TODO : Find a way to display articles from a given category : filter ?
		$from_categories = $tag->getAttribute('from_categories');
		$from_categories_condition = ($tag->getAttribute('from_categories_condition') != NULL && $tag->attr['from_categories_condition'] != 'or') ? 'and' : 'or';

		/*
		 * Preparing WHERE on articles
		 * From where do we get the article : from a page, from the parent page or from the all website ?
		 *
		 */
		// Order. Default order : ordering ASC
		$order_by = $tag->getAttribute('order_by', 'id_page, ordering ASC');
		$where = array('order_by' => $order_by);

		// Add type to the where array
		if ( ! is_null($type))
		{
			if ($type == '') {
				$where['article_type.type'] = 'NULL';
				$type = NULL;
			}
			else
				$where['article_type.type'] = $type;
		}

		// Get only articles from the detected page
		if ( ! empty($page))
			$where['id_page'] = $page['id_page'];

		// Set Limit : First : pagination, Second : limit
		$limit = $tag_pagination ? $tag_pagination : $ionize_pagination;
		if ( ! $limit && $nb_to_display > 0) $limit = $nb_to_display;
		if ( $limit ) $where['limit'] = $limit;

		// Get from DB
		$articles = self::$ci->article_model->get_lang_list(
			$where,
			$lang = Settings::get_lang(),
			$filter
		);

		// Pagination needs the total number of articles, without the pagination filter
		if ($tag_pagination OR $ionize_pagination)
		{
			$nb_total_articles = self::count_nb_total_articles($tag, $where, $filter);
			$tag->set('nb_total_items', $nb_total_articles);
		}

		self::init_articles_urls($articles);

		self::init_articles_views($articles);

		return $articles;
	}


	// ------------------------------------------------------------------------


	/**
	 * Return the number of articles, excluding the pagination filter.
	 *
	 * @param FTL_Binding
	 * @param array
	 * @param null|string
	 *
	 * @return int
	 *
	 */
	function count_nb_total_articles(FTL_Binding $tag, $where = array(), $filter=NULL)
	{
		$page = $tag->get('page');
		if (empty($page)) $page = self::registry('page');

		// Set by Page::get_current_page()
		$is_current_page = isset($page['__current__']) ? TRUE : FALSE;

		$special_uri_array = self::get_special_uri_array();

		// Nb articles for current page
		if ($is_current_page)
		{
			// Filters (except pagination)
			if (! is_null($special_uri_array))
			{
				foreach($special_uri_array as $_callback => $args)
				{
					if ($_callback != 'pagination' && method_exists(__CLASS__, 'add_articles_filter_'.$_callback))
						call_user_func(array(__CLASS__, 'add_articles_filter_'.$_callback), $tag, $args);
				}
			}
		}

		$nb_total_articles = self::$ci->article_model->count_articles(
			$where,
			$lang = Settings::get_lang(),
			$filter
		);

		return $nb_total_articles;
	}


	// ------------------------------------------------------------------------


	/**
	 * Adds one category filter
	 *
	 * @param FTL_Binding $tag
	 * @param array       $args
	 *
	 */
	function add_articles_filter_category(FTL_Binding $tag, $args = array())
	{
		$category_name = ( ! empty($args[0])) ? $args[0] : NULL;

		if ( ! is_null($category_name))
		{
			self::$ci->article_model->add_category_filter(
				$category_name,
				Settings::get_lang()
			);
		}
	}


	// ------------------------------------------------------------------------


	/**
	 * Adds one pagination filter
	 *
	 * @param FTL_Binding $tag
	 * @param array       $args
	 *
	 * @return null
	 */
	function add_articles_filter_pagination(FTL_Binding $tag, $args = array())
	{
		// Page
		$page = $tag->get('page');
		if (is_null($page)) $page = self::registry('page');

		$start_index = ! empty($args[0]) ? $args[0] : NULL;

		// Pagination : First : tag, second : page
		$pagination = $tag->getAttribute('pagination');
		if (is_null($pagination))
			$pagination = $page['pagination'];

		// Exit if no info about pagination can be found.
		if ( ! $pagination)
			return NULL;

		self::$ci->article_model->add_pagination_filter($pagination, $start_index);
	}


	// ------------------------------------------------------------------------


	/**
	 * Adds one archives filter
	 *
	 * @param FTL_Binding $tag
	 * @param array       $args
	 */
	function add_articles_filter_archives(FTL_Binding $tag, $args = array())
	{
		// Month / year
		$year =  (! empty($args[0]) ) ?	$args[0] : FALSE;
		$month =  (! empty($args[1]) ) ? $args[1] : NULL;

		if ($year)
			self::$ci->article_model->add_archives_filter($year, $month);
	}


	// ------------------------------------------------------------------------


	/**
	 * Inits articles URLs
	 * Get the contexts of all given articles and define each article correct URL
	 *
	 * @param $articles
	 *
	 */
	public function init_articles_urls(&$articles)
	{
		// Page URL key to use
		$page_url_key = (config_item('url_mode') == 'short') ? 'url' : 'path';

		// Array of all articles IDs
		$articles_id = array();
		foreach($articles as $article)
			$articles_id[] = $article['id_article'];

		// Articles contexts of all articles
		$pages_context = self::$ci->page_model->get_lang_contexts($articles_id, Settings::get_lang('current'));

		// Add pages contexts data to articles
		foreach($articles as &$article)
		{
			$contexts = array();
			foreach($pages_context as $context)
			{
				if ($context['id_article'] == $article['id_article'])
					$contexts[] = $context;
			}

			$page = array_shift($contexts);

			// Get the context of the Main Parent
			if ( ! empty($contexts))
			{
				foreach($contexts as $context)
				{
					if ($context['main_parent'] == '1')
						$page = $context;
				}
			}

			// Basic article URL : its lang URL (without "http://")
			$url = $article['url'];

			// Link ?
			if ($article['link_type'] != '' )
			{
				// External
				if ($article['link_type'] == 'external')
				{
					$article['url'] = $article['link'];
				}

				// Email
				else if ($article['link_type'] == 'email')
				{
					$article['url'] = auto_link($article['link'], 'both', TRUE);
				}

				// Internal
				else
				{
					// Article
					if($article['link_type'] == 'article')
					{
						// Get the article to which this page links
						$rel = explode('.', $article['link_id']);
						$target_article = self::$ci->article_model->get_context($rel[1], $rel[0], Settings::get_lang('current'));

						// Of course, only if not empty...
						if ( ! empty($target_article))
						{
							// Get the article's parent page
							$parent_page = self::$ci->page_model->get_by_id($rel[0], Settings::get_lang('current'));

							if ( ! empty($parent_page))
								$article['url'] = $parent_page[$page_url_key] . '/' . $target_article['url'];
						}
					}
					// Page
					else
					{
						$target_page = self::$ci->page_model->get_by_id($article['link_id'], Settings::get_lang('current'));
						$article['url'] = $target_page[$page_url_key];
					}

					// Correct the URL : Lang + Base URL
					if ( count(Settings::get_online_languages()) > 1 OR Settings::get('force_lang_urls') == '1' )
					{
						$article['url'] =  Settings::get_lang('current'). '/' . $article['url'];
					}
					$article['url'] = base_url() . $article['url'];

				}
			}
			// Standard URL
			else
			{
				if ( count(Settings::get_online_languages()) > 1 OR Settings::get('force_lang_urls') == '1' )
				{

					$article['url'] = base_url() . Settings::get_lang('current') . '/' . $page[$page_url_key] . '/' . $url;
				}
				else
				{
					$article['url'] = base_url() . $page[$page_url_key] . '/' . $url;
				}
			}
		}
	}


	// ------------------------------------------------------------------------


	/**
	 * Inits, for each article, the view to use.
	 *
	 * @param $articles
	 *
	 */
	private function init_articles_views(&$articles)
	{
		$nb = count($articles);

		foreach ($articles as $k=>$article)
		{
			if (empty($article['view']))
			{
				if ($nb > 1 && ! empty($article['article_list_view']))
				{
					$articles[$k]['view'] = $article['article_list_view'];
				}
				else if (! empty($article['article_view']))
				{
					$articles[$k]['view'] = $article['article_view'];
				}
			}
		}
	}


	// ------------------------------------------------------------------------


	/**
	 * Returns the current article content
	 * Try first to get the current article (from URL)
	 * Then the article from locals.
	 *
	 * @param	FTL_Binding object
	 *
	 * @return	String
	 *
	 */
	public static function tag_article(FTL_Binding $tag)
	{
		$cache = $tag->getAttribute('cache', TRUE);

		// Tag cache
		if ($cache == TRUE && ($str = self::get_cache($tag)) !== FALSE)
			return $str;

		// Returned string
		$str = '';

		// Extend Fields tags
		self::create_extend_tags($tag, 'article');

		// Registry article : First : Registry (URL ask), Second : Stored one
		$_article = self::registry('article');
		if (empty($_article)) $_article = $tag->get('article');

		$_articles = array();
		if ( ! empty($_article)) $_articles = array($_article);

		// Add data like URL to each article and render the article
		if ( ! empty($_articles))
		{
			$_articles = self::prepare_articles($tag, $_articles);
			$_article = $_articles[0];

			// Render the article
			$tag->set('article', $_article);
			$tag->set('index', 0);
			$tag->set('count', 1);

			// Parse the article's view if the article tag is single (<ion:article />)
			if($tag->is_single())
				$str = self::find_and_parse_article_view($tag, $_article);
			// Else expand the tag
			else
				$str = self::wrap($tag, $tag->expand());
		}

		// Tag cache
		self::set_cache($tag, $str);

		return $str;
	}


	// ------------------------------------------------------------------------


	/**
	 * Expand the articles
	 *
	 * @param	FTL_Binding object
	 *
	 * @return	String
	 *
	 */
	public static function tag_articles(FTL_Binding $tag)
	{
		$cache = $tag->getAttribute('cache', TRUE);

		// Tag cache
		if ($cache == TRUE && ($str = self::get_cache($tag)) !== FALSE)
			return $str;

		// Returned string
		$str = '';

		// Extend Fields tags
		self::create_extend_tags($tag, 'article');

		// Articles
		$_articles = self::get_articles($tag);
		$_articles = self::prepare_articles($tag, $_articles);

		// Tag data
		$count = count($_articles);
		$tag->set('count', $count);

		// Make articles in random order
		if ( $tag->getAttribute('random') == TRUE)
			shuffle ($articles);

		$tag->set('articles', $_articles);

		// Add data like URL to each article
		// and finally render each article
		foreach($_articles as $key => $article)
		{
			$tag->set('article', $article);

			// Set by self::prepare_articles()
			// $tag->set('index', $key);
			$tag->set('count', $count);

			$str .= $tag->expand();
		}

		// Experimental : To allow tags in articles
		// $str = $tag->parse_as_nested($str);

		$str = self::wrap($tag, $str);
		
		// Tag cache
		self::set_cache($tag, $str);
		
		return $str;
	}

	
	// ------------------------------------------------------------------------


	/**
	 * Returns the article tag content
	 * To be used inside an "articles" tag
	 * Only looks for locals->article
	 *
	 * @param	FTL_Binding
	 *
	 * @return	String
	 *
	 */
	public static function tag_articles_article(FTL_Binding $tag)
	{
		if (
			!is_null($tag->getAttribute('render'))
			&& !is_null($tag->get('article'))
		)
		{
			return self::find_and_parse_article_view($tag, $tag->get('article'));
		}

		return $tag->expand();
	}


	// ------------------------------------------------------------------------


	public static function tag_prev_article(FTL_Binding $tag)
	{
		$article = self::get_adjacent_article($tag, 'prev');
		$tag->set('data', $article);

		return self::process_prev_next_article($tag, $article);
	}


	// ------------------------------------------------------------------------


	public static function tag_next_article(FTL_Binding $tag)
	{
		$article = self::get_adjacent_article($tag, 'next');
		$tag->set('data', $article);

		return self::process_prev_next_article($tag, $article);
	}


	// ------------------------------------------------------------------------


	private static function get_adjacent_article(FTL_Binding $tag, $mode='prev')
	{
		$found_article = NULL;

		$articles = self::prepare_articles($tag, self::get_articles($tag));

		// Get the article : Fall down to registry if no one found in tag
		$article = $tag->get('article');

		$enum = ($mode=='prev') ? -1 : 1;
		
		foreach($articles as $key => $_article)
		{
			if ($_article['id_article'] == $article['id_article'])
			{
				if ( ! empty($articles[$key + $enum]))
				{
					$found_article = $articles[$key + $enum];
					break;
				}
			}
		}

		return $found_article;
	}


	// ------------------------------------------------------------------------


	/**
	 * Processes the next / previous article tags result
	 * Internal use only.
	 *
	 * @param	FTL_Binding
	 * @param	array
	 *
	 * @return string
	 *	 
	 */
	private static function process_prev_next_article(FTL_Binding $tag, $article = NULL)
	{
		$str = '';
		if ($article)
			$str = self::wrap($tag, $tag->expand());

		return $str;
	}


	// ------------------------------------------------------------------------


	/**
	 * Prepare the articles array
	 *
	 * @param FTL_Binding	$tag
	 * @param Array 		$articles
	 *
	 * @return	Array		Articles
	 *
	 */
	private static function prepare_articles(FTL_Binding $tag, $articles)
	{
		// Articles index starts at 1.
		$index = 1;

		// view
		$view = $tag->getAttribute('view');

		// paragraph limit ?
		$paragraph = $tag->getAttribute('paragraph');

		// auto_link
		$auto_link = $tag->getAttribute('auto_link', TRUE);

		// Last part of the URI
		$uri_last_part = array_pop(explode('/', uri_string()));

		$count = count($articles);

		foreach($articles as $key => $article)
		{
			// Force the view if the "view" attribute is defined
			if ( ! is_null($view))
				$articles[$key]['view'] = $view;

			$articles[$key]['active_class'] = '';

			if (!is_null($tag->getAttribute('active_class')))
			{
				$article_url = array_pop(explode('/', $article['url']));
				if ($uri_last_part == $article_url)
				{
					$articles[$key]['active_class'] = $tag->attr['active_class'];
				}
			}

			// Limit to x paragraph if the attribute is set
			if ( ! is_null($paragraph))
				$articles[$key]['content'] = tag_limiter($article['content'], 'p', intval($paragraph));

			// Autolink the content
			if ($auto_link)
				$articles[$key]['content'] = auto_link($articles[$key]['content'], 'both', TRUE);

			// Article's index
			$articles[$key]['index'] = $index++;

			// Article's count
			$articles[$key]['count'] = $count;

			// Article's ID
			$articles[$key]['id'] = $articles[$key]['id_article'];

		}

		return $articles;
	}


	// ------------------------------------------------------------------------


	/**
	 * Find and parses the article view
	 *
	 * @param 	FTL_Binding
	 * @param   array
	 *
	 * @return string
	 *
	 */
	private static function find_and_parse_article_view(FTL_Binding $tag, $article)
	{
		// Registered page
		$page = self::registry('page');

		// Local articles
		$articles = $tag->get('articles');

		// Try to get the view defined for article
		if ( $article['view'] == FALSE OR $article['view'] == '')
		{
			if (count($articles) == 1)
				$article['view'] = $page['article_view'];
			else
				$article['view'] = $page['article_list_view'];
		}

		// Default article view
		if (empty($article['view']))
			$article['view'] = Theme::get_default_view('article');

		// View path
		$view_path = Theme::get_theme_path().'views/'.$article['view'].EXT;

		// Return the Ionize default's theme view
		if ( ! file_exists($view_path))
		{
			$view_path = Theme::get_theme_path().'views/'.Theme::get_default_view('article').EXT;
			if ( ! file_exists($view_path))
				$view_path = APPPATH.'views/'.Theme::get_default_view('article').EXT;
		}

		return $tag->parse_as_nested(file_get_contents($view_path));
	}
}

TagManager_Article::init();


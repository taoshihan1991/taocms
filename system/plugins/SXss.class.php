<?php if(!defined('ROOT')) die('Access denied.');


/**
 * Determines if the current version of PHP is equal to or greater than the supplied value
 *
 * @param	string
 * @return	bool	TRUE if the current version is $version or higher
 */
function is_php($version)
{
	static $_is_php;
	$version = (string) $version;

	if ( ! isset($_is_php[$version]))
	{
		$_is_php[$version] = version_compare(PHP_VERSION, $version, '>=');
	}

	return $_is_php[$version];
}


/**
 * Remove Invisible Characters
 *
 * This prevents sandwiching null characters
 * between ascii characters, like Java\0script.
 *
 * @param	string
 * @param	bool
 * @return	string
 */
function remove_invisible_characters($str, $url_encoded = TRUE)
{
	$non_displayables = array();

	// every control character except newline (dec 10),
	// carriage return (dec 13) and horizontal tab (dec 09)
	if ($url_encoded)
	{
		$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
		$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
	}

	$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127

	do
	{
		$str = preg_replace($non_displayables, '', $str, -1, $count);
	}
	while ($count);

	return $str;
}





class SXss {


	/**
	 * Character set
	 *
	 * @var	string
	 */
	public $charset = 'UTF-8';


	/**
	 * List of never allowed strings
	 *
	 * @var	array
	 */
	protected $_never_allowed_str =	array(
		'document.cookie'	=> '[removed]',
		'document.write'	=> '[removed]',
		'.parentNode'		=> '[removed]',
		'.innerHTML'		=> '[removed]',
		'-moz-binding'		=> '[removed]',
		'<!--'				=> '&lt;!--',
		'-->'				=> '--&gt;',
		'<![CDATA['			=> '&lt;![CDATA[',
		'<comment>'			=> '&lt;comment&gt;'
	);

	/**
	 * List of never allowed regex replacements
	 *
	 * @var	array
	 */
	protected $_never_allowed_regex = array(
		'javascript\s*:',
		'(document|(document\.)?window)\.(location|on\w*)',
		'expression\s*(\(|&\#40;)', // CSS and IE
		'vbscript\s*:', // IE, surprise!
		'wscript\s*:', // IE
		'jscript\s*:', // IE
		'vbs\s*:', // IE
		'Redirect\s+30\d',
		"([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
	);


	// 需要的内容

	/**
	 * Attribute Conversion
	 *
	 * @param	array	$match
	 * @return	string
	 */
	protected function _convert_attribute($match)
	{
		return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
	}

	/**
	 * Do Never Allowed
	 *
	 * @param 	string
	 * @return 	string
	 */
	protected function _do_never_allowed($str)
	{
		$str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);

		foreach ($this->_never_allowed_regex as $regex)
		{
			$str = preg_replace('#'.$regex.'#is', '[removed]', $str);
		}

		return $str;
	}

	/**
	 * Remove Evil HTML Attributes (like event handlers and style)
	 *
	 * It removes the evil attribute and either:
	 *
	 *  - Everything up until a space. For example, everything between the pipes:
	 *
	 *	<code>
	 *		<a |style=document.write('hello');alert('world');| class=link>
	 *	</code>
	 *
	 *  - Everything inside the quotes. For example, everything between the pipes:
	 *
	 *	<code>
	 *		<a |style="document.write('hello'); alert('world');"| class="link">
	 *	</code>
	 *
	 * @param	string	$str		The string to check
	 * @param	bool	$is_image	Whether the input is an image
	 * @return	string	The string with the evil attributes removed
	 */
	protected function _remove_evil_attributes($str, $is_image)
	{
		$evil_attributes = array('on\w*', 'xmlns', 'formaction', 'form', 'xlink:href', 'FSCommand', 'seekSegmentTime');

		if ($is_image === TRUE)
		{
			/*
			 * Adobe Photoshop puts XML metadata into JFIF images,
			 * including namespacing, so we have to allow this for images.
			 */
			unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
		}

		do {
			$count = $temp_count = 0;

			// replace occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
			$str = preg_replace('/(<[^>]+)(?<!\w)('.implode('|', $evil_attributes).')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', '$1[removed]', $str, -1, $temp_count);
			$count += $temp_count;

			// find occurrences of illegal attribute strings without quotes
			$str = preg_replace('/(<[^>]+)(?<!\w)('.implode('|', $evil_attributes).')\s*=\s*([^\s>]*)/is', '$1[removed]', $str, -1, $temp_count);
			$count += $temp_count;
		}
		while ($count);

		return $str;
	}

	/**
	 * Sanitize Naughty HTML
	 *
	 * Callback method for clean() to remove naughty HTML elements.
	 *
	 * @param	array	$matches
	 * @return	string
	 */
	protected function _sanitize_naughty_html($matches)
	{
		return '&lt;'.$matches[1].$matches[2].$matches[3] // encode opening brace
			// encode captured opening or closing brace to prevent recursive vectors:
			.str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);
	}


	/**
	 * HTML Entities Decode
	 *
	 * A replacement for html_entity_decode()
	 *
	 * The reason we are not using html_entity_decode() by itself is because
	 * while it is not technically correct to leave out the semicolon
	 * at the end of an entity most browsers will still interpret the entity
	 * correctly. html_entity_decode() does not convert entities without
	 * semicolons, so we are left with our own little solution here. Bummer.
	 *
	 * @link	http://php.net/html-entity-decode
	 *
	 * @param	string	$str		Input
	 * @param	string	$charset	Character set
	 * @return	string
	 */
	public function entity_decode($str, $charset = NULL)
	{
		if (strpos($str, '&') === FALSE)
		{
			return $str;
		}

		static $_entities;

		isset($charset) OR $charset = $this->charset;
		$flag = is_php('5.4')
			? ENT_COMPAT | ENT_HTML5
			: ENT_COMPAT;

		do
		{
			$str_compare = $str;

			// Decode standard entities, avoiding false positives
			if (preg_match_all('/&[a-z]{2,}(?![a-z;])/i', $str, $matches))
			{
				if ( ! isset($_entities))
				{
					$_entities = array_map(
						'strtolower',
						is_php('5.3.4')
							? get_html_translation_table(HTML_ENTITIES, $flag, $charset)
							: get_html_translation_table(HTML_ENTITIES, $flag)
					);

					// If we're not on PHP 5.4+, add the possibly dangerous HTML 5
					// entities to the array manually
					if ($flag === ENT_COMPAT)
					{
						$_entities[':'] = '&colon;';
						$_entities['('] = '&lpar;';
						$_entities[')'] = '&rpar;';
						$_entities["\n"] = '&newline;';
						$_entities["\t"] = '&tab;';
					}
				}

				$replace = array();
				$matches = array_unique(array_map('strtolower', $matches[0]));
				foreach ($matches as &$match)
				{
					if (($char = array_search($match.';', $_entities, TRUE)) !== FALSE)
					{
						$replace[$match] = $char;
					}
				}

				$str = str_ireplace(array_keys($replace), array_values($replace), $str);
			}

			// Decode numeric & UTF16 two byte entities
			$str = html_entity_decode(
				preg_replace('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;])|(?:0*\d{2,4}(?![0-9;]))))/iS', '$1;', $str),
				$flag,
				$charset
			);
		}
		while ($str_compare !== $str);
		return $str;
	}

	/**
	 * Compact Exploded Words
	 *
	 * Callback method for clean() to remove whitespace from
	 * things like 'j a v a s c r i p t'.
	 *
	 * @param	array	$matches
	 * @return	string
	 */
	protected function _compact_exploded_words($matches)
	{
		return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
	}


	/**
	 * JS Link Removal
	 *
	 * Callback method for clean() to sanitize links.
	 *
	 * This limits the PCRE backtracks, making it more performance friendly
	 * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
	 * PHP 5.2+ on link-heavy strings.
	 *
	 * @param	array	$match
	 * @return	string
	 */
	protected function _js_link_removal($match)
	{
		return str_replace($match[1],
					preg_replace('#href=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
							'',
							$this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
					),
					$match[0]);
	}

	/**
	 * Filter Attributes
	 *
	 * Filters tag attributes for consistency and safety.
	 *
	 * @param	string	$str
	 * @return	string
	 */
	protected function _filter_attributes($str)
	{
		$out = '';
		if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
		{
			foreach ($matches[0] as $match)
			{
				$out .= preg_replace('#/\*.*?\*/#s', '', $match);
			}
		}

		return $out;
	}


	/**
	 * JS Image Removal
	 * @param	array	$match
	 * @return	string
	 */
	protected function _js_img_removal($match)
	{
		return str_replace($match[1],
					preg_replace('#src=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
							'',
							$this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
					),
					$match[0]);
	}


	/**
	 * XSS Clean
	 *
	 *
	 * @param	string|string[]	$str		Input data
	 * @param 	bool		$is_image	Whether the input is an image file
	 * @return	string
	 */
	public function clean($str, $is_image = FALSE)
	{
		// Is the string an array?
		if (is_array($str))
		{
			while (list($key) = each($str))
			{
				$str[$key] = $this->clean($str[$key]);
			}

			return $str;
		}

		// Remove Invisible Characters
		$str = remove_invisible_characters($str);

		/*
		 * URL Decode
		 *
		 * Just in case stuff like this is submitted:
		 *
		 * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		 *
		 * Note: Use rawurldecode() so it does not remove plus signs
		 */
		do
		{
			$str = rawurldecode($str);
		}
		while (preg_match('/%[0-9a-f]{2,}/i', $str));

		/*
		 * Convert character entities to ASCII
		 *
		 * This permits our tests below to work reliably.
		 * We only convert entities that are within tags since
		 * these are the ones that will pose security problems.
		 */
		$str = preg_replace_callback("/[^a-z0-9>]+[a-z0-9]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);
		$str =  $this->entity_decode($str, $this->charset);

		// Remove Invisible Characters Again!
		$str = remove_invisible_characters($str);

		/*
		 * Convert all tabs to spaces
		 *
		 * This prevents strings like this: ja	vascript
		 * NOTE: we deal with spaces between characters later.
		 * NOTE: preg_replace was found to be amazingly slow here on
		 * large blocks of data, so we use str_replace.
		 */
		$str = str_replace("\t", ' ', $str);

		// Capture converted string for later comparison
		$converted_string = $str;

		// Remove Strings that are never allowed
		$str = $this->_do_never_allowed($str);

		/*
		 * Makes PHP tags safe
		 *
		 * Note: XML tags are inadvertently replaced too:
		 *
		 * <?xml
		 *
		 * But it doesn't seem to pose a problem.
		 */
		if ($is_image === TRUE)
		{
			// Images have a tendency to have the PHP short opening and
			// closing tags every so often so we skip those and only
			// do the long opening tags.
			$str = preg_replace('/<\?(php)/i', '&lt;?\\1', $str);
		}
		else
		{
			$str = str_replace(array('<?', '?'.'>'), array('&lt;?', '?&gt;'), $str);
		}

		/*
		 * Compact any exploded words
		 *
		 * This corrects words like:  j a v a s c r i p t
		 * These words are compacted back to their correct state.
		 */
		$words = array(
			'javascript', 'expression', 'vbscript', 'jscript', 'wscript',
			'vbs', 'script', 'base64', 'applet', 'alert', 'document',
			'write', 'cookie', 'window', 'confirm', 'prompt'
		);

		foreach ($words as $word)
		{
			$word = implode('\s*', str_split($word)).'\s*';

			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback('#('.substr($word, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
		}

		/*
		 * Remove disallowed Javascript in links or img tags
		 * We used to do some version comparisons and use of stripos(),
		 * but it is dog slow compared to these simplified non-capturing
		 * preg_match(), especially if the pattern exists in the string
		 *
		 * Note: It was reported that not only space characters, but all in
		 * the following pattern can be parsed as separators between a tag name
		 * and its attributes: [\d\s"\'`;,\/\=\(\x00\x0B\x09\x0C]
		 * ... however, remove_invisible_characters() above already strips the
		 * hex-encoded ones, so we'll skip them below.
		 */
		do
		{
			$original = $str;

			if (preg_match('/<a/i', $str))
			{
				$str = preg_replace_callback('#<a[^a-z0-9>]+([^>]*?)(?:>|$)#si', array($this, '_js_link_removal'), $str);
			}

			if (preg_match('/<img/i', $str))
			{
				$str = preg_replace_callback('#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#si', array($this, '_js_img_removal'), $str);
			}

			if (preg_match('/script|xss/i', $str))
			{
				$str = preg_replace('#</*(?:script|xss).*?>#si', '[removed]', $str);
			}
		}
		while ($original !== $str);

		unset($original);

		// Remove evil attributes such as style, onclick and xmlns
		$str = $this->_remove_evil_attributes($str, $is_image);

		/*
		 * Sanitize naughty HTML elements
		 *
		 * If a tag containing any of the words in the list
		 * below is found, the tag gets converted to entities.
		 *
		 * So this: <blink>
		 * Becomes: &lt;blink&gt;
		 */
		$naughty = 'alert|prompt|confirm|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|button|select|isindex|layer|link|meta|keygen|object|plaintext|style|script|textarea|title|math|video|svg|xml|xss';
		$str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

		/*
		 * Sanitize naughty scripting elements
		 *
		 * Similar to above, only instead of looking for
		 * tags it looks for PHP and JavaScript commands
		 * that are disallowed. Rather than removing the
		 * code, it simply converts the parenthesis to entities
		 * rendering the code un-executable.
		 *
		 * For example:	eval('some code')
		 * Becomes:	eval&#40;'some code'&#41;
		 */
		$str = preg_replace('#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si',
					'\\1\\2&#40;\\3&#41;',
					$str);

		// Final clean up
		// This adds a bit of extra precaution in case
		// something got through the above filters
		$str = $this->_do_never_allowed($str);

		/*
		 * Images are Handled in a Special Way
		 * - Essentially, we want to know that after all of the character
		 * conversion is done whether any unwanted, likely XSS, code was found.
		 * If not, we return TRUE, as the image is clean.
		 * However, if the string post-conversion does not matched the
		 * string post-removal of XSS, then it fails, as there was unwanted XSS
		 * code found and removed/changed during processing.
		 */
		if ($is_image === TRUE)
		{
			return ($str === $converted_string);
		}

		return $str;
	}


}


?>
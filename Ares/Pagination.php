<?php

namespace Ares;

use ErrorException;

class Pagination {
	var $base_url = ''; // The page we are linking to
	var $prefix = ''; // A custom prefix added to the path.
	var $suffix = ''; // A custom suffix added to the path.
	var $query = array (); // index/15/?keyword=up
	var $total_rows = 0; // Total number of items (database results)
	var $per_page = 10; // Max number of items you want shown per page
	var $num_links = 2; // Number of "digit" links to show before/after the currently viewed page
	var $cur_page = 0; // The current page being viewed
	var $use_page_numbers = false; // Use page number for segment instead of offset
	var $first_link = '&lsaquo; First';
	var $next_link = '&gt;';
	var $prev_link = '&lt;';
	var $last_link = 'Last &rsaquo;';
	var $full_tag_open = '';
	var $full_tag_close = '';
	var $first_tag_open = '';
	var $_first_tag_open = '';
	var $first_tag_close = '&nbsp;';
	var $_first_tag_close = '';
	var $last_tag_open = '&nbsp;';
	var $_last_tag_open = '';
	var $last_tag_close = '';
	var $_last_tag_close = '';
	var $first_url = ''; // Alternative URL for the First Page.
	var $cur_tag_open = '&nbsp;<strong>';
	var $cur_tag_close = '</strong>';
	var $next_tag_open = '&nbsp;';
	var $_next_tag_open = '';
	var $next_tag_close = '&nbsp;';
	var $_next_tag_close = '';
	var $prev_tag_open = '&nbsp;';
	var $_prev_tag_open = '';
	var $prev_tag_close = '';
	var $_prev_tag_close = '';
	var $num_tag_open = '&nbsp;';
	var $_num_tag_open = ''; // surround with <a>
	var $num_tag_close = '';
	var $_num_tag_close = '';
	var $display_pages = TRUE;
	var $anchor_class = '';
	var $first_anchor_class = '';
	var $last_anchor_class = '';

	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 *        	array	initialization parameters
	 */
	public function __construct($params = array()) {
		if (count ( $params ) > 0) {
			$this->init ( $params );
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize Preferences
	 *
	 * @access public
	 * @param
	 *        	array	initialization parameters
	 * @return void
	 */
	function init($params = array()) {
		if (count ( $params ) > 0) {
			foreach ( $params as $key => $val ) {
				if (isset ( $this->$key )) {
					$this->$key = $val;
				}
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Generate the pagination links
	 *
	 * @access public
	 * @return string
	 */
	function create() {
		// make query to a string
		if (is_array ( $this->query ) && ! empty ( $this->query )) {
			$query = '/?' . http_build_query ( $this->query );
		} else {
			$query = '';
		}

		// anchor class
		if ($this->anchor_class != '') {
			$this->anchor_class = 'class="' . $this->anchor_class . '" ';
		}
		if ($this->first_anchor_class != '') {
			$this->first_anchor_class = 'class="' . $this->first_anchor_class . '" ';
		}
		if ($this->last_anchor_class != '') {
			$this->last_anchor_class = 'class="' . $this->last_anchor_class . '" ';
		}
		$first_anchor_class = empty ( $this->first_anchor_class ) ? $this->anchor_class : $this->first_anchor_class;
		$last_anchor_class = empty ( $this->last_anchor_class ) ? $this->anchor_class : $this->last_anchor_class;

		// If our item count or per-page total is zero there is no need to continue.
		if ($this->total_rows == 0 or $this->per_page == 0) {
			return '';
		}

		// Calculate the total number of pages
		$num_pages = ceil ( $this->total_rows / $this->per_page );

		// Is there only one page? Hm... nothing more to do here then.
		if ($num_pages == 1) {
			return '';
		}

		// Set the base page index for starting page number
		if ($this->use_page_numbers) {
			$base_page = 1;
		} else {
			$base_page = 0;
		}

		// get current page from url here
		// todo

		// Set current page to 1 if using page numbers instead of offset
		if ($this->use_page_numbers and $this->cur_page == 0) {
			$this->cur_page = $base_page;
		}

		$this->num_links = ( int ) $this->num_links;

		if ($this->num_links < 1) {
			throw new ErrorException ( 'Your number of links must be a positive number.' );
		}

		if (! is_numeric ( $this->cur_page )) {
			$this->cur_page = $base_page;
		}

		// Is the page number beyond the result range?
		// If so we show the last page
		if ($this->use_page_numbers) {
			if ($this->cur_page > $num_pages) {
				$this->cur_page = $num_pages;
			}
		} else {
			if ($this->cur_page > $this->total_rows) {
				$this->cur_page = ($num_pages - 1) * $this->per_page;
			}
		}

		$uri_page_number = $this->cur_page;

		if (! $this->use_page_numbers) {
			$this->cur_page = floor ( ($this->cur_page / $this->per_page) + 1 );
		}
		$output='';
		// Calculate the start and end numbers. These determine
		// which number to start and end the digit links with
		$start = (($this->cur_page - $this->num_links) > 0) ? $this->cur_page - ($this->num_links - 1) : 1;
		$end = (($this->cur_page + $this->num_links) < $num_pages) ? $this->cur_page + $this->num_links : $num_pages;

		// Render the "First" link
		if ($this->first_link !== FALSE and $this->cur_page > ($this->num_links + 1)) {
			$first_url = ($this->first_url == '') ? $this->base_url : $this->first_url;
			if (! empty ( $this->query ))
				$first_url .= $query;
			$output .= $this->first_tag_open . '<a ' . $first_anchor_class . 'href="' . $first_url . '">' . $this->_first_tag_open . $this->first_link . $this->_first_tag_close . '</a>' . $this->first_tag_close;
		}

		// Render the "previous" link
		if ($this->prev_link !== FALSE and $this->cur_page != 1) {
			if ($this->use_page_numbers) {
				$i = $uri_page_number - 1;
			} else {
				$i = $uri_page_number - $this->per_page;
			}

			if ($i == 0 && $this->first_url != '') {
				$output .= $this->prev_tag_open . '<a ' . $this->anchor_class . 'href="' . $this->first_url . $query . '">' . $this->_prev_tag_open . $this->prev_link . $this->_prev_tag_close . '</a>' . $this->prev_tag_close;
			} else {
				$i = ($i == 0) ? '' : $this->prefix . $i . $this->suffix;
				$output .= $this->prev_tag_open . '<a ' . $this->anchor_class . 'href="' . $this->base_url . $i . $query . '">' . $this->_prev_tag_open . $this->prev_link . $this->_prev_tag_close . '</a>' . $this->prev_tag_close;
			}
		}

		// Render the pages
		if ($this->display_pages !== FALSE) {
			// Write the digit links
			for($loop = $start - 1; $loop <= $end; $loop ++) {
				if ($this->use_page_numbers) {
					$i = $loop;
				} else {
					$i = ($loop * $this->per_page) - $this->per_page;
				}

				if ($i >= $base_page) {
					if ($this->cur_page == $loop) {
						$output .= $this->cur_tag_open . $loop . $this->cur_tag_close; // Current page
					} else {
						$n = ($i == $base_page) ? '' : $i;

						if ($n == '' && $this->first_url != '') {
							$output .= $this->num_tag_open . '<a ' . $this->anchor_class . 'href="' . $this->first_url . '">' . $this->_num_tag_open . $loop . $this->_num_tag_close . '</a>' . $this->num_tag_close;
						} else {
							$n = ($n == '') ? '' : $this->prefix . $n . $this->suffix;
							$output .= $this->num_tag_open . '<a ' . $this->anchor_class . 'href="' . $this->base_url . $n . $query . '">' . $this->_num_tag_open . $loop . $this->_num_tag_close . '</a>' . $this->num_tag_close;
						}
					}
				}
			}
		}

		// Render the "next" link
		if ($this->next_link !== FALSE and $this->cur_page < $num_pages) {
			if ($this->use_page_numbers) {
				$i = $this->cur_page + 1;
			} else {
				$i = ($this->cur_page * $this->per_page);
			}

			$output .= $this->next_tag_open . '<a ' . $this->anchor_class . 'href="' . $this->base_url . $this->prefix . $i . $this->suffix . $query . '">' . $this->_next_tag_open . $this->next_link . $this->_next_tag_close . '</a>' . $this->next_tag_close;
		}

		// Render the "Last" link
		if ($this->last_link !== FALSE and ($this->cur_page + $this->num_links) < $num_pages) {
			if ($this->use_page_numbers) {
				$i = $num_pages;
			} else {
				$i = (($num_pages * $this->per_page) - $this->per_page);
			}
			$output .= $this->last_tag_open . '<a ' . $last_anchor_class . 'href="' . $this->base_url . $this->prefix . $i . $this->suffix . $query . '">' . $this->_last_tag_open . $this->last_link . $this->_last_tag_close . '</a>' . $this->last_tag_close;
		}

		// restore $this->cur_page
		if (! $this->use_page_numbers) {
			$this->cur_page = ( int ) (($this->cur_page - 1) * $this->per_page);
		}

		// Kill double slashes. Note: Sometimes we can end up with a double slash
		// in the penultimate link so we'll kill all double slashes.
		$output = preg_replace ( "#([^:])//+#", "\\1/", $output );

		// Add the wrapper HTML if exists
		$output = $this->full_tag_open . $output . $this->full_tag_close;

		return $output;
	}
}
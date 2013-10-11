<?php
/**
 * Module for post-processing page content after compilation, prior to caching, that looks for video URLs from sites like YouTube and Vimeo and turns them into
 * embedded video player code. Provides a super simple way to embed videos without having to worry about RTE support or HTML purification stripping out the code.
 *
 * @package Modules
 * @author Peter Epp
 */
class VideoLinkEmbedder extends AbstractModuleController {
	/**
	 * Width of embedded player
	 *
	 * @var int
	 */
	private $_player_width = 516;
	/**
	 * Height of embedded player
	 *
	 * @var int
	 */
	private $_player_height = 344;
	/**
	 * Set the size of the video player from options, if provided, otherwise leave on defaults, then call parent constructor
	 *
	 * @author Peter Epp
	 */
	public function run() {
		if (defined('VIDEO_PLAYER_WIDTH') && (int)VIDEO_PLAYER_WIDTH > 0) {
			$this->_player_width = (int)VIDEO_PLAYER_WIDTH;
		}
		if (defined('VIDEO_PLAYER_HEIGHT') && (int)VIDEO_PLAYER_HEIGHT > 0) {
			$this->_player_height = (int)VIDEO_PLAYER_HEIGHT;
		}
	}
	/**
	 * On "index" or "show" action, search compiled content for YouTube anchor tags or URLs and replace with embedded video players.
	 *
	 * @param string $compiled_content 
	 * @return void
	 * @author Peter Epp
	 */
	protected function act_on_content_compiled() {
		$compiled_content = $this->Biscuit->get_compiled_content();
		$base_action = $this->base_action_name($this->action());
		if ($base_action != 'edit') {
			$compiled_content = $this->convert_youtube_links($compiled_content);
			$compiled_content = $this->convert_vimeo_links($compiled_content);
			$this->Biscuit->set_compiled_content($compiled_content);
		}
	}
	/**
	 * Convert YouTube links into embedded players
	 *
	 * @param string $compiled_content 
	 * @return string
	 * @author Peter Epp
	 */
	private function convert_youtube_links($compiled_content) {
		// Replace full anchor tags with just URLs:
		$pattern = '/<a[^>]+href=[\'\"](?:http:\/\/)?((?:[a-zA-Z]{1,4}\.)?youtube.com\/(?:watch)?\\?v=(.{11}?))[^"\']*[\'"][^>]*>([^<]*)?<\/a>/si';
		preg_match_all($pattern,$compiled_content,$matches);
		if (!empty($matches) && !empty($matches[0])) {
			$full_anchors = $matches[0];
			$urls_only = $matches[1];
			foreach ($full_anchors as $index => $anchor_tag) {
				$compiled_content = str_replace($anchor_tag,'http://'.$urls_only[$index],$compiled_content);
			}
		}
		// Replace URLs with embedded video players:
		$pattern = '/(?:http:\/\/)?((?:[a-zA-Z]{1,4}\.)?youtube.com\/(?:watch)?\\?v=(.{11}?))[^\s\t\r\n<\.]*/si';
		preg_match_all($pattern,$compiled_content,$matches);
		$last_vid_id = '';
		if (!empty($matches) && !empty($matches[0])) {
			$full_urls = $matches[0];
			$video_ids = $matches[2];
			// First replace duplicates with an anchor tag that will jump to named anchor for the embedded video:
			foreach ($full_urls as $index => $url) {
				if ($video_ids[$index] == $last_vid_id) {
					$compiled_content = str_replace($url,'<a href="#youtube_vid_'.$video_ids[$index].'">Jump to Video</a>',$compiled_content);
				}
				$last_vid_id = $video_ids[$index];
			}
			// Now replace remaining URLs with embedded videos:
			foreach ($full_urls as $index => $url) {
				$compiled_content = str_replace($url,'<a name="youtube_vid_'.$video_ids[$index].'"></a><object width="'.$this->_player_width.'" height="'.$this->_player_height.'"><param name="movie" value="http://www.youtube.com/v/'.$video_ids[$index].'&amp;hl=en&amp;fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/'.$video_ids[$index].'&amp;hl=en&amp;fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="'.$this->_player_width.'" height="'.$this->_player_height.'"></embed></object>',$compiled_content);
			}
		}
		return $compiled_content;
	}
	/**
	 * Convert Vimeo links into embedded players
	 *
	 * @param string $compiled_content 
	 * @return string
	 * @author Peter Epp
	 */
	private function convert_vimeo_links($compiled_content) {
		// Replace full anchor tags with just URLs:
		$pattern = '/<a[^>]+href=[\'\"](?:http:\/\/)?((?:[a-zA-Z]{1,4}\.)?vimeo.com\/([0-9]+))[^"\']*[\'"][^>]*>([^<]*)?<\/a>/si';
		preg_match_all($pattern,$compiled_content,$matches);
		if (!empty($matches) && !empty($matches[0])) {
			$full_anchors = $matches[0];
			$urls_only = $matches[1];
			foreach ($full_anchors as $index => $anchor_tag) {
				$compiled_content = str_replace($anchor_tag,'http://'.$urls_only[$index],$compiled_content);
			}
		}
		// Replace URLs with embedded video players:
		$pattern = '/(?:http:\/\/)?((?:[a-zA-Z]{1,4}\.)?vimeo.com\/([0-9]+))[^\s\t\r\n<\.]*/si';
		preg_match_all($pattern,$compiled_content,$matches);
		$last_vid_id = '';
		if (!empty($matches) && !empty($matches[0])) {
			$full_urls = $matches[0];
			$video_ids = $matches[2];
			// First replace duplicates with an anchor tag that will jump to named anchor for the embedded video:
			foreach ($full_urls as $index => $url) {
				if ($video_ids[$index] == $last_vid_id) {
					$compiled_content = str_replace($url,'<a href="#vimeo_vid_'.$video_ids[$index].'">Jump to Video</a>',$compiled_content);
				}
				$last_vid_id = $video_ids[$index];
			}
			// Now replace remaining URLs with embedded videos:
			foreach ($full_urls as $index => $url) {
				$compiled_content = str_replace($url,'<a name="vimeo_vid_'.$video_ids[$index].'"></a><object width="'.$this->_player_width.'" height="'.$this->_player_height.'"><param name="allowfullscreen" value="true" /><param name="allowscriptaccess" value="always" /><param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id='.$video_ids[$index].'&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" /><embed src="http://vimeo.com/moogaloop.swf?clip_id='.$video_ids[$index].'&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" width="'.$this->_player_width.'" height="'.$this->_player_height.'"></embed></object>',$compiled_content);
			}
		}
		return $compiled_content;
	}
	public static function install_migration() {
		$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'VideoLinkEmbedder'");
		// Ensure clean install
		DB::query("DELETE FROM `module_pages` WHERE `module_id` = ?",$module_id);
		DB::insert("INSERT INTO `module_pages` SET `module_id` = ?, `page_name` = '*', `is_primary` = 0",$module_id);
		DB::query("REPLACE INTO `system_settings` (`constant_name`, `friendly_name`, `description`, `value`, `value_type`, `required`) VALUES
		('VIDEO_PLAYER_WIDTH', 'Video Player Width', 'Should be set to the width of the content area in pixels. Defaults to 516 if left blank.', '', NULL, 0, 'Video Link Embedder'),
		('VIDEO_PLAYER_HEIGHT', 'Video Player Height', 'Should be set to a height proportional to the width at a 16x9 widescreen ratio for best results. Defaults to 344 if left blank.', '', NULL, 0, 'Video Link Embedder')");
	}
	public static function uninstall_migration() {
		$module_id = DB::fetch_one("SELECT `id` FROM `modules` WHERE `name` = 'VideoLinkEmbedder'");
		DB::query("DELETE FROM `module_pages` WHERE `module_id` = ?",$module_id);
		DB::query("DELETE FROM `system_settings` WHERE `constant_name` LIKE 'VIDEO_PLAYER_%'");
	}
}
?>
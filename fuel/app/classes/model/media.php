<?php

namespace Model;

class CommentMediaNotFound extends \FuelException {}

class CommentMediaHashNotFound extends CommentMediaNotFound {}
class CommentMediaDirNotAvailable extends CommentMediaNotFound {}
class CommentMediaFileNotFound extends CommentMediaNotFound {}
class CommentMediaHidden extends CommentMediaNotFound {}
class CommentMediaHiddenDay extends CommentMediaNotFound {}



class Media extends \Model\Model_Base
{
	public $media_id = 0;
	public $spoiler = 0;
	public $preview_orig = null;
	public $preview_w = 0;
	public $preview_h = 0;
	public $media_filename = null;
	public $media_w = 0;
	public $media_h = 0;
	public $media_size = 0;
	public $media_hash = null;
	public $media_orig = null;
	public $exif = null;

	public $_comment = null;

	private static $_fields = array(
		'media_id',
		'spoiler',
		'preview_orig',
		'preview_w',
		'preview_h',
		'media_filename',
		'media_w',
		'media_h',
		'media_size',
		'media_hash',
		'media_orig',
		'exif'
	);

	protected static function p_get_fields()
	{
		return static::$_fields;
	}


	public function __construct($comment)
	{
		$this->comment = $comment;
		$this->board = $comment->board;

		foreach($comment as $key => $item)
		{
			if(in_array($key, static::$_fields))
			{
				$this->$key = $item;
			}
		}

		if ($this->board->archive)
		{
			// archive entries for media_filename are already encoded and we risk overencoding
			$this->media_filename = html_entity_decode($this->media_filename, ENT_QUOTES, 'UTF-8');
		}

		// let's unset 0 sizes so maybe the __get() can save the day
		if ($this->preview_w === 0 || $this->preview_h === 0)
		{
			unset($this->preview_w, $this->preview_h);
		}
	}


	protected static function p_forge_from_comment($comment)
	{
		// if this comment doesn't have media data
		if (!isset($comment->media_id) || !$comment->media_id)
		{
			return null;
		}

		return new Media($comment);
	}


	public function __get($name)
	{
		switch ($name)
		{
			case 'safe_media_hash':
				return $this->safe_media_hash = $this->get_media_hash(true);
			case 'remote_media_link':
				return $this->remote_media_link = $this->get_remote_media_link();
			case 'media_link':
				return $this->media_link = $this->get_media_link();
			case 'thumb_link':
				return $this->thumb_link = $this->get_media_link(true);
			case 'preview_w':
			case 'preview_h':
				if ($this->board->archive && $this->spoiler)
				{
					try
					{
						$imgsize = \Cache::get('comment.'.$this->board->id.'.'.$this->doc_id.'_spoiler_size');
					}
					catch (\CacheNotFoundException $e)
					{
						$imgpath = $this->get_media_dir(true);
						$imgsize = false;

						if ($imgpath)
						{
							$imgsize = @getimagesize($imgpath);
						}

						\Cache::set('comment.'.$this->board->id.'.'.$this->doc_id.'_spoiler_size', $imgsize, 86400);

						if ($imgsize !== FALSE)
						{
							$post->preview_h = $imgsize[1];
							$post->preview_w = $imgsize[0];
						}

						return $this->$name;
					}
				}
				$this->preview_w = 0;
				$this->preview_h = 0;
				return 0;
		}

		if (substr($name, -10) === '_processed')
		{
			$processing_name = substr($name, 0, strlen($name) - 10);
			return $this->$name = e(@iconv('UTF-8', 'UTF-8//IGNORE', $this->$processing_name));
		}

		return null;
	}


	/**
	 * Get the path to the media
	 *
	 * @param bool $thumbnail if we're looking for a thumbnail
	 * @return bool|string FALSE if it has no image in database, string for the path
	 */
	private function p_get_media_dir($thumbnail = false)
	{
		if (!$this->media_hash)
		{
			throw new \CommentMediaHashNotFound;
		}

		if ($thumbnail === true)
		{
			if ($this->op == 1)
			{
				$image = $this->preview_op ? $this->preview_op : $this->preview_reply;
			}
			else
			{
				$image = $this->preview_reply ? $this->preview_reply : $this->preview_op;
			}
		}
		else
		{
			$image = $this->media;
		}

		// if we don't check, the return will return a valid folder that will evaluate file_exists() as TRUE
		if (is_null($image))
		{
			throw new \CommentMediaDirNotAvailable;
		}

		return Preferences::get('fu.boards_directory').'/'.$this->board->shortname.'/'
			.($thumbnail ? 'thumb' : 'image').'/'.substr($image, 0, 4).'/'.substr($image, 4, 2).'/'.$image;
	}


	/**
	 * Get the full URL to the media, and in case switch between multiple CDNs
	 *
	 * @param object $board
	 * @param object $post the database row for the post
	 * @param bool $thumbnail if it's a thumbnail we're looking for
	 * @return bool|string FALSE on not found, a fallback image if not found for thumbnails, or the URL on success
	 */
	private function p_get_media_link($thumbnail = false)
	{
		if (!$this->media_hash)
		{
			throw new \CommentMediaHashNotFound;
		}

		$this->media_status = 'available';

		// these features will only affect guest users
		if ($this->board->hide_thumbnails && !\Auth::has_access('comment.show_hidden_thumbnails'))
		{
			// hide all thumbnails for the board
			if (!$this->board->hide_thumbnails)
			{
				$this->media_status = 'forbidden';
				throw \CommentMediaHidden;
			}

			// add a delay of 1 day to all thumbnails
			if ($this->board->delay_thumbnails && ($this->timestamp + 86400) > time())
			{
				$this->media_status = 'forbidden-24h';
				throw \CommentMediaHiddenDay;
			}
		}

		// this post contain's a banned media, do not display
		if ($this->banned == 1)
		{
			$this->media_status = 'banned';
			throw \CommentMediaBanned;
		}

		// locate the image
		if ($thumbnail && file_exists($this->get_media_dir($thumbnail)) !== false)
		{
			if ($post->op == 1)
			{
				$image = $this->preview_op ? : $this->preview_reply;
			}
			else
			{
				$image = $this->preview_reply ? : $this->preview_op;
			}
		}

		// full image
		if (!$thumbnail && file_exists($this->get_media_dir(false)))
		{
			$image = $this->media;
		}

		// fallback if we have the full image but not the thumbnail
		if ($thumbnail && !isset($image) && file_exists($this->get_media_dir(false)))
		{
			$thumbnail = FALSE;
			$image = $this->media;
		}

		if(isset($image))
		{
			$media_cdn = array();
			if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' && Preferences::get('fu.boards_media_balancers_https'))
			{
				$balancers = Preferences::get('fu.boards_media_balancers_https');
			}

			if (!isset($balancers) && Preferences::get('fu.boards_media_balancers'))
			{
				$balancers = Preferences::get('fu.boards_media_balancers');
			}

			if(isset($balancers))
			{
				$media_cdn = array_filter(preg_split('/\r\n|\r|\n/', $balancers));
			}

			if(!empty($media_cdn) && $this->media_id > 0)
			{
				return $media_cdn[($this->media_id % count($media_cdn))] . '/' . $this->board->shortname . '/'
					. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
			}

			return Preferences::get('fu.boards_url', \Uri::base()) . '/' . $this->board->shortname . '/'
				. ($thumbnail ? 'thumb' : 'image') . '/' . substr($image, 0, 4) . '/' . substr($image, 4, 2) . '/' . $image;
		}

		$this->media_status = 'not-available';
		return FALSE;
	}


	/**
	 * Get the remote link for media if it's not local
	 *
	 * @return bool|string FALSE if there's no media, local URL if it's not remote, or the remote URL
	 */
	private function p_get_remote_media_link()
	{
		if (!$this->media_hash)
		{
			throw new \CommentMediaHashNotFound;
		}

		if ($this->board->archive && $this->board->images_url != "")
		{
			// ignore webkit and opera user agents
			if (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(opera|webkit)/i', $_SERVER['HTTP_USER_AGENT']))
			{
				return $this->board->images_url . $this->media_orig;
			}

			return \Uri::create(array($this->board->shortname, 'redirect')) . $this->media_orig;
		}
		else
		{
			if (file_exists($this->get_media_dir()) !== false)
			{
				return $this->get_media_link();
			}
			else
			{
				return false;
			}
		}
	}


	/**
	 * Get the post's media hash
	 *
	 * @param mixed $media
	 * @param bool $urlsafe if TRUE it will return a modified base64 compatible with URL
	 * @return bool|string FALSE if media_hash not found, or the base64 string
	 */
	private function p_get_media_hash($urlsafe = FALSE)
	{
		if (is_object($this) || is_array($this))
		{
			if (!$this->media_hash)
			{
				throw new \CommentMediaHashNotFound;
			}

			$media_hash = $this->media_hash;
		}
		else
		{
			if (strlen(trim($media_hash)) == 0)
			{
				return FALSE;
			}
		}

		// return a safely escaped media hash for urls or un-altered media hash
		if ($urlsafe === TRUE)
		{
			return static::urlsafe_b64encode(static::urlsafe_b64decode($media_hash));
		}
		else
		{
			return base64_encode(static::urlsafe_b64decode($media_hash));
		}
	}


	/**
	 * Delete media for the selected post
	 *
	 * @param bool $media if full media should be deleted
	 * @param bool $thumb if thumbnail should be deleted
	 * @return bool TRUE on success or if it didn't exist in first place, FALSE on failure
	 */
	private function p_delete_media($media = true, $thumb = true)
	{
		if (!$this->media_hash)
		{
			throw new \CommentMediaHashNotFound;
		}

		// delete media file only if there is only one image OR the image is banned
		if ($this->total == 1 || $this->banned == 1 || \Auth::has_access('comment.passwordless_deletion'))
		{
			if ($media === true)
			{
				$media_file = $this->get_media_dir();
				if (file_exists($media_file))
				{
					if (!unlink($media_file))
					{
						throw new \CommentMediaFileNotFound;
					}
				}
			}

			if ($thumb === true)
			{
				$temp = $this->op;

				// remove OP thumbnail
				$this->op = 1;
				$thumb_file = $this->get_media_dir(true);
				if (file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						throw new \CommentMediaFileNotFound;
					}
				}

				// remove reply thumbnail
				$this->op = 0;
				$thumb_file = $this->get_media_dir(TRUE);
				if (file_exists($thumb_file))
				{
					if (!unlink($thumb_file))
					{
						throw new \CommentMediaFileNotFound;
					}
				}

				$this->op = $temp;
			}
		}
	}
}
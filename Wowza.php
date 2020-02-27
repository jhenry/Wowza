<?php

class Wowza extends PluginAbstract
{
  /**
   * @var string Name of plugin
   */
  public $name = 'Wowza';

  /**
   * @var string Description of plugin
   */
  public $description = 'Adds support for Wowza streaming engine integration. Based on work by Wes Wright.';

  /**
   * @var string Name of plugin author
   */
  public $author = 'Justin Henry';

  /**
   * @var string URL to plugin's website
   */
  public $url = 'https://uvm.edu/~jhenry/';

  /**
   * @var string Current version of plugin
   */
  public $version = '0.0.3';


  /**
   * Performs install operations for plugin. Called when user clicks install
   * plugin in admin panel.
   *
   * TODO: Check for existence of homedirectory and/or LDAP plugin.
   * 
   */
  public function install()
  {
  }

  /**
   * The plugin's gateway into codebase. Place plugin hook attachments here.
   */
  public function load()
  {
    Wowza::set_asset_directories();
    Plugin::attachEvent('app.start', array(__CLASS__, 'get_upload_path'));

    if (!headers_sent() && session_id() == '') {
      session_start();
    }

    // Make sure homedir is set on any of these pages.
    Plugin::attachEvent('account.start', array(__CLASS__, 'set_homedirectory_session'));
    Plugin::attachEvent('upload_video.start', array(__CLASS__, 'set_homedirectory_session'));
    Plugin::attachEvent('upload.start', array(__CLASS__, 'set_homedirectory_session'));
    Plugin::attachEvent('edit_video.start', array(__CLASS__, 'set_homedirectory_session'));
  }

  /**
   * Set default upload path.  Requires changes to bootstrap.php.
   */
  public function get_upload_path()
  {
    Wowza::set_encoder_path();
    Wowza::set_path_from_session();
  }

  /**
   * 
   */
  public function set_path_from_session()
  {
    // If we're logged in we'll set it from session data.
    if (isset($_SESSION['homeDirectory'])) {
      $homedirectory = $_SESSION['homeDirectory'];
      Wowza::set_upload_path($homedirectory);
    }

  }

  /**
   * When the application is running via command-line in the encoder, 
   * use the video id to set the upload path.
   */
  public function set_encoder_path()
  {
    // Get encoder command line vars
    $arguments = getopt('', array('video:', 'import::'));
    $video_id = $arguments['video'] ?? false;

    // If we are in the encoder, set upload path by looking at the video's owner.
    if ($video_id) {
      $homedirectory = Wowza::get_video_owner_homedir($video_id);
      Wowza::set_upload_path($homedirectory);
    }
  }

  /**
   * Set default upload path.  Requires changes to bootstrap.php.
   */
  public function set_upload_path($homedirectory)
  {
    $wowza_root = Settings::get('wowza_upload_dir');

    $config = Registry::get('config');
    $config->default_upload_path = $wowza_root . $homedirectory;
    Registry::set('config', $config);
    Wowza::initialize_directories($config->default_upload_path);
  }

  /**
   * Create directories in the upload path.
   */
  public function initialize_directories($path)
  {
    $dirs = Wowza::get_asset_directories();
    foreach( $dirs as $directory) {
      Filesystem::createDir($path . $directory);
    }
  }

  /**
   * Set homedirectory session vars.
   */
  public function set_homedirectory_session()
  {
    $authService = new AuthService();
    $user = $authService->getAuthUser();
    if ($user) {
      $_SESSION['homeDirectory'] = Wowza::get_user_homedirectory($user->userId);
    }
  }

  /**
   * Get user homedirectory from their ID.
   * 
   * @param int $user_id id of the user whose homedir we are querying.
   */
  public function get_user_homedirectory($user_id)
  {
    if (class_exists('ExtendedUser')) 
    {
      $extendedUser = new ExtendedUser();
      $meta =  $extendedUser::get_meta($user_id, 'homeDirectory');
      return $meta->meta_value;
    }
    else {
      throw new Exception('Dependency Error: This plugin requires ExtendedUser plugin.');
      return false;
    }
  }

  /**
   * Get the home directory for the user who created the video.
   * 
   */
  public function get_video_owner_homedir($video_id)
  {
    $vmapper = new VideoMapper();
    $video = $vmapper->getVideoById($video_id);
    return Wowza::get_user_homedirectory($video->userId);
  }

  /**
   * Get a list of url fragments/directory names for 
   * assets such as thumbnails, avatars, etc.
   * 
   */
  public function get_asset_directories()
  {
    return json_decode(Settings::get('wowza_asset_dirs'));
  }

  /**
   * Set up paths for url fragments/directory names.
   * 
   */
  public function set_asset_directories()
  {
    $paths = array(
      "temp" => "/temp/",
      "h264" => "/h264/",
      "hd" => "/HD720/",
      "thumbs" => "/thumbs/",
      "mobile" => "/mobile/",
      "avatars" => "/avatars/",
      "mp3" => "/mp3/",
      "attachments" => "/files/attachments/"
    );

    Settings::set('wowza_asset_dirs', json_encode($paths));

  }


  /**
   * Outputs the settings page HTML and handles form posts on the plugin's
   * settings page.
   */
  public function settings()
  {
    $data = array();
    $errors = array();
    $message = null;

    // Retrieve settings from database
    $data['wowza_upload_dir'] = Settings::get('wowza_upload_dir');
    $data['wowza_rtmp_host'] = Settings::get('wowza_rtmp_host');
    $data['wowza_url_path'] = Settings::get('wowza_url_path');

    // Handle form if submitted
    if (isset($_POST['submitted'])) {
      // Validate form nonce token and submission speed
      $is_valid_form = Wowza::_validate_form_nonce();

      if ($is_valid_form) {
        // Validate wowza base upload dir
        if (!empty($_POST['wowza_upload_dir'])) {
          $data['wowza_upload_dir'] = trim($_POST['wowza_upload_dir']);
        } else {
          $errors['wowza_upload_dir'] = 'Invalid Wowza upload directory. ';
        }
        // Validate wowza rtmp host
        if (!empty($_POST['wowza_rtmp_host'])) {
          $data['wowza_rtmp_host'] = trim($_POST['wowza_rtmp_host']);
        } else {
          $errors['wowza_rtmp_host'] = 'Invalid Wowza upload directory. ';
        }
        // Validate wowza url path
        if (!empty($_POST['wowza_url_path'])) {
          $data['wowza_url_path'] = trim($_POST['wowza_url_path']);
        } else {
          $errors['wowza_url_path'] = 'Invalid Wowza upload directory. ';
        }
      } else {
        $errors['session'] = 'Expired or invalid session';
      }

      // Error check and update data
      Wowza::_handle_settings_form($data, $errors);
    }
    // Generate new form nonce
    $formNonce = md5(uniqid(rand(), true));
    $_SESSION['formNonce'] = $formNonce;
    $_SESSION['formTime'] = time();

    // Display form
    include(dirname(__FILE__) . '/settings_form.php');
  }

  /**
   * Check for form errors and save settings
   * 
   */
  private function _handle_settings_form($data, $errors)
  {
    if (empty($errors)) {
      foreach ($data as $key => $value) {
        Settings::set($key, $value);
      }
      $message = 'Settings have been updated.';
      $message_type = 'alert-success';
    } else {
      $message = 'The following errors were found. Please correct them and try again.';
      $message .= '<br /><br /> - ' . implode('<br /> - ', $errors);
      $message_type = 'alert-danger';
    }
  }

  /**
   * Validate settings form nonce token and submission speed
   * 
   */
  private function _validate_form_nonce()
  {
    if (
      !empty($_POST['nonce'])
      && !empty($_SESSION['formNonce'])
      && !empty($_SESSION['formTime'])
      && $_POST['nonce'] == $_SESSION['formNonce']
      && time() - $_SESSION['formTime'] >= 2
    ) {
      return true;
    } else {
      return false;
    }
  }
}

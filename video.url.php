<?php
$this->view->options->disableView = true;

$videoId = $_GET['videoId'] ?? false;
$assetType = $_GET['assetType'] ?? false;

if ($videoId && $assetType) {
  if (class_exists('CustomThumbs')) { 
    $result = CustomThumbs::thumb_url($videoId);
  }
  else {
    $videoMapper = new VideoMapper();
    $video = $videoMapper->getVideoById($videoId);
    $result = Wowza::get_url_by_video_id($videoId, $assetType) . $video->filename . ".jpg";
  }
}
else {
  $result = false;
}

if ($result) 
{
  echo $result;
}
else 
{
  echo "bad_request";
}

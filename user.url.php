<?php
$this->view->options->disableView = true;

$userId = $_GET['userId'] ?? false;
$assetType = $_GET['assetType'] ?? false;

if ($userId && $assetType) {
  $userMapper = new UserMapper();
  $user = $userMapper->getUserById($userId);
  if ($user->avatar) { 
    $result = Wowza::get_url_by_user_id($userId, $assetType) . $user->avatar;
  }
  else {
    $result = false;
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

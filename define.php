<?php

define("ROOT", str_replace('\\', '/', dirname(__FILE__)) . '/');
define("DOMAIN", '127.0.0.1');
define("APP_ROOT", '/');
define("TPL_HOME", ROOT . 'templates/');
define("INCLUDE_HOME", ROOT . 'include/');
define("BATCH_HOME", 'batch/');

define("IMG_ROOT", 'upload/img/');
define("GALLERY_ROOT", APP_ROOT.'upload/img/');

define("IMAGES_TABLE", 'images');
define("IMAGE_TAGS_TABLE", 'image_tags');
define("TAGS_TABLE", 'tags');
define("USERS_TABLE", 'users');
define("SESSIONS_TABLE", 'sessions');

define("CACHE_TIMEOUT", 1);

define("PAGE_LIMIT", 30);
define("TAG_CLOUD_LIMIT", 50);

define('PHPBB_DATABASE', 'forum1');
define('PHPBB_PREFIX', 'forum1_');

define('DB_USER','root');
define('DB_PASS','');
define('DB_NAME','gallery');

define('ADMIN_GROUP', '5');

define('SESSION_LIFETIME', 31536000); //seconds
?>

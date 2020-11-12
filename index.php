<?php

if (preg_match('/\.(?:png|jpg|jpeg|gif|css)$/', $_SERVER["REQUEST_URI"])) {
    return false;    // serve the requested resource as-is.
} else {

//ini_set('error_reporting', E_ALL & ~E_NOTICE);
require_once('define.php');
//require_once(INCLUDE_HOME."Session.php");
require_once(INCLUDE_HOME . "LoginManager.php");
require(INCLUDE_HOME . "CachedTemplate.php");
require(INCLUDE_HOME . "ImageManager.php");
require(INCLUDE_HOME . "Database.php");

class Index {

    var $tpl, $im, $lm, $db;

    function Index() {
        $this->db = new Database('127.0.0.1', DB_USER, DB_PASS, DB_NAME);
    }

    function render() {
        global $session;
        //print_r($session);

        $this->tpl = new CachedTemplate(TPL_HOME . 'index.html', $_SERVER['REQUEST_URI'], CACHE_TIMEOUT); // this is the outer template
        $this->im = new ImageManager($this->db);
        $this->lm = new LoginManager($this->db);

        //$body = &new CachedTemplate(TPL_HOME . 'body.html', $_SERVER['REQUEST_URI'], 0); // This is the inner template

        /*
         * The get_list() method of the User class simply runs a query on
         * a database - nothing fancy or complex going on here.
         */
        //if (!$body->is_cached()) {
        //    $body->set('users', array(array("name" => 'joe', "email" => 'lol', "banned" => 1),
        //        array("name" => 'peter', "email" => 'lol', "banned" => 1),
        //        array("name" => 'cleveland', "email" => 'lol', "banned" => 1)));
        //}
        //mode

        $start = intval($_REQUEST['s']);

        switch ($_REQUEST['m']) {
            // action handler
        case 'a':
            switch ($_REQUEST['a']) {
            case 'uploadnewfile':
                if (!$session) {
                    die('You have to log in!');
                }

                $file = $_FILES['uploadedfile'];
                $extension = substr($file['name'], strrpos($file['name'], '.') + 1);

                if (!checkAllowedFile($extension)) {
                    die('wrong ext');
                }

                // $_REQUEST['dir']
                $status = $this->im->saveImage($_REQUEST['title'], $_REQUEST['dir'], $session['userId'], $file, $_REQUEST['tags']);
                $this->genUploaded($status);
                //echo '<meta HTTP-EQUIV="REFRESH" content="1; url='.$_SERVER['PHP_SELF'].'">';

break;
// handle batch upload
            case 'batch':
                if (!$session['userPrivs']) {
                    die('Access denied!');
                }

                foreach ($_POST as $post => $tags) {
                    //println($post);
                    $save_dir = str_replace('\\', '/', $_POST['save_directory']);
                    $save_dir = strtolower($save_dir);
                    if (strstrb($post, '_') === 'tag') {
                        //println('Have tag');
                        //println($post);
                        $basename = substr(strstr($post, '_'), 1);
                        $basename_org = $basename;
                        $last_ = strrpos($basename, '_');
                        $basename = substr_replace($basename, '.', $last_, 1);
                        $basename = urldecode($basename);
                        //println($basename);
                        $extension = substr($basename, strrpos($basename, '.') + 1);
                        if (checkAllowedFile($extension)) {
                            $file['name'] = $basename;
                            $file['tmp_name'] = ROOT . $_POST['dir'] . '/' . $file['name'];
                            $file['batch'] = true;

                            $title = $_POST['title_' . $basename_org];
                            $all_tags = $_POST['common_tags'] . " " . $tags;
                            if (!$title) {
                                $title = $all_tags;
                            }

                            println('=========================================');
                            println('name: ' . $file['name']);
                            //println('tmp_name '.$file['tmp_name']);
                            println('title: ' . $title);
                            println('tags: ' . $all_tags);

                            $re = $this->im->saveImage($title, $save_dir,
                                                       $session['userId'], $file, $all_tags);
                            if ($re) {
                                println('Uploaded successfuly!');
                            } else {
                                println('Upload failed!');
                            }
                        }
                    }
                }
break;
            case 'editTags':
                if (!$session['userPrivs']) {
                    die('Access denied!');
                }
                $this->im->editTags($_REQUEST['id'], $_REQUEST['tags']);
                println('Image tags has been edited!');
                println('<a href="index.php">Back to index</a>');
break;
            case 'editTitle':
                if (!$session['userPrivs']) {
                    die('Access denied!');
                }
                $this->im->editTitle($_REQUEST['id'], $_REQUEST['title']);
                println('Image title has been edited!');
                println('<a href="index.php">Back to index</a>');
break;
            case 'login':
                $re = $this->lm->login($_POST['username'], $_POST['password'], true);
                if ($re === true) {
                    echo 'Login successful';
                } else {
                    echo $re . '<br>';
                }
break;
            case 'register':
                $re = $this->lm->register($_POST['username'], $_POST['password']);
                echo $re . '<br>';
break;
            default:
                $this->genDefault($this->im->getImages('date', $start, PAGE_LIMIT), $start,
                                  $this->im->getImagesNum());
            }
break;
// show image details
        case 'details':
            $this->genDetails();
break;
// search
        case 's':
            // q = search query
            $querystr = rawurldecode($_REQUEST['q']);
            $this->genDefault($this->im->getImagesByTags($querystr, $start, PAGE_LIMIT), $start,
                              $this->im->getImagesByTagsNum($querystr));
break;
// batch upload
        case 'b':
            if (!$session['userPrivs']) {
                die('You have to log in or you don\'t have permissions to access');
            }

            $dir = htmlspecialchars(rawurldecode($_REQUEST['d']));
            $dir = str_replace('.', '', $dir);
            $dir = BATCH_HOME . $dir;

            $this->genBatch($dir);
break;
// remove image
        case 'r':
            if (!$session['userPrivs']) {
                die('Access denied!');
            }
            $this->im->removeImage($_REQUEST['id']);
            println('Image has been removed!');
            println('<a href="index.php">Back to index</a>');
break;
// show the default gallery
        default:
            $this->genDefault($this->im->getImages('date', $start, PAGE_LIMIT), $start,
                              $this->im->getImagesNum());
        }

        echo $this->tpl->fetch_cache(TPL_HOME . 'index.html');
    }

    function genDefault($images, $start, $total) {
        global $session;
        //println($total);
        $tc = $this->im->getTagCloud();
        //print_r($tc);
        $tagCloud = new CachedTemplate(TPL_HOME . 'tagcloud.html', $_SERVER['REQUEST_URI'], CACHE_TIMEOUT);
        if (!$tagCloud->is_cached()) {
            $tagCloud->set('tags', $tc);
        }

        $pagination = new Template(TPL_HOME . 'pagination.html');
        $pagination->set('qstr', $_REQUEST['q']);
        $pagination->set('page', ceil($start / PAGE_LIMIT) + 1);
        $pagination->set('page_total', ceil($total / PAGE_LIMIT));

        $body = new CachedTemplate(TPL_HOME . 'basicGallery.html', $_SERVER['REQUEST_URI'], CACHE_TIMEOUT);
        if (!$body->is_cached()) {
            $body->set('pagination', $pagination);
            $body->set('images', $images);
            $body->set('page', ceil($start / PAGE_LIMIT) + 1);
            $body->set('page_total', ceil($total / PAGE_LIMIT));
            $body->set('qstr', $_REQUEST['q']);
        }

        if ($session) {
            $upload = new CachedTemplate(TPL_HOME . 'uploadNewFile.html', $_SERVER['REQUEST_URI'], CACHE_TIMEOUT);
        } else {
            $login = new CachedTemplate(TPL_HOME . 'login.html', $_SERVER['REQUEST_URI'], 9999999);
        }

        $this->tpl->set('query', $_REQUEST['q']);
        if (!$this->tpl->is_cached()) {
            $this->tpl->set('title', 'Gallery');
            $this->tpl->set('body', $body);
            $this->tpl->set('tagCloud', $tagCloud);
            $this->tpl->set('session', $session);

            if ($session) {
                $this->tpl->set('upload', $upload);
            } else {
                $this->tpl->set('login', $login);
            }
        }
    }

    function genDetails() {
        global $session;
        $body = new Template(TPL_HOME . 'details.html');

        $img = $this->im->getImage(intval($_REQUEST['id']));

        if (!$img) {
            die('Image not found');
        }
        $this->im->incViews($img->getImage_id());
        $body->set('session', $session);
        $body->set('img', $img);
        $body->set('qstr', $_REQUEST['q']);
        $body->set('page', ($_REQUEST['s'] ? $_REQUEST['s'] : 0));

        $this->tpl->set('body', $body);

        $this->tpl->set('session', $session);
        $this->tpl->set('query', $_REQUEST['q']);

        if (!$this->tpl->is_cached()) {
            $this->tpl->set('title', $img->getTitle());
        }
    }

    function genUploaded($status) {
        if ($status) {
            echo "The file " . basename($file['name']) .
                " has been uploaded<br>";
        } else {
            echo "There was an error uploading the file, please try again!<br>";
        }

        echo '<a href="index.php">Back to index</a>';
    }

    function genBatch($dir) {
        $body = new CachedTemplate(TPL_HOME . 'batchUpload.html', $_SERVER['REQUEST_URI'], CACHE_TIMEOUT);
        if (!$body->is_cached()) {
            //println($dir);
            $files = scandir(ROOT . $dir);
            $images = array();
            $dirs = array();
            foreach ($files as $file) {
                //if ($file != '.' && $file != '..') {
                //println(ROOT.BATCH_HOME.$file);
                if (is_dir(ROOT . BATCH_HOME . $file)) {
                    //println('dir!!!');
                    $dirs[] = $file;
                } else {
                    //$load = true;
                    $extension = substr($file, strrpos($file, '.') + 1);

                    //                    if (strcasecmp($extension,'jpg') != 0 &&
                    //                        strcasecmp($extension,'jpeg') != 0 &&
                    //                        strcasecmp($extension,'png') != 0 &&
                    //                        strcasecmp($extension,'gif') != 0) {
                    //                        $load = false;
                    //                    }
                    $load = checkAllowedFile($extension);

                    if ($load) {
                        $image['name'] = urlencode($file);
                        $image['path'] = $dir . '/' . $file;
                        $images[] = $image;
                    }
                }
                //}
            }

            $body->set('images', $images);
            $body->set('dirs', $dirs);
            $body->set('dir', $dir);
        }

        if (!$this->tpl->is_cached()) {
            $this->tpl->set('title', 'Batch upload');
            $this->tpl->set('body', $body);
        }
    }

}

function println($str) {
    echo $str . '<br>';
}

function checkAllowedFile($extension) {
    return!(strcasecmp($extension, 'jpg') != 0 &&
            strcasecmp($extension, 'jpeg') != 0 &&
            strcasecmp($extension, 'png') != 0 &&
            strcasecmp($extension, 'gif') != 0);
}

// pre PHP 5.3 hack. Returns the strstr BEFORE the needle
function strstrb($h, $n) {
    return array_shift(explode($n, $h, 2));
}

$time_start = microtime(true);
$index = new Index();
$db = $index->db;
$db->connect();

$session = session_resume();
$index->render();

$time_end = microtime(true);
$time = $time_end - $time_start;

echo "<br>Page generated in $time seconds\n";
}
?>

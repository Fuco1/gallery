<?php

require_once('define.php');

define('GET_SORTED_IMAGES_FROM_NUM',
        'SELECT * FROM ' . IMAGES_TABLE . ' ORDER BY :1 DESC LIMIT :2, :3');

define('GET_IMAGE_NUM',
        'SELECT COUNT(*) AS count FROM ' . IMAGES_TABLE);

define('GET_IMAGE_BY_ID',
        'SELECT * FROM ' . IMAGES_TABLE . ' WHERE image_id = :1');

define('GET_TAGS_BY_IMAGE_ID',
        'SELECT * FROM ' . TAGS_TABLE . ' t JOIN ' . IMAGE_TAGS_TABLE . ' i ON
    t.tag_id = i.tag_id WHERE i.image_id = :1');

define('SAVE_IMAGE',
        'INSERT INTO ' . IMAGES_TABLE . ' (title, path, filename, hash, author_id, date, views)
VALUES (\':1\', \':2\', \':3\', \':4\', :5, :6, :7)');

define('REMOVE_IMAGE', 'DELETE FROM ' . IMAGES_TABLE . ' WHERE image_id = :1');
define('REMOVE_IMAGE_TAGS', 'DELETE FROM ' . IMAGE_TAGS_TABLE . ' WHERE image_id = :1');

// tagcloud
//SELECT t.title, COUNT(i.tag_id) FROM `image_tags` i JOIN `tags` t ON t.tag_id = i.tag_id  GROUP BY t.tag_id HAVING COUNT(i.tag_id) >= 1

define('GET_TAG_CLOUD',
        'SELECT t.title, COUNT(i.tag_id) AS count FROM `' . IMAGE_TAGS_TABLE .
        '` AS i JOIN `' . TAGS_TABLE . '` AS t ON t.tag_id = i.tag_id GROUP BY t.tag_id HAVING COUNT(i.tag_id) >= 1
LIMIT 0, ' . TAG_CLOUD_LIMIT);

//require_once(INCLUDE_HOME."Image.php");

class ImageManager {

    private $db;

    function ImageManager($db) {
        $this->db = $db;
    }

    function getImages($sortBy, $from, $num) {
        $query = Database::buildQuery(GET_SORTED_IMAGES_FROM_NUM,
                        array($sortBy, $from, $num), $this->db);

        $re = $this->db->query($query);
        if (!$re)
            echo mysqli_error($this->db->getDb());
        $images = array();

        while ($img = Database::fetchObject($re, 'Image')) {
            $images[] = $img;
        }

        return $images;
    }

    function getImagesByTags($tags, $from, $num) {
        // first we construct the WHERE clause conditions
        // + divide the query string into groups, where at least one tag must
        // me present (logical AND)
        // spaces act as logical OR (we can use SQL IN clause for this)
        //TODO: now we only accept OR, add the and evaluation
        //echo $tags.'<br>';
        //        $where = '';
        //        $andGroups = explode('+', $tags);
        //        foreach ($andGroups as $group) {
        //        //$group = explode(' ', $group);
        //            $group = str_replace(' ', '\', \'', preg_replace("/(\s+)/", " ", trim($group)));
        //            //implode(', ', $group)
        //            $where .= 't.title IN (\''.$group.'\') OR ';
        //        }
        //
        //        if (count($andGroups) > 0) {
        //            $where = substr($where, 0, -4);
        //        }
        //echo $tags.'<br>';
        $tags = htmlspecialchars_decode($tags);
        //echo $tags.'<br>';
        $tags = stripslashes($tags);
        //echo $tags.'<br>';
        //$tags = mysqli_real_escape_string($tags);
        $tags = mysqli_real_escape_string($this->db->getDb(), $tags);
        $tags = mysqli_real_escape_string($this->db->getDb(), $tags);
        //echo $tags.'<br>';
        $tags = htmlspecialchars($tags);
        //echo $tags.'<br>';

        $tags = str_replace(' ', '\', \'', preg_replace("/(\s+)/", " ", trim($tags)));

        $where = 't.title IN (\'' . $tags . '\')';

        //echo $where.'<br>';

        $query = 'SELECT i.* FROM
(
    ' . IMAGES_TABLE . ' i INNER JOIN ' . IMAGE_TAGS_TABLE . ' it ON i.image_id = it.image_id
)
INNER JOIN ' . TAGS_TABLE . ' t ON it.tag_id = t.tag_id
WHERE (' . $where . ')
GROUP BY i.image_id
ORDER BY COUNT(t.title) DESC, i.date DESC
LIMIT ' . $from . ', ' . $num;
        //HAVING COUNT(t.title) >= '.count($andGroups).'
        //echo $query.'<br>';

        $re = $this->db->query($query);
        if (!$re)
            echo mysqli_error($this->db->getDb());
        $images = array();

        while ($img = Database::fetchObject($re, 'Image')) {
            $images[] = $img;
        }

        return $images;
    }

    function getImagesNum() {
        $re = $this->db->query(GET_IMAGE_NUM);
        if (!$re)
            echo mysqli_error($this->db->getDb());
        $row = Database::fetchArray($re);
        return $row['count'];
    }

    function getImagesByTagsNum($tags) {
        $tags = htmlspecialchars_decode($tags);
        $tags = stripslashes($tags);
        $tags = mysqli_real_escape_string($this->db->getDb(), $tags);
        $tags = mysqli_real_escape_string($this->db->getDb(), $tags);
        $tags = htmlspecialchars($tags);
        $tags = str_replace(' ', '\', \'', preg_replace("/(\s+)/", " ", trim($tags)));

        $where = 't.title IN (\'' . $tags . '\')';

        //        $query = 'SELECT DISTINCT COUNT(i.image_id) AS count FROM
        //(
        //    '.IMAGES_TABLE.' i INNER JOIN '.IMAGE_TAGS_TABLE.' it ON i.image_id = it.image_id
        //)
        //INNER JOIN '.TAGS_TABLE.' t ON it.tag_id = t.tag_id
        //WHERE ('.$where.')';
        $query = 'SELECT COUNT(s1.image_id) AS count FROM (SELECT i.* FROM
(
    ' . IMAGES_TABLE . ' i INNER JOIN ' . IMAGE_TAGS_TABLE . ' it ON i.image_id = it.image_id
)
INNER JOIN ' . TAGS_TABLE . ' t ON it.tag_id = t.tag_id
WHERE (' . $where . ')
GROUP BY i.image_id) as s1';
        //GROUP BY i.image_id';

        $re = $this->db->query($query);
        if (!$re)
            echo mysqli_error($this->db->getDb());
        $row = Database::fetchArray($re);
        return $row['count'];
    }

    function getImage($image_id) {
        $query = Database::buildQuery(GET_IMAGE_BY_ID, array($image_id), $this->db);

        $re = $this->db->query($query);
        $img = Database::fetchObject($re, 'Image');

        if (!$img) {
            return false;
        }

        $query = Database::buildQuery(GET_TAGS_BY_IMAGE_ID, array($image_id), $this->db);

        $tags = array();
        $re = $this->db->query($query);
        while ($row = Database::fetchArray($re)) {
            //$row['title'] = stripslashes($row['title']);
            $tags[] = $row;
        }

        $img->setTags($tags);
        return $img;
    }

    function incViews($image_id) {
        $this->db->query('UPDATE ' . IMAGES_TABLE . ' SET views = views + 1 WHERE image_id = ' . $image_id);
    }

    /*
     * $_FILES['uploadedfile']['name'] - name contains the original path of the user uploaded file.
      $_FILES['uploadedfile']['tmp_name']
     */

    function saveImage($title, $dir, $author_id, $file, $tags) {
        //$extension = substr($filename, strrpos($filename, '.') + 1);
        $tags = preg_replace("/(\s+)/", " ", trim($tags));

        // escape user defined directory
        $dir = mysqli_real_escape_string($this->db->getDb(), $dir);
        $dir = htmlspecialchars($dir);

        $dir = $author_id . '/' . $dir;
        if (!ends_with($dir, '/')) {
            $dir .= '/';
        }

        $title = mysqli_real_escape_string($this->db->getDb(), $title);
        $title = htmlspecialchars($title);

        //echo $dir.'<br>';
        //echo $file['tmp_name'].'<br>';

        $query = Database::buildQuery(SAVE_IMAGE, array(
                    $title, $dir, basename($file['name']),
                    $hash = md5_file($file['tmp_name']),
                    $author_id, time(), 0), $this->db
        );

        $dir = ROOT . IMG_ROOT . $dir;

        //echo $query.'<br>';

        if ($this->db->query($query)) {
            $image_id = mysqli_insert_id($this->db->getDb());
            //die("MySQL Error: " . mysqli_error());
            //}

            $tags = mysqli_real_escape_string($this->db->getDb(), $tags);
            $tags = htmlspecialchars($tags);
            // tokenize tags
            $tags = explode(' ', $tags);

            $this->setTagsToImage($tags, $image_id);

            $target_path = $dir . basename($file['name']);

            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
                @mkdir($dir . '/thumbmed', 0777, true);
                //@mkdir($dir.'/thumbsmall', 0777, true);
            }

            if (!$file['batch']) {
                $re = move_uploaded_file($file['tmp_name'], $target_path);
            } else {
                $re = copy($file['tmp_name'], $target_path);
            }
            if ($re) {

                //            $this->img_resize($target_path, 160, dirname($target_path).'/thumbmed',
                //                'med_'.basename($target_path), 160, 240);
                //            $this->img_resize($target_path, 80, dirname($target_path).'/thumbsmall',
                //                'small_'.basename($target_path), 80, 120);

                $this->img_resize($target_path, dirname($target_path) . '/thumbmed',
                        basename($target_path), 240, 160);
                //                $this->img_resize($target_path, dirname($target_path).'/thumbsmall',
                //                    basename($target_path), 120, 80);
            }

            //function img_resize( $tmpname, $save_dir, $save_name, $width, $height)

            return $re;
        } else {
            return false;
        }
    }

    function removeImage($id) {
        $id = intval($id);
        if (!$id) {
            die('Fuck off (image not found)');
        }

        $query = 'SELECT path, filename FROM ' . IMAGES_TABLE . ' WHERE image_id = ' . $id;

        $re = $this->db->query($query);
        $data = Database::fetchArray($re);

        $query = Database::buildQuery(REMOVE_IMAGE, array($id), $this->db);
        $this->db->query($query);
        $query = Database::buildQuery(REMOVE_IMAGE_TAGS, array($id), $this->db);
        $this->db->query($query);

        unlink(ROOT . IMG_ROOT . $data['path'] . $data['filename']);
        unlink(ROOT . IMG_ROOT . $data['path'] . 'thumbmed/' . $data['filename']);
        //unlink(ROOT.IMG_ROOT.$data['path'].'thumbsmall/'.$data['filename']);
    }

//    private function buildQuery($query, $values) {
//        foreach ($values as &$value) {
//            $value = mysqli_real_escape_string($value, $this->db->getDb());
//        }
//
//        return str_replace(
//                array(":1", ":2", ":3", ":4", ":5", ":6", ":7", ":8", ":9", ":10",
//                    ":11", ":12", ":13", ":14", ":15", ":16", ":17", ":18", ":19", ":20"),
//                $values, $query);
//    }

    public function setDb($db) {
        $this->db = $db;
    }

    function setTagsToImage($tags, $image_id) {
        // add tags which doesn't exist
        $re = $this->createTags($tags);
        if (!$re) {
            echo mysqli_error($this->db->getDb()) . '<br>';
        }

        // get all tags for this image
        $query = 'SELECT * FROM ' . TAGS_TABLE . ' WHERE title IN (\'' .
                implode('\',\'', $tags) . '\')';

        $existingTags = array();
        $re = $this->db->query($query);
        while ($row = Database::fetchArray($re)) {
            $existingTags[] = $row;
        }

        // pair them to image
        $re = $this->addTagsToImage($existingTags, $image_id);
        if (!$re) {
            echo mysqli_error($this->db->getDb()) . '<br>';
        }
    }

    function editTags($id, $tags) {
        $id = intval($id);
        if (!$id) {
            die('Fuck off (image not found)');
        }

        //print_r($tags);
        //echo $tags.'<br>';
        $tags = preg_replace("/(\s+)/", " ", trim($tags));
        //echo $tags.'<br>';
        $tags = mysqli_real_escape_string($this->db->getDb(), $tags);
        //echo $tags.'<br>';
        $tags = htmlspecialchars($tags);
        //echo $tags.'<br>';
        // tokenize tags
        $tags = explode(' ', $tags);

        $query = Database::buildQuery(REMOVE_IMAGE_TAGS, array($id), $this->db);
        $this->db->query($query);

        $this->setTagsToImage($tags, $id);
    }

    function editTitle($id, $title) {
        $id = intval($id);
        if (!$id) {
            die('Fuck off (image not found)');
        }

        $title = mysqli_real_escape_string($this->db->getDb(), $title);
        $title = htmlspecialchars($title);

        $query = 'UPDATE ' . IMAGES_TABLE . ' SET title = \'' . $title . '\' WHERE image_id = ' . $id;
        $this->db->query($query);
    }

    function createTags($tags) {
        $query = 'INSERT IGNORE INTO ' . TAGS_TABLE . ' (title) VALUES ';
        foreach ($tags as $tag) {
            $query .= '( \'' . $tag . '\'), ';
        }

        if (sizeof($tags) > 0) {
            $query = substr($query, 0, -2);
        }

        return $this->db->query($query);
    }

    function addTagsToImage($tags, $image_id) {
        $query = 'INSERT INTO ' . IMAGE_TAGS_TABLE . ' (image_id, tag_id) VALUES ';
        foreach ($tags as $tag) {
            $query .= '( ' . $image_id . ', ' . $tag['tag_id'] . '), ';
        }

        if (sizeof($tags) > 0) {
            $query = substr($query, 0, -2);
        }

        return $this->db->query($query);
    }

    function getTagCloud() {
        $re = $this->db->query(GET_TAG_CLOUD);
        if (!$re)
            echo mysqli_error(Database::$dbGlobal);

        $tagCloud = array();
        $max = 0;
        while ($row = Database::fetchArray($re)) {
            $row['title'] = stripslashes($row['title']);
            $row['real_weight'] = log($row['count']);
            if ($row['real_weight'] > $max) {
                $max = $row['real_weight'];
            }

            $tagCloud[] = $row;
        }

        if ($max == 0) {
            $max = 1;
        }

        foreach ($tagCloud as &$row) {
            $weight = round(1 + ((9 * ($row['real_weight'])) / $max));
            $row['style'] = $this->getStyle($weight);
        }

        return $tagCloud;
    }

    function getStyle($weight) {
        $fontSize = 4 + 3 * $weight;
        #FFFF00
        //$fontWeight = ($weight > 4) ? 400 + ($weight-4)*100: 400;
        $color = 0xFFFF00 - (10 - $weight) * 0x0D0D00;
        return 'font-size: ' . $fontSize . 'px; color: #' . dechex($color) . '; line-height: 1;';
        // font-weight: ' . $fontWeight . ';';
    }

    /**
     * Make thumbs from JPEG, PNG, GIF source file
     *
     * $tmpname = $_FILES['source']['tmp_name'];
     * $size - max width size
     * $save_dir - destination folder
     * $save_name - tnumb new name
     * $maxisheight - is max for width (if not is for height)
     *
     * Author:  David Taubmann http://www.quidware.com (edited from LEDok - http://www.citadelavto.ru/)
     */
    /* /    // And now how using this function fast:
      if ($_POST[pic])
      {
      $tmpname  = $_FILES['pic']['tmp_name'];
      @img_resize( $tmpname , 600 , "../album" , "album_".$id.".jpg");
      @img_resize( $tmpname , 120 , "../album" , "album_".$id."_small.jpg");
      @img_resize( $tmpname , 60 , "../album" , "album_".$id."_maxheight.jpg", 1);
      }
      else
      echo "No Images uploaded via POST";
      /* */

    //    function img_resize( $tmpname, $size, $save_dir, $save_name, $maxisheight = 0, $maxwidth = 0) {
    //        $save_dir     .= ( substr($save_dir,-1) != "/") ? "/" : "";
    //        $gis        = getimagesize($tmpname);
    //        $type        = $gis[2];
    //        switch($type) {
    //            case "1": $imorig = imagecreatefromgif($tmpname); break;
    //            case "2": $imorig = imagecreatefromjpeg($tmpname);break;
    //            case "3": $imorig = imagecreatefrompng($tmpname); break;
    //            default:  $imorig = imagecreatefromjpeg($tmpname);
    //        }
    //
    //        $x = imagesx($imorig);
    //        $y = imagesy($imorig);
    //
    //        $woh = (!$maxisheight)? $gis[0] : $gis[1] ;
    //
    //        if($woh <= $size) {
    //            $aw = $x;
    //            $ah = $y;
    //        }
    //        else {
    //            if(!$maxisheight) {
    //                $aw = $size;
    //                $ah = $size * $y / $x;
    //            } else {
    //                $aw = $size * $x / $y;
    //                $ah = $size;
    //            }
    //        }
    //
    //        $st = 0;
    //        if ($maxwidth) {
    //            if ($aw > $maxwidth) {
    //                $ratio = $maxwidth / $aw;
    //                //echo $ratio.'<br>';
    //                $aw = $maxwidth;
    //                //echo $aw.'<br>';
    //                $st = $x*(1-$ratio)/2;
    //                $x *= $ratio;
    //            //echo $x.'<br>';
    //            }
    //        }
    //
    //        $im = imagecreatetruecolor($aw,$ah);
    //
    //        if (imagecopyresampled($im,$imorig , 0,0,$st,0,$aw,$ah,$x,$y)) {
    //            switch($type) {
    //                case "1": $imnew = imagegif($im, $save_dir.$save_name); break;
    //                case "2": $imnew = imagejpeg($im, $save_dir.$save_name);break;
    //                case "3": $imnew = imagepng($im, $save_dir.$save_name); break;
    //                default:  $imnew = imagejpeg($im, $save_dir.$save_name);
    //            }
    //        }
    //
    //        if ($imnew)
    //            return true;
    //        else
    //            return false;
    //    }
    // all images are scaled to $height, $width is maxwidth (images are trimmed if
    // exceeding
    function img_resize($tmpname, $save_dir, $save_name, $width, $height) {
        $save_dir .= ( substr($save_dir, -1) != "/") ? "/" : "";
        $gis = getimagesize($tmpname);
        $type = $gis[2];
        switch ($type) {
            case "1": $imorig = imagecreatefromgif($tmpname);
                break;
            case "2": $imorig = imagecreatefromjpeg($tmpname);
                break;
            case "3": $imorig = imagecreatefrompng($tmpname);
                break;
            default: $imorig = imagecreatefromjpeg($tmpname);
        }

        $x = imagesx($imorig);
        $y = imagesy($imorig);

        $aw = $height * $x / $y;
        $ah = $height;

        $st = 0;

        if ($aw > $width) {
            $ratio = $width / $aw;
            $aw = $width;
            $st = $x * (1 - $ratio) / 2;
            $x *= $ratio;
        }

        $im = imagecreatetruecolor($aw, $ah);

        if (imagecopyresampled($im, $imorig, 0, 0, $st, 0, $aw, $ah, $x, $y)) {
            switch ($type) {
                case "1": $imnew = imagegif($im, $save_dir . $save_name);
                    break;
                case "2": $imnew = imagejpeg($im, $save_dir . $save_name);
                    break;
                case "3": $imnew = imagepng($im, $save_dir . $save_name);
                    break;
                default: $imnew = imagejpeg($im, $save_dir . $save_name);
            }
        }

        if ($imnew)
            return true;
        else
            return false;
    }

}

function ends_with($FullStr, $EndStr) {
// Get the length of the end string
    $StrLen = strlen($EndStr);
    // Look at the end of FullStr for the substring the size of EndStr
    $FullStrEnd = substr($FullStr, strlen($FullStr) - $StrLen);
    // If it matches, it does end with EndStr
    return $FullStrEnd == $EndStr;
}

?>

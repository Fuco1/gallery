<?php

class Image {

    private $image_id, $title, $path, $filename, $hash, $author_id, $date, $views;
    private $tags; /// array of tags

    function  __construct() {
    }

    function Image($image_id, $title, $path, $filename, $hash, $author_id, $date, $views) {
        $this->image_id = $image_id;
        $this->title = $title;
        $this->path = $path;
        $this->filename = $filename;
        $this->hash = $hash;
        $this->author_id = $author_id;
        $this->date = $date;
        $this->views = $views;
    }

    public function getImage_id() {
        return $this->image_id;
    }

    public function setImage_id($image_id) {
        $this->image_id = $image_id;
    }

    public function getTitle() {
        return stripslashes($this->title);
    }

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getPath() {
        return $this->path;
    }

    public function setPath($path) {
        $this->path = $path;
    }

    public function getFilename() {
        return $this->filename;
    }

    public function setFilename($filename) {
        $this->filename = $filename;
    }

    public function getHash() {
        return $this->hash;
    }

    public function setHash($hash) {
        $this->hash = $hash;
    }

    public function getAuthor_id() {
        return $this->author_id;
    }

    public function setAuthor_id($author_id) {
        $this->author_id = $author_id;
    }

    public function getDate() {
        return $this->date;
    }

    public function setDate($date) {
        $this->date = $date;
    }

    public function getViews() {
        return $this->views;
    }

    public function setViews($views) {
        $this->views = $views;
    }

    public function getTags() {
        return $this->tags;
    }

    public function setTags($tags) {
        $this->tags = $tags;
    }
}

?>

<?php
/**
 * Tools dor image comparison (similarity) and image unique identification (format independent equivalence).
 * @see method http://www.hackerfactor.com/blog/index.php?/archives/432-Looks-Like-It.html
 * @see php code https://gist.github.com/mncaudill/1326966
 */
class ImgCmp {
  var $fname='';
  var $ftype='';
  var $sha1='';
  var $looklike  = '';
  var $looklike2 = '';

  /**
   * Configuration and construction.
   */
  function __construct($fname=NULL) {
    //if (imagetypes() & (IMG_GIF | IMG_JPG | IMG_PNG))
    //    die ("\n ERROR22: MINIMAL PHP-imagetypes support is not enabled\n");
    if ($fname)
      $this->set($fname);
  } // func

  function set($fname=NULL) {
    $this->setFile($fname);
    $this->sha1 = $this->sha1_fromIm();
    $this->looklike = $this->hash_lookLike432();
  }

  function setFile($fname=NULL) {
    if ($fname) $this->fname = trim($fname);
    if ($this->fname) {
      $this->ftype = exif_imagetype($this->fname); // if you don't have exif you could use getImageSize()
      // ideal IMAGETYPE_GIF,IMAGETYPE_JPEG,IMAGETYPE_PNG,IMAGETYPE_TIFF_II,IMAGETYPE_TIFF_MM,IMAGETYPE_JP2
      return true;
    } else
      return false;
  } // func


  /**
   * Using GD lib for get and transform images.
   * @see http://php.net/manual/en/function.imagecreatefromjpeg.php#110547
   */
  function anyImgFile2Im($fname=NULL) {
      if ($fname) $this->setFile($fname);
      $allowedTypes = [1,2,3,6]; // gif,jpg,png,bmp
      if (!in_array($this->ftype, $allowedTypes))
          return false;
      switch ($this->ftype) {
      case 1: $im = imageCreateFromGif($this->fname);
        break;
      case 2: $im = imageCreateFromJpeg($this->fname);
        break;
      case 3: $im = imageCreateFromPng($this->fname);
    //  echo "\n degug {$this->fname}";
        break;
      case 6: $im = imageCreateFromBmp($this->fname);
        break;
      }
      return $im;
  }

  /**
   * SHA1 of a standard img.
   */
  function sha1_fromIm($fname=NULL,$quality=50) {
    $im = $this->anyImgFile2Im($fname,$quality);
    ob_start(); //Stdout --> buffer
    imagejpeg($im,NULL,$quality); // preserve original with 100
    $imgString = ob_get_contents(); //store stdout in $imgString
    ob_end_clean(); //clear buffer
    imagedestroy($im); //destroy img
    return sha1($imgString);
  }

  /**
   * A hash function that transforms any image in a generic grayscale thumbnail used for comparisons.
   * @see method http://www.hackerfactor.com/blog/index.php?/archives/432-Looks-Like-It.html
   * @see php code https://gist.github.com/mncaudill/1326966
   */
  function hash_lookLike432($fname=NULL,$L=8) {
    if ($fname) $this->setFile($fname);
    list($width, $height) = getimagesize($this->fname);
    $img = $this->anyImgFile2Im($this->fname); // correto?
    $new_img = imagecreatetruecolor($L, $L);
    imagecopyresampled($new_img, $img, 0, 0, 0, 0, $L, $L, $width, $height);
    imagefilter($new_img, IMG_FILTER_GRAYSCALE);
    $colors = [];
    $sum = 0;
    for ($i = 0; $i < $L; $i++) {
        for ($j = 0; $j < $L; $j++) {
            $color = imagecolorat($new_img, $i, $j) & 0xff;
            $sum += $color;
            $colors[] = $color;
        } // for
    } // for
    $avg = $sum / ($L*$L);
    $hash = $curr = '';
    $count = 0;
    foreach ($colors as $color) {
        if ($color > $avg) {
            $curr .= '1';
        } else {
            $curr .= '0';
        }
        $count++;
        $mL = round($L/2);
        if (!($count % $mL)) { // 4 = $L/2?
            $hash .= dechex(bindec($curr));
            $curr = '';
        }
    } // for
    return $hash;
  } // func

} // class

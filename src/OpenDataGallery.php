<?php
/**
 * xx.
 */

class OpenDataGallery {
  var $dirRoot     = NULL;
  var $files       = [];
  var $dirs        = [];
  var $roots       = [];
  var $strut       = [];
  var $file        = NULL;
  var $fileFull    = NULL;
  var $filename    = NULL;
  var $fileExt     = NULL;

  var $status = 0;
  // DEFAULT CONFIGS:
  var $dirScanRegEx = '#/gallery$|/gallery(?:/[a-z][^/]+)+$#i';
  var $dirRegEx = NULL;
  var $fileRegEx = '/\.(?:png|jpg|jpeg|jp2|j2k|jpf|jpx|jpm|mj2|tif|tiff|csv)$/i';

  /**
   * Configuration and construction.
   */
  function __construct($dirRoot=NULL,$dirRegEx=NULL,$fileRegEx=NULL,$dirScanRegEx=NULL){
    if ($dirRoot!==NULL) $this->dirRoot = $dirRoot;
    if (!$this->dirRoot) $this->dirRoot = dirname(__DIR__);
    if ($dirRegEx!==NULL) $this->dirRegEx = $dirRegEx;
    if ($dirScanRegEx!==NULL) $this->dirScanRegEx = $dirScanRegEx;
    if ($fileRegEx!==NULL) $this->fileRegEx = $fileRegEx;
    if ($this->dirRoot)
      $this->cutRgx = '#^'. preg_quote($this->dirRoot,'#') .'\/?#';
    else die("\nERROR23: need dirRoot\n");
  } // func


  /**
   * Set file or directory. Affects $file, $fileFull and $status.
   * @param $file string. When use "/" is a full path, else check root and "$dirRoot/$file".
   * @return integer 0 not exists or invalid, 1 is a valid file, 2 is a valid directory.
   */
  function setfile($f) {
    $this->file = $this->fileFull = $this->fileExt = '';
    if ($f->isDir())
        $this->status = (!$this->dirRegEx || ($this->dirRegEx && preg_match($this->dirRegEx, $f)) )? 2: 0;
    else
        $this->status = (!$this->fileRegEx || ($this->fileRegEx && preg_match($this->fileRegEx, $f)) )? 1: 0;
    if ($this->status) {
      $this->fileFull = $f;
    }
    $this->file = preg_replace($this->cutRgx,'',$f);
    if (preg_match('/\.([^\.]+)$/', $f, $m))
      $this->fileExt = $m[1];
    return $this->status;
  } //func

  function setfiles(){
    $this->strut=[];
    $this->files = [];
    $this->dirs = [];
    $this->roots = [];
    $this->csvs = [];
    $n = 0;
    $recDir = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->dirRoot,RecursiveDirectoryIterator::SKIP_DOTS),
      RecursiveIteratorIterator::SELF_FIRST,
      RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
    );
    foreach(($this->dirScanRegEx?
        new RegexIterator($recDir, $this->dirScanRegEx):
        $recDir
      ) as $path => $f) if ($this->setfile($f)) { // check valid path before
        //echo "\n* st={$this->status} = $f";
        if ($f->isDir()) {  // or getType ( )
          $this->dirs[] = $this->fileFull;
          if (preg_match('#/gallery$#i',$this->fileFull) && !in_array($this->file,$this->roots))
            $this->roots[] = $this->file;
        } else {
          if (in_array($f->getPath(),$this->dirs)) {
            if (strtolower($this->fileExt)=='csv')
              $this->csvs[] = $this->file;
            else
              $this->files[] = $this->file;
          }
          // else echo "\n\t\t!! $f fora do path!";
        }
    }
    $make = [];
    $sync = [];
    foreach ($this->roots as $c) {
      $f = "$c/description.csv";
      if (!in_array($f,$this->csvs))
        $make[$c] = "{$this->dirRoot}/$f";
      else
        $sync[$c] = "{$this->dirRoot}/$f";
      $this->strut[$c] = [];
      foreach ($this->files as $f) if (preg_match('#^'.preg_quote($c,'#').'\/?(.+)#',$f,$m))
        $this->strut[$c][] = $m[1];
    }
    $this->makeCsv($make);
    $this->syncCsv($sync);
    return count($this->files);
  } //func

  function makeCsv($roots) {
    if ($roots && count($roots)) foreach ($roots as $name=>$path) {
        echo "\n CRIANDO ARQUIVO CSV $name ($path)";
        // makeCsv_line
    }
  } //func

  function makeCsv_line($imgfile,$cap='',$csv_handle='php://stdout') {
    $im = new ImgCmp($imgfile);
    return fputcsv ($csv_handle,[
      dirname($f), $f, round(filesize($f)/1024.0), 'use', $im->sha1, $im->looklike, '_caption_'
    ]);
  }

  function syncCsv($roots) {
    if ($roots && count($roots)) foreach ($roots as $name=>$path) {
      echo "\n SYNC ARQUIVO CSV $name ($path)";
      foreach ($this->strut[$name] as $fname) echo "\n\t$fname"; // $f="{$this->dirRoot}/$name/$fname";
    }
  } //func

} //class

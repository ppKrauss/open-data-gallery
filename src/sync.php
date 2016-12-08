<?php
/**
 * Sincroniza tabelas CSV com a realidade das pastas de imagens. Roda via terminal.
 * 1. Carrega no CSV os dados das pastas, caso ainda nao tenham sido carregados.
 * 2. Preenche coluna "status" para imagens deletadas.
 * 3. Preenche coluna "status-date" para ultima realização de sync.
 * Segue convenções documentadas em https://github.com/ppKrauss/open-data-gallery
 */

include('OpenDataGallery.php');
include('ImgCmp.php');

$gal = new OpenDataGallery();
$n = $gal->setfiles();
if ($n) {
  //var_dump($gal->strut);
  foreach ($gal->files as $f) {
  }
} else
  print "\n CHI.. SEM NADA NA GALLERY.\n";
echo "\n";
?>

<?php
require __DIR__ . '/../../../api-visa/vendor/autoload.php';

use PhpOffice\PhpWord\TemplateProcessor;

$template = new TemplateProcessor(__DIR__ . '/templets/teste.docx');

$template->setValue('name', '001/2026');


// $arquivoFinal = '/templets/oficio_001.docx';

$template->saveAs(__DIR__ . '/templets/oficio_002.docx');

echo "Documento gerado com sucesso!";

?>
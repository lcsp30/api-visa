<?php

use PhpOffice\PhpWord\TemplateProcessor;

$template = new TemplateProcessor(__DIR__ . '/templets/teste.docx');

$template->setValue('name', '001/2026');

$template->saveAs(__DIR__ . '/templets/oficio_003.docx');

echo "Documento gerado com sucesso!";

?>
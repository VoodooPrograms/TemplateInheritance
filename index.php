<?php 

require_once 'Block.php';
require_once 'TemplateInheritance.php';
require_once 'ViewSupport.php';

$templateEngine = new TemplateInheritance();

$templatePath = 'templates/' . 'test.php';

$parameters = [];

if (file_exists($templatePath)) {
    //if any variable named the same as the key exist <=> extract return number != sizeof($parr), then fail
    if (extract($parameters, EXTR_SKIP) != sizeof($parameters)) {
        return 1; //fail
    }
    $vs = new ViewSupport($templateEngine);  //important $vs after extract
    include $templatePath;

    return 0; //success
} else {
    echo 'Template not found';
}

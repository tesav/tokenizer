<?php

/**
 *
 */
if (isset($_FILES['file']['name'])) {

    if (substr($_FILES['file']['name'], -4) == '.php') {
        include 'Tokenizer.php';
        $token = new Tokenizer($_FILES['file']['tmp_name'] /* , false */);
        $content = view('result', array('result' => $token->result));
    }
    else $content = 'Perhaps this is not a PHP file !';
}

else $content = view('form');

/**
 * Show the content in layout
 */
echo view('layout', array('content' => $content));

/**
 * Sends $data to the template $file of view folder
 *
 * @param string $file
 * @param array $data
 * @return string
 */
function view($file, array $data = array()) {

    $data and extract($data, EXTR_SKIP);
    ob_start();
    include "view/{$file}.php";
    return ob_get_clean();
}
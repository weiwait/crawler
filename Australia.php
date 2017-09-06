<?php
/**
 * Created by PhpStorm.
 * User: MT
 * Date: 17.9.5
 * Time: 9:31
 */
set_time_limit(0);
error_reporting(E_ALL);
require './vendor/autoload.php';

try {
    $pdo = new PDO('mysql:host=192.168.1.195:3306;dbname=mengtor', 'root', '123456');
    echo 'connected';
} catch (Exception $exception) {
    echo $exception;
}
$postcodes = file_get_contents('errorPostcodeText.txt');
$postcodes = explode('/', $postcodes);
$document = new \DiDom\Document();
foreach ($postcodes as $i) {
    $postcode = str_pad($i, 4, '0', STR_PAD_LEFT);
    $url = "https://postcodez.com.au/postcodes.cgi?search_suburb={$postcode}&search_state=&type=search";
    try {
        $content = $document->loadHtmlFile($url);
    } catch (Exception $exception) {
        file_put_contents('errorPostcodeText.txt', $postcode . '/', FILE_APPEND);
        continue;
    }
    $results = $content->find('.result');
    if (!empty($results)) {
        foreach ($results as $result) {
            $statement = $pdo->prepare('INSERT INTO `lr_australia_territory_information` (`location`, `sub_region`, `region`, `state`, `postcode`, `country`) VALUES (?, ?, ?, ?, ?, ?)');
            $info = $result->find('a');
            $location = $info[0]->getNode()->nodeValue;
            $postcode = $info[1]->getNode()->nodeValue;
            $subRegion = $info[2]->getNode()->nodeValue;
            $region = $info[3]->getNode()->nodeValue;
            $state = $info[4]->getNode()->nodeValue;
            $country = $info[5]->getNode()->nodeValue;
            $statement->bindValue(1, $location, PDO::PARAM_STR);
            $statement->bindValue(2, $subRegion, PDO::PARAM_STR);
            $statement->bindValue(3, $region, PDO::PARAM_STR);
            $statement->bindValue(4, $state, PDO::PARAM_STR);
            $statement->bindValue(5, $postcode, PDO::PARAM_STR);
            $statement->bindValue(6, $country, PDO::PARAM_STR);
            $statement->execute();
        }
    }
}

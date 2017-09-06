<?php

namespace spider;

use DiDom\Document;
use DiDom\Element;
use GuzzleHttp\Client;

/**
 * Created by PhpStorm.
 * User: MT
 * Date: 2017/7/26
 * Time: 9:30
 */

class SpiderClass
{
    protected $HttpClient;
    protected $Document;
    private $previousUrl;

    public function __construct()
    {
        if (file_exists(__DIR__ . '/page.txt')) {
            unlink(__DIR__ . '/page.txt');
        }
        $this->HttpClient = new Client();
        $this->Document = new Document();
    }

    public function spider($url, $select, $next, $chapterSelector)
    {
        $result = $this->HttpClient->request('GET', $url)->getBody()->getContents();
        if (null == json_encode($result)) {
            $content = mb_convert_encoding($result, 'UTF-8', 'GBK');
        } else {
            $content = mb_convert_encoding($result, 'UTF-8');
        }
        $document = $this->Document->loadHtml($content);
        if (false !== $chapterSelector) {
            $chapter = $document->find($chapterSelector);
            $chapter = $chapter[0]->text();
            file_put_contents(__DIR__ . '/page.txt', $chapter, FILE_APPEND);
        }
        $text = $document->find($select);
        $text = $text[0]->text();
        $text = htmlentities($text);
        $text = preg_replace('/&nbsp;&nbsp;&nbsp;&nbsp;/', "\n", $text);
        $text = html_entity_decode($text);
        file_put_contents(__DIR__ . '/page.txt', $text, FILE_APPEND);
        if (false === $next) {
            $uri = $this->getNext($document->find('[href]'), $url)['href'];
            if (filter_var($uri, FILTER_VALIDATE_URL)) {
                $this->spider($uri, $select, false, $chapterSelector);
            } else {
                $url = $this->getUrlRoot($url) . $uri;
                if ($url == $this->previousUrl) {
                    die('all done');
                }
                $this->previousUrl = $url;
                $this->spider($url, $select, false, $chapterSelector);
            }
        } else {
            $uri = $document->find($next)[0]->find('[href]')[0]->attributes()['href'];
            if (filter_var($uri, FILTER_VALIDATE_URL)) {
                $this->spider($uri, $select, $next, $chapterSelector);
            } else {
                $url = $this->getUrlRoot($url) . $uri;
                if ($url == $this->previousUrl) {
                    die('all done');
                }
                $this->previousUrl = $url;
                $this->spider($url, $select, $next, $chapterSelector);
            }
        }
    }

    protected function getUrlRoot($url)
    {
        if ($url == '.') {
            return '';
        }
        $urlRoot = dirname($url);
        if (similar_text('http:', $urlRoot) == 5 && strlen($urlRoot) < 8) {
            return $url;
        } else {
            return $this->getUrlRoot($urlRoot);
        }
    }

    public function analyzeDocument($url)
    {
        $result = $this->HttpClient->request('GET', $url)->getBody()->getContents();
        if (null == json_encode($result)) {
            $content = mb_convert_encoding($result, 'UTF-8', 'GBK');
        } else {
            $content = mb_convert_encoding($result, 'UTF-8');
        }
        $Document = $this->Document->loadHtml($content);
        $bodyChildren = $Document->find('body')[0]->children();
        $nodes = $this->analyzeNode($bodyChildren);
        $chapter = $this->getChapterSelector($nodes);
        if (isset($chapter['id'])) {
            $chapter = '#' . $chapter['id'];
        } elseif (isset($chapter['class'])) {
            $chapter = '.' . $chapter['class'];
        }
        $selector = $this->getContentSelector($nodes, $Document);
        $hrefNodes = $Document->find('[href]');
        $next = $this->getNext($hrefNodes, $url);
        if (!is_array($selector) && $selector != '') {
            if (!is_array($next) && $next != '') {
                $this->spider($url, '#' . $selector, '#' . $next, $chapter);
            }
            if (is_array($next) && key_exists('href', $next)) {
                $this->spider($url, '#' . $selector, false, $chapter);
            }
        }
    }

    private function getNext($hrefNodes, $uri)
    {
        /** @var Element $node */
        foreach ($hrefNodes as $key=> $node) {
            $attributes = $node->attributes();
            similar_text($uri, $attributes['href'], $percent);
            if ($percent < 60) {
                unset($hrefNodes[$key]);
            }
        }
        $reference = '下章';
        $result = [];
        foreach ($hrefNodes as $key=>$href) {
            /** @var Element $href */
            $result[$key] = similar_text($reference, $href->text(), $percent);
        }
        arsort($result);
        $attributes = $hrefNodes[key($result)]->attributes();
        $selector = isset($attributes['id']) ? $attributes['id'] : '';
        if ('' == $selector) {
            $selector = isset($attributes['class']) ? $attributes['class'] : '';
            if ('' != $selector) {
                $selector = explode(' ', $attributes['class']);
            }
        }
        if ('' == $selector) {
            return array('href' => $attributes['href']);
        }
        return $selector;
    }

    private function getChapterSelector($nodes)
    {
        foreach ($nodes as $key => $node) {
            if ('' == $node['text']) {
                unset($nodes[$key]);
                continue;
            }
            if (mb_strlen($node['text']) > 200) {
                unset($nodes[$key]);
                continue;
            }
            if (mb_strlen($node['text']) < 4) {
                unset($nodes[$key]);
                continue;
            }
            if (!preg_match('/.*?第.*?章/', $node['text'])) {
                unset($nodes[$key]);
                continue;
            }
            if ('' == $node['id'] && '' == $node['class']) {
                unset($nodes[$key]);
                continue;
            }
        }
        if (empty($nodes)) {
            return false;
        }
        usort($nodes, function ($first, $second) {
            $first = mb_strlen($first['text']);
            $second = mb_strlen($second['text']);
            if ($first == $second) {
                return 0;
            }
            return $first > $second ? 1 : -1;
        });
        foreach ($nodes as $node) {
            if ('' != $node['id']) {
                return ['id' => $node['id']];
            }
            if ('' != $node['class']) {
                return ['class' => $node['class']];
            }
        }
        return false;
    }

    private function getContentSelector($nodes, $document)
    {
        foreach ($nodes as $key=>$node) {
            if ('' == $node['text']) {
                unset($nodes[$key]);
                continue;
            }
            if (!preg_match('/。|？|“|”|，/', $node['text'])) {
                unset($nodes[$key]);
                continue;
            }
        }
        usort($nodes, function ($first, $second) {
            $first = mb_strlen($first['text']);
            $second = mb_strlen($second['text']);
            if ($first == $second) {
                return 0;
            }
            return $first > $second ? 1 : -1;
        });

        foreach ($nodes as $key=>$node) {
            foreach ($nodes as $key2=>$node2) {
                similar_text($node['text'], $node2['text'], $percent);
                if ($percent < 60) {
                    unset($nodes[$key]);
                }
            }
        }

        $selector = current($nodes)['id'];
        if ('' == $selector) {
            $selector = current($nodes)['class'];
            if ('' != $selector) {
                $selector = explode(' ', $selector);
                $allOfClassSelected = [];
                foreach ($selector as $classKey => $class) {
                    $classSelected = $document->find('.'.$class);
                    foreach ($classSelected as $subClassKey => $classDom) {
                        $allOfClassSelected[$classKey][$subClassKey] = similar_text(current($nodes)['text'], $classDom->getNode()->nodeValue);
                    }
                }
                print_r($allOfClassSelected);die;
            }
        }
        return $selector;
    }

    /**
     * @param $DiDomNodes array;
     * @return array
     */
    private function analyzeNode($DiDomNodes)
    {
        $nodes = [];
        foreach ($DiDomNodes as $DiDomNode) {
            /**
             * @var \DOMText $node
             * @var Element $DiDomNode
             */
            if ('DiDom\Element' != get_class($DiDomNode)) {
                continue;
            }
            $node = $DiDomNode->getNode();
            $badNodes = ['#text', 'script', 'style'];
            if (in_array($node->nodeName, $badNodes)) {
                continue;
            }

            $attributes = $DiDomNode->attributes();
            $nodes[] = [
                'nodeName' => $node->nodeName,
                'id' => isset($attributes['id']) ? $attributes['id'] : '',
                'class' => isset($attributes['class']) ? $attributes['class'] : '',
                'text' => $node->nodeValue,
            ];
            try {
                $nodeChildren = $DiDomNode->children();
                $nodes = array_merge($nodes, $this->analyzeNode($nodeChildren));
            } catch (\ErrorException $errorException) {
                file_put_contents('badNodeLog', $node->nodeName);
            }
        }
        return $nodes;
    }
}

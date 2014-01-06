<?php

/**
 *
 * @author goker
 */

include("lib/simple_html_dom.php");

class CelebrityMeter {
    
    protected $useragent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1";
    protected $googleURL = "http://www.google.com/search?hl=en&tbo=d&site=&source=hp&q=";
    protected $cacheDirectory = "./cache/";
    
    private $query;
    private $hash;
    public $Rank;
    public $Google;
    public $Wikipedia;
    public $IMDB;
    public $Twitter;
    
    function CelebrityMeter($query = 'lady gaga'){
        
        $this->query = strtolower(urlencode('"'.$query.'"'));
        $this->hash = md5($this->query);
        
        $this->googleSearch();
        $this->collectWikipedia($this->Google->WikipediaLink);
        $this->collectIMDB($this->Google->IMDBLink);
        $this->collectTwitter($this->Google->TwitterLink);
        
        $this->calculate();
    }
    
    private function calculate(){
        
        $this->Rank = 
            $this->Google->Results * .0001
            + $this->Wikipedia->Translations * 100
            + ($this->Wikipedia->Photo ? 100 : 0)
            + count($this->IMDB->JobCategories) * 10
            + $this->IMDB->Soundtrack  * 2
            + $this->IMDB->Actress * 10
            + $this->IMDB->Actor * 10
            + $this->IMDB->Producer * 1
            + $this->IMDB->Director * 1
            + $this->IMDB->Writer * 1
            + $this->IMDB->Composer * 1
            + $this->Twitter->isVerified * 1000
            + $this->Twitter->Tweets * -.001
            + $this->Twitter->Following * -.1
            + $this->Twitter->Followers * .001
            ;
        
    }
    
    private function googleSearch(){
        
        $result = $this->getCache('google');
        if(!$result) {
            $result = $this->collect($this->googleURL . $this->query);
            $this->setCache('google', $result);
        }
        
        gc_enable();
        $html = str_get_html($result);
        unset($result);
        $this->Google = (Object) array(
            'Results' => (filter_var($html->find('div[id=resultStats]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1),
            'WikipediaLink' => '',
            'IMDBLink' => '',
            'TwitterLink' => '',
            'FirstPage' => '',
        );
        
        $i = 0;
        foreach ($html->find('li[class=g]') as $element) {
            
            $this->Google->FirstPage[] = array('title' => $element->find('h3')[0]->plaintext,
                                               'link' => $element->find('cite')[0]->plaintext);
            
            if(!$this->Google->WikipediaLink && preg_match('/\.wikipedia\.org/', $element->find('cite')[0]->plaintext))
                $this->Google->WikipediaLink = 'http://' . $element->find('cite')[0]->plaintext;
            
            if(!$this->Google->IMDBLink && preg_match('/\.imdb\.com/', $element->find('cite')[0]->plaintext))
                $this->Google->IMDBLink = 'http://' . $element->find('cite')[0]->plaintext;
            
            if(!$this->Google->TwitterLink && preg_match('/\/twitter\.com/', $element->find('cite')[0]->plaintext))
                $this->Google->TwitterLink = $element->find('cite')[0]->plaintext;
        }
        gc_disable();
    }
    
    private function collectWikipedia($url){
        
        $this->Wikipedia = (Object) array(
            'Name' => '',
            'Translations' => 0,
            'Photo' => '',
        );
        if(!$url) return;
            
        $result = $this->getCache('wikipedia');
        if(!$result) {
            $result = $this->collect($url);
            $this->setCache('wikipedia', $result);
        }
        
        gc_enable();
        $html = str_get_html($result);
        unset($result);
        $this->Wikipedia->Name = trim($html->find('h1[id=firstHeading]', 0)->plaintext);
        $this->Wikipedia->Translations = (count($html->find('div[id=p-lang] li')) - 2);
        $this->Wikipedia->Photo = $html->find('table[class=vcard] img', 0)->src;
        gc_disable();
    }
    
    private function collectIMDB($url){
        
        $this->IMDB = (Object) array(
            'Name' => '',
            'JobCategories' => array(),
            'Soundtrack' => 0,
            'Actress' => 0,
            'Actor' => 0,
            'Producer' => 0,
            'Director' => 0,
            'Writer' => 0,
            'Composer' => 0,
            'Thanks' => 0,
        );
        if(!$url) return;
            
        $result = $this->getCache('imdb');
        if(!$result) {
            $result = $this->collect($url);
            $this->setCache('imdb', $result);
        }
        
        gc_enable();
        $html = str_get_html($result);
        unset($result);
        $this->IMDB->Name = trim($html->find('h1[class=header]', 0)->plaintext);
        $this->IMDB->JobCategories = explode('|',str_replace(" ", '', $html->find('div[id=name-job-categories]', 0)->plaintext));
        $this->IMDB->Soundtrack = filter_var($html->find('div[id=filmo-head-soundtrack]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDB->Actress = filter_var($html->find('div[id=filmo-head-actress]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDB->Actor = filter_var($html->find('div[id=filmo-head-actor]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDB->Producer = filter_var($html->find('div[id=filmo-head-producer]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDB->Director = filter_var($html->find('div[id=filmo-head-director]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDB->Writer = filter_var($html->find('div[id=filmo-head-writer]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDB->Composer = filter_var($html->find('div[id=filmo-head-composer]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDB->Thanks = filter_var($html->find('div[id=filmo-head-thanks]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        gc_disable();
    }
    
    private function collectTwitter($url){
        
        $this->Twitter = (Object) array(
            'Account' => '',
            'isVerified' => 0,
            'Tweets' => 0,
            'Following' => 0,
            'Followers' => 0,
        );
        if(!$url) return;
            
        $result = $this->getCache('twitter');
        if(!$result) {
            $result = $this->collect($url);
            $this->setCache('twitter', $result);
        }
        
        gc_enable();
        $html = str_get_html($result);
        unset($result);
        $this->Twitter->Account = trim($html->find('span[class=screen-name]', 0)->plaintext);
        $this->Twitter->isVerified = $html->find('span[class=verified-large-border]', 0)->plaintext ? 1 : 0;
        $this->Twitter->Tweets = filter_var($html->find('a[data-element-term=tweet_stats]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->Twitter->Following = filter_var($html->find('a[data-element-term=following_stats]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->Twitter->Followers = filter_var($html->find('a[data-element-term=follower_stats]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        gc_disable();
    }
    
    private function getCache($prefix){
        
        $cacheFile = $this->cacheDirectory . $prefix .'.'.$this->hash.'.cache';
        return @file_get_contents($cacheFile);
        
    }
    
    private function setCache($prefix, $content){
        
        $cacheFile = $this->cacheDirectory . $prefix .'.'.$this->hash.'.cache';
        return @file_put_contents($cacheFile, $content);
        
    }
    
    private function collect($url){
        
        $ch = curl_init("");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent); // set user agent
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
        
    }
    
    
    
    function returnAsJSON(){
        echo json_encode($this);
    }
    
    function returnAsReadble(){
        echo '<style>body {font: 12px/normal sans-serif; color: #333;}</style>';
        echo '<meta charset="utf-8">';
        echo '<pre>';
        
        echo '</pre>';
    }
    
}


$cm = new CelebrityMeter($_GET['q']);
$cm->returnAsJSON();


/*    
include 'google.php';
gc_enable();
include 'wikipedia.php';
gc_disable();
include 'imdb.php';
include 'twitter.php';
*/


<?php

/**
 * Celebrity Meter
 * @author goker
 */

include("lib/simple_html_dom.php");

class CelebrityMeter {
    
    protected $useragent = "Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.1.4) Gecko/20091030 Gentoo Firefox/3.5.4";
    protected $googleURL = "http://www.google.com/search?hl=en&tbo=d&site=&source=hp&q=";
    protected $googleNewsURL = "http://www.google.com/search?hl=en&tbo=d&site=&source=hp&tbm=nws&q=";
    protected $cacheDirectory = "./cache/";
    
    private $query;
    private $hash;
    public $Rank;
    public $Google;
    public $GoogleNews;
    public $Wikipedia;
    public $IMDb;
    public $Twitter;
    
    function CelebrityMeter($query = 'lady gaga'){
        
        $this->query = strtolower(urlencode('"'.$query.'"'));
        $this->hash = md5($this->query);
        
        $this->googleSearch();
        $this->collectGoogleNews();
        $this->collectWikipedia($this->Google->WikipediaLink);
        $this->collectIMDb($this->Google->IMDbLink);
        $this->collectTwitter($this->Google->TwitterLink);
        
        $this->calculate();
        
        $this->returnAsJSON();
    }
    
    private function calculate(){
        
        $this->Rank = 
            $this->Google->ResultStats * 1
            + $this->GoogleNews->ResultStats * 10
            + $this->Wikipedia->Translations * 100
            + $this->IMDb->JobCategories * 1000
            + $this->IMDb->Soundtrack * 100
            + $this->IMDb->Actress * 1000
            + $this->IMDb->Actor * 1000
            + $this->IMDb->Producer * 10
            + $this->IMDb->Director * 10
            + $this->IMDb->Writer * 10
            + $this->IMDb->Composer * 10
            + $this->IMDb->Thanks * 10
            + $this->IMDb->Self * 10
            + $this->Twitter->isVerified * 1000
            + $this->Twitter->Tweets * 0
            + $this->Twitter->Following * 0
            + $this->Twitter->Followers * 1
            ;
    }
    
    private function googleSearch($param = ''){
        
        $result = $this->getCache('google' . $param);
        if(!$result) {
            $result = $this->collect($this->googleURL . $this->query 
                                     .($param ? '%20' . $param : ''));
            $this->setCache('google' . $param, $result);
        }
        
        gc_enable();
        $html = str_get_html($result);
        unset($result);
        if(!$param)
            $this->Google = (Object) array(
                'ResultStats' => 0,
                'WikipediaLink' => '',
                'IMDbLink' => '',
                'TwitterLink' => '',
            );
        
        if(!$this->Google->ResultStats)
            $this->Google->ResultStats = (filter_var($html->find('div[id=resultStats]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1);
       
        foreach ($html->find('li[class=g]') as $element) {
       
            if(!$this->Google->WikipediaLink && preg_match('/\.wikipedia\.org/', $element->find('cite')[0]->plaintext))
                $this->Google->WikipediaLink = 'http://' . $element->find('cite')[0]->plaintext;
            
            if(!$this->Google->IMDbLink && preg_match('/\.imdb\.com/', $element->find('cite')[0]->plaintext))
                $this->Google->IMDbLink = 'http://' . $element->find('cite')[0]->plaintext;
            
            if(!$this->Google->TwitterLink && preg_match('/\/twitter\.com/', $element->find('cite')[0]->plaintext))
                $this->Google->TwitterLink = $element->find('cite')[0]->plaintext;
        
        }
        
        if(!$this->Google->WikipediaLink && !$param)
            $this->googleSearch('wikipedia');
        
        if(!$this->Google->IMDbLink && !$param)
            $this->googleSearch('IMDb');
        
        if(!$this->Google->TwitterLink && !$param)
            $this->googleSearch('twitter');
        
        gc_disable();
    }
    
    private function collectGoogleNews(){
        
        $this->GoogleNews = (Object) array(
            'ResultStats' => 0
        );
            
        $result = $this->getCache('googlenews');
        if(!$result) {
            $result = $this->collect($this->googleNewsURL . $this->query);
            $this->setCache('googlenews', $result);
        }
        
        gc_enable();
        $html = str_get_html($result);
        unset($result);
        if(!$html) return;
        $this->GoogleNews->ResultStats = (filter_var($html->find('div[id=resultStats]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1);
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
        if(!$html) return;
        $this->Wikipedia->Name = trim($html->find('h1[id=firstHeading]', 0)->plaintext);
        $this->Wikipedia->Translations = (count($html->find('div[id=p-lang] li')) - 2);
        $this->Wikipedia->Photo = $html->find('table[class=vcard] img', 0)->src;
        gc_disable();
    }
    
    private function collectIMDb($url){
        
        $this->IMDb = (Object) array(
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
            'Self' => 0,
        );
        if(!$url) return;
            
        $result = $this->getCache('IMDb');
        if(!$result) {
            $result = $this->collect($url);
            $this->setCache('IMDb', $result);
        }
        
        gc_enable();
        $html = str_get_html($result);
        unset($result);
        if(!$html) return;
        $this->IMDb->Name = trim($html->find('h1[class=header]', 0)->plaintext);
        $this->IMDb->JobCategories = explode('|',str_replace(" ", '', $html->find('div[id=name-job-categories]', 0)->plaintext));
        $this->IMDb->JobCategories = count($this->IMDb->JobCategories);
        $this->IMDb->Soundtrack = filter_var($html->find('div[id=filmo-head-soundtrack]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDb->Actress = filter_var($html->find('div[id=filmo-head-actress]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDb->Actor = filter_var($html->find('div[id=filmo-head-actor]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDb->Producer = filter_var($html->find('div[id=filmo-head-producer]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDb->Director = filter_var($html->find('div[id=filmo-head-director]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDb->Writer = filter_var($html->find('div[id=filmo-head-writer]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDb->Composer = filter_var($html->find('div[id=filmo-head-composer]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDb->Thanks = filter_var($html->find('div[id=filmo-head-thanks]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->IMDb->Self = filter_var($html->find('div[id=filmo-head-self]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        gc_disable();
    }
    
    private function collectTwitter($url){
        
        $this->Twitter = (Object) array(
            //'Account' => '',
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
        if(!$html) return;
        //$this->Twitter->Account = trim($html->find('span[class=screen-name]', 0)->plaintext);
        $this->Twitter->isVerified = $html->find('span[class=verified-large-border]', 0)->plaintext ? 1 : 0;
        $this->Twitter->Tweets = filter_var($html->find('a[data-element-term=tweet_stats]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->Twitter->Following = filter_var($html->find('a[data-element-term=following_stats]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        $this->Twitter->Followers = filter_var($html->find('a[data-element-term=follower_stats]', 0)->plaintext, FILTER_SANITIZE_NUMBER_INT) *1;
        gc_disable();
    }
    
    private function getCache($prefix, $byHash = true){
        
        $cacheFile = $this->cacheDirectory . $prefix . ($byHash ? '.' . $this->hash : '') . '.cache';
        return @file_get_contents($cacheFile);
        
    }
    
    private function setCache($prefix, $content, $byHash = true){
        
        $cacheFile = $this->cacheDirectory . $prefix . ($byHash ? '.' . $this->hash : '') . '.cache';
        return @file_put_contents($cacheFile, $content);
        
    }
    
    private function collect($url){
        
        $ch = curl_init("");
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
        
    }
    
    public function returnAsJSON(){
        echo json_encode($this);
    }
    
}

new CelebrityMeter($_GET['q']);
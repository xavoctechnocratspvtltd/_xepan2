<?php
class Frontend extends ApiFrontend {
    public $api_public_path;
    public $api_base_path;
    public $currentBridge=null;
    public $isLive = false;
    public $requestDoc= false;
    public $responseDoc = false;

    public $is_frontend= true;
    public $is_admin= false;
    public $isEditing = false;
    public $current_website_name=null;

    public $page_class='xepan\cms\page_cms';

    function init() {
        parent::init();        

        //DB Connect not default added by rakesh
        $this->dbConnect();
        
        // Might come handy when multi-timezone base networks integrates
        $this->today = date('Y-m-d',strtotime($this->recall('current_date',date('Y-m-d'))));
        $this->now = date('Y-m-d H:i:s',strtotime($this->recall('current_date',date('Y-m-d H:i:s'))));

        $this->api_public_path = dirname(@$_SERVER['SCRIPT_FILENAME']);
        $this->api_base_path = dirname(dirname(@$_SERVER['SCRIPT_FILENAME']));

        $this->add('jUI');
        

        $this->api->pathfinder
            ->addLocation(array(
                'addons' => array('vendor','shared/addons2','shared/addons'),
            ))
            ->setBasePath($this->pathfinder->base_location->getPath() );
        
        // Should come from any local DB store
        $this->xepan_addons = $addons = ['xepan\\base'];
        $this->xepan_app_initiators = $app_initiators=[];


        $app_initiators=[];
        foreach ($addons as $addon) {
            $this->xepan_app_initiators[$addon] = $app_initiators[$addon] = $this->add("$addon\Initiator")->setup_frontend();
        }


    }

    function defaultTemplate(){

        $epan_domain_array = $this->recall('epan_domain_array',[]);

        $url = "{$_SERVER['HTTP_HOST']}";
        $domain = str_replace('www.','',$this->extract_domain($url))?:'www';
        $sub_domain = str_replace('www.','',$this->extract_subdomains($url))?:'www';

        $service_host = $this->getConfig('xepan-service-host',false);
        if($service_host && $service_host!==$domain){
            $epan = $domain;
        }else{
            $epan = $sub_domain;
        }

        if(!isset($epan_domain_array[$epan])){            
            $this->readConfig("websites/www/config.php");
            $this->dbConnect();
            $epan_hash = $this->db->dsql()->table('epan')->where($this->db->dsql()->orExpr()->where('name',$epan)->where('aliases','like','"%'.$epan.'%"'))->getHash();
            $epan_domain_array[$epan] = $epan_hash['name'];
            $this->memorize('epan_domain_array',$epan_domain_array);
        }
        // die(print_r($epan,true));
        // die($epan['name']);

        $current_website = $this->current_website_name = $epan;

        $this->readConfig("websites/$this->current_website_name/config.php");
        
        if($tmpt = $this->recall('xepan-template-preview',$_GET['xepan-template-preview'])){
            $this->memorize('xepan-template-preview',$tmpt);
            $this->addLocation(array(
                'page'=>array("xepantemplates/$tmpt"),
                'js'=>array("xepantemplates/$tmpt/js"),
                'css'=>array("xepantemplates/$tmpt","xepantemplates/$tmpt/css"),
                'template'=>["xepantemplates/$tmpt"],
                'addons'=> ['xepantemplates/'.$tmpt]
            ))->setParent($this->pathfinder->base_location);
        }

        $this->addLocation(array(
            'page'=>array("websites/$current_website/www"),
            'js'=>array("websites/$current_website/www/js"),
            'css'=>array("websites/$current_website/www","websites/$current_website/www/css"),
            'template'=>["websites/$current_website/www"],
            'addons'=> ['websites/'.$current_website.'/www']
        ))->setParent($this->pathfinder->base_location);


        if($tmpt = $this->getConfig('xepan-template',false)){
            $this->addLocation(array(
                'page'=>array("xepantemplates/$tmpt"),
                'js'=>array("xepantemplates/$tmpt/js"),
                'css'=>array("xepantemplates/$tmpt","xepantemplates/$tmpt/css"),
                'template'=>["xepantemplates/$tmpt"],
                'addons'=> ['xepantemplates/'.$tmpt]
            ))->setParent($this->pathfinder->base_location);
        }

        if($this->pathfinder->locate('template',$t='layout/'.$this->page.'.html','path',false)){
            return ['layout/'.$this->page];
        }elseif($this->pathfinder->locate('template','layout/default.html','path',false)){            
            return ['layout/default'];
        }elseif(!$this->layout){
            throw new \Exception("No Website content found or No Template in Website for ". $this->current_website_name, 1);
        }else{
            throw new \Exception("Error Processing Request", 1);            
        }  
    }

    protected function loadStaticPage($page){
        $layout = $this->layout ?: $this;
        try{
            $t='page/'.str_replace('_','/',strtolower($page));
            $this->template->findTemplate($t);
            $this->page_object=$layout->add($page,$page,'Content',array($t));
        }catch(\PathFinder_Exception $e2){
            try{
                $t='page/'.strtolower($page);
                $this->template->findTemplate($t);
                $this->page_object=$layout->add($page,$page,'Content',array($t));
            }catch(\PathFinder_Exception $e3){
                $t=strtolower($page);
                try{
                    $original_page=null;
                    if(!file_exists(getcwd().'/websites/'.$this->current_website_name.'/www/'.str_replace("_", "/",$page).".html")){
                        $page='404';
	                    $original_page=$page;
                    }
                    $this->page_object=$layout->add($this->page_class,['name'=>$page,'page_requested'=>$original_page],'Content',[str_replace("_", "/",$page)]);
                }catch(\PathFinder_Exception $e4){
                    $this->app->redirect('404');
                }
            }
        }

        return $this->page_object;
    }

    function extract_domain($domain)
    {
        if(preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i", $domain, $matches))
        {
            return $matches['domain'];
        } else {
            return $domain;
        }
    }

    function extract_subdomains($domain)
    {
        $subdomains = $domain;
        $domain = $this->extract_domain($subdomains);
        $subdomains = rtrim(strstr($subdomains, $domain, true), '.');

        return $subdomains;
    }

}

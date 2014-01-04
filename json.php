<?php
if(isset($_GET["callback"])){
 header('Content-Type: application/javascript; charset=utf-8');
} else {
 header('Content-Type: application/json; charset=utf-8');
}
header('Cache-Control: max-age=420');
date_default_timezone_set('Asia/Brunei');
$cachefile=__DIR__ ."/cash/".(int)(date_timestamp_get(date_create())/7200).".json";
$headers = apache_request_headers(); 
if(file_exists($cachefile)){
if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($cachefile))) {
    header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($cachefile)).' GMT', true, 304);
}
	if(isset($_GET["callback"])) echo $_GET["callback"],"(";
	echo file_get_contents($cachefile);
	if(isset($_GET["callback"])) echo ")";
	exit();
}

$common=array("d", //filters 2d,3d
"movie","time","person","year","way","day","thing","man","world","life","hand","part","child","eye","woman","place","work","week","case","point","government","company","number","group","problem","fact","be","have","do","say","get","make","go","know","take","see","come","think","look","want","give","use","find","tell","ask","work","seem","feel","try","leave","call","good","new","first","last","long","great","little","own","other","old","right","big","high","different","small","large","next","early","young","important","few","public","bad","same","able","to","of","in","for","on","with","at","by","from","up","about","into","over","after","beneath","under","above","the","and","a","that","I","it","not","he","as","you","this","but","his","they","her","she","or","an","will","my","one","all","would","there","their",);
function not_common($v){
	global $common;
	return !in_array(strtolower($v),$common);
}


$filter_dates=array();
$d=date_create();
for($i=0;$i<7;$i++){
	$filter_dates[]=$d->format("d-m");
	$d->add(new DateInterval('P1D'));
}
function date_check($t){
	global $filter_dates;
	return in_array($t,$filter_dates);
}

function d($n){
	return $n<9?"0".$n:$n;
}


 function get_web_page( $url ) { //stole off stackoverflow lolz.
        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';

        $ch      = curl_init( $url );
        curl_setopt_array( $ch,array(
            CURLOPT_CUSTOMREQUEST  =>"GET",        //set request type post or get
            CURLOPT_POST           =>false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            //CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            //CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        ));
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return empty($errmsg) ?  mb_convert_encoding(str_replace(array("\r\n","\n","\r"),'',preg_replace('/\s+/', ' ',$content)),"HTML-ENTITIES","UTF-8") : array("error"=>":(");
    }

 function get_post( $url,$data) { //stole off stackoverflow lolz.
        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';


        $ch      = curl_init( $url );
        curl_setopt_array( $ch,array(
            CURLOPT_CUSTOMREQUEST  => "POST",        //set request type post or get
            CURLOPT_POST           => true,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_POSTFIELDS	   => http_build_query($data),
            //CURLOPT_COOKIEFILE     =>"cookie.txt", //set cookie file
            //CURLOPT_COOKIEJAR      =>"cookie.txt", //set cookie jar
            CURLOPT_REFERER        => $url,
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        ));
        $content = curl_exec( $ch );
        $err     = curl_errno( $ch );
        $errmsg  = curl_error( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return empty($errmsg) ?  mb_convert_encoding(preg_replace('/\s+/', ' ',str_replace(array('&nbsp;',"\r\n","\n","\r"),'',$content)),"HTML-ENTITIES","UTF-8") : array("error"=>":(");
    }




function get_vis($url,$name,$namePattern=null,$replace=null){
	$movies=array();
	$data=get_web_page($url);
	//should add check for failure here
	$pattern="/<td class=\"PrintShowTimesFilm\" colspan=\"2\">(.+?)<\/td>.+?(<tr style=\"height:5px;\">|<\/table>)/";
	$pattern2="/(<td class=\"PrintShowTimesDay\" valign=\"top\" colspan=\"1\">(.+?)<\/td><td class=\"PrintShowTimesSession\" valign=\"top\" colspan=\"1\">(.+?)<\/td>)+?/";
	$num=preg_match_all($pattern,$data,$matches);
	for($i=0;$i<$num;$i++){
		$dateNum=preg_match_all($pattern2,$matches[0][$i],$mm);
		$times=array();
		for($j=0;$j<$dateNum;$j++){
			$date=date_parse($mm[2][$j]);
			$date=d($date["day"])."-".d($date["month"]);
			$time=explode(',',$mm[3][$j]);
			$time=array_map(function($v){
				$temp=date_parse($v);
				return sprintf("%02d:%02d",$temp["hour"],$temp["minute"]);
			},$time);
			if(date_check($date))
				$times[$name][$date]=$time;
		}
		$movieName=preg_replace('/\s+/', ' ',trim($matches[1][$i]));
		if($namePattern)
			$movieName=preg_replace($namePattern,$replace,$movieName);
		if(count($times))
			$movies[$movieName]=$times;
	}

	return $movies;
}


function get_mall(){
	return get_vis(
		"http://mall-ticket.com/visPrintShowTimes.aspx?visCinemaID=&ReturnURL=visShowtimes.aspx%3fAspxAutoDetectCookieSupport%3d1",
		//"http://localhost/freedom/mall.htm",
		"The-Mall",
		"/ Blockbuster$/",'' //Y U NO NAME CONSISTENTLY?!!
	);
}

function get_timesSquare(){
	$movies=array();

	$data=get_web_page("http://timescineplex.com/schedule/");
	//$data=get_web_page("http://localhost/freedom/times.htm");
	$data=str_replace('&nbsp;','',$data);
	$pattern="/<div class=\"movie-title\">(.+?)<p>.+?class=\"table-scheds\">(.+?)<!--End Movie Row-->/";
	$pattern2="/<div class=\"textwidget\">(.+?)<\/.+?table-contents\">(.+?)<\/div>/";
	preg_match_all($pattern,$data,$matches);
	foreach($matches[2] as $k=>$m){
		preg_match_all($pattern2,$m,$mm);
		$dates=array_map(function($v){
			$temp=date_parse($v);
			return d($temp["day"])."-".d($temp["month"]);
		},$mm[1]);
		$times=array_map(function($v){
			$v=explode(',',$v);
			$v=array_map(function($x){ 
				if(!$x) return "";
				$temp=date_parse($x);
				return sprintf("%02d:%02d",$temp["hour"],$temp["minute"]);	
			},$v);
			return $v;
			//;
		},$mm[2]);
		//foreach
		global $filter_dates;
		$times=array_intersect_key(array_filter(array_combine($dates,$times),function($v){ return $v[0]; }), array_flip( $filter_dates ) );

		if(count($times))
			$movies[trim(iconv("UTF-8", "ISO-8859-1//TRANSLIT",html_entity_decode($matches[1][$k])))]["Times-Square"]=$times;
	}
return $movies;
}

function get_qlap(){
	$movies=array();
	$d=new DateTime();
	do{
		$qlap=get_web_page("http://www.qlapcineplex.com/iphone/services/getshowtimes.php?dt=".$d->format('m/d/Y'));
		$qlap=json_decode(@$qlap,true);
		$t=$d->format('d-m');
		if(date_check($t))
		foreach(@$qlap["items"] as $q){
			$time=explode(',',$q["showtime"]);
			$time=array_map(function($v){
				$temp=date_parse($v);
				return sprintf("%02d:%02d",$temp["hour"],$temp["minute"]);
			},$time);
				$movies[$q["mname"]]['qlap'][$t]=$time;
		}
		$d->add(new DateInterval('P1D'));	
	} while (!empty($qlap["items"]));

	return $movies;
	
}

 function get_psbSeria(){
	$data=json_decode(get_web_page("https://www.facebook.com/feeds/page.php?format=json&id=279414305434229"),true);
	//$data=json_decode(get_web_page("http://localhost/freedom/feeds.js"),true);
	$movies=array();
	foreach($data["entries"] as $m){
		$content=preg_replace(array('/<br \/>/','/<.+?>/','/\-[ \n]+?/'),array("\n",'','-'),$m["content"]); //*/
		$content=explode("\n",$content);
		$content=array_filter($content,function($v){
			return trim($v) && !preg_match("/\(.+?\).+?\(.+?\)/",$v);
		});
		//$content=$m["content"];
		if(preg_match("/Be Advice MOVIE SCREENING TIME ARE SUBJECT TO CHANGE Movie Schedule For(.+?) Updated/",@$content[0],$matches)){
			$time=preg_replace(array("/[^\d\-\/ ]/","/ .+?/"),array(""," "),$matches[1]);
			$time=array_values(array_filter(explode(" ",$time),"strlen"));
			$times=array();
			$d=date_create(str_replace("/","-",$time[0]));
			$e=date_create(@$time[1] ?: '1-1-1970');	
			do {
				$times[]=$d->format('d-m');
				$d->add(new DateInterval('P1D'));	
			} while ($d<=$e);

			$mms=array();
			foreach($content as $c){
				$mm=explode('-',$c);
				if(count($mm)>1){
					$mm[1]=explode(',',preg_replace('/[^0-9:,]/','',$mm[1]));
					foreach($times as $t){
						if(date_check($t))
							$movies[trim($mm[0])]["PSBSeria"][$t]=$mm[1];
					}
				}
			}
		}
	}
	return $movies;
}

function compare_name($str1,$str2){  //breaks down if movie is not listed
	$str1=preg_replace(array('/[^a-z ]/i','/\s+/'),array(" ",' '),trim($str1)); //if add number becomes wrong. because sequals
	$str2=preg_replace(array('/[^a-z ]/i','/\s+/'),array(" ",' '),trim($str2)); //but what if... the title is only numbers.s
	if($str1==$str2) return 100;

	//$score=100-LevenshteinDistance($str1,$str2);
	$str1=array_filter(array_filter(explode(' ',strtolower($str1)),'strlen'),'not_common');
	$str2=array_filter(array_filter(explode(' ',strtolower($str2)),'strlen'),'not_common');
	$match=count(array_intersect($str1,$str2));
	return $match==count($str1) && $match==count($str2) ? 100 : $match;
}

function get_rating($m){
	$matched=preg_match_all("/images\/frontpage\/star2(|b|c).gif/",$m,$stars);
	$star=5;
	if($stars)
		foreach($stars[1] as $p){
			$star-= ($p=="c") ? 1 : (($p=="b") ? 0.5 : 0);
		}
	return $matched==5 ? $star : "N/A";
}

function add_times($name,$value){
	global $movies;
	$likely=0;
	$likely_str="";
	foreach($movies as $k=>$m){
		$curr=compare_name($name,$k);
		if($curr==100){
			$likely_str=$k;
			break;
		}
		if($curr>$likely){
			$likely_str=$k;
			$likely=$curr;
		}
	}
	if($likely_str)
		$movies[$likely_str]["cinema"][]=array($name=>$value);
	//false positives
}




function now_showing(){
	//$movies=get_web_page('http://localhost/freedom/temp.htm');
	$movies=get_web_page("http://www.cinema.com.my/movies/nowshowing.aspx?search=moviename");
	$movies=str_replace('../','http://www.cinema.com.my/',$movies);
	$movies=str_replace('00a.jpg','00.jpg',$movies);
	$pattern="/<tr valign=\"top\"> <td style=\"width: 50px\"> .+?<\/tr/";
	$pattern2="/<tr.+?type=\"image\".+?src=\"(.+?)\".+\">(.+?)<\/a> \(.+?>(.+?)<.+?italic;\">(.+?)<.+?lbl_oneliner\">(.+?)<\/span.+?genre\" style=\"font-style:italic;\">(.+?)<\/sp.+?_lbl_format\">(.+)<\/sp.+alt_movie_contents\.aspx\?search=(.+)\"/";
	preg_match_all($pattern,$movies,$matches);
	$movies=array();
	foreach($matches[0] as $m){
		preg_match($pattern2,$m,$mm);
		$moreInfo=explode(',',@$mm[4]);
		$movies[@$mm[2]]=array(
			"id"=>@$mm[8],
			"name"=>@$mm[2],
			"rating"=>get_rating($m),
			"runningTime"=>@$moreInfo[0],
			"language"=>@$moreInfo[1],
			"image"=>@$mm[1],
			"synopsis"=>@$mm[5],
			"genre"=>@$mm[6],
			"format"=>@$mm[7],
		);
	}
	return $movies;
}

function get_upcoming(){
	
	$d=date_create();
	$start_date=$d->format('d M Y');
	$d->add(new DateInterval('P7D'));
	$end_date=$d->format('d M Y');
	$movies=get_post('http://www.cinema.com.my/movies/advancesearch.aspx',array(
	'__VIEWSTATE'=>'/wEPaA8FDzhkMGNlZWFlNTZjMDI3NRgHBSxjdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGxzdHZfbW92aWVzUGxheWluZw88KwAKAgc8KwAGAAgCBmQFHl9fQ29udHJvbHNSZXF1aXJlUG9zdEJhY2tLZXlfXxYIBSdjdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGlidG5TdGFydERhdGUFJWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkaWJ0bkVuZERhdGUFQWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkbHN0dl9tb3ZpZXNQbGF5aW5nJGN0cmwwJHBvc3Rlcl9pbWdfYnRuBUFjdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGxzdHZfbW92aWVzUGxheWluZyRjdHJsMiRwb3N0ZXJfaW1nX2J0bgVBY3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRsc3R2X21vdmllc1BsYXlpbmckY3RybDQkcG9zdGVyX2ltZ19idG4FQWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkbHN0dl9tb3ZpZXNQbGF5aW5nJGN0cmw2JHBvc3Rlcl9pbWdfYnRuBUFjdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGxzdHZfbW92aWVzUGxheWluZyRjdHJsOCRwb3N0ZXJfaW1nX2J0bgVCY3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRsc3R2X21vdmllc1BsYXlpbmckY3RybDEwJHBvc3Rlcl9pbWdfYnRuBTJjdGwwMCRDb250ZW50UGxhY2VIb2xkZXIxJGxzdHZfbW92aWVzUGxheWluZyRQYWdlcg88KwAEAQMCBmQFOWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkbW92aWVzX3RhYjEkTGlzdFZpZXdfTm93U2hvd2luZw88KwAKAgc8KwAmAAgCJmQFNWN0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkbW92aWVzX3RhYjEkbHZVbmRhdGVkTW92aWVzDzwrAAoCBzwrABQACAIUZAU3Y3RsMDAkQ29udGVudFBsYWNlSG9sZGVyMSRsc3R2X21vdmllc1BsYXlpbmckRGF0YVBhZ2VyMQ88KwAEAQMCBmQFM2N0bDAwJENvbnRlbnRQbGFjZUhvbGRlcjEkbW92aWVzX3RhYjEkTXVsdGlWaWV3X1RhYg8PZGZkhWNNJY0Ol+AN7fYDg6trWUmFVUM=', //not sure if static
	'ctl00$ContentPlaceHolder1$txtStartDate'=>$start_date,
	'ctl00$ContentPlaceHolder1$txtEndDate'=>$end_date,
	)); //*/
	//$movies = get_web_page('http://localhost/freedom/post.htm');
	$movies=str_replace('../','http://www.cinema.com.my/',$movies);
	$movies=str_replace('00a.jpg','00.jpg',$movies);
	$pattern="/<tr valign=\"top\"> <td style=\"width: 50px\"> .+?<\/tr/";
	$pattern2="/<tr.+?type=\"image\".+?src=\"(.+?)\".+?movie_contents.aspx\?search=(.+?)&quot;.+\">(.+?)<\/a> \(.+?>(.+?)<.+?italic;\">(.+?)<.+?lang\" style=\"font-style:italic;\">(.+?)<\/.+?lbl_oneliner\">(.+?)<\/span.+?genre\" style=\"font-style:italic;\">(.+?)<\/sp/";
	preg_match_all($pattern,$movies,$matches);
	$movies=array();
	foreach($matches[0] as $m){
		preg_match($pattern2,$m,$mm);
		//preg_match_all($rating,$m,$stars);
		//print_r($mm);
		$movies[$mm[3]]=array(
			"id"=>$mm[2],
			"name"=>$mm[3],
			"rating"=>get_rating($m),
			"runningTime"=>$mm[5],
			"language"=>$mm[6],
			"image"=>$mm[1],
			"synopsis"=>$mm[7],
			"genre"=>$mm[8],
		);
	}
	return $movies;
}




$movies=array_merge(get_upcoming(),now_showing());
foreach(get_mall() as $name=>$time){
	add_times($name,$time);
}

foreach(get_timesSquare() as $name=>$time){
	add_times($name,$time);
}

foreach(get_qlap() as $name=>$time){
	add_times($name,$time);	
}

foreach(get_psbSeria() as $name=>$time){
	add_times($name,$time);	
}

$movies=array_filter($movies,function($v){
	return !empty($v["cinema"]);
});	



$json_data= json_encode($movies); 

file_put_contents($cachefile,$json_data);
if(isset($_GET["callback"])) echo $_GET["callback"],"(";
echo $json_data;
if(isset($_GET["callback"])) echo ")";




?>

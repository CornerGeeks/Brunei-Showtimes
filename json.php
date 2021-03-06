<?php
if(isset($_GET["callback"])){
	header('Content-Type: application/javascript; charset=utf-8');
} else {
 	header('Content-Type: application/json; charset=utf-8');
}
header('Cache-Control: max-age=420');
date_default_timezone_set('Asia/Brunei');   //to keep date() consistent


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
	return $n<10?"0".$n:$n;
}


function prepare_input($s){ //cleans raw html. removes newline. probably don't need
	return mb_convert_encoding(preg_replace('/\s+/', ' ',str_replace(array('&nbsp;',"\r\n","\n","\r"),'',$s)),"HTML-ENTITIES","UTF-8");
}

function compare_name($str1,$str2){  //breaks down if movie is not listed
	$str1=preg_replace(array('/[^a-z ]/i','/\s+/'),array(" ",' '),trim($str1)); //if add number becomes wrong. because sequals
	$str2=preg_replace(array('/[^a-z ]/i','/\s+/'),array(" ",' '),trim($str2)); //but what if... the title is only numbers.s
	if($str1==$str2) return 100; //exactly the same

	$str1=array_filter(array_filter(explode(' ',strtolower($str1)),'strlen'),'not_common');  //reduce false positives by filtering out common words
	$str2=array_filter(array_filter(explode(' ',strtolower($str2)),'strlen'),'not_common');	 //example: The Hobbit and The Mask would have 50% match.
	$match=count(array_intersect($str1,$str2));
	return $match==count($str1) && $match==count($str2) ? 100 : $match;
	//probably can make this better. should maybe store already calculated names. perhaps email new matches to see if it's correct?
	//maybe manually set?
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


 function get_post( $url,$data=null) { //stole off stackoverflow lolz. probably should fold into one		
		if(!preg_match("/^https?:\/\//", $url))
 			$url = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'] . ":" . $_SERVER['SERVER_PORT'] . dirname($_SERVER['PHP_SELF']) . "/" . $url;
        $user_agent='Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0';


        $ch      = curl_init( $url );
        curl_setopt_array( $ch, array(
            CURLOPT_CUSTOMREQUEST  => $data? "POST" : "GET",        //set request type post or get
            CURLOPT_POST           => $data? true : false,        //set to GET
            CURLOPT_USERAGENT      => $user_agent, //set user agent
            CURLOPT_POSTFIELDS	   => $data? http_build_query($data) : null,
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
        $content = curl_exec   ( $ch );
        $err     = curl_errno  ( $ch );
        $errmsg  = curl_error  ( $ch );
        $header  = curl_getinfo( $ch );
        curl_close( $ch );
        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        return empty($errmsg) ? prepare_input($content)  : array("error"=>":(");
    }

function get_vis_print($url,$name,$namePattern=null,$replace=null){
	$movies=array();
	$data=get_post($url);
	//should add check for failure here
	$pattern="/PrintShowTimesFilm\" colspan=\"2\">(.+?)<\/.+?<tr>(.+?)<td colspan=\"2\"><\/td>[ ]+?<\/tr><tr>/";
	$pattern2="/(<td class=\"PrintShowTimesDay\" valign=\"top\" colspan=\"1\">(.+?)<\/td><td class=\"PrintShowTimesSession\" valign=\"top\" colspan=\"1\">(.+?)<\/td>)+?/";
	$num=preg_match_all($pattern,$data,$matches);
	for($i=0;$i<$num;$i++){
		// <td class="PrintShowTimesFilm" colspan="2">47 Ronin Blockbuster</td> </tr><tr> <td class="PrintShowTimesDay" valign="top" colspan="1">Daily</td><td class="PrintShowTimesSession" valign="top" colspan="1">12:15PM, 2:30PM, 4:45PM, 7:00PM, 9:15PM</td> </tr><tr style="height:5px;">
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
			if($mm[2][$j]=="Daily"){
				global $filter_dates;
				foreach($filter_dates as $v){
					$times[$name][$v]=$time;
				}
			}
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
	return get_vis_print(
	 	(@$_GET['local'] == "1" ? 
		"tests/mall.txt" :
//		"http://mall-ticket.com/visShowtimes.aspx"
		"http://mall-ticket.com/visPrintShowTimes.aspx"
		),
		"The-Mall",
		"/ Blockbuster$/",'' //Y U NO NAME CONSISTENTLY?!!
	);
}

function get_timesSquare(){
	$movies=array();
	if(@$_GET['local'] == "1")
		$data=get_post("tests/timessquare.txt");
	else
		$data=get_post("http://timescineplex.com/schedule/");
	
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
		$mm[2]=array_filter($mm[2],function($s){
			return strlen(str_replace(" ","",$s));
		});
		$times=array_map(function($v){
			if(!$v) return "";
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
		$data=array();
		foreach($times as $i=>$v){
			$data[$dates[$i]]=$v;
		}
		global $filter_dates;
		$times=array_intersect_key(array_filter($data,function($v){ return $v[0]; }), array_flip( $filter_dates ) );
		ksort($times);
		if(count($times))
			$movies[trim(iconv("UTF-8", "ISO-8859-1//TRANSLIT",html_entity_decode($matches[1][$k])))]["Times-Square"]=$times;
	}
	return $movies;
}

function get_qlap(){
	$movies=array();
	$d=new DateTime();
	do{
	 	if(@$_GET['local'] == "1")
			$qlap=get_post("tests/qlap.txt"); //probably should check if this infinite loops
		else
			$qlap=get_post("http://www.qlapcineplex.com/iphone/services/getshowtimes.php?dt=".$d->format('m/d/Y'));

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
	 	if(@$_GET['local'] == "1") # don't get following days
	 		break;
	} while (!empty($qlap["items"]));

	return $movies;
	
}

 function get_psbSeria(){
 	if(@$_GET['local'] == "1")
		$data=json_decode(get_post("tests/seria.txt"),true);
	else
		$data=json_decode(get_post("https://www.facebook.com/feeds/page.php?format=json&id=279414305434229"),true);
	$movies=array();
	foreach($data["entries"] as $m){
		$content=preg_replace(array('/<br \/>/','/<.+?>/','/\-[ \n]+?/'),array("\n",'','-'),$m["content"]); //*/
		$content=explode("\n",$content);
		$content=array_filter($content,function($v){
			return trim($v) && !preg_match("/\(.+?\).*?\(.+?\)/",$v);
		});
		//$content=$m["content"];
		if(preg_match("/Be Advice MOVIE SCREENING TIME ARE SUBJECT TO CHANGE Movie Schedule For(.+?) Updated/",@$content[0],$matches)){
			$time=preg_replace(array("/[^\d\-\/ ,]/","/ .+?/"),array(""," "),$matches[1]);
			$time=array_values(array_filter(explode(" ",$time),"strlen"));
			$times=array();
			//var_dump($time);
			$time[0]=str_replace(array(",","/"),"-",$time[0]);
			$time[0]=array_filter(explode("-",$time[0]),"strlen");
			if(count($time)>1)
				$time[1]=str_replace(array(",","/"),"-",$time[1]);
			if(count($time[0])<3)
				array_push($time[0],"2014"); //insert new year here so lazy wth psb
			$time[0]=implode("-",$time[0]);
			//echo $time[0];
			$d=date_create($time[0]);
			$endDate=date_create(@$time[1] ?: '1-1-1970');	//for range. fill dates
			do {
				$times[]=$d->format('d-m');
				$d->add(new DateInterval('P1D'));	
			} while ($d<=$endDate);

			$mms=array();
			unset($content[0]);
			$label=null;
			foreach($content as $c){
				$mm=explode('-',$c);
				if(count($mm)<2){
					if($label) {
						$mm=array($label,$c);
						$label=null;
					} else {
						$label=$c;
					}
				}
				if(count($mm)>1){
					$mm[1]=explode(',',preg_replace(array("/ and /",'/[^0-9:,]/'),array(',',''),$mm[1]));

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


function now_showing(){
	if(@$_GET['local'] == "1")	
		$movies=get_post('tests/temp.htm');
	else
		$movies=get_post("http://www.cinema.com.my/movies/nowshowing.aspx?search=moviename");
	$movies=str_replace('../','http://www.cinema.com.my/',$movies);
	$movies=str_replace('00a.jpg','00.jpg',$movies);
	$pattern="/<tr valign=\"top\"> <td style=\"width: 50px\"> .+?<\/tr/"; //looking at it now, could probably combine the regexes.
	$pattern2="/<tr.+?type=\"image\".+?src=\"(.+?)\".+\">(.+?)<\/a> \(.+?>(.+?)<.+?italic;\">(.+?)<.+?lbl_oneliner\">(.+?)<\/span.+?genre\" style=\"font-style:italic;\">(.+?)<\/sp.+?_lbl_format\">(.+)<\/sp.+alt_movie_contents\.aspx\?search=(.+)\"/";
	preg_match_all($pattern,$movies,$matches);
	$movies=array();
	foreach($matches[0] as $m){
		preg_match($pattern2,$m,$mm);
		$moreInfo=explode(',',@$mm[4]);
		$movies[@$mm[2]]=array(
		//	"id"=>@$mm[8],
			"name"=>@$mm[2],
		//	"rating"=>get_rating($m),
			"runningTime"=>@$moreInfo[0],
		//	"language"=>@$moreInfo[1],
			"image"=>@$mm[1],
			"synopsis"=>@$mm[5],
			"genre"=>@$mm[6],
		//	"format"=>@$mm[7],
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
	$movies=str_replace('../','http://www.cinema.com.my/',$movies);
	$movies=str_replace('00a.jpg','00.jpg',$movies);
	$pattern="/<tr valign=\"top\"> <td style=\"width: 50px\"> .+?<\/tr/";
	$pattern2="/<tr.+?type=\"image\".+?src=\"(.+?)\".+?movie_contents.aspx\?search=(.+?)&quot;.+\">(.+?)<\/a> \(.+?>(.+?)<.+?italic;\">(.+?)<.+?lang\" style=\"font-style:italic;\">(.+?)<\/.+?lbl_oneliner\">(.+?)<\/span.+?genre\" style=\"font-style:italic;\">(.+?)<\/sp/";
	preg_match_all($pattern,$movies,$matches);
	$movies=array();
	foreach($matches[0] as $m){
		preg_match($pattern2,$m,$mm);
		$movies[$mm[3]]=array(
		//	"id"=>$mm[2],
			"name"=>$mm[3],
		//	"rating"=>get_rating($m),
			"runningTime"=>$mm[5],
		//	"language"=>$mm[6],
			"image"=>$mm[1],
			"synopsis"=>$mm[7],
			"genre"=>$mm[8],
		);
	}
	return $movies;
}




// print_r($movies);
/*
Array
(
	[Movie name] => Array
	(
		[Cinema] => Array
		(
			[DD-MM] => Array
			(
				[0] => HH:MM
				[1] => HH:MM
			)
		)
	)
    [Paranormal Activity: The Mark Ones :] => Array
        (
            [PSBSeria] => Array
                (
                    [05-01] => Array
                        (
                            [0] => 12:30
                            [1] => 14:35
                            [2] => 16:15
                            [3] => 19:40
                            [4] => 21:20
                        )

                    [04-01] => Array
                        (
                            [0] => 10:45
                            [1] => 12:30
                            [2] => 14:15
                            [3] => 17:40
                            [4] => 19:20
                            [5] => 21:10
                            [6] => 23:10
                        )

                )

        )

)
*/

function cache(					
		//automatically caches the results of expensive functions like scrapers. Parameters:
		$func,      // callable
		$name="",  	// label?
		$time=2,   	// hours
		$json=false // stores in json. maybe should be decode_json instead
	)
{
	$cachefile=__DIR__ ."/cash/".$name.(int)(date_timestamp_get(date_create())/($time*60*60)).".json";
	$lastfile=__DIR__ ."/cash/".$name."LAST.json"; //backup
	$headers = apache_request_headers(); 
	if(file_exists($cachefile) && @empty($_GET['local'])){
		if (isset($headers['If-Modified-Since']) && (strtotime($headers['If-Modified-Since']) == filemtime($cachefile))) {
		    header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($cachefile)).' GMT', true, 304);
		    //supposed to be return 304 not modified
		    exit(); //do we need exit here? how does this work? 
		}
		$data=file_get_contents($cachefile);
		if(!$json)
			$data=unserialize($data);  //wonder should json? seems faster. other option is var_export
	} else {
		$data=$func();
		//if error / !$data
		//  use lastfile data //put this after email. don't want totally silent failure
		//	email something is wrong with $name?
		//	probably changed html and broken regex.
		//else
		//  write as usual.
		$data_str=!$json?serialize($data):$data; //wonder should json? seems faster other option is var_export
		file_put_contents($cachefile,$data_str);
		file_put_contents($lastfile,$data_str);
	}
	if(@$_GET['print'] == 1){
		echo $name,":";
		print_r($data);
	} 
	return $data;
}

if(isset($_GET["callback"])) echo $_GET["callback"],"(";
echo cache(function(){
	$cinema_data=array(
		 "mall"=>cache("get_mall","mall"),
		"times"=>cache("get_timesSquare","times"),
	  //"times"=>get_timesSquare(),
		 "qlap"=>cache("get_qlap","qlap"),
		"seria"=>cache("get_psbSeria","seria")
	);

	$movies=array_merge(
		cache("get_upcoming","upcoming",24),
		cache("now_showing","now_showing",24)
	);

	foreach($cinema_data as $cname=>$data){
		foreach($data as $mname=>$time){
			$likely=0;
			$likely_str="";
			foreach($movies as $k=>$m){  
				//tries to find a match to existing database of movies
				$curr=compare_name($mname,$k);
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
				$movies[$likely_str]["cinema"][]=array($mname=>$time);  //should fix rubbish nestedness
			//will dump movie times if it's not in the list. example. QLap is showing old indonesian films.
			//might need fix? Perhaps maintain our own movie DB
		}
	}

	$json_data= json_encode(  //move out json_encode? can remove json check in cache(). maybe way slower
		array_filter($movies,function($v){  
			return !empty($v["cinema"]); //only show movies that are released in brunei
		})
	);
	return $json_data;
	//echo $json_data;
},"main",2,true);
if(isset($_GET["callback"])) echo ")";


//*/


?>

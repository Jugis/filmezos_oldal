<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function convertToHoursMins($time, $format = '%02d:%02d') {
	if ($time < 1) {
	    return;
	}
	    $hours = floor($time / 60);
	    $minutes = ($time % 60);
	    return sprintf($format, $hours, $minutes);
}
?>
<?php
function Login(){

	//username and password of account
	$username = 'lokutus';
	$password = '204703147';

	//set the directory for the cookie using defined document root var
	$directory = '/home/admin/web/filmezek.com/public_html';

	$dir = $directory."/stemp";
	//build a unique path with every request to store 
	//the info per user with custom func. 
	//$path = $dir;
	//login form action url
	$url = "https://www.sorozatbarat.online/login"; 
	$postinfo = "login=".$username."&password=".$password.'&redirect=/&loginsubmit=Belépés';

	$cookie_file_path = $dir."/cookies.txt";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_NOBODY, false);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

	curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file_path);
	//set the cookie the site has for certain features, this is optional
	curl_setopt($ch, CURLOPT_COOKIE, "cookiename=0");
	curl_setopt($ch, CURLOPT_USERAGENT,
	    "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI']);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);

	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $postinfo);
	curl_exec($ch);

	//page with the content I want to grab
	//curl_setopt($ch, CURLOPT_URL, "https://www.sorozatbarat.online/video/series/6105/Korhataros_szerelem_online_sorozat/01_evad");
	//do stuff with the info with DomDocument() etc
	//$html = curl_exec($ch);
	curl_close($ch);

}
//sorozatbarat bejelentkezés
//Login();
//Bejelentkezés után tartalom leszedése:
function get_html($link){

	$directory = '/home/admin/web/filmezek.com/public_html';
	$dir = $directory."/stemp";
	$defaul_url = $link;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $link);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_COOKIEJAR, $dir.'/cookies.txt');
	curl_setopt($ch, CURLOPT_COOKIEFILE, $dir.'/cookies.txt');
	$link = curl_exec($ch);
	//print_r($link);
	//$info = curl_getinfo($ch);
	curl_close($ch);
	return $link;
}

//adott évad-ról szóló összes adatot tartalmazza.
function Download_Datas($link){

	$datapage = get_html($link);
	$datahtml = str_get_html($datapage);
	//cím innentől
	$datatitle = $datahtml->find('h2[class=navTitle]');
	$title = $datatitle[0]->innertext;
	$title = str_replace('részei', '', $title);
	preg_match("/([0-9,]+)/", $title, $matches);
	$evadszam = $matches[0];
	$evadszamint = intval($evadszam);
	$title = str_replace($evadszam, $evadszamint, $title);
	$title = str_replace('sorozat', '-', trim($title));

	//kiszedi az angol címet a címből
	preg_match('#\((.*?)\)#', $title, $match);
	if (!empty($match[0])) {
		$angol_cim = $match[0];
		$title = str_replace($angol_cim, '', $title);
		$title = str_replace('  ', ' ', $title );
	}
	//cím idáig.

	//tartalom innentől
	$movie_content = $datahtml->find('div[class=textcontent]');
	$adatlap_adatok = $movie_content[0]->innertext;
	$html = str_get_html($adatlap_adatok);
	
	$tart = $html->find('p');
	$tartalom = $tart[0]->innertext;
	preg_match("/további/", $tartalom, $kepek);
	if (!empty($kepek[0])) {
		$tartalom = $tart[1]->innertext;
	}
	//tartalom idáig

	//évjárat innentől
	$megjelenes_eve = $datahtml->find('td[style=width: 100px;]');
	$evjarat = $megjelenes_eve[0]->innertext;
	$img = str_get_html($evjarat);
	$imgevjarat = $img->find('img[alt=Megjelenés éve]');
	$evjarat = str_replace($imgevjarat[0]->outertext, '', $evjarat);
	$evjarat = str_replace('&nbsp;', '', $evjarat);
	//évjárat idáig

	//IMDB link innentől
	$imdbblock = $datahtml->find('td[style=width: 60px;]');
	$imdba = $imdbblock[0]->innertext;
	$imdbadat = str_get_html($imdba);
	$imdblink = $imdbadat->find('a');
	$imdbhref = $imdblink[0]->href;
	//IMDB link idáig

	//IMDB rate
	preg_match("/title\/(.*)\//", $imdbhref, $imdbid1);
	if (!empty($imdbid1[1])) {

	    $imdbid = $imdbid1[1];
	    $str = 'http://www.omdbapi.com/?i='.$imdbid1[1].'&apikey=e245cbc2';
	    $imdbinfo = json_decode(file_get_contents($str), true);
	    $imdb_rate = $imdbinfo['imdbRating'];
	}
	//IMDB rate idáig

	//borito innentől
	$borito = $imdbinfo['Poster'];
	$imdbID = $imdbinfo['imdbID'];
	if (!empty($imdbID)) {
		$borito_name = $imdbID.'.jpg';
    	file_put_contents("/home/admin/web/filmezek.com/public_html/img/$borito_name",file_get_contents($borito));
	}
	

	//borito idáig

	//IMDB runtime innentől
	    $runtime = $imdbinfo['Runtime'];
	    $imdb_ido = ConvertToHoursMins($runtime, '%2d óra %02d perc');
	//IMDB runtime idáig

	$szineszek = array();
	$rendezok = array();
	$irok = array();
	foreach ($html->find('li[class=truncate]') as $szereplo) {
		$filmografia = $szereplo->innertext;
		$td = str_get_html($filmografia);
		foreach ($td->find('a') as $a) {
			
			$adatokszoveg = $a->title;
			$szov = explode(' ', $adatokszoveg);

			if ($szov[count($szov)-1] == 'színész') {
				$szineszek[] = $a->innertext;
			}elseif ($szov[count($szov)-1] == 'rendező') {
				$rendezok[] = $a->innertext;
			}elseif ($szov[count($szov)-1] == 'író') {
				$irok[] = $a->innertext;
			}
		}
	}
	include('database_movie.php');
	include('slug.php');
	$get_categorys = ("SELECT * FROM movie_category");
	$get_categorys_result = $conn->query($get_categorys); 
	while ( $row = mysqli_fetch_array($get_categorys_result)) {
		$main_category[] = $row['cat_name'];
	}

	$cimke = $datahtml->find('p[class=tags]');
	$cimkek = $cimke[0]->innertext;
	$cimkekhtml = str_get_html($cimkek);
	$category = array();
	foreach ($cimkekhtml->find('a') as $cimkeurl) {

			for ($i=0; $i <count($main_category) ; $i++) { 
				if ($cimkeurl->innertext==$main_category[$i]) {
					$category[] = $cimkeurl->innertext;
				}
			}
	}

	//$tart = $html->find('li[class=truncate]');
	//$tartalom = $tart[0]->innertext;
	

	//print_r($adatlap_adatok);
	
	$datas['title'] = $title;

	if (isset($angol_cim)) {

		$datas['eredeticim'] = $angol_cim;

	}else{
		$datas['eredeticim'] = '';
	}
	$datas['slug'] = post_slug($title);
	if (isset($borito_name)) {
		$datas['borito'] = $borito_name;
	}else{
		$datas['borito'] = '';
	}
	
	$datas['tartalom'] = $tartalom;
	$datas['evjarat'] = $evjarat;
	$datas['imdblink'] = $imdbhref;
	$datas['imdbrate'] = $imdb_rate;
	$datas['idotartam'] = $imdb_ido;
	$datas['szineszek'] = $szineszek;
	$datas['rendezok'] = $rendezok;
	$datas['irok'] = $irok;
	$datas['category'] = $category;
	$datas['evad'] = $evadszamint;
	return $datas;

}

function Upload_Database($evad_datas, $resz){

	include('database_movie.php');
	echo "adatbázis: ";
	date_default_timezone_set('Europe/Budapest');
    $datum = date('Y-m-d H:i:s', time());

	//print_r($evad_datas);
	//print_r($resz);
	if (!empty($resz['link'])) {
	
		$get_series_from_database = ("SELECT * FROM movie_content WHERE MovieTitle LIKE '".$evad_datas['title']."'");
		$get_series_from_database_result = $conn->query($get_series_from_database);
		if ($get_series_from_database_result->num_rows>0) {

			while ($row = mysqli_fetch_array($get_series_from_database_result)){
	           $Movieid = $row['Movieid'];
	           $MovieTitle = $row['MovieTitle'];
	        }
		}

		//létezik a film, frissíteni kell
		if (!empty($Movieid)) {

			echo '<a href="http://filmezek.com/sorozat.php?id='.$Movieid.'" target="_blank">'.$MovieTitle.'</a><br>';
			//linkek hozzáadás innentől
	        for ($c=0; $c <count($resz['reszszam']) ; $c++) { 
		      
		        for ($k=0; $k <count($resz['link'][$c]) ; $k++) { 

			          //tarhely nev
	                  $select_tarhely = ("SELECT * FROM movie_tarhely_nev WHERE movie_tarhely_nev_domain='".$resz['tarhely'][$c][$k]."' ");
	                  $tarhely_result = $conn->query($select_tarhely);

	                   if ($tarhely_result->num_rows > 0) {
	                    while ($row = mysqli_fetch_array($tarhely_result)){
	                        $movie_tarhely_nev_id = $row['movie_tarhely_nev_id'];
	                    }
	                   }else{

	                      $insert_tarhely = "INSERT INTO movie_tarhely_nev (movie_tarhely_nev_domain) VALUES ('".$resz['tarhely'][$c][$k]."')";
	                      $done_tarhely_nev = $conn->query($insert_tarhely);
	                      if ($done_tarhely_nev) {

	                          $select_tarhely = ("SELECT * FROM movie_tarhely_nev WHERE movie_tarhely_nev_domain='".$resz['tarhely'][$c][$k]."' ");
	                          $tarhely_result = $conn->query($select_tarhely);

	                          while ($row = mysqli_fetch_array($tarhely_result)){
	                              $movie_tarhely_nev_id = $row['movie_tarhely_nev_id'];
	                          }
	                      }
	                      
	                    }
	                  //tarhely nev igáig

	                  //tarhely tipus
	                   $select_tarhely_tipus = ("SELECT * FROM movie_tarhely_tipus WHERE movie_tarhely_tipus_minoseg = 'DVD' and movie_tarhely_tipus_nyelv = '".$resz['nyelv'][$c][$k]."'");
	                   
	                  $tarhely_tipus_result = $conn->query($select_tarhely_tipus);

	                   if ($tarhely_tipus_result->num_rows > 0) {
	                        while ($row = mysqli_fetch_array($tarhely_tipus_result)){
	                                $movie_tarhely_tipus_id = $row['movie_tarhely_tipus_id']; 
	                        } 
	                    }else{

	                      $insert_tarhely_tipus = "INSERT INTO movie_tarhely_tipus (movie_tarhely_tipus_minoseg, movie_tarhely_tipus_nyelv) VALUES ('DVD', '".$resz['nyelv'][$c][$k]."')";
	                      $done_tarhely_tipus = $conn->query($insert_tarhely_tipus);

	                      if ($done_tarhely_tipus) {
	                        $select_tarhely_tipus = ("SELECT * FROM movie_tarhely_tipus WHERE movie_tarhely_tipus_minoseg='DVD' and movie_tarhely_tipus_nyelv ='".$resz['nyelv'][$c][$k]."'");
	                        $tarhely_tipus_result = $conn->query($select_tarhely_tipus);

	                          while ($row = mysqli_fetch_array($tarhely_tipus_result)){
	                              $movie_tarhely_tipus_id = $row['movie_tarhely_tipus_id'];
	                          }
	                      }

	                    }
	                  //tarhely tipus idáig

	                    //sorozat rész
	                      $sorozat_resz = ("SELECT * FROM sorozat_evad_resz WHERE resz = '".$resz['reszszam'][$c]."' and evad = '".$evad_datas['evad']."' ");
	                      $sorozat_resz_evad = $conn->query($sorozat_resz);

	                       if ($sorozat_resz_evad ->num_rows > 0) {
	                            while ($row = mysqli_fetch_array($sorozat_resz_evad )){
	                                    $evad_resz_id = $row['evad_resz_id']; 
	                            } 
	                        }else{

	                          $sorozat_resz_insert = "INSERT INTO sorozat_evad_resz (resz, evad) VALUES ('".$resz['reszszam'][$c]."', '".$evad_datas['evad']."')";
	                          $sorozat_resz_evad = $conn->query($sorozat_resz_insert);

	                          $sorozat_resz = ("SELECT * FROM sorozat_evad_resz WHERE resz = '".$resz['reszszam'][$c]."' and evad = '".$resz['reszszam'][$c]."' ");
	                       
	                          $sorozat_resz_evad = $conn->query($sorozat_resz);

	                           if ($sorozat_resz_evad->num_rows > 0) {
	                                while ($row = mysqli_fetch_array($sorozat_resz_evad )){
	                                     $evad_resz_id = $row['evad_resz_id']; 
	                                } 

	                            }
	                          }
	                    //sorozat rész idáig


	                    if($movie_tarhely_nev_id and $movie_tarhely_tipus_id){
	                      //tarhely joing innen
	                      $select_tarhely = ("SELECT * FROM sorozat_tarhely WHERE movie_tarhely_link = '".$resz['link'][$c][$k]."'");
	                      $tarhely_result = $conn->query($select_tarhely );
	                      if ($tarhely_result->num_rows > 0) {
	                        //exit('létező link');
	                      	echo $resz['link'][$c][$k]." link létezik!\n";
	                      }else{

	                        $insert_tarhely = "INSERT INTO sorozat_tarhely (movie_tarhely_link, Movieid, movie_tarhely_nev_id,movie_tarhely_tipus_id, tarhely_upload_date, sorozat_evad_resz) VALUES ('".$resz['link'][$c][$k]."', '".$Movieid."','".$movie_tarhely_nev_id."', '".$movie_tarhely_tipus_id."', '".$datum."', '".$evad_resz_id."')";
	                        $done_tarhely_links = $conn->query($insert_tarhely);
	                        
	                      }
	                    }

	                    if (isset($done_tarhely_links)) {
	                    	echo $resz['link'][$c][$k]." link feltöltve!\n";
	                    }

		        }//részen belüli tárhely linkek for vége

		    }//egy rész feltöltve összes linken első for vége.
	        //linkek hozzáadás idáig

		}else{
			//nem létezik, fel kell tölteni.
			echo 'Feltöltés adatbázisba: '.$evad_datas['title'].'<br>';
	                //echo $description;
	              $sql = "INSERT INTO movie_content (Movieid, MovieTitle, MovieTitleOriginal, MovieDescription, MovieYear, MovieCover, Movie_ido, MovieTrailer, MovieSlug, Movie_imdb_link, IMDB_Rate, MovieTipus, MovieUploadDate) VALUES (NULL, '".$evad_datas['title']."', '".$evad_datas['eredeticim']."', '".$evad_datas['tartalom']."', '".$evad_datas['evjarat']."', '".$evad_datas['borito']."', '".$evad_datas['idotartam']."', '', '".$evad_datas['slug']."', '".$evad_datas['imdblink']."', '".$evad_datas['imdbrate']."', 'sorozat','".$datum."')";
	              
	              //echo $sql;
	                $done = $conn->query($sql); 

	                if($done){
	                	echo $evad_datas['title'].' sikeresen feltölve!';

	                    $select_movie = ("SELECT * FROM movie_content WHERE MovieTitle='".$evad_datas['title']."'");
	                    $movie_result = $conn->query($select_movie); 
	                    while ($row = mysqli_fetch_array($movie_result)){
	                       $Movieid = $row['Movieid'];
	                    }
	                    
	                    //rendezo hozzáadás innentől
	                    for ($i=0; $i <count($evad_datas['rendezok']) ; $i++) { 
	                      if($evad_datas['rendezok'][$i]!=""){
		                      //létezik a rendező?
		                      $select_rendezo = ("SELECT * FROM movie_rendezo WHERE rendezo_nev='".$evad_datas['rendezok'][$i]."' ");
		                      $rendezo_result = $conn->query($select_rendezo);

		                      if ($rendezo_result->num_rows > 0) {
		                        while ($row = mysqli_fetch_array($rendezo_result)){
		                                $rendezo_id = $row['rendezo_id'];
		                        }
		                        
		                        if(!empty($rendezo_id)){
		                          //$select_rendezo_joint = ("SELECT * FROM rendezo_join WHERE rendezo_id ='$rendezo_id' and Movieid='".$Movieid."'");
		                         
		                          //$rendezo_join_result = $conn->query($select_rendezo_joint);

		                          //if ($rendezo_join_result->num_rows == 0) {

		                              $rendezo_joint_insert = "INSERT INTO rendezo_join (Movieid, rendezo_id) VALUES ('".$Movieid."', '".$rendezo_id."')";
		                              $done_rendezo_joint = $conn->query($rendezo_joint_insert); 
		                              //print "rendezo hozzáadva: ".$done_rendezo_joint;
		                          //}
		                        }

		                      }else{
		                          //ha nem létezik egyáltalán akkor hozzáadja.
		                          $sql_rend = "INSERT INTO movie_rendezo (rendezo_nev) VALUES ('".$evad_datas['rendezok'][$i]."')";
		                          $done_r = $conn->query($sql_rend); 
		                          if($done_r){
		                            $select_rendezo = ("SELECT * FROM movie_rendezo WHERE rendezo_nev='".$evad_datas['rendezok'][$i]."' ");
		                            $rendezo_result = $conn->query($select_rendezo); 
		                              while ($row = mysqli_fetch_array($rendezo_result)){
		                                $rendezo_id = $row['rendezo_id'];
		                              }
		                              $rendezo_joint_insert = "INSERT INTO rendezo_join (Movieid, rendezo_id) VALUES ('".$Movieid."', '".$rendezo_id."')";
		                              $done_rendezo_joint = $conn->query($rendezo_joint_insert); 
		                              //print "rendezo hozzáadva: ".$done_rendezo_joint;

		                          }
		                          
		                      } 
		                    }
	                    }//for rendező vége

	                    //rendezo hozzáadás idáig

	                    //kategoria hozzáadás innentől
	                    if (!empty($evad_datas['category'])) {
	                 
		                    for ($i=0; $i <count($evad_datas['category']) ; $i++) { 
		                      //létezik a rendező?
		                      $select_kategoria = ("SELECT * FROM movie_category WHERE cat_name='".$evad_datas['category'][$i]."' ");
		                      $kategoria_result = $conn->query($select_kategoria);

		                      if ($kategoria_result->num_rows > 0) {
		                        while ($row = mysqli_fetch_array($kategoria_result)){
		                                $cat_id = $row['cat_id'];
		                        }
		                        
		                        if(!empty($cat_id)){
		                              $kategoria_joint_insert = "INSERT INTO category_join (Movieid, cat_id) VALUES ('".$Movieid."', '".$cat_id."')";
		                              $done_kategoria_joint = $conn->query($kategoria_joint_insert); 
		                              //print "kategoria hozzáadva: ".$done_kategoria_joint;
		                        }

		                      }else{
		                          //ha nem létezik egyáltalán akkor hozzáadja.
		                          $sql_cat = "INSERT INTO movie_category (cat_name) VALUES ('".$evad_datas['category'][$i]."')";
		                          $done_cat = $conn->query($sql_cat); 
		                          if($done_cat){

		                            $select_kategoria= ("SELECT * FROM movie_category WHERE cat_name='".$evad_datas['category'][$i]."' ");
		                            $kategoria_result = $conn->query($select_kategoria); 
		                              while ($row = mysqli_fetch_array($kategoria_result)){
		                                $cat_id = $row['cat_id'];
		                              }
		                              $kategoria_joint_insert = "INSERT INTO category_join (Movieid, cat_id) VALUES ('".$Movieid."', '".$cat_id."')";
		                              $done_kategoria_joint = $conn->query($kategoria_joint_insert); 
		                              //print "rendezo hozzáadva: ".$done_kategoria_joint;

		                          }
		                          
		                      } 

		                    }//for rendező vége
	                  }
	                  //kategoria hozzáadás idáig

	                  //szereplo hozzáadás innentől
	                    if(!empty($evad_datas['szineszek'])){
		                    for ($i=0; $i <count($evad_datas['szineszek']) ; $i++) { 
			                      //létezik a rendező?
			                      if($evad_datas['szineszek'][$i]!=""){


			                      $select_szereplo = ("SELECT * FROM movie_szereplok WHERE szereplo_nev='".$evad_datas['szineszek'][$i]."' ");
			                      $szereplo_result = $conn->query($select_szereplo);

			                      if ($szereplo_result->num_rows > 0) {
			                        while ($row = mysqli_fetch_array($szereplo_result)){
			                                $szereplo_id = $row['szereplo_id'];
			                        }
			                        
			                        if(!empty($szereplo_id)){
			                          $select_szereplo_joint = ("SELECT * FROM szereplo_join WHERE szereplo_id ='".$szereplo_id."' and Movieid='".$Movieid."'");
			                          
			                          $szereplo_join_result = $conn->query($select_szereplo_joint);

			                          if ($szereplo_join_result->num_rows == 0) {

			                            $szereplo_joint_insert = "INSERT INTO szereplo_join (Movieid, szereplo_id) VALUES ('".$Movieid."', '".$szereplo_id."')";
			                            $done_szereplo_joint = $conn->query($szereplo_joint_insert); 
			                          }
			                        }

			                      }else{
			                          //ha nem létezik egyáltalán akkor hozzáadja.
			                          $sql_szerep = "INSERT INTO movie_szereplok (szereplo_nev) VALUES ('".$evad_datas['szineszek'][$i]."')";
			                          $done_szerep = $conn->query($sql_szerep); 
			                          if($done_szerep){

			                            $select_szereplo= ("SELECT * FROM movie_szereplok WHERE szereplo_nev='".$evad_datas['szineszek'][$i]."' ");
			                           
			                            $szereplo_result = $conn->query($select_szereplo); 
			                              while ($row = mysqli_fetch_array($szereplo_result)){
			                                $szereplo_id = $row['szereplo_id'];
			                              }
			                              $szereplo_joint_insert = "INSERT INTO szereplo_join (Movieid, szereplo_id) VALUES ('".$Movieid."', '".$szereplo_id."')";
			                              $done_szereplo_joint = $conn->query($szereplo_joint_insert); 
			                              //print "szereplok hozzáadva: ".$done_szereplo_joint;

			                          }
			                          
			                      } 

			                    }
		                    }
	                    }//for rendező vége
	                    //szereplo hozzáadás idáig

	                    //iro hozzáadás innentől
	                    if(!empty($evad_datas['irok'])){
		                    for ($i=0; $i <count($evad_datas['irok']) ; $i++) { 
		                      //létezik a rendező?
		                      if($evad_datas['irok'][$i]!=""){
		                      $select_iro = ("SELECT * FROM movie_iro WHERE movie_iro_nev='".$evad_datas['irok'][$i]."' ");
		                      $iro_result = $conn->query($select_iro);

		                      if ($iro_result->num_rows > 0) {
		                        while ($row = mysqli_fetch_array($iro_result)){
		                                $movie_iro_id = $row['movie_iro_id'];
		                        }
		                        
		                        if(!empty($movie_iro_id)){
		                              $iro_joint_insert = "INSERT INTO movie_iro_join (Movieid, movie_iro_id) VALUES ('".$Movieid."', '".$movie_iro_id."')";
		                              $done_iro_joint = $conn->query($iro_joint_insert); 
		                              //print "iro hozzáadva: ".$done_iro_joint;
		                        }

		                      }else{
		                          //ha nem létezik egyáltalán akkor hozzáadja.
		                          $sql_iro = "INSERT INTO movie_iro (movie_iro_nev) VALUES ('".$evad_datas['irok'][$i]."')";
		                          $done_iro = $conn->query($sql_iro); 
		                          if($done_iro){

		                            $select_iro = ("SELECT * FROM movie_iro WHERE movie_iro_nev='".$evad_datas['irok'][$i]."' ");
		                            $iro_result = $conn->query($select_iro); 
		                              while ($row = mysqli_fetch_array($iro_result)){
		                                $movie_iro_id = $row['movie_iro_id'];
		                              }
		                              $iro_joint_insert = "INSERT INTO movie_iro_join (Movieid, movie_iro_id) VALUES ('".$Movieid."', '".$movie_iro_id."')";
		                              $done_iro_joint = $conn->query($iro_joint_insert); 
		                              //print "iro hozzáadva: ".$done_iro_joint;

		                          }
		                          
		                      } 

		                    }
		                  }
	                    }//for rendező vége
	                    //iro hozzáadás idáig

	                    //linkek hozzáadás innentől
	                    for ($c=0; $c <count($resz['reszszam']) ; $c++) { 

					        //echo $resz['reszszam'][$c].'. rész:<br>';
					      
					        for ($k=0; $k <count($resz['link'][$c]) ; $k++) { 

						          //tarhely nev
			                      $select_tarhely = ("SELECT * FROM movie_tarhely_nev WHERE movie_tarhely_nev_domain='".$resz['tarhely'][$c][$k]."' ");
			                      $tarhely_result = $conn->query($select_tarhely);

			                       if ($tarhely_result->num_rows > 0) {
			                        while ($row = mysqli_fetch_array($tarhely_result)){
			                            $movie_tarhely_nev_id = $row['movie_tarhely_nev_id'];
			                        }
			                       }else{

			                          $insert_tarhely = "INSERT INTO movie_tarhely_nev (movie_tarhely_nev_domain) VALUES ('".$resz['tarhely'][$c][$k]."')";
			                          $done_tarhely_nev = $conn->query($insert_tarhely);
			                          if ($done_tarhely_nev) {

			                              $select_tarhely = ("SELECT * FROM movie_tarhely_nev WHERE movie_tarhely_nev_domain='".$resz['tarhely'][$c][$k]."' ");
			                              $tarhely_result = $conn->query($select_tarhely);

			                              while ($row = mysqli_fetch_array($tarhely_result)){
			                                  $movie_tarhely_nev_id = $row['movie_tarhely_nev_id'];
			                              }
			                          }
			                          
			                        }
			                      //tarhely nev igáig

			                      //tarhely tipus
			                       $select_tarhely_tipus = ("SELECT * FROM movie_tarhely_tipus WHERE movie_tarhely_tipus_minoseg = 'DVD' and movie_tarhely_tipus_nyelv = '".$resz['nyelv'][$c][$k]."'");
			                       
			                      $tarhely_tipus_result = $conn->query($select_tarhely_tipus);

			                       if ($tarhely_tipus_result->num_rows > 0) {
			                            while ($row = mysqli_fetch_array($tarhely_tipus_result)){
			                                    $movie_tarhely_tipus_id = $row['movie_tarhely_tipus_id']; 
			                            } 
			                        }else{

			                          $insert_tarhely_tipus = "INSERT INTO movie_tarhely_tipus (movie_tarhely_tipus_minoseg, movie_tarhely_tipus_nyelv) VALUES ('DVD', '".$resz['nyelv'][$c][$k]."')";
			                          $done_tarhely_tipus = $conn->query($insert_tarhely_tipus);

			                          if ($done_tarhely_tipus) {
			                            $select_tarhely_tipus = ("SELECT * FROM movie_tarhely_tipus WHERE movie_tarhely_tipus_minoseg='DVD' and movie_tarhely_tipus_nyelv ='".$resz['nyelv'][$c][$k]."'");
			                            $tarhely_tipus_result = $conn->query($select_tarhely_tipus);

			                              while ($row = mysqli_fetch_array($tarhely_tipus_result)){
			                                  $movie_tarhely_tipus_id = $row['movie_tarhely_tipus_id'];
			                              }
			                          }

			                        }
			                      //tarhely tipus idáig

			                        //sorozat rész
				                      $sorozat_resz = ("SELECT * FROM sorozat_evad_resz WHERE resz = '".$resz['reszszam'][$c]."' and evad = '".$evad_datas['evad']."' ");
				                      $sorozat_resz_evad = $conn->query($sorozat_resz);

				                       if ($sorozat_resz_evad ->num_rows > 0) {
				                            while ($row = mysqli_fetch_array($sorozat_resz_evad )){
				                                    $evad_resz_id = $row['evad_resz_id']; 
				                            } 
				                        }else{

				                          $sorozat_resz_insert = "INSERT INTO sorozat_evad_resz (resz, evad) VALUES ('".$resz['reszszam'][$c]."', '".$evad_datas['evad']."')";
				                          $sorozat_resz_evad = $conn->query($sorozat_resz_insert);

				                          $sorozat_resz = ("SELECT * FROM sorozat_evad_resz WHERE resz = '".$resz['reszszam'][$c]."' and evad = '".$resz['reszszam'][$c]."' ");
				                       
				                          $sorozat_resz_evad = $conn->query($sorozat_resz);

				                           if ($sorozat_resz_evad->num_rows > 0) {
				                                while ($row = mysqli_fetch_array($sorozat_resz_evad )){
				                                     $evad_resz_id = $row['evad_resz_id']; 
				                                } 

				                            }
				                          }
				                    //sorozat rész idáig


				                    if($movie_tarhely_nev_id and $movie_tarhely_tipus_id){
			                          //tarhely joing innen
			                          $select_tarhely = ("SELECT * FROM sorozat_tarhely WHERE movie_tarhely_link = '".$resz['link'][$c][$k]."'");
			                          $tarhely_result = $conn->query($select_tarhely );
			                          if ($tarhely_result->num_rows > 0) {
			                            //exit('létező link');
			                          }else{

			                            $insert_tarhely = "INSERT INTO sorozat_tarhely (movie_tarhely_link, Movieid, movie_tarhely_nev_id,movie_tarhely_tipus_id, tarhely_upload_date, sorozat_evad_resz) VALUES ('".$resz['link'][$c][$k]."', '".$Movieid."','".$movie_tarhely_nev_id."', '".$movie_tarhely_tipus_id."', '".$datum."', '".$evad_resz_id."')";
			                            $done_tarhely_links = $conn->query($insert_tarhely);
			                            
			                          }
			                        }

			                        if (isset($done_tarhely_links)) {
			                        	echo $resz['link'][$c][$k].' link feltöltve!';
			                        }

					        }//részen belüli tárhely linkek for vége

					    }//egy rész feltöltve összes linken első for vége.
	                    //linkek hozzáadás idáig

	                }//Ha sikerült feltölteni done if vége

		}//Ha nincs fent a sorozat else vége
	}else{
		echo 'nincsenek linkek';
	}
}//Upload database function vége
require_once('simple_html_dom.php');
/*
$link = get_html('https://www.sorozatbarat.online/');
$html = str_get_html($link);
$ret = $html->find('div[class=thumbs]');
$friss = $ret[0]->innertext;
$friss.= $ret[1]->innertext;

$html2 = str_get_html($friss);
foreach ($html2->find('a') as $value) {
	$frisslinkek[] = $defaul_url.$value->href;
}
$frisslinkek = array_values( array_unique($frisslinkek));
echo '<pre>';
print_r($frisslinkek);
*/
$link = 'https://www.sorozatbarat.online/video/series/636/Modern_csalad_online_sorozat/01_evad';
$adatlapactual = get_html($link);
//print_r($adatlap);
$adatlaphtml = str_get_html($adatlapactual);
$evad = '';
foreach ($adatlaphtml->find('ul[class=seasons]', 0)->children() as $evadok) {
	$evad.= $evadok->innertext;
}
$evadokhtml = str_get_html($evad);
$evadlink = $evadokhtml->find('a');
$evadlinkek = array();
for ($i=0; $i <count($evadlink) ; $i++) { 
	
	$evadlinkek[] = 'https://www.sorozatbarat.online'.$evadlink[$i]->href;
}

print_r($evadlinkek);
$sorozatadatok = array();
//evadok
for ($j=0; $j <count($evadlinkek) ; $j++) { 

	//echo $evadlinkek[$j];
	$evad_datas[$j] = Download_Datas($evadlinkek[$j]);

	echo "<pre>";
	print_r($evad_datas[$j]);
	echo "</pre>";
	//itt le kell tölteni az aktuális évadhoz tartozó adatlap adatokat.
	$evadadatlap = get_html($evadlinkek[$j]);

	$adatlaphtml2 = str_get_html($evadadatlap);

	//meghatározza, hogy hány évadot tartalmaz egy sorozat
	$evadresz = '';
	foreach ($adatlaphtml2->find('ul[itemprop=episode]', 0)->children() as $evadok) {
		$evadresz.= $evadok->innertext;
	}

	//letölti az összees részlinkjét egy évadon belül.
	$reszekhtml = str_get_html($evadresz);
	$reszlinks = $reszekhtml->find('a[itemprop=url]');
	$reszlinkek = array();
	for ($r=0; $r <count($reszlinks) ; $r++) { 
		$reszlinkek[] = 'https://www.sorozatbarat.online'.$reszlinks[$r]->href;
	}
	echo '<pre>';
	print_r($reszlinkek);

	$resz = array();
	for ($i=0; $i <count($reszlinkek) ; $i++) {
		//reszszám meghatározása 
		$reszadatlap = get_html($reszlinkek[$i]);
		$reszekhtml = str_get_html($reszadatlap);
		$navtitle = $reszekhtml->find('h2[class=navTitle]');
		$resztitle = $navtitle[0]->innertext;
		$r1 = explode('-', $resztitle);
		$reszszam = intval(trim(str_replace('. rész', '', $r1[1])));
		$sorozatadatok['resz'][$i] = $reszszam;
		//reszszám meghatározása idáig

		//reszektabla
		$episodes = $reszekhtml->find('table[class=episodes]');
		$epizodoktabla = $episodes[0]->innertext;
		$epizodoktablahtml = str_get_html($epizodoktabla);
		//ha megvannak a részlinkek akkor végig megy rajtuk és kiszedi az aktuális részhez tartozó összes linket.
		$trc = 0;
		foreach ($epizodoktablahtml->find('tr') as $tds) {
			
			if ($trc!=0 or $trc!=2 or $trc!=(count($epizodoktablahtml->find('tr'))-1) or $trc!=(count($epizodoktablahtml->find('tr'))-2)) {
				//egy sor adatai
				$tr = $tds->innertext;

				//nyelv meghatározása
				$egy_sor = str_get_html($tr);
				$egy_sor_nyelv = $egy_sor->find('img[class=flags]');

				if (!empty($egy_sor_nyelv)) {
					$nyelv = trim($egy_sor_nyelv[0]->title);
					$sorozatadatok['nyelv'][$i][] = $nyelv;
				}
				//nyelv meghatározása idáig

				//Tárhely név innentől
				$egy_sor_tarhely = $egy_sor->find('td');
				if (!empty($egy_sor_tarhely)) {

					if (!empty($egy_sor_tarhely[1])) {
						$tarhely = $egy_sor_tarhely[1]->innertext;
						$tarhely1 = explode('<br>', $tarhely);
						$tarhely1[0] = str_replace('<strong style="color: red;">(tipp)</strong>', '', $tarhely1[0]);
						$tt = explode('#', $tarhely1[0]);
						$tt2 = explode('.', $tt[0]);
						$tarhely_vegleges = strtolower($tt2[0]);

						$sorozatadatok['tarhely'][$i][] = $tarhely_vegleges;
					}
					
				}
				//Tárhely név idáig


				//Tárhely link innentől
				$egy_sor_tarhely_link = $egy_sor->find('td[align=right]');
				if (!empty($egy_sor_tarhely_link)) {
					$tarhely_link = $egy_sor_tarhely_link[0]->outertext;
					$tarhely_linkhtml = str_get_html($tarhely_link);
					$thlink = $tarhely_linkhtml->find('a');
					$videoembed = $thlink[0]->href;
					$videoembed = str_replace('/video/redirect/', 'http://www.filmorias.com/ugras-a-videohoz/', $videoembed);
					$videoembed_adatlap = get_html($videoembed);
					$player_code = 'data-hidenodesonsuccess';
					$dataurl = 'data-url';
					$evdata = str_get_html($videoembed_adatlap);
					$embed = $evdata->find('puremotion['.$player_code.'=#playercode]');
					echo $embed[0]->$dataurl."\n";
					$sorozatadatok['link'][$i][] = $embed[0]->$dataurl;
					//$linkek['reszek'][$i][] = $embed[0]->$dataurl;
					//echo $embed[0]->$dataurl;
				}
				
				//előzetes??
				//Tárhely link idáig				
				
			}
			//echo $trc;
			sleep(5);
			$trc++;
			
		}


		$resz = array('reszszam' => $sorozatadatok['resz'],  'nyelv' => $sorozatadatok['nyelv'], 'tarhely' => $sorozatadatok['tarhely'], 'link' => $sorozatadatok['link']);
	}

	//print_r ($resz);
	if (!empty($resz)) {
		
		Upload_Database($evad_datas[$j], $resz);
	}else{
		echo "üres a rész";
	}
	//print_r($sorozatadatok);
	//Itt kellene most adatbázisba menteni az évadhoz tartozó részeket.
	

}//evad linkek for



?>
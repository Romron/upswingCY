<?php


	function get_web_page($url)		//  объединить в один класс с get_google_page
		{
				global $browser, $webpage, $app;

			$q = 0;
			$patern_capcha = '#.*?google.*?\/sorry#';
			$patern_charset = '#charset="*?\s*?(.*)"#isU';

			do {
			//  если флаг поднят то была страница капчи тогда даём возможн. ввыести капчу
			if ($flag_capcha) {
				echo "<br>PAUZA in function <b>get_web_page</b> input capcha";
				$app->set_foreground_window();	// показать окно эмулятора
				$app->pause(0);
				$flag_capcha = 0;	// капча решена флаг опускаем 
				}		
			$browser->navigate($url);
			///echo "  browser busy : ";
			while ($browser->is_busy())		// если браузер занят навигацией дольше чем указано в $q остановить навигацию
				{
				  //echo $q." ";
				  $q = ++$q;
				  if ($q > 10) {	  
						$browser->stop(); 
						$q = 0;
						break;
						}
				  usleep(1000000);	// одна сек.
				}
			// проверяем URL полученной страницы для определения страницы капчи
			$url_current_pege = $webpage->get_location_url();
			if ( preg_match($patern_capcha,$url_current_pege) !== 0 ) {
				$flag_capcha = 1;
				}
			} while ( $flag_capcha );
			
			$web_page = $webpage->get_source();

			if (!$web_page) {		// если ошибка....
					echo '<br><font color = "red">ERROR in function <b>get_web_page</b> no get URL: </font><br>'.$url;    
					}	
			
			
			if (!preg_match_all($patern_charset,$web_page,$result_charset,PREG_PATTERN_ORDER)) { //если патерн не найден 
				//echo '<br><font color = "red">ERROR in function <b>get_web_page</b> patern_charset no found. </font>';
				$result_charset[1][0] = "utf-8";
				//return false;
        		}			

        	$cod_1 = $result_charset[1][0];
        	echo "<br>coding:   ".$cod_1;

			$web_page = iconv ($cod_1,"windows-1251//IGNORE",$web_page);
				if (!$web_page) {
					echo '<br><font color = "red">ERROR in function <b>get_web_page</b> no can iconv: </font><br>'.$url;    
				}

			return $web_page;	
		}

	function get_google_page( $kye,$n_res=100 )
		{	  
			global $browser, $webpage, $app;

			$url_serch_page = "http://www.google.ru/search?num=".$n_res."&q=".$kye;
			$patern_capcha = '#.*?google.*?\/sorry#';
			
			do {
			//  если флаг поднят то была страница капчи и даём возможн. ввыести капчу
			if ($flag_capcha) {
				echo "<br>PAUZA  Vvedite capchu";
				$app->set_foreground_window();	// показать окно эмулятора
				$app->pause(0);
				$flag_capcha = 0;	// капча решена флаг опускаем 
				}		
			$browser->navigate($url_serch_page);
			$browser->wait(1); 			// ждёт пока браузер освободится
			// проверяем URL полученной страницы для определения страницы капчи
			$url_current_pege = $webpage->get_location_url();
			if ( preg_match($patern_capcha,$url_current_pege) !== 0 ) {
				$flag_capcha = 1;
				}
			} while ( $flag_capcha );
			$page_search = $webpage->get_source();
			if (!$page_search) {		// если ошибка....
					echo '<br><font color = "red">ERROR no get URL: </font><br>'.$url_serch_page;    
					}	
			$page_search = iconv ("UTF-8","windows-1251//IGNORE",$page_search);
			return $page_search;
		}

	function parse_URL_with_saerch($page,$kye)
	    {
	      global $browser, $webpage, $app;

	      $patern_next_page = '#<span style="display:block;margin-left:53px">Следующая</span></a>#'; 

	      // поиск следующих страниц в выдачи
	      do  {    //  цыкл должен выполняться пока на странице поиска есть надпись "следующая"
	      $patern_link = '#<h3 class="r"><a href="(.*?)"#i'; 
	      if (!preg_match_all($patern_link,$page,$result,PREG_PATTERN_ORDER)) { 
	            echo '<br><font color = "red">ERROR in function <b>parse_URL_with_saerch</b> patern_link no found. </font><br>';
	            return false;
	        }          
	        for ($i=0; $i < count($result); $i++) {   //  редактируем полученный двумерный массив перебором 
	            for ($q=0; $q < count($result[$i]); $q++) { 
	                $result[$i][$q] = strip_tags($result[$i][$q]);    //  удалить все возможные HTML теги
	                if ($result[$i][$q]) {      //  преобразование двумерного массива в одномерный
	                  $arr_URLs[] = $result[$i][$q];  
	                }
	              }
	         } 
	        // получить очередную страницу поисковой выдачи 
	        $p = $p + 1;
	        echo "<br>Processed ".$p."serch page <br>";
	        if ($p > 2) {     //  ограничение глубины поиска
	          break;
	          }
	        $page = get_google_page( $kye ); 
	      } while ( preg_match($patern_next_page,$page) === 0);
	      return  $arr_URLs;
	    }   

	function parse_CY($url_page)                    // должна принимать на входе и массив и строку!
		{
			global $browser, $webpage, $app;
			$n = 0;
			$url_CY_page = 'http://bar-navig.yandex.ru/u?ver=2&url='.$url_page.'&show=1';
			$browser->navigate($url_CY_page);
			$browser->wait(1); 			// ждёт пока браузер освободится
			do {			// после большого количества запросов к яндекс бару он выдаёт страницу ошибки и просит подождать пару минут:
				$page = file_get_contents($url_CY_page);
				if (!$page) {
					sleep(rand(60,180));
					$n++;
					}
				if ($n > 5){
					echo '<br><font color = "red"> ERROR:   after trying N '.$n.' to get of CY failed </font><br>';
					return false;
					}
			}while (!$page);
			$patern = '#value="(\d*)#s';
			if (!preg_match($patern,$page,$CY)) {
				echo'<br><font color = "red">ERROR CY no found: </font><br>';
				$app->set_foreground_window();	// показать окно эмулятора
				$app->pause(0); 
				return false;
					}
			return $CY[1];
		}

	function get_CY($file_urls,$file_this_CY=false)
		{
		  //$file_this_CY = "file_this_CY.txt";
		  //$file_urls = "url_1.txt";     // файл с урлами сайтов ТИЦ которых нужно узнать
		  if (!$file_this_CY) {      //  если путь к файлу не задан то он по умолчанию false и сохраняем  в массив
		      $arr_with_CY = array();   
		      } else {      //  если сохраняем в фаил
		        $hendl_CY = fopen($file_this_CY,"a");   
		        if(!$hendl_CY)     //  если ошибка.... 
					{
					echo '<br><font color = "red">ERROR open file: </font><br>'.$file_this_CY;
					}
		      }
		  $file_urls = convert_file_or_str_in_arr($file_urls);
		  for ($i=0; $i < count($file_urls); $i++) 
			    {   
			      if ($file_urls[$i][0] and $file_urls[$i][1] == "/") { // для того что бы комментировать строки в файле с URLми
			          continue;   
			          }
			      $CY = parse_CY($file_urls[$i]);      //.....парсим ТИЦ
			      if (!$CY) {
			        echo '<br><font color="red">'.$file_urls[$i]."  : &nbsp".$CY."&nbsp dell<br></font>";
			        continue;
			        }
			      if (!$file_this_CY) {
				        $arr_with_CY[$file_urls[$i]] = $CY;
				        //print_r($arr_with_CY);
				        continue;  
				      	}
			      $result_str = $file_urls[$i].": ".$CY."\n";
			      fwrite($hendl_CY, $result_str);
			      //echo "<br>".$file_urls[$i]." : &nbsp ".$CY."<br>";
			    }
		  if (!$file_this_CY) {
			  	//	echo "<br>-------------------------------------------------------------------------------<br>";
			  	//	print_r( $arr_with_CY );
			  	//	echo "<br>-------------------------------------------------------------------------------<br>";
				    return $arr_with_CY;
			    }
		  fclose($hendl_CY);  
		}

	function go_URL_with_search($file_kyes)
		{
			global $app;
		    $arr_kyes = convert_file_or_str_in_arr($file_kyes);
		    for ($i=0; $i < count($arr_kyes); $i++)  
		      {
		        $page_search = get_google_page( $arr_kyes[$i] ); //получ. страницу поиск. выдачи по заданн ключу
		        //	вставить функцию проверки страницы капчи
		        $arr_URLs = parse_URL_with_saerch($page_search,$arr_kyes[$i]);  //получаем масив урлов со всех доступных страниц поисковой выдачи по текущему ключу
		        $arr_URLs = get_CY($arr_URLs);    // отсееваем урлы без тица b возвращаем ассоциативный массив вида URL:CY    
			    foreach ($arr_URLs as $key => $value) {
			    	echo $key.' => '.$value.'<br>';
			    	insert_into_mysql($key,$value);
			    	}
		      }
		  echo '<br><font color = "green">All keys processed';
		  print_r($arr_URLs);
		  $app->quit(); 
		}

	function insert_into_mysql($url,$cy)
		{
		    /* Соединяемся, выбираем базу данных */
		    $link = mysql_connect("localhost", "root", "", "nakrutka_cy") or die("Could not connect : " . mysql_error());
		    //print "Connected successfully <br>";
			mysql_select_db("nakrutka_cy") or die('ERROR: '.mysql_error());		// выбор текущей БД на подключенном сервере
			// Построение SQL-оператора			
			$strSQL = "INSERT INTO url_site (url_site, cy_site) VALUES ('$url','$cy')";
			// SQL-оператор выполняется
			mysql_query($strSQL);// or die ('ERROR:  '.mysql_error());
			// Закрытие соединения
			mysql_close();		   
		}

	function with_mysql_in_file($file_name_mysql)
		{	
			/* Соединяемся, выбираем базу данных */
		    $link = mysql_connect("localhost", "root", "", "nakrutka_cy") or die("Could not connect : " . mysql_error());
		    print "Connected successfully <br>";
			mysql_select_db("nakrutka_cy") or die('ERROR: '.mysql_error());		// выбор текущей БД на подключенном сервере
	        $hendl_mysql = fopen($file_name_mysql,"a"); 	//откроем или создадим файл с заданым именем  
	        if(!$hendl_mysql)     //  если ошибка.... 
				{
					echo '<br><font color = "red">ERROR open file: </font><br>'.$file_name_mysql;
				}
			$strSQL = 'SELECT * FROM url_site';
			$response_mysql = mysql_query($strSQL);// or die ('ERROR:  '.mysql_error());
			while ($wiht_mysql_arr = mysql_fetch_array($response_mysql, MYSQL_ASSOC))
			{
				foreach ($wiht_mysql_arr as $key => $value) {
					$result_str = $value."   ";
					echo $result_str;
					fwrite($hendl_mysql, $result_str);
				    }
				echo "<br>";
				$result_str = "\n";
				fwrite($hendl_mysql, $result_str);
			}

			mysql_close($link);	
		  	fclose($hendl_mysql); 			
		}	

	function convert_file_or_str_in_arr($file_urls)
		{
		  //echo "<br>Starting function: <u>convert_file_or_str_in_arr</u><br>";
		  //  в ходным параметром может быть файл, массив URLов или отдельный URL
		  if (!is_array($file_urls)) 
			    {
			      $str_beginning = substr($file_urls, 0, 7);    //  получим первые 7 символов переданой строки 
			      if ($str_beginning == 'http://') {   //  если передан URL 
			            $arr_result[0] = $file_urls;        // тогда ложим полученный URL в массив 
			          } else {  //  если было передано имя файла убедимся что оно с нужным расширением
								$str_end = substr($file_urls, -4);    //  получим последние 4 символов переданой строки (в имени файла это .txt)
								if ($str_end == ".txt") { //если передано имя текстового файла   
								      $arr_result = file($file_urls, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);//файл в массив посторочно 
								      if (!$arr_result) {	// если ошибка при чтении файла....
									        echo '<br><font color = "red">ERROR read file: </font><br>'.$file_urls;    
									    	}
								}else{		// если передано имя не текстового файла
								    echo '<br><font color = "red">ERROR input file can be *.txt </font><br>'.$file_urls;    // если ошибка....
									}
							 }
			    }		

			//echo "<br>END function: <u>convert_file_or_str_in_arr</u><br>";
			return $arr_result;
		}

	function title_page($page, $key=false)
		{
			$patern_title_page = '#<title.*>(.*)</title>#isU'; 
			 

			if (!preg_match_all($patern_title_page,$page,$result_title,PREG_PATTERN_ORDER)) { //если патерн не найден 
				echo '<br><font color = "red">ERROR in function <b>title_page</b> patern_title_page no found. </font><br>';
					return false;
        		}
	        $title_page = $result_title[1][0];  
	        if (!$key) { // если ключ не задан то здесь конец работы функции	
		        return $title_page;	        
		        }
			//---------------------------------сравниваем найденную строку с заданным ключём---------------------------------

			if (!is_array($key)) {
				$key_arr = explode(",", $key);
			}
			for ($i=0; $i < count($key_arr); $i++) { 
				$key_curent = $key_arr[$i];
			    $key_curent = iconv ("utf-8","windows-1251//IGNORE",$key_curent);
			    $patern_key_in_title = '#'.$key_curent.'#isU';

				if (!preg_match_all($patern_key_in_title,$title_page,$result_key,PREG_PATTERN_ORDER)) { //если патерн не найден 
					//echo '<br><font color = "red">ERROR in function <b>title_page</b> patern_key_in_title for key  <u><b>'.$key_curent.'</b></u> no found. </font>';
						//return false;
	        		}else{
	        			$title_key = $result_key[0][0];
	        			echo '<br><font color = "blue"><b>IN TITL KEY FOUND</b></font><br>';
	        			echo "<br>title_key:   ".$title_key;
	        			return $title_page;
	        			}
        		}
        	return false;

		}

	function parsing_site_donor($urls_pages,$keys_title)
		{
			$patern_URL_page = '#(h.*) \d#i'; 
			$patern_URL_main_page = '#https?://.*?/#i'; 

			$arr_urls = convert_file_or_str_in_arr($urls_pages);
			
		        $hendl_result = fopen("file_result.txt","a");  //	готовим результирующий файл 
		        if(!$hendl_result)     //  если ошибка.... 
					{
					echo '<br><font color = "red">ERROR open file: </font>   '."file_result.txt";
					}
			for ($i=0; $i < count($arr_urls); $i++) { 			//	цыкл обработки полученого URLа, перебор полученого массива URLов 
				if (!preg_match_all($patern_URL_page,$arr_urls[$i],$result_page,PREG_PATTERN_ORDER)) { //если патерн не найден 
					echo '<br><font color = "red">ERROR in function <b>parsing_site_donor</b> patern_URL_page no found. </font><br>';
					return false;
	        		}
		        $url_page = $result_page[1][0]; 
		        	//	получаем адрес главной страницы  
				if (!preg_match_all($patern_URL_main_page,$url_page,$result_main_page,PREG_PATTERN_ORDER)) { //если патерн не найден 
					echo '<br><font color = "red">ERROR in function <b>parsing_site_donor</b> patern_URL_main_page no found. </font><br>';
					return false;
	        		}
		        $url_main_page = $result_main_page; 

			       echo "<br>-----------------------------------------------------";
			       echo "<br> url_page: ".$url_page;	
		       $page = get_web_page($url_page);		//	получим страницу для анализа темы
		       $title = title_page($page,$keys_title);	//	анализ темы страницы
			       echo "<br> <b> title page: </b> ".$title;
			       echo "<br> url_main_page: ".$url_main_page[0][0];
		       $page_main = get_web_page($url_main_page[0][0]);		//	получим ГЛАВНУЮ страницу сайта для анализа темы
		       $title_main = title_page($page_main,$keys_title);
			       echo "<br> <b>title main page</b>:  ".$title_main;
			       echo "<br>-----------------------------------------------------";
		       
		       if ($title or $title_main) {					//	если темы совпали с ключём:

		       	// пишим в файл
		       		$result_str = $url_page."\n";
			    	echo '<br>'.$result_str;
			    	//fwrite($hendl_result, $result_str);
			    
			    //	здесь дальнейший анализ сайта:
			    //	поиск внешних ссылок:
			    echo "<br>*********************   find links  **************************<br>";
			    	//links_check($url_page);
		       }
			}	
			fclose($hendl_result); 
		}	

	function links_check($url)
		{
			$patern_link = '#<a.*/a>#isU';
			$patern_URL_main_page = '#https?://.*?/#i';
			$patern_href = '#href="(https?://.*/).*"#U';

			$page = get_web_page($url);
			//	из полученного адреса страницы выделяем адрес главной страницы
			if (!preg_match_all($patern_URL_main_page,$url,$result_main_page,PREG_PATTERN_ORDER)) { //если патерн не найден 
				echo '<br><font color = "red">ERROR in function <b>links_check</b> patern_URL_main_page no found. </font><br>';
	    		}			
			//	ищем все ссылки на странце т.е. то что внутри тега <а></a> 
			if (!preg_match_all($patern_link,$page,$result_link,PREG_PATTERN_ORDER)) { //если патерн не найден 
				echo '<br><font color = "red">ERROR in function <b>links_check</b> patern_link no found. </font>';
	    		}	   

	    	// выбираем только внешние ссылки
	    	for ($i=0; $i < count($result_link); $i++) { 
		    		for ($q=0; $q < count($result_link[$i]); $q++) { 
						//	выделяем параметр href в теге <а></a>
						if (!preg_match_all($patern_href,$result_link[$i][$q],$result_href,PREG_PATTERN_ORDER)) { //если патерн не найден 
							//echo '<br><font color = "red">ERROR in function <b>links_check</b> patern_href no found. </font>';
				    		//echo '<br>';
				    		continue;
				    		}	    			
				    	echo "<br>".$i.'.'.$q.'. '.$result_link[$i][$q];
					    	//	сравниваем параметр href с адресом главной страницы если несовпали то ссылка внешняя
					    	if (strcmp($result_main_page[0][0],$result_href[1][0])) {
					    		echo '<font color="green">current link is external</font>';
					    		//	здесь продалжаем анализ полученной ссылки

					    		//	получить блок который обрамляет найденную ссылку

					    		
					    		}
			    		}
			    	}

		}

	function serch_div($url)
		{
			global $app, $browser, $webpage;

			$url_mod = "http://test_js/XWeb_Human_Emulator/temp.html";
			$file_name = "temp.html";

			$page = get_web_page($url);	//	получаем страницу
			
			$str_script = '<br><br>  <script src="functions_xweb.js" ></script>   <br><br>';  
			$str_script_3 = '<script> document.write("hello world!");</script>';


			$str_html_1 = '<br><div id="divResult"></div>';
			//$str_html_2 = '<br><input id="inpText" />';
			$str_html_3 = '<button id="btnRun">to send</button>';

			$page = $page.$str_script.$str_script_3.$str_html_1./*$str_html_2.*/$str_html_3;

			$browser -> set_count(2);
			$browser -> set_active_browser(1,true);

			$hendl = fopen($file_name,"w");
			fwrite($hendl, $page);
			fclose($hendl); 

			$browser -> navigate($url_mod);
			
		}







?>



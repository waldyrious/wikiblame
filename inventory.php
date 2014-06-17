<?php header('Content-Type: text/html; charset=utf-8');  ?>
<?php 

//shows the entries from zukunft that have been removed
include("shared_inc/wiki_functions.inc.php");

//$article = "Benutzer:Flominator/Zukunft";
$cat = $_REQUEST['cat'];
$other_cat_enc = urlencode($_REQUEST['other_cat']);
$template = urlencode($_REQUEST['template']);
$template_missing = $_REQUEST['template_missing'] == "true";
$catenc = urlencode($cat); //Wikipedia%3AZukunft
$articleenc = name_in_url($article);
$lang = "de";
$project = "wikipedia";
$server = "$lang.$project.org";
$number_of_current_entries = 0;




$plainfuture_text = retrieve_current_list_old($catenc, $template, $other_cat_enc, $template_missing);	

echo '<form method="post" action="https://'.$server.'/w/index.php?action=submit&title='. $articleenc .'" target="_blank">'."\n";
echo "<textarea  name=\"wpTextbox1\">";
echo "\n== Einbindungen ==\n";
echo $plainfuture_text;
echo "\n&nbsp;Anzahl: $number_of_current_entries";
echo "</textarea><br>";
echo '<input type="hidden" value="1" name="wpSection" />';
set_up_media_wiki_input_fields("Inventar-Seite mit inventory.php aktualisiert", "Inventar-Seite aktualisieren");
echo "</form>\n";


$plain_text = get_plain_text_from_article($articleenc);
$entries_removed = compare_lists($plain_text, $plainfuture_text);
$entries_added= compare_lists($plainfuture_text, $plain_text);

echo '<form method="post" id="diff_form" action="https://'.$server.'/w/index.php?action=submit&title='. urlencode('Wikipedia:Spielwiese') .'" target="_blank">'."\n";
echo "<textarea  name=\"wpTextbox1\">";
echo ":via [[".$article."]]\n";
echo "\n===weg===\n";

foreach($entries_removed AS $removed)
{
	echo "$removed\n";
}
echo "\n Änderungen: ". count($entries_removed);

echo "\n===dazu===\n";

foreach($entries_added AS $added)
{
	echo "$added\n";
}
echo "\n Änderungen: ". count($entries_added);
echo "</textarea><br>";
echo '<input type="hidden" value="new" name="wpSection" />';
set_up_media_wiki_input_fields("Änderungen", "Änderungen anschauen");
echo "</form>\n";

function get_plain_text_from_article($articleenc)
{
	global $server;
	$page = "http://".$server."/w/index.php?action=raw&title=".$articleenc;
	//echo "$page<br>";
			
	//echo "get_request($server, $page, true )";
	$art_text = get_request($server, $page, false );
	//echo "<h1>art_text</h1>$art_text";
	$plain_text = strip_tags(chop_content_local($art_text));
	//echo "<h1>plain_text</h1>$plain_text<hr>";
	return $plain_text;
}

function compare_lists($needles, $haystack)
{
	$results = array();
	//$hits = 0;
	$paragraphsRemoved = explode("\n",$needles);
	// echo "<h2> haystack</h2>".$haystack;
	// echo "<h2> needles</h2>".$needles;
	foreach($paragraphsRemoved AS $newLine)
	{
		$onlyOneNewArticle = explode("]]:", $newLine);
		if(stristr( $onlyOneNewArticle[0], "*" ) && !stristr($haystack, $onlyOneNewArticle[0] ))
		{
			//echo str_replace('_', ' ', $newLine) ."\n";
			$results[] = str_replace('_', ' ', $newLine);
			//$hits++;
		}
	}
	return $results;
}

function retrieve_current_list($catenc, $template)
{
	global $cat, $number_of_current_entries;

	$all_namespaces ="ns%5B-2%5D=1&ns%5B0%5D=1&ns%5B2%5D=1&ns%5B4%5D=1&ns%5B6%5D=1&ns%5B8%5D=1&ns%5B10%5D=1&ns%5B12%5D=1&ns%5B14%5D=1&ns%5B100%5D=1&ns%5B828%5D=1&ns%5B-1%5D=1&ns%5B1%5D=1&ns%5B3%5D=1&ns%5B5%5D=1&ns%5B7%5D=1&ns%5B9%5D=1&ns%5B11%5D=1&ns%5B13%5D=1&ns%5B15%5D=1&ns%5B101%5D=1&ns%5B829%5D=1";
	$url ="http://tools.wmflabs.org/catscan2/catscan2.php?language=de&categories=$catenc&doit=1&format=csv&$all_namespaces";
	
		if($template!="")
	{
		$url.="&templates_yes=$template";
	}
	$csv_list = get_request("tools.wmflabs.org", $url, true );
	
	 
	$rows = explode("\"\n", $csv_list);
	$bulleted_list = "";
	echo "done";

	foreach($rows AS $row)
	{
		if(strpos($row, "\"") == 0)
		{
			//echo "$row<br>";
			$cols = explode("\"", $row);
			if($cols[1]!="")
			{
			//echo $cols[1]."<br>";
				$bulleted_list.="* [[".$cols[1]."]]: [[:Kategorie:$cat|$cat]]\n";
				$number_of_current_entries = $number_of_current_entries + 1;
			}
		}
	}
	return $bulleted_list;
}

function retrieve_current_list_old($catenc, $template="", $other_cat_enc="", $template_not_present=false)
{
	global $number_of_current_entries;
	$catpage ="http://toolserver.org/~daniel/WikiSense/CategoryIntersect.php?wikilang=de&wikifam=.wikipedia.org&basecat=$catenc&basedeep=3&go=Scannen&format=wiki&userlang=de";
	if($template!="")
	{
		$catpage.="&mode=ts&templates=$template";
		if($template_not_present)
		{
			$catpage.="&untagged=on";
		}
	}
	else if($other_cat_enc!="")
	{
		$catpage.="&mode=cs&tagcat=$other_cat_enc";
	}
	else 
	{
	$catpage.="&mode=al";
	}
	
	$page_content = strip_tags(chop_content_local(removeheaders(get_request("toolserver.org", $catpage, true ))));
	$number_of_current_entries = count(explode("*", $page_content))-1;
	echo "<!-- $catpage -->";
	return $page_content;
}

function chop_content_local($art_text)
{
	//echo "chopping text";
	$content_begins = strpos($art_text, '<!-- start content -->');
	$content_ends = strpos($art_text, '<!-- end content -->');
	$content = substr($art_text, $content_begins, strlen($art_text)-$content_ends);
	return str_replace('[bearbeiten]', '', $content);
}

?>
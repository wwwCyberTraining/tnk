<?php

/** 
 * Windows encoding ����� ������
 * @file findpsuq_lib.php - functions for finding text in verses
 * @author Erel Segal ���� ���
 */

require_once("../admin/db_connect.php");

/**
 * Fix some common mistakes users do when entering regular expressions for searching:
 * replace dashes with spaces (unless the regexp includes a set of chars),
 * remove duplicate spaces,
 * remove quotes and other chars that are irrelevant for searching in the Tanakh.
 */
function fix_regexp($phrase) {
	if (!preg_match("|\\[|",$phrase))
		$phrase = preg_replace("/-/"," ",$phrase);
	$phrase = preg_replace("/ +/", " ", $phrase);
	$quote = "'\"";
	$phrase = preg_replace("/[$quote><&\\/]/","",$phrase);
	return $phrase;
}

/**
 * Check if the given regular expression is valid.
 * If it is valid - returns an empty string.
 *	else - returns the error message.
 */
function regexp_error ($phrase) {
	try {
		if (@preg_match("/$phrase/", "")===FALSE)
			return "����� �����";
		else
			return "";
	} catch (Exception $e) {
		return $e->getMessage();
	}
}

/**
 * Internal function - search a regular expression in SQL SELECT results.
 * 
 * @param $verses the result of an SQL SELECT query on the Tanakh verses table.
 * @param $phrase the regular expression to search.
 * @param $emphasize_phrase boolean true to make the search phrase boldface. 
 * @param $single_verse boolean true to find matches in 1 verse, false to find matches also in 2 adjacent verses.
 * @param $niqud_level boolean true to add dots (niqud) to the emphasized verses.
 */ 
function search_results($verses,$phrase,$emphasize_phrase,$single_verse=0,$niqud_level=0) {
	global $linkroot, $newline;
	$result = '';
	$result_wikisource = '';
	$kotrt_qodmt=""; $ktovt_qodm=""; $mspr_psuq_qodm=""; $verse_text_bli_niqud_qodmt="";

	$match_count = 0;
	while ($verse = sql_fetch_assoc($verses)) {
		$kotrt = $verse['kotrt'];
		$ktovt = $verse['ktovt'];
		$mspr_psuq = $verse['verse_number'];
		$ot_psuq = $verse['verse_letter'];
		$verse_text = $verse['verse_text'];
		$ktovt_trgum = $verse['ktovt_trgum'];
		$ktovt_sikum = $verse['ktovt_sikum'];

		$verse_text_bli_niqud =
			preg_replace("/����/", "�'",
			preg_replace("/<b>.*<\/b>/","",
			$verse_text));

		if (preg_match("/$phrase/",$verse_text_bli_niqud)) {
			list($verse_text_bli_niqud, $verse_text_bli_niqud_wikisource) = emphasize_phrase_if_needed($verse_text_bli_niqud, $phrase, $emphasize_phrase);

			++$match_count;

			$anchor = "<a class='psuq' href='$linkroot/tnk1/$ktovt#$mspr_psuq'>$kotrt$mspr_psuq</a>";

			$result .= cite_link_item($anchor, $verse_text_bli_niqud, $ktovt_trgum, $ktovt_sikum, $niqud_level);

			$result_wikisource .= "* {{��|$verse_text_bli_niqud_wikisource|$kotrt $ot_psuq}}\n";

			$kotrt_qodmt=""; $ktovt_qodm=""; $mspr_psuq_qodm=""; $verse_text_bli_niqud_qodmt="";
			# if there is a match in this verse, don't keep this verse for checking it's combination with the next verse
		} else {  # no match
			if ($verse_text_bli_niqud_qodmt && !$single_verse) {
				$jtei_jurot_bli_niqud = "$verse_text_bli_niqud_qodmt $verse_text_bli_niqud";
				if (preg_match("/$phrase/",$jtei_jurot_bli_niqud)) {
					list($jtei_jurot_bli_niqud, $jtei_jurot_bli_niqud_wikisource) = emphasize_phrase_if_needed($jtei_jurot_bli_niqud, $phrase, $emphasize_phrase);

					++$match_count;

					#using "#mspr_psuq and _blank" causes a strange error on some instances of MSIE (see above)
					$anchor = "<a class='psuq' href='$linkroot/$site/$ktovt_qodm#$mspr_psuq_qodm'>$kotrt_qodmt$mspr_psuq_qodm-" . ($kotrt === $kotrt_qodmt? '': $kotrt) . "$mspr_psuq</a>";

					$result .= cite_link_item($anchor, $jtei_jurot_bli_niqud, $ktovt_trgum, $ktovt_sikum, $niqud_level);

					$result_wikisource .= "* {{��|$jtei_jurot_bli_niqud_wikisource|$kotrt $ot_psuq}}\n";
				}
			}
			list($kotrt_qodmt, $ktovt_qodm, $mspr_psuq_qodm, $verse_text_bli_niqud_qodmt) = array($kotrt, $ktovt, $mspr_psuq, $verse_text_bli_niqud);
			# if there is no match in this verse, keep this verse for checking its combination with the next verse
		}
	}
	
	return array($result, $result_wikisource, $match_count);
}


/**
 * If $emphasize_phrase is true, return two versions of the verse in which the phrase is emphasized.
 *  
 * @param sting $verse_text_bli_niqud
 * @param sting $phrase
 * @param boolean $emphasize_phrase
 * @return multitype:string
 */
function emphasize_phrase_if_needed($verse_text_bli_niqud, $phrase, $emphasize_phrase) {
	if ($emphasize_phrase) {
		$phrase_without_spaces = 
			preg_replace("/^\s+/","",
			preg_replace("/\s+$/","",
			$phrase));
		return array(
			preg_replace("/([^ ]*{$phrase_without_spaces}[^ ]*)/","<b>$1</b>", $verse_text_bli_niqud),
			preg_replace("/($phrase_without_spaces)/","'''$1'''", $verse_text_bli_niqud)
			);
	} else {
		return array($verse_text_bli_niqud,$verse_text_bli_niqud);
	}
}

/**
 * Create an HTML LI element with the given verse.
 * @return string
 */
function cite_link_item($verse_anchor, $verse_text, $ktovt_trgum, $ktovt_sikum, $niqud_level) {
	global $linkroot, $newline;

	$mamr_anchor = ($ktovt_trgum?
		"<a href='".(preg_match("/:/",$ktovt_trgum)? "": "$linkroot/")."$ktovt_trgum'>�����</a>":
		"");

	$sikum_anchor = (isset($_GET['sikum'])?
		"<a href='$linkroot/tnk1/sikum.php?$ktovt_sikum&utf8=1&find=1'>�����</a>": 
		"");

	$trgum_anchor = (
		$mamr_anchor && $sikum_anchor? " ($mamr_anchor, $sikum_anchor)": (
		$mamr_anchor ? " ($mamr_anchor)": (
		$sikum_anchor ? " ($sikum_anchor)": 
		"")));

	$item = "
		<li><!--m-->
			$verse_anchor: \"<q class='psuq'>$verse_text</q>\"<!--n-->".$trgum_anchor."
		</li>$newline";
	if ($niqud_level)
		$item = niqud_psuqim($item, $niqud_level);
	return $item;
}


/**
 * Main function - search a regular expression in Tanakh verses.
 * 
 * @param $phrase the regular expression to look for.
 * @param $single_verse [boolean] - true to find matches in 1 verse, false to find matches also in 2 adjacent verses.
 * @param $niqud_level [boolean] - true to display the verses with dots ("niqud"). NOTE: This currently does not affect the search (i.e. the search is in the undotted version).
 */ 
function find_phrase($phrase, $single_verse, $niqud_level) {
	$findpsuq_table = "findpsuq";

	//If the phrase contains niqud, look in the table of verses with niqud.
	//	-- This currently does not work, because of encoding. The input encoding is hebrew, but the database is utf8 :(
	//if (preg_match("/[��������]/",$phrase))  
	//	$findpsuq_table = "findpsuq_niqud"; 
	$newline = "\n";
	$fullbody = '';
	$count = 0;
	$e = regexp_error($phrase);
	if ($e) {
		$fullbody .= "<p>���� ����� ������ ����. ����� ������ ���:</p>";
		$fullbody .= "<div dir='ltr'>$e</div>";
		$match_count = 0;
	} else {
		$emphasize_phrase = TRUE;
		mysql_query("set character_set_client=hebrew");
		if (preg_match("/^[�-� ]*$/",$phrase))  { // If we look for a simple phrase, not a regexp - approximate the result count:
			$approximate_result_count = sql_evaluate("SELECT COUNT(*) FROM $findpsuq_table WHERE verse_text LIKE '%$phrase%'");
			$emphasize_phrase = ($approximate_result_count>=2);
		}

		mysql_query("set character_set_client=utf8");
		mysql_query("set character_set_results=hebrew");
		mysql_query("set character_set_database=utf8");

		if (!preg_match("/[(]/",$phrase) && preg_match("/[|]/",$phrase) ) {
			$subphrases = explode("|",$phrase);
			foreach ($subphrases as $subphrase) {
				$verses = sql_query_or_die("SELECT * FROM $findpsuq_table");
				list ($results, $results_wikisource, $match_count) = search_results($verses,$subphrase,TRUE, $single_verse, $niqud_level);
				$fullbody .= "<h2>$subphrase</h2>\n";
				$fullbody .= $results;
			}
		} else {
			$verses = sql_query_or_die("SELECT * FROM $findpsuq_table");
			list ($results, $results_wikisource, $match_count) = search_results($verses,$phrase,$emphasize_phrase, $single_verse, $niqud_level);
			$fullbody .= $results;
		}
		if ($fullbody)
			$fullbody = "
				<!--a-->
				$fullbody
				<!--z-->
				";  # --z-- is for Mozilla search
	}
	return array($fullbody, $match_count);
}

?>

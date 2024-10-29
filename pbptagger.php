<?php

	if (file_exists(WP_PLUGIN_DIR.'/pingbackpro/pingbackpro.php')) {
		$valchemyid = get_option('pingbackpro_alchemy');
		$vyahooappid = get_option('pingbackpro_yahoo');
		$vzemantaid = get_option('pingbackpro_zemanta');
		$vtagthenet = get_option('pingbackpro_tagthenet');
		$vthispage = "pingbackpro";
		$vtaggingprocessfile = WP_PLUGIN_DIR.'/pingbackpro/data/tagging-process.txt';
	}

	if ((file_exists(WP_PLUGIN_DIR.'/autotagsuggest/autotagsuggest.php')) || ($vautotagsuggest == "yes")) {
		$valchemyid = get_option('autotagsuggest_alchemy');
		$vyahooappid = get_option('autotagsuggest_yahoo');
		$vzemantaid = get_option('autotagsuggest_zemanta');
		$vtagthenet = get_option('autotagsuggest_tagthenet');
		$vthispage = "autotagsuggest";
		$vtaggingprocessfile = WP_PLUGIN_DIR.'/autotagsuggest/data/tagging-process.txt';
	}

	function quick_save_post_tags() {
		save_post_tags();
		wp_die('Post tags saved.');
	}

	function save_post_tags() {
		if ($_REQUEST['postid'] != "") {$vpostid = $_REQUEST['postid'];}
		else {global $post; $vpostid = $post->ID;}

		$vsavetags = $_REQUEST['autotaggerposttags'];
		$vsavetags = preg_replace("/[\r\n]/",",",$vsavetags);
		$vsavetags = preg_replace("/[^A-Za-z0-9\,\_,\ ]/i","",$vsavetags);
		$vsavedata = explode(",",$vsavetags);
		$vi = 0;
		foreach ($vsavedata as $vadata) {
			$vadata = trim($vadata);
			if ($vadata != "") {$vsavedata[$vi] = $vadata;}
			$vi = $vi + 1;
		}
		$vsavetags = implode(",",$vsavedata);
		wp_set_post_terms($vpostid,$vsavetags,'post_tag',false);
		if ($_REQUEST['postid'] != "") {
			echo "<script language='javascript' type='text/javascript'>";
			echo "alert('Tags have been saved for post ".$vpostid.".');";
			echo "</script>";
		}
	}

	if (($_REQUEST['autotagger'] == "yes") && ($_REQUEST['masstagdatadelete'] == "yes")) {

		if ($vautotagsuggest == 'yes') {
			$vallpostids = ats_get_all_post_ids(); $vgotids = "yes";
			$vdatafileprefix = WP_PLUGIN_DIR."/autotagsuggest/data/";
		}
		if ($vgotids != "yes") {
			$vallpostids = get_all_post_ids();
			$vdatafileprefix = WP_PLUGIN_DIR."/pingbackpro/data/";
		}

		foreach ($vallpostids as $vapostid) {
			$vdatafile = $vdatafileprefix.$vapostid."-rankedtags.txt";
			// echo $vdatafile."<br>";
			@unlink($vdatafile);
		}
		@unlink($vtaggingprocessfile);
		echo "<script language='javascript' type='text/javascript'>
			alert('All ranked tag data deleted.');
			</script>";
	}

	if (($_REQUEST['autotagger'] == "yes") && ($_REQUEST['masstagsuggest'] == "yes")) {

		echo "Retrieving and ranking tag suggestions for all posts.<br>";
		echo "<a href='options-general.php?page=".$vthispage."&autotagger=yes&masstagsuggest=yes'>Click here to auto-resume if processing does not complete.</a><br><br>";

		if (file_exists($vtaggingprocessfile)) {
			$vfh = fopen($vtaggingprocessfile,'r');
			$vtaggingcsv = fgets($vfh);
			$vtaggedposts = explode(",",$vtaggingcsv);
			fclose($vfh);
			$vi = count($vtaggedposts);
			$vtowrite = $vtaggingcsv;
		}
		else {$vtaggedposts = array(); $vi = 0;}

		if ($vautotagsuggest == 'yes') {$vallpostids = ats_get_all_post_ids();}
		else {$vallpostids = get_all_post_ids();}

		$vfh = fopen($vtaggingprocessfile,'w');

		foreach ($vallpostids as $vapostid) {
			if (!in_array($vapostid,$vtaggedposts)) {
				$vrankedtags = get_ranked_tags($vapostid,$valchemyid,$vyahooappid,$vzemantaid,$vtagthenet);
				echo "Tag suggestions retrieved, ranked and written for post ".$vapostid."<br>";
				if ($vi == 0) {$vtowrite = $vapostid;} else {$vtowrite = $vtowrite.",".$vapostid;}
				fwrite($vfh,$vtowrite);
				$vi = $vi + 1;
				sleep(0.1);
			}
			else {echo "Skipping post ".$vapostid.": tagging results already retrieved.<br>";}
		}
		fclose($vfh);
		echo "<br>Finished retrieving tag suggestions for all posts!<br>";
		if ($vthispage == 'autotagsuggest') {echo "<a href='options-general.php?page=autotagsuggest&posttaglist=yes'>Click here to return to sitewide post tag list.</a><br>";}
		if ($vthispage == 'pingbackpro') {echo "<a href='options-general.php?page=pingbackpro&pingbacklist=yes'>Click here to return to pingback list.</a><br>";}
		exit;
	}

	if ($_REQUEST['posttaglist'] == "yes") {

		echo "<script language='javascript' type='text/javascript'>";
		echo "function showrow(rowref) {";
		echo " var tagsrowref = 'tagsrow'+rowref;";
		echo " var plusref = 'plus'+rowref;";
		echo " var minusref = 'minus'+rowref;";
		echo " document.getElementById(tagsrowref).style.display = '';";
		echo " document.getElementById(plusref).style.display = 'none';";
		echo " document.getElementById(minusref).style.display = '';";
		echo "}";
		echo "function hiderow(rowref) {";
		echo " var tagsrowref = 'tagsrow'+rowref;";
		echo " var plusref = 'plus'+rowref;";
		echo " var minusref = 'minus'+rowref;";
		echo " document.getElementById(tagsrowref).style.display = 'none';";
		echo " document.getElementById(plusref).style.display = '';";
		echo " document.getElementById(minusref).style.display = 'none';";
		echo "}";
		echo "function addtag(row,tag) {";
		echo " var posttagboxref = 'posttags'+row;";
		echo " var posttags = document.getElementById(posttagboxref).value;";
		echo " if (posttags == '') {posttags = tag;} else {posttags += ', '+tag;}";
		echo " document.getElementById(posttagboxref).value = posttags;";
		echo "}";
		echo "function saveposttags(row,postid) {";
		echo " var posttagsref = 'posttags'+row;";
		echo " var posttags = document.getElementById(posttagsref).value;";
		echo " document.getElementById('savetagsframe').src = 'options-general.php?page=".$vthispage."&quicksaveposttags=yes&postid='+postid+'&autotaggerposttags='+posttags;";
		echo "}";
		echo "function reverttosaved(row) {";
		echo " var posttagsref = 'posttags'+row;";
		echo " var savedtagsref = 'savedtags'+row;";
		echo " document.getElementById(posttagsref).value = document.getElementById(savedtagsref).value;";
		echo "}";
		echo "function autotagscan(postid) {";
		echo "	location.href = 'options-general.php?page=".$vthispage."&autotagger=yes&forpostid='+postid;";
		echo "}";
		echo "function domasstagsuggest() {";
		echo "	location.href = 'options-general.php?page=".$vthispage."&autotagger=yes&masstagsuggest=yes';";
		echo "}";
		echo "function domasstagdatadelete() {";
		echo "	location.href = 'options-general.php?page=".$vthispage."&autotagger=yes&masstagdatadelete=yes&posttaglist=yes';";
		echo "}";
		echo "</script>";

		echo "<br><center><table><tr><td align='center'><input type='button' value='Retrieve and Rank Tag Suggestions for All Posts' style='font-size:9pt;' onclick='domasstagsuggest();'><br>";
		echo "</td><td width=50></td><td><input type='button' value='Clear Ranked Tag Suggestion Data for All Posts' style='font-size:9pt;' onclick='domasstagdatadelete();'></td></tr></table></center><br>";

		$vi = 0;
		if ($vautotagsuggest == 'yes') {$vallpostids = ats_get_all_post_ids(); $vgotids = "yes";}
		if ($vgotids != "yes") {$vallpostids = get_all_post_ids();}

		echo "<table cellspacing=3><tr><td colspan='2' align='right'><font style='font-size:7pt;'>Post ID</font></td><td align='center' width='45%'><b>Post Title</b></td><td align='center' width='45%'><b>Post Tags</b></td><td></td></tr>";
		foreach ($vallpostids as $vapostid) {
			$vposttitle = get_the_title($vapostid);
			$vpermalink = get_permalink($vapostid);
			$vposttags = wp_get_post_tags($vapostid);
			$vposttagslist = ""; $vposttagcsv = "";
			if ((count($vposttags) > 0) && ($vposttags != "")) {
				$vj = 0;
				foreach($vposttags as $vaposttag) {
					$vthisposttag = (string)$vaposttag->name;
					if ($vposttagslist == "") {$vposttagslist = $vthisposttag; $vposttagcsv = $vthisposttag;} else {$vposttagslist .= " - ".$vthisposttag; $vposttagcsv .= ", ".$vthisposttag;}
					$vposttagarray[$vj] = $vthisposttag;
					$vj = $vj + 1;
				}
			}
			echo "<tr><td>";
			$vtagsuggestfile = WP_PLUGIN_DIR."/".$vthispage."/data/".$vapostid."-rankedtags.txt";
			if (file_exists($vtagsuggestfile)) {
				$vfh = fopen($vtagsuggestfile,'r');
				$vdatatemp = fgets($vfh);
				$vj = 0;
				$vrankedtags = array();
				while ($vdatatemp != "") {
					$vthistagdata = explode(",",$vdatatemp);
					$vrankedtags[$vj] = trim($vthistagdata[1]);
					$vdatatemp = fgets($vfh);
					$vj = $vj + 1;
				}
				fclose($vfh);
				echo "<div id='plus".$vi."'><a href='javascript:void(0);' style='text-decoration:none;' onclick='showrow(\"".$vi."\");'>[+]</a></div>";
				echo "<div id='minus".$vi."' style='display:none;'><a href='javascript:void(0);' style='text-decoration:none;' onclick='hiderow(\"".$vi."\");'>[-]</a></div>";
			}
			echo "</td><td align='center'><font style='font-size:8pt;'>".$vapostid.":</font> </td><td><a href='".$vpermalink."' target=_blank style='text-decoration:none;'>".$vposttitle."</a></td><td><font style='font-size:7pt;'>".$vposttagslist."</font></td><td><input type='button' onclick='autotagscan(\"".$vapostid."\");' value='Suggest Tags' style='font-size:8pt;'></td></tr>";
			if (file_exists($vtagsuggestfile)) {
				echo "<tr id='tagsrow".$vi."' style='display:none;'><td colspan='3'><font style='font-size:8pt; line-height:1.4em;'>";
				$vj = 0;
				foreach ($vrankedtags as $varankedtag) {
					if ($vj > 0) {echo " - ";}
					if (!in_array($varankedtag,$vposttags)) {
						echo "<a href='javascript:void(0);' style='text-decoration:none; background-color:#eeeeee;' onclick='addtag(\"".$vi."\",\"".$varankedtag."\");'>".$varankedtag."</a>";
					}
					$vj = $vj + 1;
				}
				echo "</td><td align='center'><textarea rows=6 cols=45 name='posttags".$vi."' id='posttags".$vi."' style='font-size:8pt;'>".$vposttagcsv."</textarea></td>";
				echo "<td align='center'><input type='hidden' name='savedtags".$vi."' id='savedtags".$vi."' value='".$vposttagcsv."'><input type='button' value='Revert to Saved' style='font-size:8pt;' onclick='reverttosaved(\"".$vi."\");'><br><br>";
				echo "<input type='button' value='Save Tags' onclick='saveposttags(\"".$vi."\",\"".$vapostid."\");'></td></tr>";
				$vi = $vi + 1;
			}
		}
		echo "</table><iframe id='savetagsframe' name='savetagsframe' style='display:none;' src='javascript:void(0);' width=450 height=50></iframe>";
		exit;
	}

	if (($_REQUEST['autotagger'] == "yes") && ($_REQUEST['forpostid'] != "")  && ($vinternal != "yes")) {

		$vpostid = $_REQUEST['forpostid'];
		$vposttags = get_the_post_tags($vpostid,'string');
		$vposttagarray = get_the_post_tags($vpostid,'array');

		echo "<script language='javascript' type='text/javascript'>";
		echo "var oldposttags = '".$vposttags."';";
		echo "function addtag(tag) {";
		echo " var posttags = document.getElementById('autotaggerposttags').value;";
		echo " if (posttags == '') {posttags = tag;} else {posttags += ', '+tag;}";
		echo " document.getElementById('autotaggerposttags').value = posttags;";
		echo "}";
		echo "function cleartags() {document.getElementById('autotaggerposttags').value = '';}";
		echo "function reverttosaved() {document.getElementById('autotaggerposttags').value = oldposttags;}";
		echo "function addtoptags(inputbox) {";
		echo " var numtags = document.getElementById(inputbox).value;";
		echo " var quickadd = '';";
		echo " for (i=0;i<numtags;i++) {";
		echo " 	var tagref = 'tag'+i;";
		echo "  var thistag = document.getElementById(tagref).value;";
		echo "  if (quickadd == '') {quickadd = thistag;} else {quickadd += ', '+thistag;}";
		echo " }";
		echo " var posttags = document.getElementById('autotaggerposttags').value;";
		echo " if (posttags == '') {posttags = quickadd;} else {posttags += ', '+quickadd;}";
		echo " document.getElementById('autotaggerposttags').value = posttags;";
		echo "}";
		echo "function saveposttags() {";
		echo " var posttags = document.getElementById('autotaggerposttags').value;";
		echo " document.getElementById('savetagsframe').src = 'options-general.php?quicksaveposttags=yes&postid=".$vpostid."&autotaggerposttags='+posttags;";
		echo "}";
		if (file_exists(WP_PLUGIN_DIR.'/pingbackpro/pingbackpro.php')) {
			echo "function startautoscan(postid) {";
			echo "	location.href = 'options-general.php?page=pingbackpro&scanner=yes&forpostid='+postid;";
			echo "}";
			echo "function pingbacklist() {";
			echo "  location.href = 'options-general.php?page=pingbackpro&pingbacklist=yes';";
			echo "}";
		}
		echo "</script>";

		$vposttitle = get_the_title($vpostid);
		echo "<center><table><tr><td>Post ".$vpostid." - <b>".$vposttitle."</b></td>";
		if (file_exists(WP_PLUGIN_DIR.'/pingbackpro/pingbackpro.php')) {
			echo "<td width=150></td><td><input type='button' value='Back to Pingback List' onclick='pingbacklist();'></td>";
		}
		echo "</tr></table></center><br><br>";
		$vrankedtags = get_ranked_tags($vpostid,$valchemyid,$vyahooappid,$vzemantaid,$vtagthenet);

		echo "<table><tr><td width=250><center><b>Top Ranked Tags</b> (click to add)</center><br>";
		$vi = 0;
		$vj = 0;
		foreach ($vrankedtags as $varankedtag) {
			if (isset($vrankedtags[$vi]['keyword'])) {
				if ((!in_array($vrankedtags[$vi]['keyword'],$vposttagarray)) && ($vj < 30)) {
		 			echo ($vj+1).". <a href='javascript:void(0);' style='text-decoration:none;' onclick='addtag(\"".$vrankedtags[$vi]['keyword']."\");'>".$vrankedtags[$vi]['keyword']."</a><br>";
		 			echo "<input type='hidden' name='tag".$vj."' id='tag".$vj."' value='".$vrankedtags[$vi]['keyword']."'>";
		 			$vj = $vj + 1;
				}
				$vrankedtagkeyword[$vi] = $vrankedtags[$vi]['keyword'];
				$vi = $vi + 1;
			}
		}
		echo "</td><td width=20></td>";
		echo "<td align='center'><b>Current Post Tags</b><br><br>";
		if ($vposttags != "") {echo $vposttags;} else {echo "None";}

		if ($vautotagsuggest = "yes") {$vcontent = ats_get_just_post_content($vpostid);}
		else {$vcontent = get_just_post_content($vpostid);}

		$vpostcontent = $vcontent[0];
		$valchemyctags = get_alchemy_concepttags($vpostcontent,$valchemyid);
		if (is_array($valchemyctags)) {
			echo "<br><br><b>Concept Tags</b><br><br>";
			$vi = 0;
			foreach ($valchemyctags as $vatag) {
				if ($vi != 0) {echo " - ";}
				echo "<a href='javascript:void(0);' style='text-decoration:none;' onclick='addtag(\"".$vatag."\");'>".$vatag."</a>";
				$vi = $vi + 1;
			}
		}

		echo "<br><br><b>Edit Post Tags</b> (comma separated)<br><br>";
		echo "<textarea id='autotaggerposttags' name='autotaggerposttags' rows=7 cols=56>".$vposttags."</textarea><br><br>";

		echo "<table><tr><td align='center'>Top <input type='text' name='quickaddone' id='quickaddone' value='5' size='2' style='text-align:right;'> Tags -&gt; ";
		echo "<input type='button' value='Quick Add' onclick='addtoptags(\"quickaddone\");'></td><td width=20></td>";
		echo "<td align='center'>Top <input type='text' name='quickaddtwo' id='quickaddtwo' value='10' size='2' style='text-align:right;'> Tags -&gt; ";
		echo "<input type='button' value='Quick Add' onclick='addtoptags(\"quickaddtwo\");'></td></tr></table><br>";

		echo "<table><tr><td><input type='button' value='Clear Tag Box' onclick='cleartags();'></td><td width=20></td>";
		echo "<td><input type='button' value='Revert to Saved' onclick='reverttosaved();'></td><td width=20></td>";
		echo "<td><input type='button' style='font-weight:bold;' onclick='saveposttags();' value='Save These Tags'></td></tr></table><br>";
		if (file_exists(WP_PLUGIN_DIR.'/pingbackpro/pingbackpro.php')) {
			echo "<input type='button' value='Launch Pingback Scanner for this Post' onclick='startautoscan(\"".$vpostid."\");'><br><br>";
		}
		echo "<iframe id='savetagsframe' name='savetagsframe' style='display:none;' src='javascript:void(0);' width=450 height=50></iframe>";
		echo "</td></tr></table>";
		exit;
	}

function show_tagger_box() {

	global $post;
	$vpostid = $post->ID;
	global $vautotagsuggest;

	if (file_exists(WP_PLUGIN_DIR.'/pingbackpro/pingbackpro.php')) {$vboxtitle = "Pingback Pro Tagger"; $vthispage = "pingbackpro";}
	if (file_exists(WP_PLUGIN_DIR.'/autotagsuggest/autotagsuggest.php')) {$vboxtitle = "Auto Tag Suggest"; $vthispage = "autotagsuggest";}

	echo '<div class="postbox " id="autosuggestpostbox">
		<div class="handlediv" title="Click to toggle"><br /></div>
		<h3 class="hndle"><span>'.$vboxtitle.'</span></h3><div class="inside">';

		$vposttags = get_the_post_tags($vpostid,'string');
		$vposttagarray = get_the_post_tags($vpostid,'array');

		echo "<center><b>Saved Post Tags</b>";
		if ($vposttags != "") {echo "</center>".$vposttags;} else {echo " - None</center>";}
		echo "<br><br>";

		echo "<script language='javascript' type='text/javascript'>";
		echo "function loadtagsuggestions() {";
		echo " document.getElementById('tagsuggestionsframe').style.display = '';";
		echo " document.getElementById('tagsuggestionsframe').src = '?action=edit&post=".$vpostid."&autotagger=yes&loadtagsuggestions=yes';";
		echo "}";
		echo "function loadtagsuggestwindow() {";
		echo " window.open('options-general.php?page=".$vthispage."&autotagger=yes&forpostid=".$vpostid."');";
		echo "}";
		echo "</script>";
		echo "<center><table><tr><td><input type='button' value='Retrieve and Show Tag Suggestions' onclick='loadtagsuggestions();'></td><td width=30></td>";
		echo "<td><input type='button' value='Open in a New Window' onclick='loadtagsuggestwindow();'></td></tr></table></center><br>";
		if ($vautotagsuggest == "yes") {$vaddlink = ats_get_link(); echo '<center><table cellspacing=5 style="background-color:#eeeeee;"><tr><td align="center"><font style="font-size:9pt;line-height:1.4em;"><a href="'.$vaddlink[0].'" target=_blank style="text-decoration:none;">'.$vaddlink[1].'</a></font></td></tr></table></center><br>';}
		echo "<center><iframe style='display:none;' name='tagsuggestionsframe' id='tagsuggestionsframe' src='javascript:void(0);' width=485 height=400 frameborder=0></iframe></center>";
		echo "</div></div>";
}

function load_tag_suggestions() {

		global $post;
		$vpostid = $post->ID;
		global $vautotagsuggest;

		if (file_exists(WP_PLUGIN_DIR.'/pingbackpro/pingbackpro.php')) {
				$valchemyid = get_option('pingbackpro_alchemy');
				$vyahooappid = get_option('pingbackpro_yahoo');
				$vzemantaid = get_option('pingbackpro_zemanta');
				$vtagthenet = get_option('pingbackpro_tagthenet');
				$vthispage = "pingbackpro";
				$vboxtitle = "Pingback Pro Tagger";
		}
		if (file_exists(WP_PLUGIN_DIR.'/autotagsuggest/autotagsuggest.php')) {
				$valchemyid = get_option('autotagsuggest_alchemy');
				$vyahooappid = get_option('autotagsuggest_yahoo');
				$vzemantaid = get_option('autotagsuggest_zemanta');
				$vtagthenet = get_option('autotagsuggest_tagthenet');
				$vthispage = "autotagsuggest";
				$vboxtitle = "Auto Tag Suggest";
		}

		$vposttags = get_the_post_tags($vpostid,'string');
		$vposttagarray = get_the_post_tags($vpostid,'array');

		echo "<body onload='resizeIframe(document.body.scrollHeight)'>";
		echo "<script language='javascript' type='text/javascript'>";
		echo "function resizeIframe(newHeight) {";
		echo " parent.document.getElementById('tagsuggestionsframe').style.height = parseInt(newHeight) + 30 + 'px';";
		echo "}";
		echo "var oldposttags = '".$vposttags."';";
		echo "function addtag(tag) {";
		echo " var posttags = document.getElementById('autotaggerposttags').value;";
		echo " if (posttags == '') {posttags = tag;} else {posttags += ', '+tag;}";
		echo " document.getElementById('autotaggerposttags').value = posttags;";
		echo "}";
		echo "function cleartags() {document.getElementById('autotaggerposttags').value = '';}";
		echo "function reverttosaved() {document.getElementById('autotaggerposttags').value = oldposttags;}";
		echo "function addtoptags(toptags) {";
		echo " var quickadd = '';";
		echo " var fromtag = toptags - 7;";
		echo " var toptags = (toptags * 1);";
		// echo " alert(fromtag+'-'+toptags);";
		echo " for (i=fromtag; i < toptags; i++) {";
		echo " 	var tagref = 'tag'+i;";
		echo "  var thistag = document.getElementById(tagref).value;";
		echo "  if (quickadd == '') {quickadd = thistag;} else {quickadd += ', '+thistag;}";
		echo " }";
		// echo " alert(quickadd);";
		echo " var posttags = document.getElementById('autotaggerposttags').value;";
		echo " if (posttags == '') {posttags = quickadd;} else {posttags += ', '+quickadd;}";
		echo " document.getElementById('autotaggerposttags').value = posttags;";
		echo "}";
		echo "function showmoretags() {";
		echo " document.getElementById('morerankedtags').style.display = '';";
		echo " document.getElementById('showmorelink').style.display = 'none';";
		echo " document.getElementById('hidemorelink').style.display = '';";
		echo "}";
		echo "function hidemoretags() {";
		echo " document.getElementById('morerankedtags').style.display = 'none';";
		echo " document.getElementById('showmorelink').style.display = '';";
		echo " document.getElementById('hidemorelink').style.display = 'none';";
		echo "}";
		echo "function saveposttags() {";
		echo " var posttags = document.getElementById('autotaggerposttags').value;";
		echo " document.getElementById('savetagsframe').src = 'options-general.php?quicksaveposttags=yes&postid=".$vpostid."&autotaggerposttags='+posttags;";
		echo " var simpletaginput;";
		echo " if (simpletaginput = document.getElementById('adv-tags-input')) {";
		echo " 	simpletaginput.value = posttags;";
		echo " }";
		echo "}";
		echo " if (posttagsbox = document.getElementById('tagsdiv-post_tag')) {";
		echo "  posttagsbox.style.display = 'none';}";
		echo "</script>";

		$vposttitle = get_the_title($vpostid);
		$vrankedtags = get_ranked_tags($vpostid,$valchemyid,$vyahooappid,$vzemantaid,$vtagthenet);

		echo "<table cellpadding=0 cellspacing=0 width=450><tr><td>";

		echo "<center><b>Ranked Tag Suggestions</b> (click to add)</center>";
		$vi = 0;
		$vj = 0;
		$vincremented = "";
		echo "<center><table width=450><tr>";
		foreach ($vrankedtags as $varankedtag) {
			if (isset($vrankedtags[$vi]['keyword'])) {
				if (($vj == 0) && ($vincremented == "")) {echo "<td align='left'><font style='font-size:9pt;line-height:1.4em;'>";}
				if ($vincremented == "yes") {
					if (($vj == 7) || ($vj == 14) || ($vj == 21)) {echo "<center><input type='button' value='Quick Add' style='font-size:7pt;' onclick='addtoptags(\"".$vj."\");'></center></td>";}
					if (($vj == 7) || ($vj == 14)) {echo "<td align='left'><font style='font-size:9pt;line-height:1.4em;'>";}
					if ($vj == 21) {
						echo "</tr></table></center>";
						echo "<div id='showmorelink' align='right'><a href='javascript:void(0);' onclick='showmoretags();' style='text-decoration:none;'>Click here to show more</a>...</div>";
						echo "<div id='hidemorelink' style='display:none;' align='right'><a href='javascript:void(0);' onclick='hidemoretags();' style='text-decoration:none;'>Hide</a></div>";
						echo "<div id='morerankedtags' style='display:none;'><center><font style='font-size:9pt;line-height:1.4em;'>";
					}
				}
				if (!in_array($vrankedtags[$vi]['keyword'],$vposttagarray)) {
					if ($vj < 7) {echo "<font style='font-size:8pt;'>".($vj+1).".</font> <span style='background-color:#eeeeee;'><a href='javascript:void(0);' style='text-decoration:none;' onclick='addtag(\"".$vrankedtags[$vi]['keyword']."\");'>".$vrankedtags[$vi]['keyword']."</a></span><br>";}
					if (($vj > 6) && ($vj < 14)) {echo "<font style='font-size:8pt;'>".($vj+1).".</font> <span style='background-color:#eeeeee;'><a href='javascript:void(0);' style='text-decoration:none;' onclick='addtag(\"".$vrankedtags[$vi]['keyword']."\");'>".$vrankedtags[$vi]['keyword']."</a></span><br>";}
					if (($vj > 13) && ($vj < 21)) {echo "<font style='font-size:8pt;'>".($vj+1).".</font> <span style='background-color:#eeeeee;'><a href='javascript:void(0);' style='text-decoration:none;' onclick='addtag(\"".$vrankedtags[$vi]['keyword']."\");'>".$vrankedtags[$vi]['keyword']."</a></span><br>";}
					if ($vj > 22) {echo " - ";}
					if ($vj > 21) {echo "<span style='background-color:#eeeeee;'><a href='javascript:void(0);' style='text-decoration:none;' onclick='addtag(\"".$vrankedtags[$vi]['keyword']."\");'>".$vrankedtags[$vi]['keyword']."</a></span> ";}
					echo "<input type='hidden' name='tag".$vj."' id='tag".$vj."' value='".$vrankedtags[$vi]['keyword']."'>";
					$vj = $vj + 1;
					$vincremented = "yes";
				}
				else {$vincremented = "no";}
				$vrankedtagkeyword[$vi] = $vrankedtags[$vi]['keyword'];
				$vi = $vi + 1;
			}
		}
		if ($vj > 20) {echo "</center></font></div><br>";}
		if ($vj < 21) {echo "</tr></table></center><br>";}

		if ($valchemyid != "") {

			if ($vautotagsuggest = "yes") {$vcontent = ats_get_just_post_content($vpostid);}
			else {$vcontent = get_just_post_content($vpostid);}

			$vpostcontent = $vcontent[0];
			$valchemyctags = get_alchemy_concepttags($vpostcontent,$valchemyid);
			// print_r($valchemyctags);

			if (is_array($valchemyctags)) {
				echo "<center><b>Concept Tag Suggestions</b></center>";
				echo "<center><font style='font-size:9pt;line-height:1.4em;'>";
				$vi = 0;
				foreach ($valchemyctags as $vatag) {
					if ($vi != 0) {echo " - ";}
					echo "<span style='background-color:#eeeeee'><a href='javascript:void(0);' style='text-decoration:none;' onclick='addtag(\"".$vatag."\");'>".$vatag."</a></span>";
					$vi = $vi + 1;
				}
				echo "</font></center>";
			}
		}

		echo "<br><center><b>Edit Post Tags</b> (comma separated list)</center>";
		echo "<center><textarea id='autotaggerposttags' name='autotaggerposttags' rows=4 cols=56>".$vposttags."</textarea></center><br>";
		echo "<input type='hidden' name='autosaveposttags' value='yes'>";

		echo "<center><table><tr><td><input type='button' value='Clear Tag Box' onclick='cleartags();'></td><td width=30></td>";
		echo "<td><input type='button' value='Revert to Saved' onclick='reverttosaved();'></td><td width=30></td>";
		echo "<td><input type='button' style='font-weight:bold;' value='Save These Tags' onclick='saveposttags();'></td></tr></table></center><br>";
		echo "<iframe id='savetagsframe' name='savetagsframe' style='display:none;' src='javascript:void(0);' width=450 height=50></iframe>";
		echo "</td></tr></table></body>";
		wp_die(false);
}

function get_the_post_tags($vpostid,$vformat) {
	$vgetposttags = get_the_tags($vpostid);
	$vposttagarray = array();
	if (is_array($vgetposttags)) {
		$vi = 0;
		foreach ($vgetposttags as $vaposttag) {
			$vposttag = strtolower((string)$vaposttag->name);
			if ($vposttags == "") {$vposttags = $vposttag;} else {$vposttags .= ", ".$vposttag;}
			$vposttagarray[$vi] = $vposttag;
			$vi = $vi + 1;
		}
	}
	if ($vformat == "array") {return $vposttagarray;}
	if ($vformat == "string") {return $vposttags;}
}

function get_ranked_tags($vpostid,$valchemyid,$vyahooappid,$vzemantaid,$vtagthenet) {

	global $vautotagsuggest;
	$vallblogtags = wp_tag_cloud('format=array&number=300');
	if ((count($vallblogtags) > 0) && ($vallblogtags != "")) {
		$vi = 0;
		foreach ($vallblogtags as $vablogtag) {
			$vposition = stripos($vablogtag,'/tag/')+5;
			$vtagchunks = str_split($vablogtag,$vposition);
			$vposition = stripos($vtagchunks[1],'/');
			$vtagtemp = str_split($vtagchunks[1],$vposition);
			$vblogtags[$vi] = strtolower(str_replace("-"," ",$vtagtemp[0]));
			$vi = $vi + 1;
		}
		// print_r($vblogtags);
	}

	$vposttitle = get_the_title($vpostid);

	// echo "<br><br><b>Post Tags</b>";
	$vgetposttags = get_the_tags($vpostid);
	$vposttags = array();
	if (is_array($vgetposttags)) {
		$vi = 0;
		foreach ($vgetposttags as $vaposttag) {
			$vposttags[$vi] = strtolower((string)$vaposttag->name);
			$vposttaglist .= " - ".$vposttags[$vi];
			$vi = $vi + 1;
		}
	}

	if ($vautotagsuggest = "yes") {$vcontent = ats_get_just_post_content($vpostid);}
	else {$vcontent = get_just_post_content($vpostid);}

	// $vpostcontent = $vcontent[0];
	$vpostcontent = preg_replace('/<[^>]*>/', ' ', $vcontent[0]);
	$vpostcontent = $vposttaglist." - ".$vpostcontent;

	if ($vtagthenet == "on") {$vtagthenettags = get_tagthenet_tags($vpostcontent);}
	if (is_array($vtagthenettags)) {$vtagthenettags = array_unique($vtagthenettags);} else {$vtagthenettags = array();}
	if ($vyahooappid != "") {$vyahootags = get_yahoo_tags($vpostcontent,$vyahooappid);}
	if (is_array($vyahootags)) {$vyahootags = array_unique($vyahootags);} else {$vyahootags = array();}
	if ($valchemyid != "") {$valchemytags = get_alchemy_tags($vpostcontent,$valchemyid);}
	if (is_array($valchemytags)) {$valchemytags = array_unique($valchemytags);} else {$valchemytags = array();}
	if ($vzemantaid != "") {$vzemantatags = get_zemanta_tags($vpostcontent,$vposttitle,$vzemantaid);}
	if (is_array($vzemantatags)) {$vzemantatags = array_unique($vzemantatags);} else {$vzemantatags = array();}

	if ((get_option('pingbackpro_debug') == '1') || (get_option('autotagsuggest_debug') == 'on')) {
		echo "<br>Tag the Net: ".count($vtagthenettags)." - Alchemy: ".count($valchemytags)." - Yahoo: ".count($vyahootags)." - Zemanta: ".count($vzemantatags)."<br>";
	}

	// $vopencalaistags = get_opencalais_tags($vpostcontent,$vopencalaisid);
	// if (is_array($vopencalaistags)) {$vopencalaistags = array_unique($vopencalaistags);} else {$vopencalaistags = array();}

	$vtitletags = array();
	// echo "<br><br><b>From Title</b>";
	if ($vtagthenet == "on") {$vtitletagstemp = get_tagthenet_tags($vposttitle);}
	if (is_array($vtitletagstemp)) {$vtitletags = array_merge($vtitletags,$vtitletagstemp);}
	if ($vyahooappid != "") {$vtitletagstemp = get_yahoo_tags($vposttitle,$vyahooappid);}
	if (is_array($vtitletagstemp)) {$vtitletags = array_merge($vtitletags,$vtitletagstemp);}
	if ($valchemyid != "") {$vtitletagstemp = get_alchemy_tags($vposttitle,$valchemyid);}
	if (is_array($vtitletagstemp)) {$vtitletags = array_merge($vtitletags,$vtitletagstemp);}
	if (is_array($vtitletags)) {$vtitletags = array_unique($vtitletags);}

	$vallthetags = array_merge($vposttags,$vtagthenettags);
	$vallthetags = array_merge($vallthetags,$vyahootags);
	$vallthetags = array_merge($vallthetags,$valchemytags);
	$vallthetags = array_merge($vallthetags,$vzemantatags);
	$vallthetags = array_merge($vallthetags,$vtitletags);
	if (is_array($vallthetags)) {$vallthetags = array_unique($vallthetags);}

	// print_r($vposttags); echo "<br>";
	// print_r($vtagthenetags); echo "<br>";
	// print_r($vyahootags); echo "<br>";
	// print_r($valchemytags); echo "<br>";
	// print_r($vzemantatags); echo "<br>";
	// print_r($vallthetags); echo "<br>";
	// print_r($vtitletags); echo "<br>";

	$vi = 0;
	foreach ($vallthetags as $vagoodtag) {
		if (substr_count($vagoodtag," ") == 0) {$vonewordtags[$vi] = strtolower($vagoodtag); $vi++;}
	}
	if (is_array($vonewordtags)) {$vonewordtags = array_unique($vonewordtags);}

	$vi = 0;
	$vrankeddatalines = "";
	foreach ($vallthetags as $vagoodtag) {
		$vagoodtag = strtolower($vagoodtag);
		$vnumterms = substr_count($vagoodtag," ") + 1;
		$vtagmatches = 0;
		foreach ($vblogtags as $vablogtag) {if (stristr($vagoodtag,$vablogtag)) {$vtagmatches = $vtagmatches + 1;}}
		foreach ($vposttags as $vaposttag) {if (stristr($vagoodtag,$vaposttag)) {$vtagmatches = $vtagmatches + 2;}}
		foreach ($vonewordtags as $vawordtag) {if (stristr($vagoodtag,$vawordtag)) {$vtagmatches = $vtagmatches + 2;}}
		$vtagscore = ($vnumterms * 10) + $vtagmatches;
		$vrankedtags[$vi]['keyword'] = $vagoodtag;
		$vrankedtags[$vi]['score'] = $vtagscore;
		$vi = $vi + 1;
	}
	if ($vautotagsuggest == "yes") {$vrankedtags = ats_subval_sort($vrankedtags,'score');}
	else {$vrankedtags = subval_sort($vrankedtags,'score');}

	foreach ($vrankedtags as $varankedtag) {
		$vagoodtag = $varankedtag['keyword'];
		$vtagscore = $varankedtag['score'];
		$vrankeddataline = $vtagscore.",".$vagoodtag."\r\n";
		$vrankeddatalines .= $vrankeddataline;
	}

	if ($vautotagsuggest == "yes") {$vrankedtagfile = WP_PLUGIN_DIR.'/autotagsuggest/data/'.$vpostid.'-rankedtags.txt';}
	else {$vrankedtagfile = WP_PLUGIN_DIR.'/pingbackpro/data/'.$vpostid.'-rankedtags.txt';}

	$vfh = fopen($vrankedtagfile,'w');
	fwrite($vfh,$vrankeddatalines);
	fclose($vfh);

	// print_r($vrankedtags);
	return($vrankedtags);
}

function get_tagthenet_tags($vpostcontent) {

	$vpostfields = "text=".$vpostcontent;

	$vurl = "http://tagthe.net/api";
	$vch = curl_init();
	curl_setopt($vch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1");
	curl_setopt($vch, CURLOPT_URL,$vurl);
	curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($vch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($vch, CURLOPT_POSTFIELDS, $vpostfields);
	curl_setopt($vch, CURLOPT_POST, TRUE);

	$vurlcontents = curl_exec($vch);
	$vhttp_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
	curl_close ($vch);
	unset($vch);

	// echo $vurlcontents;
	// echo "<br><br>";

	if ($vhttp_code == "503") {echo "Tag the Net 503: Service Currently Unavailable.<br>"; return false;}

	$vtagthenetdata = @simplexml_load_string($vurlcontents);

	// if ((get_option('pingbackpro_debug') == '1') || (get_option('autotagsuggest_debug') == 'on')) {
	// 	echo "Tag the Net Result Array:<br>"; print_r($vtagthenetdata);
	// }

	// print_r($vtagthenetdata);
	if (isset($vtagthenetdata)) {
		$vi = 0;
		foreach ($vtagthenetdata->meme->dim as $vadim) {
		 	if ($vatag[0] != "english") {
		 		foreach ($vadim->item as $vatag) {
		 			$vtagthenettags[$vi] = strtolower(trim((string)$vatag[0]));
					$vi = $vi + 1;
				}
			}
		}
		// print_r($vtagthenettags);
		return $vtagthenettags;
	}
	return false;
}

function get_yahoo_tags($vpostcontent,$vyahooappid) {

	$vyahooappid = rawurlencode($vyahooappid);
	$vpostcontent = rawurlencode($vpostcontent);
	$vpostfields = "appid=".$vyahooappid."&context=".$vpostcontent;

	$vurl = "http://search.yahooapis.com/ContentAnalysisService/V1/termExtraction";
	$vch = curl_init();
	curl_setopt($vch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1");
	curl_setopt($vch, CURLOPT_URL,$vurl);
	curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($vch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($vch, CURLOPT_POSTFIELDS, $vpostfields);
	curl_setopt($vch, CURLOPT_POST, TRUE);

	$vurlcontents = curl_exec($vch);
	$vhttp_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
	curl_close ($vch);
	unset($vch);

	// echo $vurlcontents;
	// echo "<br><br>";

	if ($vhttp_code == "503") {echo "Yahoo 503: Service Currently Unavailable.<br>"; return false;}

	$vyahoodata = simplexml_load_string($vurlcontents);
	// print_r($vyahoodata);

	// if ((get_option('pingbackpro_debug') == '1') || (get_option('autotagsuggest_debug') == 'on')) {
	//	echo "Yahoo Result Array:<br>"; print_r($vyahoodata); echo "<br>";
	// }

	if (isset($vyahoodata)) {
		$vi = 0;
		foreach ($vyahoodata->Result as $varesult) {
			$vyahootags[$vi] = trim((string)$varesult);
		 	$vi = $vi + 1;
		}
		return $vyahootags;
	}
	return false;
}

function get_alchemy_tags($vpostcontent,$valchemyid) {

	$vpostcontent = urlencode($vpostcontent);
	$vpostfields = "apikey=".$valchemyid."&html=".$vpostcontent."&maxresults=20";

	$vurl = "http://access.alchemyapi.com/calls/html/HTMLGetRankedKeywords";
	$vch = curl_init();
	curl_setopt($vch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1");
	curl_setopt($vch, CURLOPT_URL,$vurl);
	curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($vch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($vch, CURLOPT_POSTFIELDS, $vpostfields);
	curl_setopt($vch, CURLOPT_POST, TRUE);

	$vurlcontents = curl_exec($vch);
	$vhttp_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
	curl_close ($vch);
	unset($vch);

	// echo $vurlcontents;
	// echo "<br><br>";

	if ($vhttp_code == "503") {echo "Alchemy 503: Service Currently Unavailable.<br>"; return false;}

	$valchemydata = simplexml_load_string($vurlcontents);
	// print_r($valchemydata);

	if (isset($valchemydata)) {
		$vi = 0;
		foreach ($valchemydata->keywords->keyword as $varesult) {
			// print_r($varesult);
			// echo $varesult->text."<br>";
			$valchemytags[$vi] = strtolower(trim((string)$varesult->text));
		 	$vi = $vi + 1;
		}
		return $valchemytags;
	}
	return false;
}

function get_alchemy_concepttags($vpostcontent,$valchemyid) {

	$vpostcontent = urlencode($vpostcontent);
	$vpostfields = "apikey=".$valchemyid."&html=".$vpostcontent."&maxRetrieve=20&linkedData=0";

	$vurl = "http://access.alchemyapi.com/calls/html/HTMLGetRankedConcepts";
	$vch = curl_init();
	curl_setopt($vch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1");
	curl_setopt($vch, CURLOPT_URL,$vurl);
	curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($vch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($vch, CURLOPT_POSTFIELDS, $vpostfields);
	curl_setopt($vch, CURLOPT_POST, TRUE);

	$vurlcontents = curl_exec($vch);
	$vhttp_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
	curl_close ($vch);
	unset($vch);

	// echo $vurlcontents;
	// echo "<br><br>";

	if ($vhttp_code == "503") {return false;}

	$valchemydata = simplexml_load_string($vurlcontents);
	// print_r($valchemydata);

	if (isset($valchemydata->concepts)) {
		$vi = 0;
		foreach ($valchemydata->concepts->concept as $varesult) {
			// echo $varesult->text."<br>";
			$valchemyctags[$vi] = strtolower(trim((string)$varesult->text));
		 	$vi = $vi + 1;
		}
		return $valchemyctags;
	}
	return false;
}

function get_zemanta_tags($vpostcontent,$vposttitle,$vzemantaid) {

	$vpostcontent = urlencode($vpostcontent);
	$vpostfields = "method=zemanta.suggest&api_key=".$vzemantaid."&text=".$vpostcontent."&text_title=".$vpostttitle."&return_images=0&articles_limit=1&markup_limit=1";

	$vurl = "http://api.zemanta.com/services/rest/0.0/";
	$vch = curl_init();
	curl_setopt($vch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1");
	curl_setopt($vch, CURLOPT_URL,$vurl);
	curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($vch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($vch, CURLOPT_POSTFIELDS, $vpostfields);
	curl_setopt($vch, CURLOPT_POST, TRUE);

	$vurlcontents = curl_exec($vch);
	$vhttp_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
	curl_close ($vch);
	unset($vch);

	// echo $vurlcontents;
	// echo "<br><br>";

	if ($vhttp_code == "503") {echo "Zemanta 503: Service Currently Unavailable.<br>"; return false;}

	$vzemantadata = simplexml_load_string($vurlcontents);
	// print_r($vzemantadata);

	if (isset($vzemantadata)) {
		$vi = 0;
		foreach ($vzemantadata->keywords->keyword as $varesult) {
			// echo $varesult->name;
		 	$vzemantatags[$vi] = strtolower(trim((string)$varesult->name));
		 	$vi = $vi + 1;
		}
		return $vzemantatags;
	}
	return false;
}


function get_opencalais_tags($vpostcontent,$vopencalaisid) {

	$vpostdata = urlencode($vpostcontent);
	$vopencalaisid = urlencode($vopencalaisid);
	$vxmlparams = urlencode('<c:params xmlns:c="http://s.opencalais.com/1/pred/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
	<c:processingDirectives c:contentType="text/html" c:enableMetadataType="GenericRelations" c:calculateRelevanceScore="true" c:docRDFaccesible="false" >
	</c:processingDirectives></c:params>');

	$vpostfields = "licenseID=".$vopencalaisid."&content=".$vpostdata."&paramsXML=".$vxmlparams;

	$vurl = "http://api.opencalais.com/enlighten/rest/";
	$vch = curl_init();
	curl_setopt($vch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.1) Gecko/2008070208 Firefox/3.0.1");
	curl_setopt($vch, CURLOPT_URL,$vurl);
	curl_setopt($vch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($vch, CURLOPT_CONNECTTIMEOUT, 5);
	curl_setopt($vch, CURLOPT_POSTFIELDS, $vpostfields);
	curl_setopt($vch, CURLOPT_POST, TRUE);

	$vurlcontents = curl_exec($vch);
	$vhttp_code = curl_getinfo($vch, CURLINFO_HTTP_CODE);
	curl_close ($vch);
	unset($vch);

	echo $vurlcontents;
	echo "<br><br><br>";

	$vopencalaisdata = simplexml_load_string($vurlcontents);
	print_r($vopencalaisdata);

	if (isset($vopencalaisdata)) {
		$vi = 0;
		// foreach ($vopencalaisdata as $vatagdata) {

		// }
		// print_r($vopencalaisdata);
		echo "<br>";
		return $vopencalaisdata;
	}
}

?>

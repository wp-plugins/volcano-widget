<?php
/*
Plugin Name: VolcanoWidget
Plugin URI: http://earthquakes.volcanodiscovery.com/
Description: Interactive map of active volcanoes and current earthquakes world-wide
Author: Tom Pfeiffer
Version: 1.1
Author URI: http://www.volcanodiscovery.com/
License: GPL2
*/
 
 
class VolcanoWidget extends WP_Widget
{
  
  function VolcanoWidget()
  {
    $widget_ops = array('classname' => 'VolcanoWidget', 'description' => 'Interactive map of active volcanoes and current earthquakes world-wide' );
    $this->WP_Widget('VolcanoWidget', 'Volcanoes and earthquakes map', $widget_ops);
  }
 
  function form($instance)
  {
    $instance = wp_parse_args( (array) $instance, array( 'title' => '' ) );
    $title = $instance['title'];
?>
  <p><label for="<?php echo $this->get_field_id('title'); ?>">Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo attribute_escape($title); ?>" /></label></p>
<?php
  }
 
  function update($new_instance, $old_instance)
  {
    $instance = $old_instance;
    $instance['title'] = $new_instance['title'];
    return $instance;
  }
 
  function widget($args, $instance)
  {
    extract($args, EXTR_SKIP);
 
    echo $before_widget;
    $title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
 
    if (!empty($title))
      echo $before_title . $title . $after_title;;
 
    // WIDGET CODE GOES HERE
    $add = $this->mapLanguage?'&L='.intval($this->mapLanguage):'';
    $add .= $this->showVolcanoes?'':'&hideVolcanoes=1';
    $add .= $this->showEarthquakes?'':'&hideEarthquakes=1';
    $add .= $this->minMag!=4?'':'&minMag='.intval($this->minMag);
    if ($add) $add = '?'.$add;
    $code = get_option('volcano_widget_data');
    if (!$code) $code = '
<!-- begin VolcanoWidget -->
<div id="VW_bigMap" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:999999;">
<div id="VW_backDiv" style="background:#000;filter:alpha(opacity=80);opacity:.8;height:100%;width:100%;position:absolute;top:0px;left:0px;z-index:-1;" onclick="switchFrame(\'VW_smallMap\',\'VW_bigMap\',\'enlarge\',\'small map\',\'100%\',\'200px\',0,-180);return false;"></div></div>
<div id="VW_smallMap" style="clear:left"><div id="VW_mCont" style="width:100%;height:200px;position:relative;margin:0;background:#fff;"><a name="VW_mCont"></a><div style="position:absolute;top:8px;right:28px;height:15px;text-align:right;vertical-align:middle;font:12px Verdana,sans-serif;font-weight:bold">[<a href="#" style="color:#bb202a" onclick="switchFrame(\'VW_smallMap\',\'VW_bigMap\',\'enlarge\',\'small map\',\'100%\',\'200px\',0,-180);return false;"><span id="VW_mSwitch">enlarge</span></a>]</div><iframe id="VW_iframe" width="100%" height="100%" scrolling="no" frameborder="0" marginwidth="0" marginheight="0" src="http://earthquakes.volcanodiscovery.com'.$add.'"></iframe></div></div>
<script type="text/javascript">function switchFrame(a,b,c,d,e,f,g,h){var i=document.getElementById("VW_mCont");var j=document.getElementById("VW_mSwitch").firstChild;if(j.nodeValue==c){j.nodeValue=d}else{j.nodeValue=c}var k=i.parentNode.getAttribute("id");if(k==a){var l=b}else{var l=a}var m=i.parentNode;var n=document.getElementById(l);n.appendChild(i);m.style.display="none";n.style.display="";if(l==a){i.style.width=e;i.style.height=f;i.style.margin=0;i.style.top=""}else{i.style.width="80%";i.style.height="80%";i.style.margin="auto";i.style.top="20px"}window.location.hash="VW_mCont"}</script>
<!-- end VolcanoWidget / http://www.volcano-news.com/active-volcanoes-map/get-widget.html -->
';
    echo $code; 
    echo $after_widget;
  }
  

} // end of class definition

add_action( 'widgets_init', create_function('', 'return register_widget("VolcanoWidget");') );

?>

<?php
/* Runs when plugin is activated */
register_activation_hook(__FILE__,'volcano_widget_install'); 

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'volcano_widget_remove' );

function volcano_widget_install() {
/* Creates new database field */
add_option("volcano_widget_data", 'Default', '', 'yes');
}

function volcano_widget_remove() {
/* Deletes the database field */
delete_option('volcano_widget_data');
}


if ( is_admin() ){

/* Call the html code */
add_action('admin_menu', 'volcano_widget_admin_menu');

function volcano_widget_admin_menu() {
add_options_page('Volcano Widget', 'Volcano Widget', 'administrator',
'VolcanoWidget', 'volcano_widget_html_page');
}
}
?>

<?php
function volcano_widget_html_page() {
?>
<div>
<h2>VolcanoWidget Options</h2>

<form method="post" action="options.php">
<?php wp_nonce_field('update-options'); ?>

<?php 
// get code
$html=get_option('volcano_widget_data');
// analyze
// finds the first occurence of s1 OUT1 s2 in str, and cuts this from string
function findBetween($s1,$s2,&$str) {
  $pos1=strpos($str,$s1);
  if ($pos1===false) {
    return false;
  }
  $pos2=strpos($str,$s2,$pos1+strlen($s1));
  if ($pos2===false) {
    return false;
  }
  $val = substr($str,$pos1+strlen($s1),$pos2-$pos1-strlen($s1));
  $str = substr($str,$pos2+strlen($s2));
  return $val;
}
// defaults
$params = array(
  'L'=>0,
  'width'=>'auto',
  'height'=>200,
  'title'=>'',
  'terrain'=>0,
  'zoom'=>0,
  'hideVolcanoes'=>0,
  'hideQuakes'=>0,
  'maxAge'=>'48h',
  'minMag'=>4,
  'lat'=>0,
  'lon'=>-180,
);
// check if saved code contains modified params
$paramString = findBetween('earthquakes.volcanodiscovery.com','"',$html);

if ($paramString !== false) {
  $paramString = ltrim($paramString,'/');
  $paramString = ltrim($paramString,'?');
  $parts = explode('&',$paramString);
  foreach ($parts as $part) {
    $part = explode('=',$part);
    $params[$part[0]]=$part[1];
  }
}


?>
<input name="volcano_widget_data" type="hidden" id="volcano_widget_data"
value="<?php echo htmlspecialchars($html); ?>" />

<div id="VW_bigMap" style="display:none;position:fixed;top:0;left:0;height:100%;width:100%;z-index:999999;">
    <div id="VW_backDiv" style="background:#000;filter:alpha(opacity=80);opacity:.8;height:100%;width:100%;position:absolute;top:0px;left:0px;z-index:-1;" onclick="switchFrame('VW_smallMap','VW_bigMap','enlarge','small map','280px','200px',5,180);"></div>
</div>
<table><tr><td width="33%" valign="top"><h5>Customize your map:</h5>
  Width / height in px: <input value="<?php echo($params['width']); ?>" size="3" id="width" onchange="updateCode();" /> / <input value="200" size="3" id="height" onchange="updateCode();" /> <select id="terrain" onchange="updateCode();"><option value="0"<?php echo(($params['terrain']==0?' selected="selected"':'')); ?>>satellite</option><option value="1"<?php echo(($params['terrain']==1?' selected="selected"':'')); ?>>terrain</option></select>
  Optional: change default title on (enlarged) map: <input id="title" size="40" value="<?php echo(urldecode($params['title'])); ?>" onchange="updateCode();"></input>
  <br />Show volcanoes by default: <input type="checkbox" id="showVolcanoes" onchange="updateCode();"<?php echo(($params['hideVolcanoes']==1?'':' checked="checked"')); ?> />
  <br />Show earthquakes by default: <input type="checkbox" id="showQuakes" onchange="updateCode();"<?php echo(($params['hideQuakes']==1?'':' checked="checked"')); ?> />
  <br />Language on map: <select id="language" onchange="updateCode();"><option value="0"<?php echo(($params['L']==0?' selected="selected"':'')); ?>>English</option><option value="1"<?php echo(($params['L']==1?' selected="selected"':'')); ?>>Deutsch</option><option value="2"<?php echo(($params['L']==2?' selected="selected"':'')); ?>>Français</option></select>
  <br /><i>Modify zoom and center coordinates to start:</i>
  <br />Zoom (0-6): <select id="zoom" onchange="updateCode();"><option value="0"<?php echo(($params['zoom']==0?' selected="selected"':'')); ?>>0</option><option value="1"<?php echo(($params['zoom']==1?' selected="selected"':'')); ?>>1</option><option value="2"<?php echo(($params['zoom']==2?' selected="selected"':'')); ?>>2</option><option value="3"<?php echo(($params['zoom']==3?' selected="selected"':'')); ?>>3</option><option value="4"<?php echo(($params['zoom']==4?' selected="selected"':'')); ?>>4</option><option value="5"<?php echo(($params['zoom']==5?' selected="selected"':'')); ?>>5</option><option value="6"<?php echo(($params['zoom']==6?' selected="selected"':'')); ?>>6</option></select>
  
  <br />Latitude (-80 (S) to 80 (N)):<input id="lat" size="3" value="<?php echo($params['lat']); ?>" onchange="updateCode();"></input>
  <br />Longitude (-180 (W) to 180 (E)):<input id="lon" size="3" value="<?php echo($params['lon']); ?>" onchange="updateCode();"></input>
  <br /><i>OR:</i>
  <br /><i>Show only earthquakes in specific area defined by W/E and S/N coordinates:</i>
  <br />Eastern / western boundary (-180 (W) to 180 (E)):<input id="lon1" size="3" value="<?php echo($params['lon1']); ?>" onchange="updateCode();"></input> / <input id="lon2" size="3" value="<?php echo($params['lon2']); ?>" onchange="updateCode();"></input>
  <br />Southern / northern boundary (-90 (S) to 90 (N)):<input id="lat1" size="3" value="<?php echo($params['lat1']); ?>" onchange="updateCode();"></input> / <input id="lat2" size="3" value="<?php echo($params['lon2']); ?>" onchange="updateCode();"></input>
  
  <br />Default minimum earthquake magnitude: <select id="minMag" onchange="updateCode();"><option value="0"<?php echo(($params['minMag']==0?' selected="selected"':'')); ?>>any</option><option value="3"<?php echo(($params['minMag']==3?' selected="selected"':'')); ?>>M3+</option><option value="4"<?php echo(($params['minMag']==4?' selected="selected"':'')); ?>>M4+</option><option value="5"<?php echo(($params['minMag']==5?' selected="selected"':'')); ?>>M5+</option><option value="6"<?php echo(($params['minMag']==6?' selected="selected"':'')); ?>>M6+</option></select>
  <br />Default time frame: <select id="maxAge" onchange="updateCode();"><option value="24h"<?php echo(($params['maxAge']=='24h'?' selected="selected"':'')); ?>>24h</option><option value="48h"<?php echo(($params['maxAge']=='48h'?' selected="selected"':'')); ?>>48h</option><option value="1w"<?php echo(($params['maxAge']=='1w'?' selected="selected"':'')); ?>>1 week</option></select>

  </td><td><h5>Preview:</h5>
  

  <div id="VW_smallMap">
  <div id="VW_mCont" style="width:280px;height:200px;position:relative;margin:0;background:#fff;">
  <a name="VW_mCont"></a>
  <div style="position:absolute;top:8px;right:28px;height:15px;vertical-align:middle;text-align:right;font:12px Verdana,sans-serif;font-weight:bold">[<a id="switchLink" href="#" onclick="switchFrame('VW_smallMap','VW_bigMap','enlarge','small map','280px','200px',5,180);return false;"><span id="VW_mSwitch">enlarge</span></a>]</div>
  <iframe id="VW_iframe" width="100%" height="100%" scrolling="no" frameborder="0" marginwidth="0" marginheight="0" src="http://www.volcanodiscovery.com/eruptingvolcanoes.php">
  </iframe>
    
  </div></div>
  </td></tr></table>
  
  <b>Stand-alone URL: <a id="standAlone" href="http://earthquakes.volcanodiscovery.com/" target="_blank">http://earthquakes.volcanodiscovery.com/</a></b><br />
  
  
  
<script type="text/javascript">
function SelectAll(id){
    document.getElementById(id).focus();
    document.getElementById(id).select();
}
 
function updateCode() {
  var c=document.getElementById('VW_mCont');
  var iFrame = document.getElementById('VW_iframe');
  var switchLink = document.getElementById('switchLink');
  var VW_backDiv = document.getElementById('VW_backDiv');
  if (document.getElementById('width').value=="auto") var width="100%";
  else {
    var width = parseInt(document.getElementById('width').value);
    if (width<200) width=200; if (width>1000) width=1000;
    width = width+"px";
  }
  var height = parseInt(document.getElementById('height').value);
  if (height<120) height=120; if (height>500) height=500;
  var baseURL = 'http://earthquakes.volcanodiscovery.com';
  //document.getElementById('baseURL').options[document.getElementById('baseURL').selectedIndex].value;
  var title = encodeURIComponent (document.getElementById('title').value); // escape
  var terrain = document.getElementById('terrain').selectedIndex;
  var zoom = parseInt(document.getElementById('zoom').selectedIndex);
  if (zoom>10) zoom = 10; if (zoom<1) zoom = 0;
  
  var lat = parseFloat(document.getElementById('lat').value);
  if (lat>80) lat = 80; if (lat<-80) lat = -80;
  var lon = parseFloat(document.getElementById('lon').value);
  if (lon>180) lon = 180; if (lon<-180) lon = -180;
  var lat1 = parseFloat(document.getElementById('lat1').value);
  if (lat1>90) lat1 = 90; if (lat1<-90) lat1 = -90;
  var lat2 = parseFloat(document.getElementById('lat2').value);
  if (lat2>90) lat2 = 90; if (lat2<-90) lat2 = -90;
  var lon1 = parseFloat(document.getElementById('lon1').value);
  if (lon1>180) lon1 = 180; if (lon1<-180) lon1 = -180;
  var lon2 = parseFloat(document.getElementById('lon2').value);
  if (lon2>180) lon2 = 180; if (lon2<-180) lon2 = -180;
  var lang = document.getElementById('language').value;
  var langParam = '';
  if (lang>0) langParam = '&L='+lang;
  
  var showVolcanoes = document.getElementById('showVolcanoes').checked;
  var showQuakes = document.getElementById('showQuakes').checked;
  var maxAge = document.getElementById('maxAge').options[document.getElementById('maxAge').selectedIndex].value;
  var minMag = document.getElementById('minMag').options[document.getElementById('minMag').selectedIndex].value;
  
  
  // width of container on preview page not allowed to 100%
  var width2=width;
  if (width=="100%") width2="280px"; 
  c.style.width=width2;c.style.height=height+'px';
  
  var params = '';
  if (title!='') var params='?title='+title;
  if (lang>0) params += langParam;
  if (terrain==1) params += '&terrain=1';
  if (lon1 && lon2 && lat1 && lat2) params += '&lat1='+lat1+'&lat2='+lat2+'&lon1='+lon1+'&lon2='+lon2;
  else {
    if (zoom>0) params += '&zoom='+zoom;
    if (lat!=0) params += '&lat='+lat;
    if (lon!=-180) params += '&lon='+lon;
  }
  if (showVolcanoes!=1) params += '&hideVolcanoes=1';
  if (showQuakes!=1) params += '&hideQuakes=1';
  if (minMag!='4') params += '&minMag='+minMag;
  if (maxAge!='48h') params += '&maxAge='+maxAge;
  
  
  if (params) if (params.substring(0,1)!='?') params = '?'+params.substring(1);
  // update iframe on this page
  iFrame.src=baseURL+params;
  var widgetSrc1 = baseURL+params;
  var widgetSrc2 = baseURL+params;
  var iFrameCode1 = '<iframe id="VW_iframe" width="'+width+'" height="'+height+'" scrolling="no" frameborder="0" marginwidth="0" marginheight="0" src="'+widgetSrc1+'"></iframe>';
  var iFrameCode2 = '<iframe id="VW_iframe" width="100%" height="100%" scrolling="no" frameborder="0" marginwidth="0" marginheight="0" src="'+widgetSrc2+'"></iframe>';
  
  // update direkt link
  document.getElementById('standAlone').href=widgetSrc1;
  document.getElementById('standAlone').innerHTML=widgetSrc1;
  
  var enlarge = 'enlarge'; var small = 'small map';
  if (lang==1) {enlarge = 'vergrößern'; var small = 'kleine Karte';}
  if (lang==2) {enlarge = 'agrandir'; var small = 'petite carte';}
  document.getElementById('VW_mSwitch').innerHTML = enlarge;
  // on preview
  var onClick = "switchFrame('VW_smallMap','VW_bigMap','"+enlarge+"','"+small+"','"+width2+"','"+height+"px',"+lat+","+lon+");return false;";  
  switchLink.setAttribute('onClick',onClick);
  VW_backDiv.setAttribute('onClick',onClick);
  // on live version
  var onClickCode = "switchFrame('VW_smallMap','VW_bigMap','"+enlarge+"','"+small+"','"+width+"','"+height+"px',"+lat+","+lon+");return false;";  
  
  var finalCode = '<!-- begin VolcanoWidget -->\n<div id="VW_bigMap" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;z-index:999999;">\n<div id="VW_backDiv" style="background:#000;filter:alpha(opacity=80);opacity:.8;height:100%;width:100%;position:absolute;top:0px;left:0px;z-index:-1;" onclick="'+onClick+'"></div></div>';
  finalCode += '\n<div id="VW_smallMap" style="clear:left"><div id="VW_mCont" style="width:'+width+';height:'+height+'px;position:relative;margin:0;background:#fff;"><a name="VW_mCont"></a>';
  finalCode += '<div style="position:absolute;top:8px;right:28px;height:15px;text-align:right;vertical-align:middle;font:12px Verdana,sans-serif;font-weight:bold">[<a href="#" style="color:#bb202a" onclick="'+onClickCode+'"><span id="VW_mSwitch">'+enlarge+'</span></a>]</div>';
  finalCode += iFrameCode2+'</div></div>\n';
  finalCode += '<script type="text/javascript">'+'function switchFrame(a,b,c,d,e,f,g,h){var i=document.getElementById("VW_mCont");var j=document.getElementById("VW_mSwitch").firstChild;if(j.nodeValue==c){j.nodeValue=d}else{j.nodeValue=c}var k=i.parentNode.getAttribute("id");if(k==a){var l=b}else{var l=a}var m=i.parentNode;var n=document.getElementById(l);n.appendChild(i);m.style.display="none";n.style.display="";if(l==a){i.style.width=e;i.style.height=f;i.style.margin=0;i.style.top=""}else{i.style.width="80%";i.style.height="80%";i.style.margin="auto";i.style.top="20px"}window.location.hash="VW_mCont"}'; 
  finalCode += '<\/script>\n<!-- end VolcanoWidget / http://www.volcano-news.com/active-volcanoes-map/get-widget.html -->\n\n';
  
  document.getElementById('volcano_widget_data').value=finalCode;
  
}
function switchFrame(destinationParent1,destinationParent2,text1,text2,width,height,lat,lon) {
	var VW_mCont = document.getElementById('VW_mCont');
  var mapSwitch = document.getElementById('VW_mSwitch').firstChild;
	if (mapSwitch.nodeValue==text1) {mapSwitch.nodeValue=text2;}
	else {mapSwitch.nodeValue=text1;};

	var currentParentId = VW_mCont.parentNode.getAttribute('id');
	if (currentParentId == destinationParent1) {
	  var destinationParent = destinationParent2;
  } else {
    var destinationParent = destinationParent1;
  };

	var oldParentNode = VW_mCont.parentNode;
  var newParentNode = document.getElementById(destinationParent);
  newParentNode.appendChild(VW_mCont);
  oldParentNode.style.display='none';
  newParentNode.style.display='';
  
  
	// update container
  
  if (destinationParent == destinationParent1) {
			VW_mCont.style.width = width;
			VW_mCont.style.height = height;
			VW_mCont.style.margin = 0;
      VW_mCont.style.top = "";   
     
	} else {
  		VW_mCont.style.width = "80%";
      VW_mCont.style.height = "80%";
			VW_mCont.style.margin = "auto";
			VW_mCont.style.top = "20px";			
	};
	window.location.hash='VW_mCont';

       
}
updateCode();

</script>





<input type="hidden" name="action" value="update" />
<input type="hidden" name="page_options" value="volcano_widget_data" />

<p>
<input type="submit" value="<?php _e('Save Changes') ?>" />
</p>

</form>
</div>
<?php
}
?>

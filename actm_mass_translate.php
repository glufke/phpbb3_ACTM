<?
/**
*
* @package actm
* @version $Id$
* @copyright (c) 2013 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/
/**

*/

  // ============================================================
  // Variables and Includes to start phpbb3 environment
  // ============================================================
  
  //-----------------
  $post_id  = '';
  $topic_id = '';
  $forum_id = '';
  //-----------------
  
  $translator = 'bing';      // bing or google  
  $current_time = time();
  $cont =0;

  require_once("includes/functions_translation.php"); 
  
  define('IN_PHPBB', true);
  $phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
  $phpEx = substr(strrchr(__FILE__, '.'), 1);
  include($phpbb_root_path . 'common.' . $phpEx);
  include($phpbb_root_path . 'includes/bbcode.' . $phpEx);
  include($phpbb_root_path . 'includes/functions_display.' . $phpEx);  
  include($phpbb_root_path . 'includes/message_parser.' . $phpEx);
  
  // Start session management
  $user->session_begin();
  $auth->acl($user->data);
  $user->setup('common');
  $message_parser = new parse_message();  
  
  
//the meta-refresh below will re-run this script each 600 seconds.  
?>
<html lang='en'>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="refresh" content="600" >
<?

  //Check if the user is a moderator. (we dont want the whole world running this script)
  if (!$auth->acl_gets('m_', 11))
  { echo 'You shouldnt be here. But dont worry, <br>we are saving your IP ('.$_SERVER['REMOTE_ADDR'].') and we will inform you when this program is ready for public';
    exit();
  }


  //FUNCTION that create a LOG   
  function fnc_log($t, $text)
  { 
    $type = ' N';  // N or NL
 
    if (strpos($type, $t,0)>0) echo $text;
    return;
  }

 
  // Start session management
  $auth->acl($user->data);
  $user->setup('viewforum');


  
  //REPORT that show the situation before. 
 $sql5 = '
          SELECT
            count(a.post_text) qt_posts2
          , sum(if(a.post_time_en<>0 ,1,0) ) translated2
          , sum(if(a.post_time_en<>0, 1,0) ) / count(a.post_text) * 100 perc2
          FROM `phpbb_posts` a';

  $result = $db->sql_query_limit($sql5, 100);
  while ($row = $db->sql_fetchrow($result))
	{  		 
    echo '<h1><table><tr><b><td>TOTAL</td>
          <td>'.$row['qt_posts2'].'</td>
          <td>'.$row['translated2'].'</td>
          <td>'.$row['perc2'].'</td>
          <td>'.ceil(($row['qtd_posts2']-$row['translated2'])/48).'</td>
          </b><tr>
         ';
  }
  echo '</table></h1>';



  // Create where clause based on parameters; (NULL for all)  
  if ($forum_id != '') $filtro=' AND t.forum_id  = '.$forum_id. ' AND p.forum_id  = '.$forum_id ;
  if ($topic_id != '') $filtro=' AND t.topic_id  = '.$topic_id;
  if ($post_id  != '') $filtro=' AND p.post_id   = '.$post_id;
  
  //this field indicates that the post need translation.
  $filtro = $filtro . ' AND post_time_en=0 ';   

  $sql = 'SELECT
            p.post_id
          , ( SELECT MIN( t.post_id ) FROM phpbb_posts t WHERE t.topic_id = p.topic_id) first_post_id            
          , p.post_text   text
          , p.bbcode_uid
          , p.post_subject  
          , t.topic_title
          , t.topic_id
          , t.forum_id
          FROM phpbb_posts  p
          ,    phpbb_topics t
          WHERE p.topic_id = t.topic_id'.$filtro.' 
            AND t.forum_id NOT IN  (18,19,28,11 )';
        //ORDER BY t.forum_id DESC, t.topic_id, p.post_id';   //removed ORDER BY because some problems with temporary space on server.


  fnc_log('L', $sql.'<br><br><br>');          
  fnc_log('L', 'TIME: '.$current_time.'<br>');
  $result = $db->sql_query_limit($sql,48);

  //RUN the translation for each post fetched above!
  while ($row = $db->sql_fetchrow($result))
	{
    echo $cont. ' ';		

    //BING has a limit of 50 request per minute.     
    if ($cont < 48 )
    {
     
      //set information about the current post.
      $post_id = $row['post_id'];
      $topic_id = $row['topic_id'];
      $forum_id = $row['forum_id'];

      //Log
      fnc_log('N', 'FORUM: '.$forum_id);
      fnc_log('N', ' TOPIC: '.$topic_id);
      fnc_log('N', ' POST: '.$post_id);
      
      //------------------------------------
      //Translate the MESSAGE and the SUBJECT
      //------------------------------------
      
      //TRANSLATING THE SUBJECT
      // Only translate the subject if it is the first post of the topic.
      $s = '';
      if ($row['first_post_id']==$post_id )
      {
        fnc_log('L',"<br>The first topic of the post. We will translate the subject");
        $s = fnc_translate_post ($row['post_subject'], 'pt', 'en', $translator);
        $cont = $cont +1 ;
        $su = ' post_subject_en="'.$s.'", '; //this will be added on the UPDATE command.
        fnc_log('L', "<br>$s<br>");
      }
      
      
      //TRANSLATE THE MESSAGE
      $t = $row['text'];
      fnc_log('L', '<br><br>ORIGINAL:<br>'.$t);
      
      // The command below will remove all special tags saved on database.
      // It return exactly what the user had typed.
      // Example:
      // Transform THIS.. :  [b:29gg7cl3]boldtext[/b:29gg7cl3]   [code:29gg7cl3]SELECT[/code:29gg7cl3]
      // to THIS..........:  [b]boldtext/b]    [code]SELECT[/code]
      decode_message($t, $row['bbcode_uid']);
      fnc_log('L','<br><br>DECODE:<br>'.$t);
      
      $translated =fnc_translate_post ($t, 'pt', 'en', $translator);
      $cont = $cont +1 ;
      fnc_log('L', '<br><br>TRANSLATED:<br>'.$translated);
      
      
      // ----------------------------------
      // Now, leave the text in PHPBB format 
      // ----------------------------------
      $text = utf8_normalize_nfc( $translated );              //using core phpbb3 function
      $text = preg_replace("%\n%", "\r\n", $text);            //test this  (if its necessary or not). 
      $uid = $bitfield = $options = '';                       //will be modified by generate_text_for_storage
      $allow_bbcode = $allow_urls = $allow_smilies = true;

      //http://wiki.phpbb.com/Tutorial.Parsing_text#Generating_text_for_editing
      generate_text_for_storage($text, $uid, $bitfield, $options, $allow_bbcode, $allow_urls, $allow_smilies);
     
      $sql_ary = array(
          'text'              => $text
      //    'bbcode_uid'        => $uid,
      //    'bbcode_bitfield'   => $bitfield,
      //    'enable_bbcode'     => $allow_bbcode,
      //    'enable_magic_url'  => $allow_urls,
      //    'enable_smilies'    => $allow_smilies,
      );

      $text = str_replace($uid, $row['bbcode_uid'], $text);   //leave the former UID!
      $text = str_replace( '"', '\"', $text);                 //remove quotes to the insert command
      
      //Changes the URL to new domain. 
      $text = str_replace('href=\"http://glufke.net/oracle'  
                         ,'href=\"http://en.glufke.net/oracle'  
                         , $text);                     


      // --------------------------------
      // UPDATE THE TABLE
      // -------------------------------- 
      fnc_log('L', '<br><br>Su = '.$su. '<br><br><br>');
      $sql2 = 'UPDATE phpbb_posts SET '.$su.' post_text_en="' . $text .'", post_time_en = '.$current_time.' WHERE post_id ='.$post_id;
      fnc_log('L', '<hl><br><br><br>SQL:<br>'); 
      fnc_log('L', $sql2);
      $db->sql_query($sql2);
      fnc_log('L', '<hl><br><br><br>'.$row['bbcode_uid'].'<br>'.$uid.'<br>');
      fnc_log('L', $sql2); 
       

      fnc_log('N', ' - '.'<a href="http://en.glufke.net/oracle/viewtopic.php?f='.$forum_id.'&p='.$post_id.'">LINK</a> '.substr($text,0,20).'<br>');

      if ($row['first_post_id']==$row['post_id'])
      {
        $sql3 = 'UPDATE phpbb_topics SET topic_title_en ="' . $s .'" WHERE topic_id ='.$row['topic_id'];
        fnc_log('L', '<hl><br><br><br>SQL:<br>'); 
        fnc_log('L', $sql3);
        $db->sql_query($sql3);      
      }

	}
  }  
// END OF TRANSLATION PROCESS  
  
  
  
  
  
/// REPORT TO FOLLOW THE PROGRESS.
 $sql3 = '
SELECT
  a.forum_id
, if (f.forum_name_en <>"0", f.forum_name_en, f.forum_name) forum_name
, count(DISTINCT a.topic_id) qtd_topicos
, count(DISTINCT (if(a.post_text_en<>"",a.topic_id,0)) ) traduzido
, count(DISTINCT (if(a.post_text_en<>"",a.topic_id,0)) )
/ count(DISTINCT a.topic_id) * 100 perc


, count(a.post_text) qtd_posts2
, sum(if(a.post_text_en<>"",1,0) ) traduzido2
, sum(if(a.post_text_en<>"",1,0) ) / count(a.post_text) * 100 perc2
FROM `phpbb_posts` a
,     phpbb_forums f
WHERE a.forum_id = f.forum_id
GROUP BY forum_id, f.forum_name
order by 8 desc';

$result = $db->sql_query_limit($sql3, 100);
echo '<br><br><br><table><th>
<td>Forum</td>
<td>Posts</td>
<td>Traduzido</td>
<td>Perc</td>
<td>F5</td>
</th>';

while ($row = $db->sql_fetchrow($result))
	{
		 
echo '<tr><td>'.$row['forum_id'].'-'.$row['forum_name'].'</td>
      <td>'.$row['qtd_posts2'].'</td>
      <td>'.$row['traduzido2'].'</td>
      <td>'.$row['perc2'].'</td>
      <td>'.ceil(($row['qtd_posts2']-$row['traduzido2'])/48).'</td>
<tr>';
  }

  

/// TOTAL  
 $sql4 = '
SELECT
  count(a.post_text) qtd_posts2
, sum(if(a.post_text_en<>"",1,0) ) traduzido2
, sum(if(a.post_text_en<>"",1,0) ) / count(a.post_text) * 100 perc2
FROM `phpbb_posts` a';

$result = $db->sql_query_limit($sql4, 100);

while ($row = $db->sql_fetchrow($result))
	{
		 
echo '<tr><b><td>GERAL</td>
      <td>'.$row['qtd_posts2'].'</td>
      <td>'.$row['traduzido2'].'</td>
      <td>'.$row['perc2'].'</td>
      <td>'.ceil(($row['qtd_posts2']-$row['traduzido2'])/48).'</td>
</b><tr>';


  }

  
  echo '      </table>';




  
  $db->sql_freeresult($result);
  
?>
</body>
</html>

<?php
/*
Plugin Name: Quote-O-Matic
Plugin URI: http://lukemorton.co.uk/
Description: Displays a single random quote. Quotes can be added and edited through the Wordpress admin.  There is a <a href="http://localhost/wp/wp-admin/edit.php?page=quote-o-matic.php">'Quote-O-Matic' tab</a> under the 'manage' tab.  <a href="http://lukemorton.co.uk/">Click here</a> for usage instructions. Based on <a href="http://butlerblog.com/verse-o-matic/">Verse-o-Matic</a> by Chad Butler.
Version: 1.0.5
Author: Luke Morton
Author URI: http://lukemorton.co.uk/
*/



/*  Copyright 2009,  Luke Morton (email : lukemorton.designs@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/



// NOT a good idea to change these.
define("WP_QOM_VERSES", $wpdb->prefix."qom");
define("WP_QOM_VERSION", "1.0.5");

// this is the actual verse-o-matic
function verse_o_matic()
{
	global $wpdb;
	//Get user defined variables
	$displayMethod     = get_option('qom_displayMethod'); //random, daily, daily specific, static specific

	//Get the verse based on display
	
	switch ($displayMethod) {
	
	case "daily":
		$today = date('Y-m-d');
		$sql = "select * from ".WP_QOM_VERSES." where visible='yes' and date='{$today}'";
		$verseArr = $wpdb->get_row($sql, ARRAY_N);
		
		if ( empty($verseArr) ) { 
			$sql = "select * from ".WP_QOM_VERSES." where visible='yes' order by rand() limit 1";
			$newVerseArr = $wpdb->get_row($sql, ARRAY_N);

			$sql = "update ".WP_QOM_VERSES." set date='{$today}' where qomID={$newVerseArr[0]}"; 
			$wpdb->query($sql);

			$verseArr = $newVerseArr;
		}	
		break;
	
	case "daily_specific":
		$today = date('Y-m-d');
		$sql = "select * from ".WP_QOM_VERSES." where visible='yes' and date='{$today}'";
		$verseArr = $wpdb->get_row($sql, ARRAY_N);
		
		// This sets a random verse for the day if you are set for daily specific,
		// but there is no verse set with today's date. Otherwise, you get blank output.
		if ( empty($verseArr) ) { 
			$sql = "select * from ".WP_QOM_VERSES." where visible='yes' order by rand() limit 1";
			$newVerseArr = $wpdb->get_row($sql, ARRAY_N);

			$sql = "update ".WP_QOM_VERSES." set date='{$today}' where qomID={$newVerseArr[0]}"; 
			$wpdb->query($sql);

			$verseArr = $newVerseArr;
		}	
		break;
		
	case "static_specific":
	        $qom_staticID = get_option('qom_staticID');
		$sql = "select * from ".WP_QOM_VERSES." where visible='yes' and qomID='{$qom_staticID}'";
		$verseArr = $wpdb->get_row($sql, ARRAY_N);
		break;
		
	case "random": //this is the default
		$sql = "select * from ".WP_QOM_VERSES." where visible='yes' order by rand() limit 1";
		$verseArr = $wpdb->get_row($sql, ARRAY_N);
		break;
	
	} //end select display method
	
	//get the size of the array
	$verseArrCount = count($verseArr);
	
	//put array contents into variables
	$verseOwner = $verseArr[1]; //$verseOwner => The owner to the verse
	$verseText = $verseArr[2]; //$verseText => The actual verse
	$verseLink = $verseArr[3]; //$verseLink => link for the verse, if any

	//build the full verse reference, i.e. John 3:16
	$verseRef = "$verseOwner";

	
	//different output based on whether there is a link or not.
	if (empty($verseLink)) {
		$verseOutput = "\n<blockquote>\n<p>$verseText</p>\m<p>$verseRef</p>\n</blockquote>\n";
	} else {
		$verseOutput = "\n<blockquote cite=\"$verseLink\">\n<p class=\"quote\"><a href=\"$verseLink\">$verseText</a></p>\n<p class=\"quote-owner\">$verseRef</p>\n</blockquote>\n";
	}

	//finally, give us nice, clean output
	$i=0;	
	echo "\n\n<!-- BEGIN Quote-O-Matic Plugin -->\n";
	echo "<!--       version ". WP_QOM_VERSION ."       -->\n";
	echo $verseOutput;
	if ($turnOnAltVersions == "true") {
	  echo "<br /><br />\n";
	}
	echo "\n<!-- /END Quote-O-Matic Plugin -->\n\n";

} // end of the verse-o-matic function


//DO NOT EDIT below this line


// the hooks...
add_action('admin_menu', 'qom_admin_menu');
add_action('activate_quote-o-matic.php','qom_install');


// function to put the Verse-O-Matic tab in the Manage submenu
function qom_admin_menu()
{
	add_submenu_page('edit.php', 'Quote-O-Matic', 'Quote-O-Matic', '8', basename(__FILE__), 'qom_admin' );
}


//installation function
function qom_install() 
{
   global $wpdb;

   require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

   $table_name = WP_QOM_VERSES;
   if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
      
	$sql = "CREATE TABLE `".WP_QOM_VERSES."` (
		`qomID` INT(11) NOT NULL AUTO_INCREMENT ,
		`owner` VARCHAR( 20 ) NOT NULL ,
		`verseText` TEXT NOT NULL ,
		`link` TEXT DEFAULT NULL ,
		`visible` ENUM( 'yes', 'no' ) NOT NULL ,
  		`date` DATE DEFAULT NULL, 
  		PRIMARY KEY  (`qomID`),
  		KEY `date` (`date`)	)";	
      dbDelta($sql);

      $insert  = "INSERT INTO `".WP_QOM_VERSES."` (owner, verseText, link, visible) values "
	     . "('Luke Morton','This is pretty kewl', 'http://lukemorton.co.uk', 'yes')";
      $results = $wpdb->query( $insert );

	// replacing wp_qom_settings with native options table
	add_option('qom_displayMethod', 'random', '', 'yes');
	add_option('qom_staticID', '', '', 'yes');

   }
} // end of the install function


//functions to widgetize the verse-o-matic
// if you don't have a widget compatible theme... 
// don't worry about this, qom is backward compatible
function widget_qomwidget_init() {
	
	function widget_qomwidget($args) {
		extract($args);
		
		$options = get_option('widget_qomwidget');
		$title = $options['title'];
			
		echo $before_widget;
			// Widget Title
			if ($title) echo "\n".$before_title . $title . $after_title."\n";
			// The Widget
			verse_o_matic();
		echo $after_widget;
	}
	
	function widget_qomwidget_control() {
	
		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_qomwidget');
		if ( !is_array($options) )
			$options = array('title'=>'', 'buttontext'=>__('Quote-O-Matic', 'widgets'));
		if ( $_POST['qomwidget-submit'] ) {

			// Remember to sanitize and format use input appropriately.
			$options['title'] = strip_tags(stripslashes($_POST['qomwidget-title']));
			update_option('widget_qomwidget', $options);
		}

		// Be sure you format your options to be valid HTML attributes.
		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		
		// Here is our little form segment. Notice that we don't need a
		// complete form. This will be embedded into the existing form.
		echo '<p style="text-align:right;"><label for="qomwidget-title">' . __('Title:') . ' <input style="width: 200px;" id="qomwidget-title" name="qomwidget-title" type="text" value="'.$title.'" /></label></p>';
		echo '<input type="hidden" id="qomwidget-submit" name="qomwidget-submit" value="1" />';
	}

	register_sidebar_widget('Quote-O-Matic', 'widget_qomwidget');
	register_widget_control('Quote-O-Matic', 'widget_qomwidget_control');
}

add_action('widgets_init', 'widget_qomwidget_init');
// end of widgetization



/********************************
ADMIN FUNCTIONS 
*********************************/

function qom_admin()
{
		global $wpdb;
		
		require_once('admin.php');
		$parent_file = 'edit.php';
		
		// clear all globals. 
		$edit = $create = $save = $delete = false;
		
		// Request necessary variables, etc...
		$action     = !empty($_REQUEST['action'])        ? $_REQUEST['action'] : '';
		$display    = !empty($_REQUEST['display'])       ? $_REQUEST['display'] : '';
		$qomID      = !empty($_REQUEST['qomID'])         ? $_REQUEST['qomID'] : '';
		$owner      = !empty($_REQUEST['qom_owner'])     ? $_REQUEST['qom_owner'] : '';
		$verseText  = !empty($_REQUEST['qom_verseText']) ? $_REQUEST['qom_verseText'] : '';
		$link       = !empty($_REQUEST['qom_link'])      ? $_REQUEST['qom_link'] : '';
		$visible    = !empty($_REQUEST['qom_visible'])   ? $_REQUEST['qom_visible'] : '';
		$date       = !empty($_REQUEST['qom_date'])      ? $_REQUEST['qom_date'] : '';
		$qomDisplay = !empty($_REQUEST['qom_display'])   ? $_REQUEST['qom_display'] : '';
		$staticID   = !empty($_REQUEST['qom_staticID'])  ? $_REQUEST['qom_staticID'] : '';
		
		$sortby = $_REQUEST['sortby'];
		
		if (ini_get('magic_quotes_gpc')) {
			if($owner)    {$owner     = stripslashes($owner);}
			if($verseText){$verseText = stripslashes($verseText);}
			if($link)     {$link      = stripslashes($link);}
			if($visible)  {$visible   = stripslashes($visible);}	
		}
		
		require_once('admin-header.php');
		
		
		/* Handle any data based on 'action':
		    * add
		    * update
		    * delete
		    * update_settings
		    * reset_daily
		*/
		
		switch ($action) {
		
		
		case "add":	
		
			$sql1 = "insert into ".WP_QOM_VERSES." set "
				."owner = '".mysql_escape_string($owner)."', "
				."verseText = '".mysql_escape_string($verseText)."', "
				."link = '".mysql_escape_string($link)."', "
				."visible = '".mysql_escape_string($visible)."', " 
				."date = '".mysql_escape_string($date)."'";
				 
			$wpdb->get_results($sql1);
			
			$sql = "select qomID from ".WP_QOM_VERSES."
				where verseText='" . mysql_escape_string($verseText)."' 
				and visible='".mysql_escape_string($visible)."' 
				limit 1";
				
			$result = $wpdb->get_results($sql);
			
			if (empty($result) || empty($result[0]->qomID)) {?>
				<div class="error"><p><strong>Failure:</strong> Quote-O-Matic experienced and error and nothing was inserted.<?php echo $sql1; ?></p></div>
				<?php
			} else {?>
				<div id="message" class="updated fade"><p>Quote-O-Matic successfully added <?php echo $owner;?>'s quote to the database.</p></div>
				<?php
			}
			break;
		  
		
		case "update":	
			
			if (empty($qomID)) {?>
				<div class="error"><p><strong>Failure:</strong> No verse ID.  Giving up...</p></div>
				<?php		
			} else {
				
				$sql = "update ".WP_QOM_VERSES." set 
					owner = '".mysql_escape_string($owner)."', 
					verseText = '".mysql_escape_string($verseText)."', 
					link = '".mysql_escape_string($link)."', 
					visible = '".mysql_escape_string($visible)."', 
					date = '".mysql_escape_string($date)."' 
					where qomID = '".mysql_escape_string($qomID)."'";
				
				$wpdb->get_results($sql);
				
				$sql = "select qomID from ".WP_QOM_VERSES."
					where verseText='" . mysql_escape_string($verseText)."' 
					and visible='".mysql_escape_string($visible)."' 
					limit 1";
					
				$result = $wpdb->get_results($sql);
				
				if (empty($result) || empty($result[0]->qomID)) {
					?>
					<div class="error"><p><strong>Failure:</strong> Quote-O-Matic was unable to edit the verse.  Try again?</p></div>
					<?php
				} else {
					?>
					<div id="message" class="updated fade"><p>Quote-O-Matic updated <?php echo $book." ".$chapter.":".$verse;?> successfully!</p></div>
					<?php
				}		
			}
			break;
		  
		
		case "delete":
		
			if (empty($qomID)) {
				?>
				<div class="error"><p><strong>Failure:</strong> No verse ID given. Nothing was deleted.</p></div>
				<?php			
			} else {
				$sql = "delete from ".WP_QOM_VERSES." where qomID = '".mysql_escape_string($qomID)."'";
				$wpdb->get_results($sql);
				
				$sql = "select qomID from ".WP_QOM_VERSES." where qomID = '".mysql_escape_string($qomID)."'";
				$result = $wpdb->get_results($sql);
				
				if (empty($result) || empty($result[0]->qomID)) {
					?>
					<div id="message" class="updated fade"><p><strong><?php echo $owner."'s";?></strong> quote was deleted successfully</p></div>
					<?php
				} else {
					?>
					<div class="error"><p><strong>Failure:</strong> Nothing was successfully deletd.</p></div>
					<?php
				}		
			}
			break;
		
		  
		case("update_settings"):
			
			update_option('qom_displayMethod', $qomDisplay, '', 'yes');
			update_option('qom_staticID', $staticID, '', 'yes');	
			?>
			<div id="message" class="updated fade"><p>Quote-O-Matic settings updated!</p></div>
			<?php	
			break;
			
		
		case("reset_daily"):
		
			$sql = "update ".WP_QOM_VERSES." set date=null";
			$wpdb->query($sql);?>
			
			<div id="message" class="updated fade"><p>Daily random verse successfully reset!</p></div>
			<?php
		
		} // end of handling data
		
		
		
		// Begin functions
		
		
		//function to display the edit form
		function qom_edit_form($mode='add', $qomID=false)
		{
			global $wpdb;
			$data = false;
			
			if ($qomID !== false) {
				$data = $wpdb->get_results("select * from ".WP_QOM_VERSES." where qomID='".mysql_escape_string($qomID)."' limit 1");
				if (empty($data)) {
					echo "<div class=\"error\"><p>No verse was found with that id. <a href=\"edit.php?page=quote-o-matic.php\">Go back</a> and try again?</p></div>";
					return;
				}
				$data = $data[0];
			}
			
			if ($mode=="update") {
				$buttonText = "Edit Verse &raquo;";
			} else {
				$buttonText = "Add Verse &raquo;";
			}
			?>
			<form name="quoteform" id="quoteform" method="post" action="<?php echo $_SERVER['PHP_SELF']?>?page=quote-o-matic.php">
				<input type="hidden" name="action" value="<?php echo $mode?>">
				<input type="hidden" name="qomID" value="<?php echo $qomID?>">
			
				<div id="item_manager">
				
				<table class="optiontable">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e('Verse'); ?></th>
							<td><textarea name="qom_verseText" class="input" cols=80 rows=5><?php if ( !empty($data) ) echo htmlspecialchars($data->verseText); ?></textarea></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Owner'); ?></th>
							<td><input type="text" name="qom_owner" class="input" size=30 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->owner); ?>" /></td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Link'); ?></th>
							<td><input type="text" name="qom_link" class="input" size=40 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->link); ?>" />
							Not Required: leave blank if none.</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Date'); ?></th>
							<td><input type="text" name="qom_date" class="input" size=10 value="<?php if ( !empty($data) ) echo htmlspecialchars($data->date); ?>" />
							Use to set VOTD order (format: yyyy-mm-dd). Leave blank if not using VOTD</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Visible'); ?></th>
							<td><input type="radio" name="qom_visible" class="input" value="yes" 
								<?php if ( empty($data) || $data->visible=='yes' ) echo "checked" ?>/> Yes
								<br />
								<input type="radio" name="qom_visible" class="input" value="no" 
								<?php if ( !empty($data) && $data->visible=='no' ) echo "checked" ?>/> No
							</td>
						</tr>
						<tr valign="top">
							<th scopt="row">&nbsp;</th>
							<td><input type="submit" name="save" value="<?php echo $buttonText;?>" style="font-weight: bold;" tabindex="4" class="button" /></td>
						</tr>
					</tbody>
				</table>			
				</div>
			</form>
			<?php
		} //end of the edit form function
		
		
		//The list of verses
		function qom_display_list()
		{
			global $wpdb;
			
			$sortby = $_GET['sortby'];
			if (!$sortby) { $sortby = "qomID"; }
			$verses = $wpdb->get_results("SELECT * FROM " . WP_QOM_VERSES . " order by $sortby");
			if (!empty($verses)) {
				//coming soon!?>
				<!--<input type="submit" name="reset" class="button" value="Reset daily random verse &raquo;" onclick="javascript:document.location.href='edit.php?page=qom-admin.php&action=reset'" />
				<br /><br />-->
				<h3>Verses: (<a href="edit.php?page=quote-o-matic.php#add")>Add New &raquo;</a>)</h3>
				<table class="widefat">
					<tr class="head">
						<th scope="col"><a href="edit.php?page=quote-o-matic.php&amp;sortby=qomID"><?php _e('ID') ?></a></th>
						<th scope="col"><a href="edit.php?page=quote-o-matic.php&amp;sortby=owner"><?php _e('Owner') ?></a></th>
						<th scope="col"><?php _e('Quote') ?></th>
						<th scope="col"><?php _e('Link') ?></th>
						<th scope="col"><?php _e('Visible') ?></th>
						<th scope="col"><a href="edit.php?page=quote-o-matic.php&amp;sortby=date"><?php _e('Date') ?></a></th>
						<th scope="col"><?php _e('Edit') ?></th>
						<th scope="col"><?php _e('Delete') ?></th>
					</tr>
				<?php
				$class = '';
				foreach ($verses as $verse) {
					$class = ($class == 'alternate') ? '' : 'alternate';
					$today = date('Y-m-d');
					$class = $verse->date == $today ? 'active' : $class;
					?>
					<tr class="<?php echo $class; ?>" valign="top">
						<th scope="row"><?php echo $verse->qomID; ?></th>
						<td nowrap><?php echo $verse->owner; ?></td>
						<td><?php echo $verse->verseText; ?></td>
						<td><?php
						if ($verse->link){
							echo "<a href=\"".$verse->link."\">Link"; 
						}?></td>
						<td><?php echo $verse->visible=='yes' ? 'Yes' : 'No'; ?></td>
						<td><?php echo $verse->date; ?></td>
						<td><a href="edit.php?page=quote-o-matic.php&action=edit&amp;qomID=<?php echo $verse->qomID;?>" class='edit'><?php echo __('Edit'); ?></a></td>
						<td><a href="edit.php?page=quote-o-matic.php&action=delete&amp;qomID=<?php echo $verse->qomID."&amp;qom_owner=".$verse->owner;?>" class="delete" onclick="return confirm('Are you sure you want to delete this quote?')"><?php echo __('Delete'); ?></a></td>
					</tr>
					<?php
				}
				?>
				</table>
				<?php
			} else {
				?>
				<p><?php _e("You haven't entered any verses yet.") ?></p>
				<?php	
			}
		} // end of the qom_display_list function
		
		
		//  End functions
		
		
		//  Display the user interface
		
		if ($action == 'edit') {?>
			<div class="wrap">
				<h2><?php _e('Edit Verse'); ?></h2>
				<?php
				if (empty($qomID)) {
					echo "<div class=\"error\"><p>Verse ID not received, Cannot edit. <a href=\"edit.php?page=quote-o-matic.php\">Go back</a> and try again?</p></div>";
				} else {
					qom_edit_form('update', $qomID);
				}?>
			</div>
				
		<?php } else {
		
			$displayMethod  = get_option('qom_displayMethod');
			$staticSpecific = get_option('qom_staticID');
			?>
			<div class="wrap">
				<h2>Manage Quote-O-Matic</h2>
				<h3><?php _e('Settings');?></h3>
				<form name="settings" id="settings" method="post" action="<?php echo $_SERVER['PHP_SELF']?>?page=quote-o-matic.php">
				
			<table width="100%" cellpadding="3" cellspacing="3">
			  <tr> 
				<td width="100">Quote-O-Matic version <?php echo WP_QOM_VERSION; ?></td>
				<td align="right" nowrap>Display Method</td>
				<td align="left"> <select name="qom_display">
					<option value="random" <?php if ($displayMethod=="random") {echo "selected";}?>>Random</option>
					<option value="daily"  <?php if ($displayMethod=="daily") {echo "selected";}?>>Daily Random</option>
					<option value="daily_specific"    <?php if ($displayMethod=="daily_specific") {echo "selected";}?>>Daily Specific</option>
					<option value="static_specific" <?php if ($displayMethod=="static_specific") {echo "selected";}?>>Static Specific</option>
				  </select> </td>
			  </tr>
			  <tr> 
				<td nowrap><p><a href="http://lukemorton.co.uk">Check 
					for upgrade &raquo;</a></p></td>
				<td align="right" nowrap>Static ID</td>
				<td align="left"><input name="qom_staticID" type="text" size="3" maxlength="10" value="<?php echo $staticSpecific ?>"></td>
			  </tr>
			  <tr> 
				<td>&nbsp;</td>
				<td align="right">
					</td>
				<td align="left"> <input type="hidden" name="action" value="update_settings"> 
				  <input type="submit" name="EditSettings" value="Edit Settings &raquo;" style="font-weight: bold;" tabindex="4" class="button" /> 
				</td>
			  </tr>
			</table>
				</form>
				<?php qom_display_list();?>
				<h3>Reset Daily Random Verse:</h3>
				<p>
				<input type="submit" 
					name="reset_daily" 
					class="button" 
					value="Reset Daily Random Verse &raquo;" 
					onclick="javascript:document.location.href='edit.php?page=quote-o-matic.php&action=reset_daily'" />
					<br />Important Note: if you are using "Daily Specific", this clears ALL dates
					before reseting a random date.</p>
			</div>
			<div class="wrap"><a name="add"></a>
				<h2><?php _e('Add Verse'); ?></h2>
				<?php qom_edit_form(); ?>
			</div>
		<?php }?>
		
<?php } ?>
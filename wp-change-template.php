<?php
/*
	Plugin Name: WP Change Template
	Plugin URI: http://wordpress.org/extend/plugins/wp-change-template/
	Description: Change template (theme) on desided dates and times.
	Version: 0.3
	Author: T1gr0u
*/

/*  Copyright 2009  t1gr0u

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
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class WP_changeTemplate {
	var $opt_name = 'change_template' ;
	var $opt_val;
	var $changeTemplate ;
	var $desired_template ;
	var $desired_title;
	var $output_started ;
		
	function WP_changeTemplate() {
		$this->output_started = false;
		$this->changeTemplate = false;
		
		// Read in existing option value from database
		$this->opt_val = get_option( $this->opt_name ) ;
		
		add_action( 'plugins_loaded', array(&$this, 'findDates' ) ) ;
		add_filter( 'stylesheet', array(&$this, 'get_stylesheet' ) ) ;
		add_filter( 'template', array(&$this, 'get_template' ) ) ;
		add_filter( 'init', array(&$this, 'findDates' ) ) ;
		
		$this->findDates();
	}
	
	function updateChangeTemplate() {
		// Save the posted value in the database
		update_option( $this->opt_name, $this->opt_val ) ;
	}
	
	function get_template($template) {
		//$this->findDates() ;
		if ( $this->changeTemplate && $this->desired_template ) {
			return $this->desired_template;
		} else {
			return $template;
		}
	}
	
	function get_stylesheet($stylesheet) {
		if ( $this->changeTemplate && $this->desired_template ) {
			return $this->desired_template;
		} else {
			return $stylesheet;
		}
	}
	
	function findDates() {
		
		$timeZone = get_option( 'timezone_string' ) ;
		
		//print 'time1: ' . time() ;
		if ( function_exists('date_default_timezone_set') ) {
			date_default_timezone_set( $timeZone );
		}
		$today = time() ;
		$tomorrowTime = $today + 24 * 60 * 60 ;
		
		//print 'today: ' . $today ;
		$year = Date('Y') ;
		$day = Date('d') ;
		$month = Date('m') ;
		$tomorrow = Date('d', $tomorrowTime) ;
		
		$templates = unserialize( $this->opt_val ) ;
		/*
			0 -> Summer => title
			1 -> 21 => start day
			2 -> 06 => start month
			3 -> 20 => end day
			4 -> 09 => end month
			5 -> classic => theme
			'active' -> true => active
			
			'from_hour' -> 00 => start hour
			'from_minute' -> 00 => start minute
			'to_hour' -> 23 => end hour
			'to_minute' -> 59 => end minute
		*/
		
		foreach( (array) $templates AS $key => $template ) {
			$startDay = ( $template[1] == '--') ? $day : $template[1] ;
			$startMonth = ( $template[2] == '--') ? $month : $template[2] ;
			$startHour = ( $template['from_hour'] ) ? $template['from_hour'] : 0 ;
			$startMinute = ( $template['from_minute'] ) ? $template['from_minute'] : 0 ;
			
			
			$endHour = ( $template['to_hour'] ) ? $template['to_hour'] : 0 ;
			$endMinute = ( $template['to_minute'] ) ? $template['to_minute'] : 0 ;
			$endDay = ( $template[3] == '--') ? $day : $template[3] ;
			$endMonth = ( $template[4] == '--') ? $month : $template[4] ;
			
			// deal with end time < start time on same day go to tomorrow
			if ( ( $endHour <= $startHour ) and ( $endMinute < $startMinute ) ) {
				$endDay = $tomorrow ;
			}
			
			
			
			$dateStart = mktime($startHour, $startMinute, 0, $startMonth, $startDay, $year) ;
			$dateEnd = mktime($endHour, $endMinute, 59, $endMonth, $endDay, $year) ;
			
			if ( ( $dateStart < $today ) and ( $dateEnd < $today ) and ( $dateStart < $dateEnd) )
				$dateStart = mktime($startHour, $startMinute, 0, $startMonth, $startDay, $year + 1) ;
			
			if ( $dateEnd < $today )
				$dateEnd = mktime($endHour, $endMinute, 59, $endMonth, $endDay, $year + 1) ;

			// deal with Leap year
			if ( !date('L', $dateStart) and ($startMonth == '02') and ( $startDay == '29') ) {
				$sYear = Date('Y', $dateStart) ;
				$dateStart = mktime($startHour, $startMinute, 0, 2, 28, $sYear) ;
			}
			
			if ( !date('L', $dateEnd) and ($endMonth == '02') and ( $endDay == '29') ) {
				$eYear = Date('Y', $dateEnd) ;
				$dateEnd = mktime($endHour, $endMinute, 59, 2, 28, $eYear) ;
			}
			
			if ( ( $dateStart <= $today ) 
				&& ( $today < $dateEnd ) 
				&& ( is_dir( WP_CONTENT_DIR . '/themes/' . $template[5] ) ) 
				&& ( $template['active'] ) // active ?
			) {
				$this->desired_template = $template[5] ;
				$this->desired_title = $template[0];
				//print 'found theme: ' . $template[0] . ' | ' . $template[5] . ' | ' . date('d/m/Y', $dateStart) . ' | ' . date('d/m/Y', $dateEnd) ;
				$this->changeTemplate = true ;
			}
		}
	}

}


global $wp_plugin;
$WP_changeTemplate = & new WP_changeTemplate();

add_thickbox();
wp_enqueue_script( 'theme-preview' );



function ct_wp_changeTemplate_get_menu_pages() {
	$v = get_option('change_template');
	return $v;
}

function ct_options_menu() {
	add_options_page( __( 'WP change Template', 'wpchangetemplate' ), 'WP Change Template', 9, __FILE__, ct_wp_changeTemplate_page);
}

function addZero( $d ) {
	if ( strlen( $d ) == 1 )
		return '0' . $d ;
	else
		return $d ;
}

function ct_wp_changeTemplate_page() {

	if ( $_GET['delete_id'] ) {
		// Delete a theme
		$id = $_GET['delete_id'] - 1 ;
		$changeTemplates = unserialize( get_option('change_template') ) ;
		
		unset( $changeTemplates[$id] ) ;
		
		$nCT = array() ;
		foreach( (array) $changeTemplates AS $changeTemplate ) {
			$nCT[] = $changeTemplate ;
		}
		
		update_option('change_template', serialize( $nCT ) );
		print '<div class="wrap">Deleted: ' . $title . '</div>' ;
		die() ;
		
	} elseif ( $_GET['move_up_id'] ) {
		// Move a theme up in the rules
		$id = $_GET['move_up_id'] - 1 ;
		$changeTemplates = unserialize( get_option('change_template') ) ;
		if ( $id > 0) {
			$tmp = $changeTemplates[$id] ;
			$changeTemplates[$id] = $changeTemplates[$id-1] ;
			$changeTemplates[$id-1] = $tmp ;
			
			update_option('change_template', serialize( $changeTemplates ) );
			print '<div class="wrap">Moved up: ' . $title . '</div>' ;
		}
		die() ;
		
	} elseif ( $_GET['move_down_id'] ) {
		// Move a theme down in the rules
		$id = $_GET['move_down_id'] - 1 ;
		$changeTemplates = unserialize( get_option('change_template') ) ;
		$maxCt = count( $changeTemplates ) ;
		
		if ( $id <= $maxCt) {
			$tmp = $changeTemplates[$id] ;
			$changeTemplates[$id] = $changeTemplates[$id+1] ;
			$changeTemplates[$id+1] = $tmp ;
			
			update_option('change_template', serialize( $changeTemplates ) );
			print '<div class="wrap">Moved up: ' . $title . '</div>';
		}
		die() ;
	} elseif ( $_GET['ct_active_id'] ) {
		$id = $_GET['ct_active_id'] - 1 ;
		$changeTemplates = unserialize( get_option('change_template') ) ;
		
		if ( $changeTemplates[$id]['active'] ) {
			$changeTemplates[$id]['active'] = false ;
			print '<div class="wrap">Deactivated: ' . $title . '</div>';
		} else {
			$changeTemplates[$id]['active'] = true ;
			print '<div class="wrap">Activated: ' . $title . '</div>';
		}

		update_option('change_template', serialize( $changeTemplates ) );
		die() ;
			
	} elseif ( $_POST['ct_key'] ) {
		// update a theme rule
		$id = $_POST['ct_key'] - 1 ;
		$changeTemplates = unserialize( get_option('change_template') ) ;
		
		$pTitle = $changeTemplates[$id][0] ;
		$pFromDay = $changeTemplates[$id][1] ;
		$pFromMonth = $changeTemplates[$id][2] ;
		$pToDay = $changeTemplates[$id][3] ;
		$pToMonth = $changeTemplates[$id][4] ;
		$pTheme = $changeTemplates[$id][5] ;
		$pFromHour = $changeTemplates[$id]['from_hour'] ;
		$pFromMinute = $changeTemplates[$id]['from_minute'] ;
		$pToHour = $changeTemplates[$id]['to_hour'] ;
		$pToMinute = $changeTemplates[$id]['to_minute'] ;
		
		$changeTemplates[$id] = array(
			$_POST['ct_title'],
			$_POST['ct_from_day'],
			$_POST['ct_from_month'],
			$_POST['ct_to_day'],
			$_POST['ct_to_month'],
			$_POST['ct_theme'],
			'active'			=>true,
			'from_hour'		=> $_POST['ct_from_hour'],
			'from_minute'	=> $_POST['ct_from_minute'],
			'to_hour'			=> $_POST['ct_to_hour'],
			'to_minute'		=> $_POST['ct_to_minute']
		);
		
		$nTitle = $changeTemplates[$id][0] ;
		$nFromDay = $changeTemplates[$id][1] ;
		$nFromMonth = $changeTemplates[$id][2] ;
		$nToDay = $changeTemplates[$id][3] ;
		$nToMonth = $changeTemplates[$id][4] ;
		$nTheme = $changeTemplates[$id][5] ;
		$nFromHour = $changeTemplates[$id]['from_hour'] ;
		$nFromMinute = $changeTemplates[$id]['from_minute'] ;
		$nToHour = $changeTemplates[$id]['to_hour'] ;
		$nToMinute = $changeTemplates[$id]['to_minute'] ;
		
		update_option('change_template', serialize( $changeTemplates ) );
		echo '<div class="wrap">
			<b>Edited:</b><br/> ' 
			. $pTitle . ' | ' . $pFromDay . '/' . $pFromMonth . ' '. $pFromHour . ':' . $pFromMinute . ' | ' . $pToDay . '/' . $pToMonth . ' '. $pToHour . ':' . $pToMinute . ' | ' . $pTheme 
			. '<br/><b>TO</b><br/> ' 
			. $nTitle . ' | ' . $nFromDay . '/' . $nFromMonth . ' '. $pFromHour . ':' . $pFromMinute . ' | ' . $nToDay . '/' . $nToMonth . ' '. $nToHour . ':' . $nToMinute . ' | ' . $nTheme ;
		
		echo '<br/>
			<br/>
			<a href="' . $_SERVER["REQUEST_URI"] . '" class="button-primary">Back to \'WP Change Template\'</a>
			</div>' ;
		
	} else {
		echo '<div class="wrap">
			<h2>WP Change Template</h2>
			<p>
				The admin panel will allow to move re-order your theme changes.<br/>
Just remember that, the top is the lowest and the bottom is the highest level.<br/>
So, if you have a theme change (\'theme1\') set for the 23/07 - 01/08 first and theme change (\'theme2\') for the 24/07 - 26/07, and today\'s date is 24/07,
\'theme2\' will be displayed unless you move down \'theme1\'.<br/>
<br/>
The rules, which you have created, will stay year on year.
			</p>' ;
			
		$defTemplate = get_option( 'template' ) ;
		$home = get_option( 'home' ) ;
		
		$WP_changeTemplate = & new WP_changeTemplate();
		$WP_changeTemplate->findDates() ;
		$actualTemplate = $WP_changeTemplate->desired_template ;
		$actualTplTitle = $WP_changeTemplate->desired_title ;
		
		echo '<h3>Default Template</h3>
		<p>
			Default theme: <a href="' . $home . '/?preview=1&template=' . $defTemplate. '&stylesheet=' . $defTemplate . '&TB_iframe=true" class="thickbox thickbox-preview">' . $defTemplate . '</a>
			(template applied if there is no active rules)<br/>
			' . 
			( ( $WP_changeTemplate->changeTemplate) ? 'Actual rule in use: <b>"' . $actualTplTitle. '"</b> <br/>Actual theme in use: <a href="' . $home . '/?preview=1&template=' . $actualTemplate. '&stylesheet=' . $actualTemplate . '&TB_iframe=true" class="thickbox thickbox-preview">' . $actualTemplate .'</a>' :'') . '
		</p>
		' ;
		
		if ( isset( $_POST['submit'] ) ) {
			// add a new theme rule
			$changeTemplates = unserialize( get_option('change_template') ) ;
			
			$changeTemplates[] = array(
				$_POST['ct_title'],
				$_POST['ct_from_day'],
				$_POST['ct_from_month'],
				$_POST['ct_to_day'],
				$_POST['ct_to_month'],
				$_POST['ct_theme'],
				'active'			=> true,
				'from_hour'		=> $_POST['ct_from_hour'],
				'from_minute'	=> $_POST['ct_from_minute'],
				'to_hour'			=> $_POST['ct_to_hour'],
				'to_minute'		=> $_POST['ct_to_minute']
			) ;
			
			update_option('change_template', serialize( $changeTemplates ) );
		}
	
		print '<h3>Existing changes</h3>
		<div id="ct_status" style="background:#e7f7d3; border:1px solid #228B22; display:none; text-align: center; padding-top:5px;padding-bottom:5px;">
		</div><br/>
		<table class="widefat">
			<thead>
				<tr>
					<th>Title</th>
					<th>From</th>
					<th>To</th>
					<th>Theme</th>
					<th>Active</th>
					<th>Edit</th>
					<th>Delete</th>
					<th>Move Up</th>
					<th>Move Down</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>Title</th>
					<th>From</th>
					<th>To</th>
					<th>Theme</th>
					<th>Active</th>
					<th>Edit</th>
					<th>Delete</th>
					<th>Move Up</th>
					<th>Move Down</th>
				</tr>
			</tfoot>
			<tbody>' ;
		$changeTemplates = unserialize( get_option('change_template') ) ;
		
		$siteUrl = get_option('siteurl') ;
		
		$maxCt = count( $changeTemplates ) ;
		$first = 0 ;
		
		foreach( (array) $changeTemplates AS $key => $changeTemplate ) {
			print '<tr id="line_' . ( $key + 1 ) . '">
				<td id="post_ct_title_' . ( $key + 1 ) . '"><a href="' . $home . '/?preview=1&template=' . $changeTemplate[5]. '&stylesheet=' . $changeTemplate[5] . '&TB_iframe=true" class="thickbox thickbox-preview">' . $changeTemplate[0] . '</a></td>
				<td id="post_ct_from_' . ( $key + 1 ) . '">' . $changeTemplate[1] . '/'. $changeTemplate[2] . '  ' . ( ( $changeTemplate['from_hour'] )?$changeTemplate['from_hour'] :'00') . ':' . ( ( $changeTemplate['from_minute'] )?$changeTemplate['from_minute'] :'00') . '</td>
				<td id="post_ct_to_' . ( $key + 1 ) . '">' . $changeTemplate[3] . '/'. $changeTemplate[4] . '  ' . ( ( $changeTemplate['to_hour'] )?$changeTemplate['to_hour'] :'23') . ':' . ( ( $changeTemplate['to_minute'] )?$changeTemplate['to_minute'] :'59') . '</td>
				<td id="post_ct_theme_' . ( $key + 1 ) . '">' . $changeTemplate[5] . '</td>
				<td><input type="checkbox" name="post_ct_active_' . ( $key + 1 ) . '" alt="' . ( $key + 1 ) . '" class="post_ct_active" ' . ( ($changeTemplate['active']) ? 'checked=checked' : '' ). '/></td>
				<td><a href="' . $_SERVER["REQUEST_URI"] . '&edit_id=' . ( $key + 1 ) . '" class="post_ct_key" alt="' . ( $key + 1 ) . '" ><img src="' . $siteUrl . '/wp-content/plugins/wp-change-template/images/pencil.png" alt="edit"></a></td>
				<td><a href="' . $_SERVER["REQUEST_URI"] . '&delete_id=' . ( $key + 1 ) . '" class="ct_delete" alt="' . ( $key + 1 ) . '" ><img src="' . $siteUrl . '/wp-content/plugins/wp-change-template/images/bin.png" alt="delete"></a></td>
				<td>' . ( ( $first > 0 ) ? '<a href="' . $_SERVER["REQUEST_URI"] . '&move_up_id=' . ( $key + 1 ) . '" class="ct_move_up" alt="' . ( $key + 1 ) . '" ><img src="' . $siteUrl . '/wp-content/plugins/wp-change-template/images/bullet_arrow_up.png" alt="move up"></a>' : '' ) . '</td>
				<td>' . ( ( $first < ($maxCt-1) ) ? '<a href="' . $_SERVER["REQUEST_URI"] . '&move_down_id=' . ( $key + 1 ) . '" class="ct_move_down" alt="' . ( $key + 1 ) . '" ><img src="' . $siteUrl . '/wp-content/plugins/wp-change-template/images/bullet_arrow_down.png" alt="move down"></a>' : '' ) . '</td>
			</tr>' ;
			
			$first++;
		}
		print '</tbody></table><br/>' ;
?>
	
	<script type="text/javascript">
		//<![CDATA[
		jQuery.fn.addOption = function(text,val,active) { 
			return jQuery(this).each(
				function() { 
					try {
						var optionObj = new Option( text, val );
						var optionRank = this.options.length;
						this.options[optionRank] = optionObj;
						if(active) {
							this.selectedIndex=optionRank;
						}
					} catch(e) { }
				} 
			)
		};
		
		function addZero(d) {
			var s = '' + d ;
			if ( s.length <= 1 )
				return '0' + s ;
			else
				return s ;
		}
		
		function setDayDates( v, d ) {
			var maxDay = 31 ;
			switch( v ) {
				case '02':
					maxDay = 29 ; // leap year is chek on the back end
					break;
				case '04':
				case '06':
				case '09':
				case '11':
					maxDay = 30 ;
					break;
				default:
					maxDay = 31 ;
					break;
			}
			
			
			var oldValue = jQuery(d).val();
			jQuery(d).empty() ;
			jQuery(d).addOption( 'Every', '--') ;
			for(var i=1; i <= maxDay ; i++ ) {
				jQuery(d).addOption( addZero(i), addZero(i)) ;
			}
			
			if ( oldValue > (maxDay - 1) ) { // Change the days if they are too far
				jQuery(d).val( maxDay ) ;
			} else { // do not change the value
				jQuery(d).val( oldValue ) ;
			}
		}	
		
		function showstatus(text) {
			jQuery('#ct_status').html(text).show().animate({opacity: 1}, 1500).fadeOut('slow');
		}
		
		jQuery( function() {
			jQuery('.post_ct_key').click( function() {
				var key = jQuery(this).attr('alt') ;
				var title = jQuery('#post_ct_title_' + key + '> a ').html() ;
				var from = jQuery('#post_ct_from_' + key ).html() ;
				var to = jQuery('#post_ct_to_' + key ).html() ;
				var theme = jQuery('#post_ct_theme_' + key ).html() ;
				
				jQuery('input[name="ct_key"]').val( key ) ;
				jQuery('input[name="ct_title"]').val( title ) ;
				
				jQuery('select[name="ct_from_month"]').val( from.substr(3,2) ) ;
				jQuery('select[name="ct_from_day"]').val( from.substr(0,2) ) ;
				jQuery('select[name="ct_from_hour"]').val( from.substr(7,2) ) ;
				jQuery('select[name="ct_from_minute"]').val( from.substr(10,2) ) ;
				
				jQuery('select[name="ct_to_month"]').val( to.substr(3,2) ) ;
				jQuery('select[name="ct_to_day"]').val( to.substr(0,2) ) ;
				jQuery('select[name="ct_to_hour"]').val( to.substr(7,2) ) ;
				jQuery('select[name="ct_to_minute"]').val( to.substr(10,2) ) ;
				
				jQuery('select[name="ct_theme"]').val( theme ) ;
				
				jQuery('#ct_editMode_title').html( title ) ;
				
				jQuery('#ct_editMode').show() ;
				return false ;
			}) ;
		
			jQuery('.ct_delete').click( function() {
				var key = jQuery(this).attr('alt') ;
				
				var deltitle = jQuery('#post_ct_title_' + key +'> a').html() ;
				jQuery.get( '<?php echo $_SERVER["REQUEST_URI"];?>&delete_id='+key ,function( data ) {
					jQuery( '#line_' + key ).hide() ;
					//location.reload(true) ;
					location.href = '<?php echo $_SERVER["REQUEST_URI"];?>' ;
				}) ;
				
				showstatus('Deleted: ' + deltitle) ;
				return false ;
			}) ;
		
		
			jQuery('#ct_cancel_edit').click( function() {
				jQuery('input[name="ct_key"]').val( '' ) ;
				jQuery('#ct_editMode').hide() ;
				
				jQuery('input[name="ct_title"]').val( '' ) ;
				jQuery('select[name="ct_from_month"]').val( '01' ) ;
				jQuery('select[name="ct_from_day"]').val( '01' ) ;
				jQuery('select[name="ct_to_month"]').val( '01' ) ;
				jQuery('select[name="ct_to_day"]').val( '01' ) ;
				
				return false ;
			});
			
			jQuery('select[name="ct_from_month"]').change( function() {
				setDayDates( jQuery('select[name="ct_from_month"]').val(), 'select[name="ct_from_day"]');
			}) ;
			
			jQuery('select[name="ct_to_month"]').change( function() {
				setDayDates( jQuery('select[name="ct_to_month"]').val(), 'select[name="ct_to_day"]');
			}) ;
			
			jQuery('.ct_move_up').click( function() {
				var key = jQuery(this).attr('alt') ;
				if ( key > 1 ) {
					jQuery.get( '<?php echo $_SERVER["REQUEST_URI"];?>&move_up_id='+key , function(data) {
							var utitle = jQuery('#post_ct_title_' + key ).html() ;
							var textutitle = jQuery('#post_ct_title_' + key + '> a').html() ;
							
							var ufrom = jQuery('#post_ct_from_' + key ).html() ;
							var uto = jQuery('#post_ct_to_' + key ).html() ;
							var utheme = jQuery('#post_ct_theme_' + key ).html() ;
							var uactive = jQuery('#post_ct_active_' + key ).attr('checked') ;
							
							jQuery('#post_ct_title_' + key ).html( jQuery('#post_ct_title_' + (key-1) ).html() ) ;
							jQuery('#post_ct_from_' + key ).html( jQuery('#post_ct_from_' + (key-1) ).html() ) ;
							jQuery('#post_ct_to_' + key ).html( jQuery('#post_ct_to_' + (key-1) ).html() ) ;
							jQuery('#post_ct_theme_' + key ).html( jQuery('#post_ct_theme_' + (key-1) ).html() ) ;
							jQuery('#post_ct_active_' + key ).attr('checked', jQuery('#post_ct_active_' + (key-1) ).attr('checked') ) ;
							
							jQuery('#post_ct_title_' + (key-1) ).html( utitle ) ;
							jQuery('#post_ct_from_' + (key-1) ).html( ufrom ) ;
							jQuery('#post_ct_to_' + (key-1) ).html( uto ) ;
							jQuery('#post_ct_theme_' + (key-1) ).html( utheme ) ;
							jQuery('#post_ct_active_' + (key-1) ).attr('checked', uactive ) ;
							
							showstatus('Moved up: ' + textutitle) ;
					}) ;
				}
				return false ;
			}) ;
			
			jQuery('.ct_move_down').click( function() {
				var key = jQuery(this).attr('alt') - 0 ;
					jQuery.get( '<?php echo $_SERVER["REQUEST_URI"];?>&move_down_id='+key , function(data) {
							var dtitle = jQuery('#post_ct_title_' + key ).html() ;
							var textdtitle = jQuery('#post_ct_title_' + key + '> a').html() ;
							
							var dfrom = jQuery('#post_ct_from_' + key ).html() ;
							var dto = jQuery('#post_ct_to_' + key ).html() ;
							var dtheme = jQuery('#post_ct_theme_' + key ).html() ;
							var dactive = jQuery('#post_ct_active_' + key ).attr('checked') ;
							
							jQuery('#post_ct_title_' + key ).html( jQuery('#post_ct_title_' + (key+1) ).html() ) ;
							jQuery('#post_ct_from_' + key ).html( jQuery('#post_ct_from_' + (key+1) ).html() ) ;
							jQuery('#post_ct_to_' + key ).html( jQuery('#post_ct_to_' + (key+1) ).html() ) ;
							jQuery('#post_ct_theme_' + key ).html( jQuery('#post_ct_theme_' + (key+1) ).html() ) ;
							jQuery('#post_ct_active_' + key ).attr('checked', jQuery('#post_ct_active_' + (key+1) ).attr('checked') ) ;
							
							jQuery('#post_ct_title_' + (key+1) ).html( dtitle ) ;
							jQuery('#post_ct_from_' + (key+1) ).html( dfrom ) ;
							jQuery('#post_ct_to_' + (key+1) ).html( dto ) ;
							jQuery('#post_ct_theme_' + (key+1) ).html( dtheme ) ;
							jQuery('#post_ct_active_' + (key-1) ).attr('checked', dactive ) ;
							
							showstatus('Moved down: ' + textdtitle ) ;
					}) ;
				return false ;
			}) ;
			
			jQuery('.post_ct_active').change( function() {
				var key = jQuery(this).attr('alt') - 0 ;
				var acttitle = jQuery('#post_ct_title_' + key +'> a').html() ;
				jQuery.get( '<?php echo $_SERVER["REQUEST_URI"];?>&ct_active_id='+key , function(data) {
					//alert( key ) ;
					showstatus('Activated or Deactived: ' + acttitle) ;
				});
				return false;
			}) ;
		
		}) ;
		//]]>
	</script>
	<h3>Add another</h3>
	<p id="ct_editMode" style="display:none">
		<img src="<?php echo $siteUrl;?>/wp-content/plugins/wp-change-template/images/error.png" alt="Warning">
		Edit Mode for: <span id="ct_editMode_title" style="font-weight:bold;"></span>&nbsp;
		<a href="#" id="ct_cancel_edit" class="button-primary"><?php _e('Cancel Edit', 'wpchangetemplate' ); ?></a>
	</p>
	
	<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<input type="hidden" name="ct_key" value="" />
		<table class="form-table">
			<tr valign="top">
				<th scope="row">
					<label for="ct_title">Title:</label>
				</th>
				<td>
					<input type="text" name="ct_title" id="ct_title" value="" />
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row">
					<label for="ct_from_month">Date from</label>
				</th>
				<td>
					<label for="ct_from_day">day:</label>
					<select name="ct_from_day" id="ct_from_day">
						<option value="--">Every</option>
						<?php 
							for( $j = 1; $j < 32; $j++) {
								echo '<option value="' . addZero( $j) . '">' . addZero( $j) . '</option>' ;
							}
							?>
					</select>
					<label for="ct_from_month">/ month:</label>
					<select name="ct_from_month" id="ct_from_month">
						<option value="--">Every</option>
						<?php 
							for( $i = 1; $i < 13; $i++) {
								echo '<option value="' . addZero( $i) . '">' . addZero( $i) . '</option>' ;
							}
						?>
					</select>
					&nbsp;
					<label for="ct_from_hour">hour:</label>
					<select name="ct_from_hour" id="ct_from_hour">
						<?php 
							for( $j = 0; $j < 24; $j++) {
								echo '<option value="' . addZero( $j) . '">' . addZero( $j) . '</option>' ;
							}
							?>
					</select>
					<label for="ct_from_minute">minute:</label>
					<select name="ct_from_minute" id="ct_from_minute">
						<?php 
							for( $j = 0; $j < 60; $j++) {
								echo '<option value="' . addZero( $j) . '">' . addZero( $j) . '</option>' ;
							}
							?>
					</select>
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row">
					<label for="ct_to_month">Date to</label>
				</th>
				<td>
					<label for="ct_to_day">day:</label>
					<select name="ct_to_day" id="ct_to_day">
						<option value="--">Every</option>
						<?php 
							for( $j = 1; $j < 32; $j++) {
								echo '<option value="' . addZero( $j) . '">' . addZero( $j) . '</option>' ;
							}
							?>
					</select>
					<label for="ct_to_month">/ month:</label>
					<select name="ct_to_month" id="ct_to_month">
						<option value="--">Every</option>
						<?php 
							for( $i = 1; $i < 13; $i++) {
								echo '<option value="' . addZero( $i) . '">' . addZero( $i) . '</option>' ;
							}
						?>
					</select>
					&nbsp;
					<label for="ct_to_hour">hour:</label>
					<select name="ct_to_hour" id="ct_to_hour">
						<?php 
							for( $j = 0; $j < 23; $j++) {
								echo '<option value="' . addZero( $j) . '">' . addZero( $j) . '</option>' ;
							}
							?>
							<option value="23" selected="selected">23</option>
					</select>
					<label for="ct_to_minute">minute:</label>
					<select name="ct_to_minute" id="ct_to_minute">
						<?php 
							for( $j = 0; $j < 59; $j++) {
								echo '<option value="' . addZero( $j) . '">' . addZero( $j) . '</option>' ;
							}
							?>
							<option value="59" selected="selected">59</option>
					</select>
					
				</td>
			</tr>
			
			<tr valign="top">
				<th scope="row">
					<label for="ct_theme">Theme:</label>
				</th>
				<td>
					<select name="ct_theme" id="ct_theme">
					<?php
						$dir = WP_CONTENT_DIR . '/themes/' ;
						$handle = opendir( $dir ) ;
						while( ( $theme = readdir( $handle ) ) !== false) {
							if ( ( is_dir( $dir . $theme ) ) and ($theme != '.') and ($theme != '..') ) {
								echo '<option value="' . $theme . '">' . $theme . '</option>' ;
							}
						}
						closedir( $handle ) ;
					?>
					</select>
				</td>
			</tr>
			
			<tr valign="top">
				<td colspan="2">
					<input type="submit" name="submit" value="<?php _e('Save Options', 'wpchangetemplate' ); ?>" id="wpchangetemplate-button" class="button-primary" />
				</td>
			</tr>
			
		</table>
	</form>
</div>
<?php
	}
}

add_action('admin_menu', 'ct_options_menu');
?>
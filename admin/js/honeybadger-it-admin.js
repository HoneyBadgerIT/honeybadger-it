function showHoneyBadgerStatusTableMain(){
	var seconds=100;
	jQuery('#hb-status-table tr').each(function(index,val){
			if(jQuery(val).hasClass('hb-hidden')){
				setTimeout(function(){showHoneyBadgerStatusTableRow(index);},seconds);
			}
			seconds=seconds+100;
		});
}
function showHoneyBadgerStatusTableRow(the_index){
	jQuery('#hb-status-table tr').each(function(index,val){
		if(the_index==index){
			jQuery(val).fadeIn("fast");
		}
	});
}
jQuery(document).ready(function(){
	//showHoneyBadgerStatusTableMain();
});
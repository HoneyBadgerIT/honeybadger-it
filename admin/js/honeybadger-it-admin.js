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
function validateHbEmail(email)
{
    if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email))
    {
        return (true)
    }
    return (false)
}
function validateHbPassword(inputtxt) 
{ 
    var passw = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{6,20}$/;
    if(inputtxt.match(passw)) 
    { 
        return true;
    }
    else
    { 
        return false;
    }
}
jQuery(document).ready(function(){
	if(jQuery('#hb_password').length>0)
	{
		var passwordInput = document.querySelector("#hb_password")
		var eye = document.querySelector("#eye")
		eye.addEventListener("click", function(){
		  this.classList.toggle("fa-eye-slash")
		  const type = passwordInput.getAttribute("type") === "password" ? "text" : "password"
		  passwordInput.setAttribute("type", type)
		})
	}
});

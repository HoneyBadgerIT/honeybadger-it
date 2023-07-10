function createHoneybadgerUserRole()
{
    jQuery(".hb-setup-no-use").prop("disabled",true);
    jQuery.post(honeybadger_ajax_obj.ajax_url, {
        _ajax_nonce: honeybadger_ajax_obj.nonce,
        action: "honeybadger_it_create_user_role"
        }, function(data) {
            var response=data.msg;
            var status=data.status;
            var nonce=data.nonce;
            jQuery("#hb-status-update").html(jQuery("#hb-status-update").html()+response);
            if(status=="ok")
            {
                honeybadger_ajax_obj.nonce=nonce;
                createHoneybadgerConnection();
            }
            else
            {
                honeybadger_ajax_obj.nonce=nonce;
                disableHonebadgerCreateButton();
            }
        }
    );
}
function createHoneybadgerConnection()
{
    jQuery.post(honeybadger_ajax_obj.ajax_url, {
        _ajax_nonce: honeybadger_ajax_obj.nonce,
        action: "create_honeybadger_connection"
        }, function(data) {
            var response=data.msg;
            var status=data.status;
            var nonce=data.nonce;
            jQuery("#hb-status-update").html(jQuery("#hb-status-update").html()+response);
            if(status=="ok")
            {
                //continue
                honeybadger_ajax_obj.nonce=nonce;
            }
            else
            {
                honeybadger_ajax_obj.nonce=nonce;
                disableHonebadgerCreateButton();
            }
        }
    );
}
function refreshHoneybadgerConnection()
{
    jQuery(".hb-setup-no-use").prop("disabled",true);
    if(jQuery('#revoke_form').length>0)
    {
        jQuery('#revoke_form').remove();
    }
    jQuery.post(honeybadger_ajax_obj.ajax_url, {
        _ajax_nonce: honeybadger_ajax_obj.nonce,
        action: "refresh_honeybadger_connection"
        }, function(data) {
            var response=data.msg;
            var status=data.status;
            jQuery("#hb-status-update").html(jQuery("#hb-status-update").html()+response);
            if(status=="ok")
            {
                //continue
            }
            else
            {
                disableHonebadgerCreateButton();
            }
        }
    );
}
function disableHonebadgerCreateButton()
{
    jQuery(".hb-setup-no-use").prop("disabled",true);
    jQuery(".hb-setup-no-use").attr("title",honeybadger_ajax_obj.hb_setup_no_us_msg_1);
    jQuery(".hb-setup-no-use").attr("value",honeybadger_ajax_obj.hb_setup_no_us_msg_2);
}
function isHoneybadgerJson(str) {
    try {
        JSON.parse(str);
    } catch (e) {
        return false;
    }
    return true;
}
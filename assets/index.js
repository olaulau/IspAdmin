$(function () {

    // server choice submit
    $("select#isp_rest_conf_id").on("change", function () {
        $(this).parent().trigger("submit");
    });

});

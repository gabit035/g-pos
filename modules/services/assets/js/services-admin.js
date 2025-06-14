;(function($){
    $(function(){
        var app = $('#wp-pos-services-app');
        var i18n = wp_pos_services_admin.i18n;
        var addUrl = wp_pos_services_admin.add_url;
        var rest_url = wp_pos_services_admin.rest_url;
        var nonce = wp_pos_services_admin.nonce;
        var serviceUrl = rest_url + '?_wpnonce=' + encodeURIComponent(nonce);

        // Add New Service link
        var html = '<a href="'+ addUrl +'" class="button button-primary">'+ i18n.add_service +'</a>' +
                   '<div id="services-list" style="margin-top:20px;">'+ i18n.loading +'</div>';
        app.html(html);

        function loadServices(){
            $('#services-list').html('<p>'+ i18n.loading +'</p>');
            $.ajax({ url: serviceUrl, method: 'GET', dataType: 'json' })
             .done(function(data){
                if (Array.isArray(data) && data.length) {
                    var html = '<table class="wp-list-table widefat fixed striped"><thead><tr><th>'+i18n.name+'</th><th>'+i18n.sale_price+'</th></tr></thead><tbody>';
                    data.forEach(function(s){ html += '<tr><td>'+s.name+'</td><td>'+s.sale_price+'</td></tr>'; });
                    html += '</tbody></table>';
                    $('#services-list').html(html);
                } else {
                    $('#services-list').html('<p>'+i18n.no_services+'</p>');
                }
            }).fail(function(){
                $('#services-list').html('<p>'+i18n.no_services+'</p>');
            });
        }

        loadServices();
    });
})(jQuery);
